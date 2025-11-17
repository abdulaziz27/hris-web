<?php

namespace App\Filament\Resources\Payrolls\Schemas;

use App\Services\PayrollCalculator;
use Carbon\Carbon;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class PayrollInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Informasi Payroll')
                    ->schema([
                        TextEntry::make('user.name')
                            ->label('Karyawan'),

                        TextEntry::make('period')
                            ->label('Periode')
                            ->formatStateUsing(function ($state) {
                                if (! $state) {
                                    return '-';
                                }
                                $date = \Carbon\Carbon::parse($state);
                                $months = [
                                    1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
                                    5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
                                    9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
                                ];
                                return $months[$date->month] . ' ' . $date->year;
                            }),

                        TextEntry::make('standard_workdays')
                            ->label('Hari Kerja Standar'),

                        TextEntry::make('present_days')
                            ->label('Hari Hadir')
                            ->helperText('Total = Hadir + Cuti + Sakit'),

                        TextEntry::make('attendance_days_breakdown')
                            ->label('Hadir')
                            ->state(function ($record) {
                                if (!$record || !$record->user_id || !$record->period) {
                                    return '-';
                                }
                                
                                try {
                                    $period = Carbon::parse($record->period);
                                    $start = $period->copy()->startOfMonth();
                                    $end = $period->copy()->endOfMonth();
                                    
                                    $user = $record->user;
                                    $attendanceDays = PayrollCalculator::calculatePresentDays(
                                        $record->user_id,
                                        $start,
                                        $end,
                                        $user->location_id ?? null
                                    );
                                    
                                    return $attendanceDays . ' hari';
                                } catch (\Exception $e) {
                                    return '-';
                                }
                            }),

                        TextEntry::make('cuti_days_breakdown')
                            ->label('Cuti')
                            ->state(function ($record) {
                                if (!$record || !$record->user_id || !$record->period) {
                                    return '-';
                                }
                                
                                try {
                                    $period = Carbon::parse($record->period);
                                    $start = $period->copy()->startOfMonth();
                                    $end = $period->copy()->endOfMonth();
                                    
                                    $cutiDays = PayrollCalculator::calculateCutiDays(
                                        $record->user_id,
                                        $start,
                                        $end
                                    );
                                    
                                    return $cutiDays . ' hari';
                                } catch (\Exception $e) {
                                    return '-';
                                }
                            }),

                        TextEntry::make('sakit_days_breakdown')
                            ->label('Sakit')
                            ->state(function ($record) {
                                if (!$record || !$record->user_id || !$record->period) {
                                    return '-';
                                }
                                
                                try {
                                    $period = Carbon::parse($record->period);
                                    $start = $period->copy()->startOfMonth();
                                    $end = $period->copy()->endOfMonth();
                                    
                                    $sakitDays = PayrollCalculator::calculateSakitDays(
                                        $record->user_id,
                                        $start,
                                        $end
                                    );
                                    
                                    return $sakitDays . ' hari';
                                } catch (\Exception $e) {
                                    return '-';
                                }
                            }),

                        // hk_review di-hide karena default = present_days
                        TextEntry::make('hk_review')
                            ->label('HK Review')
                            ->hidden(),

                        TextEntry::make('nilai_hk')
                            ->label('Nilai HK')
                            ->formatStateUsing(function ($state) {
                                return 'Rp ' . number_format($state, 0, ',', '.');
                            }),

                        TextEntry::make('basic_salary')
                            ->label('Gaji Pokok')
                            ->formatStateUsing(function ($state) {
                                return 'Rp ' . number_format($state, 0, ',', '.');
                            }),

                        TextEntry::make('estimated_salary')
                            ->label('Gaji')
                            ->formatStateUsing(function ($state) {
                                return 'Rp ' . number_format($state, 0, ',', '.');
                            })
                            ->extraAttributes(['class' => 'font-bold']),

                        // final_salary di-hide karena sama dengan estimated_salary
                        // Tetap ada di database untuk jaga-jaga di masa depan
                        TextEntry::make('final_salary')
                            ->label('Final Gaji')
                            ->formatStateUsing(function ($state) {
                                return 'Rp ' . number_format($state, 0, ',', '.');
                            })
                            ->hidden(),

                        TextEntry::make('percentage')
                            ->label('Persentase')
                            ->formatStateUsing(fn ($state) => number_format($state, 2) . '%'),

                        TextEntry::make('selisih_hk')
                            ->label('Selisih HK')
                            ->formatStateUsing(fn ($state) => $state > 0 ? "+{$state}" : (string) $state),

                        TextEntry::make('status')
                            ->label('Status')
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                'draft' => 'gray',
                                'approved' => 'success',
                                default => 'gray',
                            })
                            ->formatStateUsing(fn (string $state): string => match ($state) {
                                'draft' => 'Draft',
                                'approved' => 'Disetujui',
                                default => $state,
                            }),

                        TextEntry::make('notes')
                            ->label('Catatan')
                            ->columnSpanFull()
                            ->placeholder('Tidak ada catatan'),
                    ])
                    ->columns(2),
            ]);
    }
}
