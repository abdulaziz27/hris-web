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

        // Create 10 leave requests (only employees, not admin/manager)
        $employeeUsers = $users->where('role', 'employee')->take(10);
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
        ];

        $leaveCount = 0;
        foreach ($employeeUsers as $user) {
            if ($leaveCount >= 10) {
                break;
            }
            
            $leaveType = $leaveTypes->random();
            $statuses = ['pending', 'approved', 'approved', 'pending', 'approved'];
            $status = $statuses[array_rand($statuses)];

            $startDate = \Carbon\Carbon::create(2025, rand(1, 12), rand(1, 25));
            $totalDays = rand(1, 5);
            $endDate = (clone $startDate)->addDays($totalDays - 1);

            if ($status === 'approved') {
                $approvedAt = (clone $startDate)->subDays(rand(2, 5));
                if ($approvedAt->year < 2025) {
                    $approvedAt = \Carbon\Carbon::create(2025, 1, rand(1, 10));
                }
                
                Leave::create([
                    'employee_id' => $user->id,
                    'leave_type_id' => $leaveType->id,
                    'start_date' => $startDate->toDateString(),
                    'end_date' => $endDate->toDateString(),
                    'total_days' => $totalDays,
                    'reason' => $realLeaveReasons[array_rand($realLeaveReasons)],
                    'status' => 'approved',
                    'approved_by' => $manager->id,
                    'approved_at' => $approvedAt,
                    'created_at' => $approvedAt->subDays(rand(1, 3)),
                    'updated_at' => $approvedAt,
                ]);
            } else {
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
            }
            
            $leaveCount++;
        }

        $this->command->info('✅ Created 10 leave requests successfully.');
    }
}
