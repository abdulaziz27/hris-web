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
                            ->disabled()
                                    ->dehydrated(true)
                            ->helperText('Total kehadiran (dihitung otomatis dari attendance)'),

                        TextInput::make('hk_review')
                            ->label('HK Review')
                            ->numeric()
                                    ->required()
                                    ->minValue(0)
                            ->reactive()
                            ->afterStateUpdated(function ($state, $set, $get) {
                                        try {
                                            // Only recalculate final_salary and selisih_hk when hk_review changes
                                            $hkReview = $state !== null && $state !== '' ? (int) $state : null;
                                            
                                            if ($hkReview === null || $hkReview < 0) {
                                                return;
                                            }
                                            
                                            $standardWorkdays = (int) ($get('standard_workdays') ?? 21);
                                            
                                            // Get nilai_hk from form
                                            $nilaiHKValue = $get('nilai_hk');
                                            $nilaiHK = $nilaiHKValue !== null && $nilaiHKValue !== '' ? (float) $nilaiHKValue : 0;
                                            
                                            if ($nilaiHK > 0 && $hkReview >= 0) {
                                                // Calculate final salary based on hk_review
                                                $finalSalary = PayrollCalculator::calculateFinalSalary($nilaiHK, $hkReview);
                                                $set('final_salary', $finalSalary);
                                                
                                                // Calculate selisih HK
                                                $selisihHK = PayrollCalculator::calculateSelisihHK($hkReview, $standardWorkdays);
                                                $set('selisih_hk', $selisihHK);
                                            }
                                        } catch (\Exception $e) {
                                            // Silently handle errors to prevent form breaking
                                            \Log::error('Error in hk_review afterStateUpdated: ' . $e->getMessage());
                                        }
                            })
                                    ->helperText('Review manual hari kerja (default: sama dengan Hari Hadir). Mengubah ini akan mengupdate Final Gaji.'),

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
                                    ->helperText('Selisih = HK Review - Hari Kerja Standar'),
                            ]),

                        Grid::make(3)
                            ->schema([
                        TextInput::make('estimated_salary')
                            ->label('Estimasi Gaji')
                            ->numeric()
                            ->prefix('Rp')
                            ->disabled()
                                    ->dehydrated(true)
                            ->helperText('Estimasi = Nilai HK × Hari Hadir'),

                        TextInput::make('final_salary')
                            ->label('Final Gaji')
                            ->numeric()
                            ->prefix('Rp')
                            ->disabled()
                                    ->dehydrated(true)
                                    ->helperText('Final = Nilai HK × HK Review')
                                    ->extraAttributes(['class' => 'font-bold']),

                        Select::make('status')
                            ->label('Status')
                            ->options([
                                'draft' => 'Draft',
                                'approved' => 'Disetujui',
                                'paid' => 'Sudah Dibayar',
                            ])
                            ->default('draft')
                            ->required(),
                            ]),

                        Textarea::make('notes')
                            ->label('Catatan')
                            ->rows(3)
                            ->columnSpanFull()
                            ->placeholder('Catatan tambahan untuk payroll ini'),
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

            // Calculate present days (only if period is set and valid)
        $start = $period->copy()->startOfMonth();
        $end = $period->copy()->endOfMonth();
        $presentDays = PayrollCalculator::calculatePresentDays($userId, $start, $end);
        $set('present_days', $presentDays);

            // Get hk_review from form (don't override if already set)
        $hkReview = $get('hk_review');
            if ($hkReview === null || $hkReview === '' || $hkReview === 0) {
                // Only set default if not set or is 0
                if ($presentDays > 0) {
                    $hkReview = $presentDays;
            $set('hk_review', $presentDays);
                } else {
                    $hkReview = 0;
                }
        } else {
            $hkReview = (int) $hkReview;
        }

        // Calculate estimated salary
        $estimatedSalary = PayrollCalculator::calculateEstimatedSalary($nilaiHK, $presentDays);
        $set('estimated_salary', $estimatedSalary);

            // Calculate final salary (based on hk_review)
        $finalSalary = PayrollCalculator::calculateFinalSalary($nilaiHK, $hkReview);
        $set('final_salary', $finalSalary);

        // Calculate percentage
        $percentage = PayrollCalculator::calculatePercentage($presentDays, $standardWorkdays);
        $set('percentage', $percentage);

        // Calculate selisih HK
        $selisihHK = PayrollCalculator::calculateSelisihHK($hkReview, $standardWorkdays);
        $set('selisih_hk', $selisihHK);
        } catch (\Exception $e) {
            // Log error but don't break the form
            \Log::error('Error in calculatePayroll: ' . $e->getMessage());
        }
    }
}
