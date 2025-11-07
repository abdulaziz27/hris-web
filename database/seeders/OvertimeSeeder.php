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
        // Get employees only (not admin/manager)
        $employees = User::where('role', 'employee')->get();

        if ($employees->isEmpty()) {
            $this->command->warn('No employees found. Please run EmployeeBulkSeeder first.');

            return;
        }

        // Get manager for approval
        $manager = User::whereIn('role', ['manager', 'admin'])->first() ?? $employees->first();

        // Generate 2 pending overtime requests for employees
        $pendingEmployees = $employees->random(min(2, $employees->count()));

        $pendingReasons = [
            'Menyelesaikan pekerjaan urgent yang tertunda karena hujan',
            'Menyelesaikan target pemanenan yang belum selesai',
            'Maintenance peralatan kebun yang rusak',
            'Menyelesaikan laporan akhir bulan yang harus dikumpulkan besok',
            'Menyelesaikan pekerjaan yang memerlukan perhatian segera',
            'Menyelesaikan target produksi harian yang belum tercapai',
            'Menyelesaikan pekerjaan administrasi yang tertunda',
        ];

        foreach ($pendingEmployees as $employee) {
            // Random date in 2025 (past or near future)
            $month = rand(1, 12);
            $day = rand(1, 28);
            $date = Carbon::create(2025, $month, $day);

            // Random start time (after normal shift end)
            $startHour = rand(17, 20);
            $startMinute = rand(0, 59);
            $startTime = Carbon::createFromTime($startHour, $startMinute, 0);

            // Random end time (1-4 hours after start)
            $durationHours = rand(1, 4);
            $endTime = (clone $startTime)->addHours($durationHours);

            Overtime::create([
                'user_id' => $employee->id,
                'date' => $date->toDateString(),
                'start_time' => $startTime->format('H:i:s'),
                'end_time' => $endTime->format('H:i:s'),
                'reason' => $pendingReasons[array_rand($pendingReasons)],
                'status' => 'pending',
                'created_at' => $date->subDays(rand(1, 3)),
                'updated_at' => $date,
            ]);
        }

        $this->command->info('✅ Created 2 pending overtime requests for employees (2025).');
    }
}
