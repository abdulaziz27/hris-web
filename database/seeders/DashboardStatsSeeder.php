<?php

namespace Database\Seeders;

use App\Models\Leave;
use App\Models\LeaveType;
use App\Models\Overtime;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class DashboardStatsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Generate realistic data for dashboard statistics:
     * - Overtime Approved (this month)
     * - Leave Approved (this month)
     * - Complete Attendance is handled by DashboardAttendanceSeeder
     */
    public function run(): void
    {
        $users = User::where('role', 'employee')->get();
        $leaveTypes = LeaveType::all();

        if ($users->isEmpty()) {
            $this->command->warn('Skipping DashboardStatsSeeder: No employees found.');

            return;
        }

        $this->command->info('Generating dashboard statistics data for 2025...');

        // Use current month in 2025
        $thisMonth = Carbon::create(2025, Carbon::now()->month, 1);

        // Clear existing data for this month in 2025
        Overtime::whereMonth('date', $thisMonth->month)
            ->whereYear('date', 2025)
            ->delete();

        Leave::whereMonth('approved_at', $thisMonth->month)
            ->whereYear('approved_at', 2025)
            ->where('status', 'approved')
            ->delete();

        // Generate Overtime Approved this month
        $this->generateOvertimeApproved($users, $thisMonth);

        // Generate Leave Approved this month
        if ($leaveTypes->isNotEmpty()) {
            $this->generateLeaveApproved($users, $leaveTypes, $thisMonth);
        }

        $overtimeCount = Overtime::where('status', 'approved')
            ->whereMonth('date', $thisMonth->month)
            ->whereYear('date', 2025)
            ->count();

        $leaveCount = Leave::where('status', 'approved')
            ->whereNotNull('approved_at')
            ->whereMonth('approved_at', $thisMonth->month)
            ->whereYear('approved_at', 2025)
            ->count();

        $this->command->info("✅ Generated {$overtimeCount} approved overtime records for this month.");
        $this->command->info("✅ Generated {$leaveCount} approved leave records for this month.");
    }

    /**
     * Generate approved overtime records for this month
     */
    private function generateOvertimeApproved($users, Carbon $month): void
    {
        // Generate 15-25% of employees with overtime this month
        $employeesWithOvertime = $users->random((int) ($users->count() * rand(15, 25) / 100));
        $manager = User::where('role', 'manager')->first() ?? User::where('role', 'admin')->first() ?? $users->first();

        foreach ($employeesWithOvertime as $user) {
            // Each employee can have 1-3 overtime records this month
            $overtimeCount = rand(1, 3);

            for ($i = 0; $i < $overtimeCount; $i++) {
                // Random date this month in 2025 (past dates only)
                $daysInMonth = $month->daysInMonth;
                $today = Carbon::now();
                $year = 2025;
                $maxDay = ($today->year === $year && $today->month === $month->month) ? min($daysInMonth, $today->day - 1) : $daysInMonth;
                if ($maxDay < 1) {
                    continue; // Skip if no valid days
                }
                $randomDay = rand(1, $maxDay);
                $date = Carbon::create($year, $month->month, $randomDay);

                // Random start time (after normal shift end)
                $startHour = rand(17, 20);
                $startMinute = rand(0, 59);
                $startTime = Carbon::createFromTime($startHour, $startMinute, 0);

                // Random end time (1-4 hours after start)
                $durationHours = rand(1, 4);
                $endTime = (clone $startTime)->addHours($durationHours);

                // Approved 1-2 days after request
                $approvedAt = (clone $date)->addDays(rand(1, 2))->setTime(rand(8, 10), rand(0, 59), 0);

                $reasons = [
                    'Menyelesaikan pekerjaan urgent yang tertunda',
                    'Menyelesaikan target produksi bulanan',
                    'Maintenance peralatan kebun',
                    'Menyelesaikan laporan akhir bulan',
                    'Menyelesaikan proyek khusus',
                    'Menyelesaikan pekerjaan yang memerlukan perhatian segera',
                    'Menyelesaikan target pemanenan',
                    'Menyelesaikan pekerjaan administrasi',
                ];

                Overtime::create([
                    'user_id' => $user->id,
                    'date' => $date->toDateString(),
                    'start_time' => $startTime->format('H:i:s'),
                    'end_time' => $endTime->format('H:i:s'),
                    'reason' => $reasons[array_rand($reasons)],
                    'status' => 'approved',
                    'approved_by' => $manager->id,
                    'approved_at' => $approvedAt,
                    'created_at' => $date->subDays(rand(1, 3)),
                    'updated_at' => $approvedAt,
                ]);
            }
        }
    }

    /**
     * Generate approved leave records for this month
     */
    private function generateLeaveApproved($users, $leaveTypes, Carbon $month): void
    {
        // Generate 10-20% of employees with approved leave this month
        $employeesWithLeave = $users->random((int) ($users->count() * rand(10, 20) / 100));
        $manager = User::where('role', 'manager')->first() ?? User::where('role', 'admin')->first() ?? $users->first();

        foreach ($employeesWithLeave as $user) {
            // Each employee can have 1-2 approved leave records this month
            $leaveCount = rand(1, 2);

            for ($i = 0; $i < $leaveCount; $i++) {
                $leaveType = $leaveTypes->random();

                // Random date this month in 2025 (past dates only)
                $daysInMonth = $month->daysInMonth;
                $today = Carbon::now();
                $year = 2025;
                $maxDay = ($today->year === $year && $today->month === $month->month) ? min($daysInMonth, $today->day - 1) : $daysInMonth;
                if ($maxDay < 1) {
                    continue; // Skip if no valid days
                }
                $randomDay = rand(1, $maxDay);
                $startDate = Carbon::create($year, $month->month, $randomDay);

                // Leave duration: 1-5 days
                $totalDays = rand(1, 5);
                $endDate = (clone $startDate)->addDays($totalDays - 1);

                // Approved 2-5 days before start date
                $approvedAt = (clone $startDate)->subDays(rand(2, 5))->setTime(rand(8, 10), rand(0, 59), 0);

                // Don't create leave in the future
                if ($approvedAt->isFuture()) {
                    continue;
                }

                $reasons = [
                    'Cuti tahunan',
                    'Keperluan keluarga',
                    'Keperluan pribadi',
                    'Sakit',
                    'Izin keperluan mendesak',
                    'Cuti bersama',
                    'Keperluan keluarga sakit',
                ];

                Leave::create([
                    'employee_id' => $user->id,
                    'leave_type_id' => $leaveType->id,
                    'start_date' => $startDate->toDateString(),
                    'end_date' => $endDate->toDateString(),
                    'total_days' => $totalDays,
                    'reason' => $reasons[array_rand($reasons)],
                    'status' => 'approved',
                    'approved_by' => $manager->id,
                    'approved_at' => $approvedAt,
                    'created_at' => $approvedAt->subDays(rand(1, 3)),
                    'updated_at' => $approvedAt,
                ]);
            }
        }
    }
}
