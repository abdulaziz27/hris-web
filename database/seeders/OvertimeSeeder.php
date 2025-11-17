<?php

namespace Database\Seeders;

use App\Models\Overtime;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class OvertimeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Generate realistic overtime data for 2025.
     */
    public function run(): void
    {
        // Get employees only (not admin/manager) from our seeded data
        $employees = User::where('role', 'employee')
            ->whereNotNull('shift_kerja_id')
            ->whereNotNull('location_id')
            ->get();

        if ($employees->isEmpty()) {
            $this->command->warn('No employees found. Please run UserSeeder first.');
            return;
        }

        // Get manager for approval
        $manager = User::whereIn('role', ['manager', 'admin'])->first() ?? $employees->first();

        // Generate sample overtime requests (10-15 requests from different employees)
        $targetCount = min(15, $employees->count());
        $selectedEmployees = $employees->random($targetCount);

        $overtimeReasons = [
            'Menyelesaikan pekerjaan urgent yang tertunda karena hujan',
            'Menyelesaikan target pemanenan yang belum selesai',
            'Maintenance peralatan kebun yang rusak',
            'Menyelesaikan laporan akhir bulan yang harus dikumpulkan besok',
            'Menyelesaikan pekerjaan yang memerlukan perhatian segera',
            'Menyelesaikan target produksi harian yang belum tercapai',
            'Menyelesaikan pekerjaan administrasi yang tertunda',
            'Menyelesaikan pekerjaan yang memerlukan koordinasi tim',
            'Menyelesaikan target panen yang harus selesai hari ini',
            'Menyelesaikan perbaikan mesin yang mendesak',
            'Menyelesaikan pekerjaan kebun yang tertunda',
            'Menyelesaikan pengolahan hasil panen',
            'Menyelesaikan pekerjaan pembibitan yang mendesak',
        ];

        $statuses = ['pending', 'approved', 'approved', 'pending', 'approved', 'approved', 'pending', 'approved', 'pending', 'approved', 'approved', 'pending', 'approved'];

        // Get holidays for the period
        $today = Carbon::today();
        $twoMonthsAgo = $today->copy()->subMonths(2)->startOfMonth();
        $holidays = \App\Models\Holiday::whereBetween('date', [
            $twoMonthsAgo->toDateString(),
            $today->toDateString()
        ])->pluck('date')->map(fn($date) => Carbon::parse($date)->toDateString())->toArray();

        $createdCount = 0;
        $maxAttempts = $targetCount * 3; // Try up to 3x target count to find valid dates
        $attempt = 0;

        foreach ($selectedEmployees as $index => $employee) {
            if ($createdCount >= $targetCount || $attempt >= $maxAttempts) {
                break;
            }

            // Get employee's shift to determine overtime start time
            $shift = $employee->shiftKerja;
            if (!$shift) {
                continue;
            }

            // Parse shift end time - get raw value from database
            $shiftEndTimeString = $shift->getRawOriginal('end_time') ?? $shift->end_time?->format('H:i:s');
            if (!$shiftEndTimeString) {
                continue;
            }
            
            // Normalize time format (handle both H:i and H:i:s)
            $normalizedTime = strlen($shiftEndTimeString) === 5 ? $shiftEndTimeString . ':00' : $shiftEndTimeString;
            $shiftEndTime = Carbon::createFromFormat('H:i:s', $normalizedTime);
            $shiftEndHour = (int)$shiftEndTime->format('H');
            $shiftEndMinute = (int)$shiftEndTime->format('i');

            // Try to find a valid workday date
            $date = null;
            $dateAttempts = 0;
            while ($dateAttempts < 10) {
                $randomDays = rand(0, $today->diffInDays($twoMonthsAgo));
                $candidateDate = $twoMonthsAgo->copy()->addDays($randomDays);
                
                // Check if it's a workday
                if (!$candidateDate->isWeekend() && !in_array($candidateDate->toDateString(), $holidays)) {
                    $date = $candidateDate;
                    break;
                }
                $dateAttempts++;
            }

            // Skip if no valid date found
            if (!$date) {
                $attempt++;
                continue;
            }

            // Start time: after shift end (15-30 minutes after shift end)
            $startHour = $shiftEndHour;
            $startMinute = $shiftEndMinute + rand(15, 30);
            if ($startMinute >= 60) {
                $startHour++;
                $startMinute -= 60;
            }
            $startTime = Carbon::createFromTime($startHour, $startMinute, 0);

            // End time: 1-4 hours after start
            $durationHours = rand(1, 4);
            $endTime = (clone $startTime)->addHours($durationHours);

            $status = $statuses[$index % count($statuses)];

            // Check for duplicate overtime request (same user, same date, same start_time)
            $existingOvertime = Overtime::where('user_id', $employee->id)
                ->where('date', $date->toDateString())
                ->where('start_time', $startTime->format('H:i'))
                ->first();

            if ($existingOvertime) {
                $this->command->info("Skipping duplicate overtime for user {$employee->name} on {$date->toDateString()} at {$startTime->format('H:i')}");
                $attempt++;
                continue;
            }

            $overtimeData = [
                'user_id' => $employee->id,
                'date' => $date->toDateString(),
                'start_time' => $startTime->format('H:i'),
                'end_time' => $endTime->format('H:i'),
                'reason' => $overtimeReasons[$index % count($overtimeReasons)],
                'status' => $status,
            ];

            // If approved, add approval info
            if ($status === 'approved') {
                $approvedAt = $date->copy()->subDays(rand(1, 3));
                $overtimeData['approved_by'] = $manager->id;
                $overtimeData['approved_at'] = $approvedAt;
                $overtimeData['created_at'] = $approvedAt->copy()->subDays(rand(1, 2));
                $overtimeData['updated_at'] = $approvedAt;
            } else {
                $overtimeData['created_at'] = $date->copy()->subDays(rand(1, 3));
                $overtimeData['updated_at'] = $date->copy()->subDays(rand(1, 3));
            }

            // Use updateOrCreate to prevent duplicates
            Overtime::updateOrCreate(
                [
                    'user_id' => $employee->id,
                    'date' => $date->toDateString(),
                    'start_time' => $startTime->format('H:i'),
                ],
                $overtimeData
            );
            $createdCount++;
            $attempt++;
        }

        $this->command->info('✅ Created ' . $createdCount . ' sample overtime requests from seeded employees.');
    }
}
