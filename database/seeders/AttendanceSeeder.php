<?php

namespace Database\Seeders;

use App\Models\Attendance;
use App\Models\Holiday;
use App\Models\ShiftKerja;
use App\Models\User;
use App\Support\WorkdayCalculator;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class AttendanceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = User::with(['shiftKerja', 'location'])->get();
        $shifts = ShiftKerja::all();

        if ($users->isEmpty() || $shifts->isEmpty()) {
            return;
        }

        Attendance::query()->delete();

        // Get sample employees (5-8 employees from different locations)
        $employeeUsers = $users->where('role', 'employee')
            ->whereNotNull('location_id')
            ->whereNotNull('shift_kerja_id')
            ->take(8);

        if ($employeeUsers->isEmpty()) {
            $this->command->warn('No employees with shift and location found.');
            return;
        }

        // Get 2 months back (current month and previous month)
        $today = Carbon::today();
        $currentMonth = $today->copy()->startOfMonth();
        $previousMonth = $today->copy()->subMonth()->startOfMonth();
        
        // Start from previous month start, end at today
        $startDate = $previousMonth->copy();
        $endDate = $today->copy();

        // Get all holidays for the period
        $holidays = Holiday::whereBetween('date', [
            $startDate->toDateString(),
            $endDate->toDateString()
        ])->pluck('date')->map(fn($date) => Carbon::parse($date)->toDateString())->toArray();

        $totalRecords = 0;

        foreach ($employeeUsers as $user) {
            $shift = $user->shiftKerja ?? $shifts->first();
            $locationId = $user->location_id;

            // Generate attendance for each workday in the period
            $currentDate = $startDate->copy();

            while ($currentDate <= $endDate) {
                // Skip weekends and holidays
                $isWeekend = WorkdayCalculator::isWeekend($currentDate, $locationId);
                $isHoliday = in_array($currentDate->toDateString(), $holidays);
            
                if (!$isWeekend && !$isHoliday) {
                    // Generate attendance for this workday
                    $flowData = $this->generateFlowData($shift, $currentDate, $locationId);

            Attendance::create([
                'user_id' => $user->id,
                'shift_id' => $shift->id,
                        'location_id' => $locationId,
                        'date' => $currentDate->toDateString(),
                'time_in' => $flowData['time_in']->format('H:i:s'),
                'time_out' => $flowData['time_out']?->format('H:i:s'),
                        'latlon_in' => $this->getLocationLatLon($locationId),
                        'latlon_out' => $flowData['time_out'] ? $this->getLocationLatLon($locationId) : null,
                'status' => $flowData['status'],
                        'is_weekend' => false,
                'is_holiday' => false,
                'holiday_work' => false,
                'late_minutes' => $flowData['late_minutes'],
                'early_leave_minutes' => $flowData['early_leave_minutes'],
            ]);

                    $totalRecords++;
        }

                $currentDate->addDay();
            }
        }

        $this->command->info("✅ Created {$totalRecords} attendance records for " . $employeeUsers->count() . " employees (2 months: " . $previousMonth->format('M Y') . " - " . $currentMonth->format('M Y') . ").");
    }

    private function generateFlowData(ShiftKerja $shift, Carbon $date, ?int $locationId): array
    {
        // Use location timezone for accurate time calculation
        $timezone = $locationId 
            ? \App\Services\TimezoneService::getLocationTimezone($locationId)
            : config('app.timezone');

        $shiftStart = $this->buildDateTime($date, $shift->getRawOriginal('start_time'), $timezone);
        $shiftEnd = $this->buildDateTime($date, $shift->getRawOriginal('end_time'), $timezone);

        if ($shiftEnd->lessThanOrEqualTo($shiftStart)) {
            $shiftEnd->addDay();
        }

        // Random status untuk variasi (mostly on_time untuk payroll accuracy)
        $statuses = ['on_time', 'on_time', 'on_time', 'late', 'on_time', 'on_time', 'on_time', 'early_leave'];
        $status = $statuses[array_rand($statuses)];

        return match ($status) {
            'late' => $this->buildLateFlow($shiftStart, $shiftEnd),
            'early_leave' => $this->buildEarlyLeaveFlow($shiftStart, $shiftEnd),
            default => $this->buildOnTimeFlow($shiftStart, $shiftEnd),
        };
    }

    private function buildDateTime(Carbon $date, string $time, string $timezone): Carbon
    {
        $timeWithSeconds = strlen($time) === 5 ? $time . ':00' : $time;

        return Carbon::createFromFormat(
            'Y-m-d H:i:s',
            $date->format('Y-m-d') . ' ' . $timeWithSeconds,
            $timezone
        );
    }

    private function buildOnTimeFlow(Carbon $shiftStart, Carbon $shiftEnd): array
    {
        // Check-in: on time or slightly early (0-5 minutes)
        $checkIn = (clone $shiftStart)->addMinutes(random_int(0, 5));
        // Check-out: on time or slightly late (0-5 minutes)
        $checkOut = (clone $shiftEnd)->subMinutes(random_int(0, 5));

        return [
            'time_in' => $checkIn,
            'time_out' => $checkOut,
            'status' => 'on_time',
            'late_minutes' => 0,
            'early_leave_minutes' => 0,
        ];
    }

    private function buildLateFlow(Carbon $shiftStart, Carbon $shiftEnd): array
    {
        // Late: 10-30 minutes late
        $lateMinutes = random_int(10, 30);
        $checkIn = (clone $shiftStart)->addMinutes($lateMinutes);
        $checkOut = (clone $shiftEnd)->subMinutes(random_int(0, 10));

        return [
            'time_in' => $checkIn,
            'time_out' => $checkOut,
            'status' => 'late',
            'late_minutes' => $lateMinutes,
            'early_leave_minutes' => 0,
        ];
    }

    private function buildEarlyLeaveFlow(Carbon $shiftStart, Carbon $shiftEnd): array
    {
        $checkIn = (clone $shiftStart)->addMinutes(random_int(0, 10));
        // Early leave: 15-45 minutes early
        $earlyLeave = random_int(15, 45);
        $checkOut = (clone $shiftEnd)->subMinutes($earlyLeave);

        return [
            'time_in' => $checkIn,
            'time_out' => $checkOut,
            'status' => 'on_time',
            'late_minutes' => 0,
            'early_leave_minutes' => $earlyLeave,
        ];
    }

    private function getLocationLatLon(?int $locationId): string
    {
        // Use default coordinates if location not found
        $defaultLat = '-7.424154';
        $defaultLon = '109.242088';

        if ($locationId) {
            $location = \App\Models\Location::find($locationId);
            if ($location && $location->latitude && $location->longitude) {
                return number_format((float)$location->latitude, 6, '.', '') . ',' . 
                       number_format((float)$location->longitude, 6, '.', '');
            }
        }

        return $defaultLat . ',' . $defaultLon;
    }
}
