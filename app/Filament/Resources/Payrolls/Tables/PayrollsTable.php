<?php

namespace App\Filament\Resources\Payrolls\Tables;

use App\Models\Location;
use App\Models\Payroll;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Carbon\Carbon;

class PayrollsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(function (Builder $query) {
                // Eager load relationships to prevent N+1 queries
                return $query->with(['user.location', 'user.jabatan', 'user.departemen']);
            })
            ->defaultPaginationPageOption(25) // Limit records per page for better performance
            ->columns([
                TextColumn::make('user.name')
                    ->label('Karyawan')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('user.location.name')
                    ->label('Lokasi')
                    ->badge()
                    ->color('info')
                    ->searchable()
                    ->sortable()
                    ->placeholder('Tidak Ada Lokasi')
                    ->toggleable(),

                TextColumn::make('period')
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
                    })
                    ->sortable(),

                TextColumn::make('standard_workdays')
                    ->label('Hari Kerja')
                    ->alignCenter()
                    ->sortable(),

                TextColumn::make('present_days')
                    ->label('Hadir')
                    ->alignCenter()
                    ->sortable(),

                // hk_review di-hide karena default = present_days, bisa di-edit manual di form jika diperlukan
                TextColumn::make('hk_review')
                    ->label('HK Review')
                    ->alignCenter()
                    ->sortable()
                    ->placeholder('-')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('percentage')
                    ->label('Persentase')
                    ->formatStateUsing(fn ($state) => number_format($state, 2) . '%')
                    ->alignCenter()
                    ->sortable()
                    ->color(fn ($state) => $state >= 100 ? 'success' : ($state >= 80 ? 'warning' : 'danger')),

                TextColumn::make('nilai_hk')
                    ->label('Nilai HK')
                    ->formatStateUsing(function ($state) {
                        return 'Rp ' . number_format($state, 0, ',', '.');
                    })
                    ->sortable(),

                TextColumn::make('estimated_salary')
                    ->label('Gaji')
                    ->formatStateUsing(function ($state) {
                        return 'Rp ' . number_format($state, 0, ',', '.');
                    })
                    ->sortable()
                    ->weight('bold'),

                // final_salary di-hide karena sama dengan estimated_salary
                // Tetap ada di database untuk jaga-jaga di masa depan
                TextColumn::make('final_salary')
                    ->label('Final Gaji')
                    ->formatStateUsing(function ($state) {
                        return 'Rp ' . number_format($state, 0, ',', '.');
                    })
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('selisih_hk')
                    ->label('Selisih HK')
                    ->formatStateUsing(fn ($state) => $state > 0 ? "+{$state}" : (string) $state)
                    ->color(fn ($state) => $state > 0 ? 'success' : ($state < 0 ? 'danger' : 'gray'))
                    ->alignCenter()
                    ->sortable(),

                TextColumn::make('status')
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
                    })
                    ->sortable(),

                TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Filter::make('period_range')
                    ->label('Rentang Periode')
                    ->form([
                        \Filament\Forms\Components\DatePicker::make('period_from')
                            ->label('Dari Periode')
                            ->placeholder('Pilih bulan mulai')
                            ->displayFormat('F Y')
                            ->native(false)
                            ->default(now()->startOfMonth()),
                        \Filament\Forms\Components\DatePicker::make('period_to')
                            ->label('Sampai Periode')
                            ->placeholder('Pilih bulan akhir')
                            ->displayFormat('F Y')
                            ->native(false)
                            ->default(now()->startOfMonth()),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['period_from'] ?? null,
                                function (Builder $query, $date): Builder {
                                    $start = Carbon::parse($date)->startOfMonth();
                                    return $query->whereDate('period', '>=', $start);
                                }
                            )
                            ->when(
                                $data['period_to'] ?? null,
                                function (Builder $query, $date): Builder {
                                    $end = Carbon::parse($date)->endOfMonth();
                                    return $query->whereDate('period', '<=', $end);
                                }
                            );
                    })
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];
                        if ($data['period_from'] ?? null) {
                            $date = Carbon::parse($data['period_from']);
                            $months = [
                                1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
                                5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
                                9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
                            ];
                            $indicators['period_from'] = 'Dari: ' . $months[$date->month] . ' ' . $date->year;
                        }
                        if ($data['period_to'] ?? null) {
                            $date = Carbon::parse($data['period_to']);
                            $months = [
                                1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
                                5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
                                9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
                            ];
                            $indicators['period_to'] = 'Sampai: ' . $months[$date->month] . ' ' . $date->year;
                        }
                        return $indicators;
                    }),

                SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'draft' => 'Draft',
                        'approved' => 'Disetujui',
                    ]),

                Filter::make('this_month')
                    ->label('Bulan Ini')
                    ->query(fn (Builder $query): Builder => $query
                        ->whereYear('period', now()->year)
                        ->whereMonth('period', now()->month))
                    ->toggle()
                    ->default(),

                Filter::make('last_month')
                    ->label('Bulan Lalu')
                    ->query(fn (Builder $query): Builder => $query
                        ->whereYear('period', now()->subMonth()->year)
                        ->whereMonth('period', now()->subMonth()->month))
                    ->toggle(),

                SelectFilter::make('location')
                    ->label('Lokasi')
                    ->options(function () {
                        return Location::where('is_active', true)
                            ->orderBy('name')
                            ->pluck('name', 'id')
                            ->toArray();
                    })
                    ->query(function (Builder $query, array $data): Builder {
                        if (!empty($data['values'])) {
                            return $query->whereHas('user', function ($q) use ($data) {
                                $q->whereIn('location_id', $data['values']);
                            });
                        }
                        return $query;
                    })
                    ->searchable()
                    ->preload()
                    ->multiple()
                    ->placeholder('Pilih Lokasi'),
            ])
            ->recordActions([
                Action::make('approve')
                    ->label('Setujui')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn (Payroll $record) => $record->status === 'draft')
                    ->requiresConfirmation()
                    ->modalHeading('Setujui Payroll')
                    ->modalDescription(fn (Payroll $record) => 'Apakah Anda yakin ingin menyetujui payroll untuk ' . $record->user->name . '?')
                    ->modalSubmitActionLabel('Ya, Setujui')
                    ->mountUsing(function (Payroll $record) {
                        // Mount with record only, prevent records dependency resolution
                    })
                    ->action(function (Payroll $record) {
                        try {
                            $record->update([
                                'status' => 'approved',
                                'approved_by' => auth()->id(),
                                'approved_at' => now(),
                            ]);

                            \Filament\Notifications\Notification::make()
                                ->title('Payroll Disetujui')
                                ->success()
                                ->body('Payroll berhasil disetujui.')
                                ->send();
                        } catch (\Exception $e) {
                            \Filament\Notifications\Notification::make()
                                ->title('Gagal menyetujui payroll')
                                ->danger()
                                ->body($e->getMessage())
                                ->send();
                        }
                    }),
                EditAction::make()
                    ->label('Ubah'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->label('Hapus'),
                ]),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    Action::make('bulk_approve')
                        ->label('Setujui Terpilih')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->requiresConfirmation()
                        ->action(function ($records) {
                            $count = 0;
                            $records->each(function ($record) use (&$count) {
                                if ($record->status === 'draft') {
                                    $record->update([
                                        'status' => 'approved',
                                        'approved_by' => auth()->id(),
                                        'approved_at' => now(),
                                    ]);
                                    $count++;
                                }
                            });
                            
                            \Filament\Notifications\Notification::make()
                                ->title('Payroll Disetujui')
                                ->success()
                                ->body("{$count} payroll berhasil disetujui.")
                                ->send();
                        }),

                    DeleteBulkAction::make()
                        ->label('Hapus Terpilih'),
                ]),
            ])
            ->defaultSort('period', 'desc');
    }
}
