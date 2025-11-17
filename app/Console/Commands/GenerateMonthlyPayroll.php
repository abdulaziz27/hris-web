<?php

namespace App\Console\Commands;

use App\Models\Location;
use App\Models\Payroll;
use App\Models\User;
use App\Services\PayrollCalculator;
use Carbon\Carbon;
use Illuminate\Console\Command;

class GenerateMonthlyPayroll extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'payroll:generate-monthly 
                            {--month= : Bulan (format: YYYY-MM, default: bulan ini)}
                            {--location= : ID Lokasi (kosongkan untuk semua lokasi)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate payroll otomatis untuk semua karyawan di bulan tertentu';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        // Parse month argument
        $monthInput = $this->option('month');
        if ($monthInput) {
            try {
                $period = Carbon::parse($monthInput)->startOfMonth();
            } catch (\Exception $e) {
                $this->error("Format bulan tidak valid. Gunakan format: YYYY-MM (contoh: 2025-11)");
                return self::FAILURE;
            }
        } else {
            $period = now()->startOfMonth();
        }

        $locationId = $this->option('location') ? (int) $this->option('location') : null;

        $this->info("Generating payroll for period: {$period->format('F Y')}...");
        if ($locationId) {
            $location = Location::find($locationId);
            if ($location) {
                $this->info("Location: {$location->name}");
            }
        } else {
            $this->info("Location: All locations");
        }
        $this->newLine();

        // Get users (employee, manager, admin)
        $query = User::whereIn('role', ['employee', 'manager', 'admin']);
        if ($locationId) {
            $query->where('location_id', $locationId);
        }
        $users = $query->get();

        if ($users->isEmpty()) {
            $this->warn('Tidak ada user yang ditemukan.');
            return self::SUCCESS;
        }

        $this->info("Found {$users->count()} user(s)");
        $this->newLine();

        // Note: standard workdays will be calculated per user in generateMonthlyPayroll()
        // This is a fallback value, but each user will have their own standard workdays
        $defaultStandardWorkdays = PayrollCalculator::calculateStandardWorkdays(
            $period->copy()->startOfMonth(),
            $period->copy()->endOfMonth()
        );

        $generated = 0;
        $skipped = 0;
        $errors = [];

        $bar = $this->output->createProgressBar($users->count());
        $bar->start();

        foreach ($users as $user) {
            try {
                // Check if payroll already exists
                $existingPayroll = Payroll::where('user_id', $user->id)
                    ->where('period', $period->toDateString())
                    ->first();

                if ($existingPayroll) {
                    $skipped++;
                    $bar->advance();
                    continue;
                }

                // Generate payroll data (pass null untuk standardWorkdays agar dihitung per user dengan userId)
                $payrollData = PayrollCalculator::generateMonthlyPayroll(
                    $user->id,
                    $period,
                    null // Will be calculated per user with userId (supports standard_workdays_per_month)
                );

                // Create payroll record
                Payroll::create([
                    'user_id' => $user->id,
                    'period' => $period->toDateString(),
                    'standard_workdays' => $payrollData['standard_workdays'],
                    'present_days' => $payrollData['present_days'],
                    'hk_review' => $payrollData['hk_review'],
                    'nilai_hk' => $payrollData['nilai_hk'],
                    'basic_salary' => $payrollData['basic_salary'],
                    'estimated_salary' => $payrollData['estimated_salary'],
                    'final_salary' => $payrollData['final_salary'],
                    'selisih_hk' => $payrollData['selisih_hk'],
                    'percentage' => $payrollData['percentage'],
                    'status' => 'draft',
                    'created_by' => 1, // System user
                ]);

                $generated++;
            } catch (\Exception $e) {
                $errors[] = "{$user->name}: {$e->getMessage()}";
                \Log::error("Failed to generate payroll for user {$user->id}: " . $e->getMessage());
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        // Show results
        $this->info("✓ Generated: {$generated} payroll(s)");
        if ($skipped > 0) {
            $this->warn("⊘ Skipped: {$skipped} payroll(s) (already exists)");
        }
        if (count($errors) > 0) {
            $this->error("✗ Errors: " . count($errors));
            foreach ($errors as $error) {
                $this->line("  - {$error}");
            }
        }

        $this->newLine();
        $this->info('Payroll generation completed!');

        return self::SUCCESS;
    }
}

