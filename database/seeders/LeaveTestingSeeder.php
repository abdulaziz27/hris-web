<?php

namespace Database\Seeders;

use App\Models\Leave;
use App\Models\LeaveBalance;
use App\Models\LeaveType;
use App\Models\User;
use Illuminate\Database\Seeder;

class LeaveTestingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = User::all();
        $leaveTypes = LeaveType::all();
        $currentYear = now()->year;

        if ($users->isEmpty() || $leaveTypes->isEmpty()) {
            $this->command->warn('No users or leave types found. Please run UserSeeder and LeaveTypeSeeder first.');

            return;
        }

        // Create leave balances for all users
        foreach ($users as $user) {
            foreach ($leaveTypes as $leaveType) {
                LeaveBalance::updateOrCreate(
                    [
                        'employee_id' => $user->id,
                        'leave_type_id' => $leaveType->id,
                        'year' => $currentYear,
                    ],
                    [
                        'quota_days' => $leaveType->quota_days,
                        'used_days' => 0,
                        'remaining_days' => $leaveType->quota_days,
                        'carry_over_days' => 0,
                        'last_updated' => now(),
                    ]
                );
            }
        }

        // Create realistic pending leave requests (only employees, not admin/manager)
        $employeeUsers = $users->where('role', 'employee')->take(5);
        $manager = $users->whereIn('role', ['manager', 'admin'])->first() ?? $users->first();

        $realLeaveReasons = [
            'Izin cuti untuk menikahkan anak di kampung',
            'Cuti sakit karena demam dan flu',
            'Izin cuti untuk mengantar orang tua berobat ke rumah sakit',
            'Cuti tahunan untuk liburan keluarga',
            'Izin cuti untuk keperluan keluarga yang mendesak',
            'Cuti untuk mengurus dokumen penting di kelurahan',
            'Izin cuti karena ada acara keluarga besar',
            'Cuti untuk merawat anak yang sedang sakit',
            'Izin cuti untuk menghadiri pernikahan saudara',
            'Cuti tahunan untuk refreshing dan istirahat',
            'Izin cuti untuk mengurus kepindahan rumah',
            'Cuti untuk mengikuti acara keluarga di luar kota',
            'Izin cuti untuk mengantar anak daftar sekolah',
            'Cuti tahunan untuk liburan bersama keluarga',
            'Izin cuti karena ada acara adat keluarga',
        ];

        foreach ($employeeUsers as $user) {
            $leaveType = $leaveTypes->random();

            // Pending leave - dengan alasan real
            $startDate = \Carbon\Carbon::create(2025, rand(1, 12), rand(1, 25));
            $totalDays = rand(1, 5);
            $endDate = (clone $startDate)->addDays($totalDays - 1);

            Leave::create([
                'employee_id' => $user->id,
                'leave_type_id' => $leaveType->id,
                'start_date' => $startDate->toDateString(),
                'end_date' => $endDate->toDateString(),
                'total_days' => $totalDays,
                'reason' => $realLeaveReasons[array_rand($realLeaveReasons)],
                'status' => 'pending',
                'created_at' => \Carbon\Carbon::create(2025, rand(1, 12), rand(1, 28)),
                'updated_at' => \Carbon\Carbon::create(2025, rand(1, 12), rand(1, 28)),
            ]);

            // Approved leave - dengan alasan real (past dates in 2025)
            $approvedMonth = rand(1, 12);
            $approvedDay = rand(1, 20);
            $approvedStartDate = \Carbon\Carbon::create(2025, $approvedMonth, $approvedDay)->subDays(rand(5, 15));
            // Ensure it's still in 2025
            if ($approvedStartDate->year < 2025) {
                $approvedStartDate = \Carbon\Carbon::create(2025, 1, rand(1, 15));
            }
            $approvedTotalDays = rand(1, 3);
            $approvedEndDate = (clone $approvedStartDate)->addDays($approvedTotalDays - 1);
            $approvedAt = (clone $approvedStartDate)->subDays(rand(2, 5));
            // Ensure approved_at is not before 2025
            if ($approvedAt->year < 2025) {
                $approvedAt = \Carbon\Carbon::create(2025, 1, rand(1, 10));
            }

            $approvedReasons = [
                'Cuti tahunan untuk liburan keluarga',
                'Izin cuti untuk keperluan pribadi',
                'Cuti sakit karena demam',
                'Izin cuti untuk mengurus dokumen penting',
                'Cuti untuk menghadiri acara keluarga',
            ];

            Leave::create([
                'employee_id' => $user->id,
                'leave_type_id' => $leaveType->id,
                'start_date' => $approvedStartDate->toDateString(),
                'end_date' => $approvedEndDate->toDateString(),
                'total_days' => $approvedTotalDays,
                'reason' => $approvedReasons[array_rand($approvedReasons)],
                'status' => 'approved',
                'approved_by' => $manager->id,
                'approved_at' => $approvedAt,
                'created_at' => $approvedAt->subDays(rand(1, 3)),
                'updated_at' => $approvedAt,
            ]);
        }

        $this->command->info('Leave testing data created successfully.');
    }
}
