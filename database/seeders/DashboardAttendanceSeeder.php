<?php

namespace Database\Seeders;

use App\Models\Attendance;
use App\Models\Location;
use App\Models\ShiftKerja;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class DashboardAttendanceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Generate attendance data for the last 30 days to enrich the dashboard chart.
     */
    public function run(): void
    {
        $users = User::where('role', 'employee')->with(['shiftKerja'])->get();
        $locations = Location::where('is_active', true)->get();
        $shifts = ShiftKerja::all();

        if ($users->isEmpty() || $shifts->isEmpty() || $locations->isEmpty()) {
            $this->command->warn('Skipping DashboardAttendanceSeeder: Missing users, shifts, or locations.');

            return;
        }

        // Clear existing attendance data for the last 30 days
        $startDate = Carbon::today()->subDays(30);
        Attendance::where('date', '>=', $startDate)->delete();

        $this->command->info('Generating attendance data for the last 30 days...');

        // Generate data for each day in the last 30 days
        for ($day = 30; $day >= 0; $day--) {
            $date = Carbon::today()->subDays($day);

            // Skip weekends and holidays (optional - you can adjust this)
            if ($date->isWeekend()) {
                // Only 30% of employees work on weekends
                $weekendUsers = $users->random((int) ($users->count() * 0.3));
                $this->generateDayAttendance($weekendUsers, $date, $locations, $shifts, true);
            } else {
                // Regular weekday - 80-95% attendance
                $attendanceRate = rand(80, 95) / 100;
                $dayUsers = $users->random((int) ($users->count() * $attendanceRate));
                $this->generateDayAttendance($dayUsers, $date, $locations, $shifts, false);
            }
        }

        $totalAttendances = Attendance::where('date', '>=', $startDate)->count();
        $this->command->info("✅ Successfully generated {$totalAttendances} attendance records for the last 30 days!");
    }

    /**
     * Generate attendance for a specific day
     */
    private function generateDayAttendance($users, Carbon $date, $locations, $shifts, bool $isWeekend): void
    {
        foreach ($users as $user) {
            $shift = $user->shiftKerja ?? $shifts->random();
            $location = $user->location ?? $locations->random();

            // Determine attendance status (on_time, late, absent, early_leave)
            $statusRoll = rand(1, 100);

            if ($statusRoll <= 5) {
                // 5% chance - absent (no check-in)
                continue;
            } elseif ($statusRoll <= 20) {
                // 15% chance - late
                $attendance = $this->createLateAttendance($user, $shift, $location, $date, $isWeekend);
            } elseif ($statusRoll <= 25) {
                // 5% chance - early leave
                $attendance = $this->createEarlyLeaveAttendance($user, $shift, $location, $date, $isWeekend);
            } else {
                // 75% chance - on time
                $attendance = $this->createOnTimeAttendance($user, $shift, $location, $date, $isWeekend);
            }

            if ($attendance) {
                Attendance::create($attendance);
            }
        }
    }

    /**
     * Create on-time attendance
     */
    private function createOnTimeAttendance($user, $shift, $location, Carbon $date, bool $isWeekend): array
    {
        $shiftStartTime = $this->getShiftTimeString($shift->start_time);
        $shiftEndTime = $this->getShiftTimeString($shift->end_time);

        // Check-in: 0-5 minutes after shift start
        $checkInMinutes = rand(0, 5);
        $timeIn = (clone $date)->setTimeFromTimeString($shiftStartTime)->addMinutes($checkInMinutes);

        // Check-out: 0-10 minutes before shift end
        $checkOutMinutes = rand(0, 10);
        $timeOut = (clone $date)->setTimeFromTimeString($shiftEndTime)->subMinutes($checkOutMinutes);

        // If shift ends next day (e.g., night shift)
        if ($timeOut->lessThan($timeIn)) {
            $timeOut->addDay();
        }

        return [
            'user_id' => $user->id,
            'shift_id' => $shift->id,
            'location_id' => $location->id,
            'date' => $date->toDateString(),
            'time_in' => $timeIn->format('H:i:s'),
            'time_out' => $timeOut->format('H:i:s'),
            'latlon_in' => $location->latitude.','.$location->longitude,
            'latlon_out' => $location->latitude.','.$location->longitude,
            'status' => 'on_time',
            'is_weekend' => $isWeekend,
            'is_holiday' => false,
            'holiday_work' => $isWeekend,
            'late_minutes' => 0,
            'early_leave_minutes' => 0,
        ];
    }

    /**
     * Create late attendance
     */
    private function createLateAttendance($user, $shift, $location, Carbon $date, bool $isWeekend): array
    {
        $shiftStartTime = $this->getShiftTimeString($shift->start_time);
        $shiftEndTime = $this->getShiftTimeString($shift->end_time);

        // Late: 15-45 minutes after shift start
        $lateMinutes = rand(15, 45);
        $timeIn = (clone $date)->setTimeFromTimeString($shiftStartTime)->addMinutes($lateMinutes);

        $checkOutMinutes = rand(0, 10);
        $timeOut = (clone $date)->setTimeFromTimeString($shiftEndTime)->subMinutes($checkOutMinutes);

        if ($timeOut->lessThan($timeIn)) {
            $timeOut->addDay();
        }

        return [
            'user_id' => $user->id,
            'shift_id' => $shift->id,
            'location_id' => $location->id,
            'date' => $date->toDateString(),
            'time_in' => $timeIn->format('H:i:s'),
            'time_out' => $timeOut->format('H:i:s'),
            'latlon_in' => $location->latitude.','.$location->longitude,
            'latlon_out' => $location->latitude.','.$location->longitude,
            'status' => 'late',
            'is_weekend' => $isWeekend,
            'is_holiday' => false,
            'holiday_work' => $isWeekend,
            'late_minutes' => $lateMinutes,
            'early_leave_minutes' => 0,
        ];
    }

    /**
     * Create early leave attendance
     */
    private function createEarlyLeaveAttendance($user, $shift, $location, Carbon $date, bool $isWeekend): array
    {
        $shiftStartTime = $this->getShiftTimeString($shift->start_time);
        $shiftEndTime = $this->getShiftTimeString($shift->end_time);

        $checkInMinutes = rand(0, 5);
        $timeIn = (clone $date)->setTimeFromTimeString($shiftStartTime)->addMinutes($checkInMinutes);

        // Early leave: 30-90 minutes before shift end
        $earlyLeaveMinutes = rand(30, 90);
        $timeOut = (clone $date)->setTimeFromTimeString($shiftEndTime)->subMinutes($earlyLeaveMinutes);

        if ($timeOut->lessThan($timeIn)) {
            $timeOut->addDay();
        }

        return [
            'user_id' => $user->id,
            'shift_id' => $shift->id,
            'location_id' => $location->id,
            'date' => $date->toDateString(),
            'time_in' => $timeIn->format('H:i:s'),
            'time_out' => $timeOut->format('H:i:s'),
            'latlon_in' => $location->latitude.','.$location->longitude,
            'latlon_out' => $location->latitude.','.$location->longitude,
            'status' => 'on_time',
            'is_weekend' => $isWeekend,
            'is_holiday' => false,
            'holiday_work' => $isWeekend,
            'late_minutes' => 0,
            'early_leave_minutes' => $earlyLeaveMinutes,
        ];
    }

    /**
     * Get shift time as string in H:i:s format
     */
    private function getShiftTimeString($time): string
    {
        // If it's a Carbon instance, format it
        if ($time instanceof \Carbon\Carbon) {
            return $time->format('H:i:s');
        }

        // If it's a string, handle both "H:i" and "H:i:s" formats
        if (is_string($time)) {
            if (strlen($time) === 5) {
                return $time.':00';
            }

            return $time;
        }

        // Default fallback
        return '08:00:00';
    }
}
