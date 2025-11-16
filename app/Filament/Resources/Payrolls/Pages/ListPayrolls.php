<?php

namespace App\Filament\Resources\Payrolls\Pages;

use App\Exports\PayrollAttendanceExport;
use App\Filament\Resources\Payrolls\PayrollResource;
use App\Models\Location;
use App\Models\Payroll;
use App\Models\User;
use App\Services\PayrollCalculator;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Maatwebsite\Excel\Facades\Excel;

class ListPayrolls extends ListRecords
{
    protected static string $resource = PayrollResource::class;

    /**
     * Auto-generate payroll when page is mounted
     * DISABLED: This was causing slow page load. Use manual generate button instead.
     */
    public function mount(): void
    {
        parent::mount();
        
        // DISABLED: Auto-generate was too slow with 300+ employees
        // Users should use "Generate Payroll Otomatis" button instead
        // $this->autoGeneratePayrollsForPeriod(now()->startOfMonth(), now()->endOfMonth());
    }

    /**
     * Auto-generate payroll when filters are updated
     * DISABLED: This was causing slow filter changes. Use manual generate button instead.
     */
    public function updatedTableFilters(): void
    {
        parent::updatedTableFilters();
        
        // DISABLED: Auto-generate on filter change was too slow
        // Users should use "Generate Payroll Otomatis" button instead
        
        // // Get period range from filters
        // $periodFrom = null;
        // $periodTo = null;
        // $locationId = null;

        // // Check if period_range filter is active
        // if (isset($this->tableFilters['period_range']['period_from'])) {
        //     $periodFrom = Carbon::parse($this->tableFilters['period_range']['period_from'])->startOfMonth();
        // }
        // if (isset($this->tableFilters['period_range']['period_to'])) {
        //     $periodTo = Carbon::parse($this->tableFilters['period_range']['period_to'])->endOfMonth();
        // }

        // // Check if this_month filter is active
        // if (isset($this->tableFilters['this_month']) && $this->tableFilters['this_month']) {
        //     $periodFrom = now()->startOfMonth();
        //     $periodTo = now()->endOfMonth();
        // }

        // // Check if last_month filter is active
        // if (isset($this->tableFilters['last_month']) && $this->tableFilters['last_month']) {
        //     $periodFrom = now()->subMonth()->startOfMonth();
        //     $periodTo = now()->subMonth()->endOfMonth();
        // }

        // // Get location filter if active
        // if (isset($this->tableFilters['location']['values']) && is_array($this->tableFilters['location']['values']) && count($this->tableFilters['location']['values']) > 0) {
        //     // If multiple locations selected, use first one (or could generate for all)
        //     $locationId = (int) $this->tableFilters['location']['values'][0];
        // }

        // // If period range is determined, auto-generate
        // if ($periodFrom && $periodTo) {
        //     $this->autoGeneratePayrollsForPeriod($periodFrom, $periodTo, $locationId);
        // } elseif ($periodFrom) {
        //     $this->autoGeneratePayrollsForPeriod($periodFrom, $periodFrom->copy()->endOfMonth(), $locationId);
        // } elseif ($periodTo) {
        //     $this->autoGeneratePayrollsForPeriod($periodTo->copy()->startOfMonth(), $periodTo, $locationId);
        // }
    }

    /**
     * Auto-generate payrolls for a specific period range
     */
    protected function autoGeneratePayrollsForPeriod(Carbon $periodFrom, Carbon $periodTo, ?int $locationId = null): void
    {
        // Get active employees (filter by location if provided)
        $query = User::where('role', 'employee');
        
        if ($locationId) {
            $query->where('location_id', $locationId);
        }
        
        $users = $query->get();

        if ($users->isEmpty()) {
            return;
        }

        // Generate payroll for each month in the range
        $current = $periodFrom->copy()->startOfMonth();
        $end = $periodTo->copy()->endOfMonth();

        while ($current <= $end) {
            $period = $current->copy()->startOfMonth();
            
            foreach ($users as $user) {
                try {
                    // Calculate standard workdays for this month
                    $standardWorkdays = PayrollCalculator::calculateStandardWorkdays(
                        $period->copy()->startOfMonth(),
                        $period->copy()->endOfMonth()
                    );

                    // Generate payroll data
                    $payrollData = PayrollCalculator::generateMonthlyPayroll(
                        $user->id,
                        $period,
                        $standardWorkdays
                    );

                    // Check if payroll already exists
                    $existingPayroll = Payroll::where('user_id', $user->id)
                        ->where('period', $period->toDateString())
                        ->first();

                    if ($existingPayroll) {
                        // Update existing payroll (especially nilai_hk if it changed)
                        // Only update if status is 'draft' to avoid overwriting approved payrolls
                        if ($existingPayroll->status === 'draft') {
                            $existingPayroll->update([
                                'standard_workdays' => $payrollData['standard_workdays'],
                                'present_days' => $payrollData['present_days'],
                                'nilai_hk' => $payrollData['nilai_hk'],
                                'basic_salary' => $payrollData['basic_salary'],
                                'estimated_salary' => $payrollData['estimated_salary'],
                                'final_salary' => $payrollData['final_salary'],
                                'selisih_hk' => $payrollData['selisih_hk'],
                                'percentage' => $payrollData['percentage'],
                                // Don't update hk_review if it was manually set
                                'hk_review' => $existingPayroll->hk_review ?? $payrollData['hk_review'],
                            ]);
                        }
                        // Skip if payroll is approved (to preserve manual changes)
                        continue;
                    }

                    // Create new payroll record
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
                        'created_by' => auth()->id(),
                    ]);
                } catch (\Exception $e) {
                    // Log error but continue with other users
                    \Log::error("Failed to generate payroll for user {$user->id}: " . $e->getMessage());
                    continue;
                }
            }

