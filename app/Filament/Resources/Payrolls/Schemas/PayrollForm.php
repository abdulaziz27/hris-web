<?php

namespace App\Filament\Resources\Payrolls\Schemas;

use App\Services\PayrollCalculator;
use Carbon\Carbon;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class PayrollForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Section::make('Informasi Payroll')
                    ->schema([
                        Grid::make(3)
                    ->schema([
                        Select::make('user_id')
                            ->label('Karyawan')
                            ->relationship('user', 'name')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->disabled(fn ($record) => $record !== null)
                            ->reactive()
                            ->afterStateUpdated(function ($state, $set, $get) {
                                        if ($state) {
                                self::calculatePayroll($state, $get, $set);
                                        }
                            }),

                        DatePicker::make('period')
                            ->label('Periode')
                            ->required()
                            ->native(false)
                            ->displayFormat('F Y')
                            ->default(now()->startOfMonth())
                            ->disabled(fn ($record) => $record !== null)
                            ->reactive()
                            ->afterStateUpdated(function ($state, $set, $get) {
                                        $userId = $get('user_id');
                                        if ($userId && $state) {
                                            self::calculatePayroll($userId, $get, $set);
                                        }
                            }),

                        TextInput::make('standard_workdays')
                            ->label('Hari Kerja Standar')
                            ->numeric()
                            ->default(21)
                            ->required()
                                    ->minValue(1)
                            ->reactive()
                            ->afterStateUpdated(function ($state, $set, $get) {
                                        $userId = $get('user_id');
                                        if ($userId && $state && $state > 0) {
                                            self::calculatePayroll($userId, $get, $set);
                                        }
                            })
                            ->helperText('Jumlah hari kerja standar dalam bulan (default: 21 hari)'),
                            ]),

                        Grid::make(3)
                            ->schema([
                        TextInput::make('present_days')
                            ->label('Hari Hadir')
                            ->numeric()
                            ->minValue(0)
                            ->required()
                            ->reactive()
                            ->afterStateUpdated(function ($state, $set, $get) {
                                // Recalculate salary fields when present_days is manually changed
                                try {
                                    $presentDays = $state !== null && $state !== '' ? (int) $state : 0;
                                    $nilaiHKValue = $get('nilai_hk');
                                    $nilaiHK = $nilaiHKValue !== null && $nilaiHKValue !== '' ? (float) $nilaiHKValue : 0;
                                    $standardWorkdays = (int) ($get('standard_workdays') ?? 21);
                                    
                                    if ($nilaiHK > 0 && $presentDays >= 0) {
                                        // Recalculate estimated_salary and final_salary
                                        $estimatedSalary = PayrollCalculator::calculateEstimatedSalary($nilaiHK, $presentDays);
                                        $set('estimated_salary', $estimatedSalary);
                                        $set('final_salary', $estimatedSalary); // final_salary = estimated_salary
                                        
                                        // Recalculate percentage
                                        if ($standardWorkdays > 0) {
                                            $percentage = PayrollCalculator::calculatePercentage($presentDays, $standardWorkdays);
                                            $set('percentage', $percentage);
                                        }
                                        
                                        // Recalculate selisih_hk
                                        $selisihHK = $presentDays - $standardWorkdays;
                                        $set('selisih_hk', $selisihHK);
                                    }
                                } catch (\Exception $e) {
                                    \Log::error('Error in present_days afterStateUpdated: ' . $e->getMessage());
                                }
                            })
                            ->helperText('Total kehadiran (dihitung otomatis dari attendance). Bisa di-edit manual jika ada kesalahan sistem atau koreksi.'),

                        // hk_review tidak digunakan untuk perhitungan, hanya untuk jaga-jaga di masa depan
                        // Final salary langsung dari present_days (estimated_salary)
                        // Jika perlu adjustment, edit langsung di Excel (adjust present_days atau final_salary)
                        TextInput::make('hk_review')
                            ->label('HK Review')
                            ->numeric()
                            ->minValue(0)
                            ->default(fn ($get) => $get('present_days') ?? 0)
                            ->hidden() // Hidden karena tidak digunakan
                            ->dehydrated() // Tetap simpan ke database untuk jaga-jaga
                            ->disabled(), // Disabled karena tidak digunakan

                                TextInput::make('percentage')
                                    ->label('Persentase')
                            ->numeric()
                                    ->suffix('%')
                            ->disabled()
                                    ->dehydrated(true)
                                    ->helperText('Persentase = (Hari Hadir / Hari Kerja Standar) × 100%'),
                            ]),

                        Grid::make(3)
                            ->schema([
                        TextInput::make('nilai_hk')
                            ->label('Nilai HK')
                            ->numeric()
                            ->prefix('Rp')
                            ->disabled()
                                    ->dehydrated(true)
                                    ->helperText('Nilai HK dari master data (lokasi atau user). Gaji dihitung per hari kerja.'),

                                TextInput::make('basic_salary')
                                    ->label('Gaji Pokok (Informasi)')
                                    ->numeric()
                                    ->prefix('Rp')
                                    ->disabled()
                                    ->dehydrated(true)
                                    ->helperText('Gaji pokok = Nilai HK × Hari Kerja Standar (untuk informasi saja)'),

                                TextInput::make('selisih_hk')
                                    ->label('Selisih HK')
                                    ->numeric()
                                    ->disabled()
                                    ->dehydrated(true)
                                    ->helperText('Selisih = Hari Hadir - Hari Kerja Standar (langsung dari kehadiran)'),
                            ]),

                        Grid::make(3)
                            ->schema([
                        TextInput::make('estimated_salary')
                            ->label('Gaji')
                            ->numeric()
                            ->prefix('Rp')
                            ->disabled()
                                    ->dehydrated(true)
                            ->helperText('Gaji = Nilai HK × Hari Hadir. Jika perlu adjustment, edit langsung di Excel (adjust present_days atau gaji)')
                            ->extraAttributes(['class' => 'font-bold']),

                        // final_salary di-hide karena sama dengan estimated_salary
                        // Tetap ada di database untuk jaga-jaga di masa depan
                        TextInput::make('final_salary')
                            ->label('Final Gaji')
                            ->numeric()
                            ->prefix('Rp')
                            ->hidden()
                            ->dehydrated(true)
                            ->disabled(),

                        Select::make('status')
                            ->label('Status')
                            ->options([
                                'draft' => 'Draft',
                                'approved' => 'Disetujui',
                            ])
                            ->default('draft')
                            ->required(),
                            ]),

                        Textarea::make('notes')
                            ->label('Catatan')
                            ->rows(3)
                            ->columnSpanFull()
                            ->placeholder('Catatan tambahan untuk payroll ini. Contoh: "Koreksi hari hadir dari 10 menjadi 11 karena kesalahan sistem absensi tanggal X"'),
                    ]),
            ]);
    }

    protected static function calculatePayroll(?int $userId, $get, $set): void
    {
        if (! $userId) {
            return;
        }

        try {
        $period = $get('period');
        if (! $period) {
            $period = now()->startOfMonth();
        } else {
                try {
                    // Handle different period formats (string, Carbon instance, DateTime, etc.)
                    if ($period instanceof \Carbon\Carbon) {
                        $period = $period->copy()->startOfMonth();
                    } elseif (is_string($period)) {
            $period = Carbon::parse($period)->startOfMonth();
                    } elseif ($period instanceof \DateTime) {
                        $period = Carbon::instance($period)->startOfMonth();
                    } else {
                        $period = now()->startOfMonth();
                    }
                } catch (\Exception $e) {
                    // If period is invalid, use current month
                    $period = now()->startOfMonth();
                }
        }

        $standardWorkdays = (int) ($get('standard_workdays') ?? 21);

            if ($standardWorkdays <= 0) {
                return; // Avoid division by zero
            }

            // Get user with location
        $user = \App\Models\User::with('location')->find($userId);
            if (! $user) {
                return;
            }

            // Get Nilai HK from master data (prioritas: user.nilai_hk OR location.nilai_hk)
            $nilaiHK = PayrollCalculator::getNilaiHK($userId, $user->location_id ?? null);
            
            if ($nilaiHK <= 0) {
                // If no nilai_hk found, show warning but don't error
                $set('nilai_hk', 0);
                $set('basic_salary', 0);
                $set('estimated_salary', 0);
                $set('final_salary', 0);
                return;
            }
            
            $set('nilai_hk', $nilaiHK);

            // Calculate basic salary from nilai_hk × standard_workdays (untuk informasi)
            $basicSalary = PayrollCalculator::calculateBasicSalary($nilaiHK, $standardWorkdays);
        $set('basic_salary', $basicSalary);

            // Calculate present days (only if period is set and valid, with location timezone awareness)
            // Hanya set jika belum ada nilai (untuk tidak override edit manual)
            $currentPresentDays = $get('present_days');
            if ($currentPresentDays === null || $currentPresentDays === '' || $currentPresentDays === 0) {
                $start = $period->copy()->startOfMonth();
                $end = $period->copy()->endOfMonth();
                $user = \App\Models\User::find($userId);
                $presentDays = PayrollCalculator::calculatePresentDays($userId, $start, $end, $user->location_id ?? null);
                $set('present_days', $presentDays);
            } else {
                // Gunakan nilai yang sudah ada (bisa dari edit manual)
                $presentDays = (int) $currentPresentDays;
            }

            // hk_review tidak digunakan untuk perhitungan, hanya untuk jaga-jaga di masa depan
            // Tetap di-set default = present_days untuk konsistensi data

        // Calculate estimated salary = nilai_hk × present_days
        $estimatedSalary = PayrollCalculator::calculateEstimatedSalary($nilaiHK, $presentDays);
        $set('estimated_salary', $estimatedSalary);

        // Calculate final salary = estimated_salary (langsung dari present_days)
        // hk_review tidak digunakan, hanya untuk jaga-jaga di masa depan
        $finalSalary = $estimatedSalary; // Langsung dari present_days
        $set('final_salary', $finalSalary);

        // Calculate percentage
        $percentage = PayrollCalculator::calculatePercentage($presentDays, $standardWorkdays);
        $set('percentage', $percentage);

        // Calculate selisih HK = present_days - standard_workdays
        // hk_review tidak digunakan, langsung dari present_days
        $selisihHK = $presentDays - $standardWorkdays;
        $set('selisih_hk', $selisihHK);
        
        // hk_review tetap di-set untuk jaga-jaga di masa depan (tidak digunakan untuk perhitungan)
        // Set default = present_days untuk konsistensi data
        if ($presentDays > 0) {
            $set('hk_review', $presentDays);
        } else {
            $set('hk_review', 0);
        }
        } catch (\Exception $e) {
            // Log error but don't break the form
            \Log::error('Error in calculatePayroll: ' . $e->getMessage());
        }
    }
}
