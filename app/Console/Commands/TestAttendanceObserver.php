<?php

namespace App\Console\Commands;

use App\Models\Attendance;
use App\Models\Payroll;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Console\Command;

class TestAttendanceObserver extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:attendance-observer 
                            {--user= : User ID untuk testing}
                            {--date= : Tanggal untuk testing (format: YYYY-MM-DD)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test AttendanceObserver - Simulasi absensi dan cek apakah payroll ter-update';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('=== Testing AttendanceObserver ===');
        $this->newLine();

        // Get user
        $userId = $this->option('user');
        if (!$userId) {
            $users = User::where('role', 'employee')->take(5)->get();
            if ($users->isEmpty()) {
                $this->error('Tidak ada karyawan yang ditemukan.');
                return self::FAILURE;
            }

            $this->info('Pilih user untuk testing:');
            foreach ($users as $index => $user) {
                $this->line("  [{$index}] {$user->name} (ID: {$user->id})");
            }
            $selected = $this->ask('Masukkan nomor user (0-' . ($users->count() - 1) . ')', 0);
            $user = $users[(int) $selected];
        } else {
            $user = User::find($userId);
            if (!$user) {
                $this->error("User dengan ID {$userId} tidak ditemukan.");
                return self::FAILURE;
            }
        }

        $this->info("User: {$user->name} (ID: {$user->id})");
        $this->newLine();

        // Get date
        $dateInput = $this->option('date');
        if ($dateInput) {
            try {
                $date = Carbon::parse($dateInput);
            } catch (\Exception $e) {
                $this->error("Format tanggal tidak valid. Gunakan format: YYYY-MM-DD");
                return self::FAILURE;
            }
        } else {
            $date = now();
        }

        $period = $date->copy()->startOfMonth();
        $this->info("Tanggal: {$date->format('d/m/Y')}");
        $this->info("Periode: {$period->format('F Y')}");
        $this->newLine();

        // Check existing payroll
        $existingPayroll = Payroll::where('user_id', $user->id)
            ->where('period', $period->toDateString())
            ->first();

        if ($existingPayroll) {
            $this->info('Payroll sebelum testing:');
            $this->table(
                ['Field', 'Value'],
                [
                    ['ID', $existingPayroll->id],
                    ['Status', $existingPayroll->status],
                    ['Present Days', $existingPayroll->present_days ?? 0],
                    ['Nilai HK', 'Rp ' . number_format($existingPayroll->nilai_hk ?? 0, 0, ',', '.')],
                    ['Estimated Salary', 'Rp ' . number_format($existingPayroll->estimated_salary ?? 0, 0, ',', '.')],
                    ['Percentage', ($existingPayroll->percentage ?? 0) . '%'],
                ]
            );
        } else {
            $this->warn('Payroll belum ada untuk periode ini.');
        }

        $this->newLine();
        $this->info('Membuat attendance baru...');

        // Count existing attendances for this user in this month
        $startDate = $period->copy()->startOfMonth();
        $endDate = $period->copy()->endOfMonth();
        $existingAttendances = Attendance::where('user_id', $user->id)
            ->whereBetween('date', [$startDate->toDateString(), $endDate->toDateString()])
            ->count();

        $this->info("Jumlah attendance bulan ini (sebelum): {$existingAttendances}");

        // Create test attendance
        try {
            $attendance = Attendance::create([
                'user_id' => $user->id,
                'shift_id' => $user->shift_kerja_id,
                'location_id' => $user->location_id,
                'date' => $date->toDateString(),
                'time_in' => now()->format('H:i:s'),
                'latlon_in' => '0,0',
                'status' => 'on_time',
                'is_weekend' => false,
                'is_holiday' => false,
            ]);

            $this->info("✓ Attendance berhasil dibuat (ID: {$attendance->id})");
        } catch (\Exception $e) {
            $this->error("✗ Gagal membuat attendance: " . $e->getMessage());
            return self::FAILURE;
        }

        // Wait a bit for observer to process
        sleep(1);

        $this->newLine();
        $this->info('Mengecek payroll setelah attendance dibuat...');

        // Refresh payroll
        $payroll = Payroll::where('user_id', $user->id)
            ->where('period', $period->toDateString())
            ->first();

        if (!$payroll) {
            $this->error('✗ Payroll tidak ditemukan setelah attendance dibuat!');
            $this->warn('Mungkin Observer tidak berjalan. Cek log untuk detail.');
            return self::FAILURE;
        }

        // Count attendances after
        $newAttendances = Attendance::where('user_id', $user->id)
            ->whereBetween('date', [$startDate->toDateString(), $endDate->toDateString()])
            ->count();

        $this->info("Jumlah attendance bulan ini (sesudah): {$newAttendances}");
        $this->newLine();

        $this->info('Payroll setelah testing:');
        $this->table(
            ['Field', 'Value', 'Status'],
            [
                ['ID', $payroll->id, $existingPayroll ? '✓ Existing' : '✓ Created'],
                ['Status', $payroll->status, '✓'],
                ['Present Days', $payroll->present_days ?? 0, $payroll->present_days == $newAttendances ? '✓ Match' : '⚠ Mismatch'],
                ['Nilai HK', 'Rp ' . number_format($payroll->nilai_hk ?? 0, 0, ',', '.'), $payroll->nilai_hk > 0 ? '✓ Set' : '⚠ Not Set'],
                ['Estimated Salary', 'Rp ' . number_format($payroll->estimated_salary ?? 0, 0, ',', '.'), $payroll->estimated_salary > 0 ? '✓ Calculated' : '⚠ Waiting nilai_hk'],
                ['Percentage', ($payroll->percentage ?? 0) . '%', '✓'],
            ]
        );

        $this->newLine();

        // Validation
        $success = true;
        if ($payroll->present_days != $newAttendances) {
            $this->error("⚠ Present Days tidak sesuai! Expected: {$newAttendances}, Got: {$payroll->present_days}");
            $success = false;
        } else {
            $this->info("✓ Present Days sesuai dengan jumlah attendance");
        }

        if ($payroll->nilai_hk == 0) {
            $this->warn("⚠ Nilai HK belum diisi (ini normal, bisa diisi nanti)");
        } else {
            $this->info("✓ Nilai HK sudah diisi");
        }

        if ($success) {
            $this->newLine();
            $this->info('✓✓✓ TEST BERHASIL! AttendanceObserver berfungsi dengan baik.');
        } else {
            $this->newLine();
            $this->error('✗✗✗ TEST GAGAL! Ada masalah dengan AttendanceObserver.');
        }

        // Ask if want to delete test attendance
        if ($this->confirm('Hapus attendance test yang baru dibuat?', false)) {
            $attendance->delete();
            $this->info('✓ Attendance test dihapus');
        }

        return $success ? self::SUCCESS : self::FAILURE;
    }
}