            // Move to next month
            $current->addMonth();
        }
    }


    protected function getHeaderActions(): array
    {
        return [
            Action::make('generate_payrolls')
                ->label('Generate Payroll Otomatis')
                ->icon('heroicon-o-sparkles')
                ->color('success')
                ->form([
                    DatePicker::make('period')
                        ->label('Periode')
                        ->required()
                        ->native(false)
                        ->displayFormat('F Y')
                        ->default(now()->startOfMonth())
                        ->helperText('Pilih periode bulan untuk generate payroll'),

                    Select::make('location')
                        ->label('Lokasi Kebun')
                        ->placeholder('Semua Lokasi')
                        ->options(function () {
                            return Location::where('is_active', true)
                                ->orderBy('name')
                                ->pluck('name', 'id')
                                ->toArray();
                        })
                        ->searchable()
                        ->preload()
                        ->helperText('Kosongkan untuk generate semua lokasi'),

                    Select::make('standard_workdays')
                        ->label('Hari Kerja Standar')
                        ->options([
                            20 => '20 hari',
                            21 => '21 hari',
                            22 => '22 hari',
                            23 => '23 hari',
                        ])
                        ->placeholder('Otomatis (dihitung dari kalender)')
                        ->helperText('Kosongkan untuk menghitung otomatis berdasarkan kalender (weekend + hari libur). Atau pilih manual jika ingin override.')
                        ->reactive(),
                ])
                ->modalHeading('Generate Payroll Otomatis')
                ->modalDescription('Sistem akan otomatis generate payroll untuk semua karyawan aktif di periode yang dipilih. Hari kerja standar akan dihitung otomatis berdasarkan kalender (weekend + hari libur) jika tidak diisi manual. Payroll yang sudah ada dengan status "Draft" akan di-update, sedangkan yang sudah "Disetujui" akan dilewati.')
                ->action(function (array $data) {
                    $period = Carbon::parse($data['period'])->startOfMonth();
                    $locationId = $data['location'] ?? null;
                    
                    // Jika standard_workdays tidak diisi, hitung otomatis
                    $standardWorkdays = null;
                    if (isset($data['standard_workdays']) && $data['standard_workdays'] !== null) {
                        $standardWorkdays = (int) $data['standard_workdays'];
                    } else {
                        // Hitung otomatis berdasarkan periode
                        $start = $period->copy()->startOfMonth();
                        $end = $period->copy()->endOfMonth();
                        $standardWorkdays = PayrollCalculator::calculateStandardWorkdays($start, $end);
                    }

                    // Get users to generate payroll for
                    $query = User::where('role', 'employee');
                    
                    if ($locationId) {
                        $query->where('location_id', $locationId);
                    }

                    $users = $query->get();
                    $totalUsers = $users->count();

                    if ($totalUsers === 0) {
                        Notification::make()
                            ->title('Tidak ada karyawan')
                            ->warning()
                            ->body('Tidak ada karyawan yang ditemukan untuk generate payroll.')
                            ->send();
                        return;
                    }

                    $generated = 0;
                    $skipped = 0;
                    $errors = [];

                    foreach ($users as $user) {
                        try {
                            // Check if payroll already exists
                            $existingPayroll = Payroll::where('user_id', $user->id)
                                ->where('period', $period->toDateString())
                                ->first();

                            // Generate payroll data
                            $payrollData = PayrollCalculator::generateMonthlyPayroll(
                                $user->id,
                                $period,
                                $standardWorkdays
                            );

                            if ($existingPayroll) {
                                // Update existing payroll only if status is 'draft'
                                if ($existingPayroll->status === 'draft') {
                                    $existingPayroll->update([
                                        'standard_workdays' => $payrollData['standard_workdays'],
                                        'present_days' => $payrollData['present_days'],
                                        'nilai_hk' => $payrollData['nilai_hk'],
                                        'basic_salary' => $payrollData['basic_salary'],
                                        'estimated_salary' => $payrollData['estimated_salary'],
                                        'final_salary' => $payrollData['final_salary'],
                                        'selisih_hk' => $payrollData['selisih_hk'],
                                        'percentage' => $payrollData['percentage'],
                                        // Don't update hk_review if it was manually set
                                        'hk_review' => $existingPayroll->hk_review ?? $payrollData['hk_review'],
                                    ]);
                                    $generated++;
                                } else {
                                    // Skip if payroll is approved (to preserve manual changes)
                                    $skipped++;
                                }
                            } else {
                                // Create new payroll record
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
                                'created_by' => auth()->id(),
                            ]);

                            $generated++;
                            }
                        } catch (\Exception $e) {
                            $errors[] = "{$user->name}: {$e->getMessage()}";
                        }
                    }

                    // Show notification
                    $message = "Berhasil generate/update {$generated} payroll";
                    if ($skipped > 0) {
                        $message .= ", {$skipped} dilewati (sudah disetujui/dibayar)";
                    }
                    if (count($errors) > 0) {
                        $message .= ", " . count($errors) . " error";
                        // Check if errors are related to nilai_hk
                        $nilaiHKErrors = array_filter($errors, function($error) {
                            return str_contains($error, 'Nilai HK');
                        });
                        if (count($nilaiHKErrors) > 0) {
                            $message .= "\n\nCatatan: Beberapa karyawan belum memiliki Nilai HK. Payroll tetap dibuat dengan nilai_hk = 0. Isi Nilai HK di lokasi atau user untuk menghitung gaji.";
                        }
                    }

                    $notificationType = count($errors) > 0 ? 'warning' : 'success';

                    Notification::make()
                        ->title('Generate Payroll Selesai')
                        ->{$notificationType}()
                        ->body($message)
                        ->send();

                }),

            Action::make('regenerate_payrolls')
                ->label('Regenerate Payroll (Update Draft)')
                ->icon('heroicon-o-arrow-path')
                ->color('warning')
                ->form([
                    DatePicker::make('period')
                        ->label('Periode')
                        ->required()
                        ->native(false)
                        ->displayFormat('F Y')
                        ->default(now()->startOfMonth())
                        ->helperText('Pilih periode bulan untuk regenerate payroll'),

                    Select::make('location')
                        ->label('Lokasi Kebun')
                        ->placeholder('Semua Lokasi')
                        ->options(function () {
                            return Location::where('is_active', true)
                                ->orderBy('name')
                                ->pluck('name', 'id')
                                ->toArray();
                        })
                        ->searchable()
                        ->preload()
                        ->helperText('Kosongkan untuk regenerate semua lokasi'),
                ])
                ->modalHeading('Regenerate Payroll')
                ->modalDescription('Sistem akan mengupdate payroll yang statusnya "Draft" dengan nilai HK terbaru dari lokasi. Payroll yang sudah "Disetujui" tidak akan diubah.')
                ->action(function (array $data) {
                    $period = Carbon::parse($data['period'])->startOfMonth();
                    $locationId = $data['location'] ?? null;

                    // Get users to regenerate payroll for
                    $query = User::where('role', 'employee');
                    
                    if ($locationId) {
                        $query->where('location_id', $locationId);
                    }

                    $users = $query->get();
                    $totalUsers = $users->count();

                    if ($totalUsers === 0) {
                        Notification::make()
                            ->title('Tidak ada karyawan')
                            ->warning()
                            ->body('Tidak ada karyawan yang ditemukan untuk regenerate payroll.')
                            ->send();
                        return;
                    }

                    $updated = 0;
                    $skipped = 0;
                    $errors = [];

                    foreach ($users as $user) {
                        try {
                            // Find existing payroll
                            $existingPayroll = Payroll::where('user_id', $user->id)
                                ->where('period', $period->toDateString())
                                ->first();

                            if (!$existingPayroll) {
                                $skipped++;
                                continue;
                            }

                            // Only update draft payrolls
                            if ($existingPayroll->status !== 'draft') {
                                $skipped++;
                                continue;
                            }

                            // Calculate standard workdays
                            $standardWorkdays = PayrollCalculator::calculateStandardWorkdays(
                                $period->copy()->startOfMonth(),
                                $period->copy()->endOfMonth()
                            );

                            // Generate payroll data
                            $payrollData = PayrollCalculator::generateMonthlyPayroll(
                                $user->id,
                                $period,
                                $standardWorkdays
                            );

                            // Update payroll
                            $existingPayroll->update([
                                'standard_workdays' => $payrollData['standard_workdays'],
                                'present_days' => $payrollData['present_days'],
                                'nilai_hk' => $payrollData['nilai_hk'],
                                'basic_salary' => $payrollData['basic_salary'],
                                'estimated_salary' => $payrollData['estimated_salary'],
                                'final_salary' => $payrollData['final_salary'],
                                'selisih_hk' => $payrollData['selisih_hk'],
                                'percentage' => $payrollData['percentage'],
                                'hk_review' => $existingPayroll->hk_review ?? $payrollData['hk_review'],
                            ]);

                            $updated++;
                        } catch (\Exception $e) {
                            $errors[] = "{$user->name}: {$e->getMessage()}";
                        }
                    }

                    // Show notification
                    $message = "Berhasil update {$updated} payroll";
                    if ($skipped > 0) {
                        $message .= ", {$skipped} dilewati (sudah disetujui/dibayar atau belum ada)";
                    }
                    if (count($errors) > 0) {
                        $errorCount = count($errors);
                        $message .= ", {$errorCount} error";
                        
                        // Get common error message
                        $commonError = "Kebanyakan error karena karyawan belum memiliki Nilai HK. Pastikan lokasi atau karyawan memiliki nilai HK yang sudah di-set.";
                        
                        Notification::make()
                            ->title('Regenerate Payroll Selesai')
                            ->warning()
                            ->body($message . "\n\n" . $commonError)
                            ->persistent()
                            ->send();
                    } else {
                        Notification::make()
                            ->title('Regenerate Payroll Selesai')
                            ->success()
                            ->body($message)
                            ->send();
                    }

                }),

            Action::make('export_excel')
                ->label('Export Excel')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('success')
                ->form([
                    DatePicker::make('start_date')
                        ->label('Tanggal Mulai')
                        ->required()
                        ->default(now()->startOfMonth())
                        ->displayFormat('d/m/Y'),
                    DatePicker::make('end_date')
                        ->label('Tanggal Selesai')
                        ->required()
                        ->default(now()->endOfMonth())
                        ->displayFormat('d/m/Y'),
                    Select::make('location_id')
                        ->label('Lokasi')
                        ->options(Location::pluck('name', 'id')->toArray())
                        ->searchable()
                        ->preload()
                        ->placeholder('Semua Lokasi (kosongkan untuk semua)'),
                ])
                ->modalHeading('Export Laporan Payroll ke Excel')
                ->modalDescription('Export laporan absensi dan payroll dalam format Excel dengan multiple sheets per lokasi.')
                ->action(function (array $data) {
                    try {
                        $startDate = Carbon::parse($data['start_date'])->startOfDay();
                        $endDate = Carbon::parse($data['end_date'])->endOfDay();
                        $locationId = $data['location_id'] ?? null;

                        $filename = 'laporan-payroll-' . $startDate->format('Y-m-d') . '-to-' . $endDate->format('Y-m-d') . '.xlsx';

                        \Log::info('Starting Excel export', [
                            'start_date' => $startDate->toDateString(),
                            'end_date' => $endDate->toDateString(),
                            'location_id' => $locationId,
                            'filename' => $filename,
                        ]);

                        Notification::make()
                            ->title('Export Excel')
                            ->success()
                            ->body('File Excel sedang dipersiapkan...')
                            ->send();

                        $export = new PayrollAttendanceExport($startDate, $endDate, $locationId);
                        
                        \Log::info('Excel export created, starting download');

                        return Excel::download($export, $filename);
                    } catch (\Exception $e) {
                        // Log full error details for debugging
                        \Log::error('Excel export failed', [
                            'error' => $e->getMessage(),
                            'trace' => $e->getTraceAsString(),
                            'file' => $e->getFile(),
                            'line' => $e->getLine(),
                        ]);

                        Notification::make()
                            ->title('Export Gagal')
                            ->danger()
                            ->body('Terjadi kesalahan: ' . $e->getMessage() . '. Silakan cek log untuk detail lebih lanjut.')
                            ->persistent()
                            ->send();
                    }
                }),

            CreateAction::make()
                ->label('Buat Payroll Manual'),
        ];
    }
}
