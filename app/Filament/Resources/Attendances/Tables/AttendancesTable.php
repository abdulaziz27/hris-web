<?php

namespace App\Filament\Resources\Attendances\Tables;

use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

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
                        \Filament\Forms\Components\DatePicker::make('date_from')
                            ->label('Dari Tanggal')
                            ->default(now()->subMonth()),
                        \Filament\Forms\Components\DatePicker::make('date_to')
                            ->label('Sampai Tanggal')
                            ->default(now()),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['date_from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('date', '>=', $date),
                            )
                            ->when(
                                $data['date_to'],
                                fn (Builder $query, $date): Builder => $query->whereDate('date', '<=', $date),
                            );
                    })
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];
                        if ($data['date_from'] ?? null) {
                            $indicators[] = 'Dari: '.\Carbon\Carbon::parse($data['date_from'])->format('d M Y');
                        }
                        if ($data['date_to'] ?? null) {
                            $indicators[] = 'Sampai: '.\Carbon\Carbon::parse($data['date_to'])->format('d M Y');
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
