<?php

namespace App\Filament\Resources\Attendances\Tables;

use App\Exports\LaporanAbsensiExport;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Facades\Excel;

class AttendancesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(function (Builder $query) {
                return $query->with(['user', 'shift', 'location']);
            })
            ->columns([
                TextColumn::make('user.name')
                    ->label('Karyawan')
                    ->searchable()
                    ->sortable()
                    ->weight('medium'),
                TextColumn::make('date')
                    ->label('Tanggal')
                    ->date('d M Y')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('time_in')
                    ->label('Masuk')
                    ->time('H:i')
                    ->sortable()
                    ->icon('heroicon-o-arrow-right-on-rectangle')
                    ->color('success'),
                TextColumn::make('time_out')
                    ->label('Keluar')
                    ->time('H:i')
                    ->sortable()
                    ->placeholder('-')
                    ->icon('heroicon-o-arrow-left-on-rectangle')
                    ->color('danger'),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'on_time' => 'Tepat Waktu',
                        'late' => 'Terlambat',
                        'absent' => 'Tidak Hadir',
                        default => ucfirst($state),
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'on_time' => 'success',
                        'late' => 'warning',
                        'absent' => 'danger',
                        default => 'gray',
                    })
                    ->sortable(),
                TextColumn::make('total_hours')
                    ->label('Total Jam')
                    ->getStateUsing(function ($record) {
                        if (! $record->time_out) {
                            return '-';
                        }
                        $checkIn = \Carbon\Carbon::parse($record->time_in);
                        $checkOut = \Carbon\Carbon::parse($record->time_out);
                        $duration = $checkIn->diff($checkOut);

                        return sprintf('%d:%02d jam', $duration->h, $duration->i);
                    })
                    ->badge()
                    ->color('info')
                    ->icon('heroicon-o-clock'),
                TextColumn::make('shift.name')
                    ->label('Shift')
                    ->badge()
                    ->color('primary')
                    ->placeholder('Tidak Ada Shift')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('location.name')
                    ->label('Lokasi')
                    ->placeholder('Tidak Ada Lokasi')
                    ->badge()
                    ->color('success')
                    ->sortable()
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('latlon_in')
                    ->label('Lokasi Masuk')
                    ->limit(20)
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('latlon_out')
                    ->label('Lokasi Keluar')
                    ->limit(20)
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Filter::make('date_range')
                    ->label('Rentang Tanggal')
                    ->form([
                        \Filament\Forms\Components\Select::make('preset')
                            ->label('Preset Periode')
                            ->options([
                                'today' => 'Hari Ini',
                                'this_month' => 'Bulan Ini',
                                'last_month' => 'Bulan Lalu',
                                'custom' => 'Custom (Pilih Manual)',
                            ])
                            ->default('today')
                            ->reactive()
                            ->afterStateUpdated(function ($state, callable $set) {
                                $today = now();
                                [$dateFrom, $dateTo] = match ($state) {
                                    'today' => [$today->toDateString(), $today->toDateString()],
                                    'this_month' => [$today->startOfMonth()->toDateString(), $today->copy()->endOfMonth()->toDateString()],
                                    'last_month' => [$today->copy()->subMonth()->startOfMonth()->toDateString(), $today->copy()->subMonth()->endOfMonth()->toDateString()],
                                    default => [null, null],
                                };
                                
                                if ($dateFrom && $dateTo) {
                                    $set('date_from', $dateFrom);
                                    $set('date_to', $dateTo);
                                }
                            }),
                        \Filament\Forms\Components\DatePicker::make('date_from')
                            ->label('Dari Tanggal')
                            ->default(fn () => now()->toDateString())
                            ->visible(fn ($get) => $get('preset') === 'custom' || $get('preset') === null)
                            ->reactive(),
                        \Filament\Forms\Components\DatePicker::make('date_to')
                            ->label('Sampai Tanggal')
                            ->default(fn () => now()->toDateString())
                            ->visible(fn ($get) => $get('preset') === 'custom' || $get('preset') === null)
                            ->reactive(),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        $preset = $data['preset'] ?? 'today';
                        $today = now();
                        $dateFrom = null;
                        $dateTo = null;

                        // Jika preset dipilih, gunakan preset
                        if ($preset !== 'custom' && $preset !== null) {
                            [$dateFrom, $dateTo] = match ($preset) {
                                'today' => [$today->toDateString(), $today->toDateString()],
                                'this_month' => [$today->startOfMonth()->toDateString(), $today->copy()->endOfMonth()->toDateString()],
                                'last_month' => [$today->copy()->subMonth()->startOfMonth()->toDateString(), $today->copy()->subMonth()->endOfMonth()->toDateString()],
                                default => [$data['date_from'] ?? $today->toDateString(), $data['date_to'] ?? $today->toDateString()],
                            };
                        } else {
                            // Jika custom, gunakan date_from dan date_to dari form
                            $dateFrom = $data['date_from'] ?? $today->toDateString();
                            $dateTo = $data['date_to'] ?? $today->toDateString();
                        }

                        return $query
                            ->when(
                                $dateFrom,
                                fn (Builder $query, $date): Builder => $query->whereDate('date', '>=', $date),
                            )
                            ->when(
                                $dateTo,
                                fn (Builder $query, $date): Builder => $query->whereDate('date', '<=', $date),
                            );
                    })
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];
                        $preset = $data['preset'] ?? 'today';
                        $today = now();

                        // Tentukan label berdasarkan preset
                        if ($preset !== 'custom' && $preset !== null) {
                            $label = match ($preset) {
                                'today' => 'Hari Ini',
                                'this_month' => 'Bulan Ini',
                                'last_month' => 'Bulan Lalu',
                                default => null,
                            };

                            if ($label) {
                                $indicators[] = $label;
                            }
                        } else {
                        if ($data['date_from'] ?? null) {
                            $indicators[] = 'Dari: '.\Carbon\Carbon::parse($data['date_from'])->format('d M Y');
                        }
                        if ($data['date_to'] ?? null) {
                            $indicators[] = 'Sampai: '.\Carbon\Carbon::parse($data['date_to'])->format('d M Y');
                            }
                        }

                        return $indicators;
                    }),
                SelectFilter::make('user_id')
                    ->label('Karyawan')
                    ->relationship('user', 'name')
                    ->searchable()
                    ->preload(),
                SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'on_time' => 'Tepat Waktu',
                        'late' => 'Terlambat',
                        'absent' => 'Tidak Hadir',
                    ])
                    ->multiple(),
                SelectFilter::make('shift_id')
                    ->label('Shift')
                    ->relationship('shift', 'name')
                    ->searchable()
                    ->preload(),
                SelectFilter::make('location_id')
                    ->label('Lokasi')
                    ->relationship('location', 'name')
                    ->searchable()
                    ->preload(),
            ])
            ->recordActions([
                ViewAction::make()
                    ->label('Lihat'),
                Action::make('download_monthly_report')
                    ->label('Download Report Bulanan')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('success')
                    ->action(function ($record) {
                        // Ambil tanggal dari record untuk menentukan bulan
                        $date = \Carbon\Carbon::parse($record->date);
                        $startOfMonth = $date->copy()->startOfMonth();
                        $endOfMonth = $date->copy()->endOfMonth();
                        
                        // Query semua attendance untuk user ini di bulan tersebut
                        $attendances = \App\Models\Attendance::query()
                            ->where('user_id', $record->user_id)
                            ->whereBetween('date', [$startOfMonth->toDateString(), $endOfMonth->toDateString()])
                            ->with(['user', 'location'])
                            ->orderBy('date', 'asc')
                            ->get();
                        
                        if ($attendances->isEmpty()) {
                            \Filament\Notifications\Notification::make()
                                ->title('Tidak ada data')
                                ->body('Tidak ada data absensi untuk karyawan ini di bulan ' . $date->format('F Y'))
                                ->warning()
                                ->send();
                            return;
                        }
                        
                        // Export ke Excel
                        $export = new LaporanAbsensiExport($attendances);
                        $userName = str_replace(' ', '-', $record->user->name);
                        $monthName = strtolower($date->format('F-Y'));
                        $filename = "laporan-absensi-{$userName}-{$monthName}.xlsx";
                        
                        return Excel::download($export, $filename);
                    })
                    ->requiresConfirmation()
                    ->modalHeading('Download Report Bulanan')
                    ->modalDescription(fn ($record) => 
                        'Download laporan absensi bulanan untuk ' . 
                        $record->user->name . ' - ' . 
                        \Carbon\Carbon::parse($record->date)->format('F Y')
                    )
                    ->modalSubmitActionLabel('Download'),
            ])
            ->toolbarActions([
                Action::make('export_csv')
                    ->label('Ekspor CSV')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('success')
                    ->hidden()
                    ->action(function ($livewire) {
                        $query = $livewire->getFilteredSortedTableQuery();

                        if (! $query) {
                            return null;
                        }

                        $attendances = (clone $query)
                            ->reorder()
                            ->orderByDesc('date')
                            ->orderByDesc('time_in')
                            ->with(['user', 'shift'])
                            ->get();

                        $csv = "Karyawan,Tanggal,Masuk,Keluar,Status,Total Jam,Shift\n";
                        foreach ($attendances as $attendance) {
                            $totalHours = '-';
                            if ($attendance->time_out) {
                                $checkIn = \Carbon\Carbon::parse($attendance->time_in);
                                $checkOut = \Carbon\Carbon::parse($attendance->time_out);
                                $duration = $checkIn->diff($checkOut);
                                $totalHours = sprintf('%d:%02d', $duration->h, $duration->i);
                            }

                            $csv .= sprintf(
                                '"%s","%s","%s","%s","%s","%s","%s"'."\n",
                                $attendance->user->name,
                                $attendance->date ? \Carbon\Carbon::parse($attendance->date)->format('d M Y') : '-',
                                $attendance->time_in ? \Carbon\Carbon::parse($attendance->time_in)->format('H:i') : '-',
                                $attendance->time_out ? \Carbon\Carbon::parse($attendance->time_out)->format('H:i') : '-',
                                match ($attendance->status) {
                                    'on_time' => 'Tepat Waktu',
                                    'late' => 'Terlambat',
                                    'absent' => 'Tidak Hadir',
                                    default => ucfirst(str_replace('_', ' ', $attendance->status)),
                                },
                                $totalHours,
                                $attendance->shift->name ?? 'Tidak Ada Shift'
                            );
                        }

                        return response()->streamDownload(function () use ($csv) {
                            echo $csv;
                        }, 'attendances-'.now()->format('Y-m-d').'.csv');
                    }),
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->label('Hapus'),
                ]),
            ])
            ->defaultSort('date', 'desc');
    }
}
