<?php

namespace App\Filament\Resources\Holidays\Tables;

use App\Models\Holiday;
use App\Support\WorkdayCalculator;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class HolidaysTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('date')
                    ->label('Tanggal')
                    ->date('d/m/Y')
                    ->sortable()
                    ->badge()
                    ->color('primary'),

                TextColumn::make('name')
                    ->label('Nama')
                    ->searchable()
                    ->sortable(),

                BadgeColumn::make('type')
                    ->label('Tipe')
                    ->colors([
                        'info' => Holiday::TYPE_NATIONAL,
                        'warning' => Holiday::TYPE_COMPANY,
                        'gray' => Holiday::TYPE_WEEKEND,
                    ])
                    ->formatStateUsing(fn ($state) => match ($state) {
                        Holiday::TYPE_NATIONAL => 'Nasional',
                        Holiday::TYPE_COMPANY => 'Perusahaan',
                        Holiday::TYPE_WEEKEND => 'Weekend',
                        default => ucfirst($state),
                    })
                    ->sortable(),

                IconColumn::make('is_official')
                    ->label('Resmi')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('gray'),

                TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('year')
                    ->label('Tahun')
                    ->options(function () {
                        $years = DB::table('holidays')
                            ->selectRaw('YEAR(date) as year')
                            ->distinct()
                            ->orderByDesc('year')
                            ->pluck('year', 'year');

                        if ($years->isEmpty()) {
                            $currentYear = now()->year;

                            return [
                                $currentYear - 1 => $currentYear - 1,
                                $currentYear => $currentYear,
                                $currentYear + 1 => $currentYear + 1,
                            ];
                        }

                        return $years;
                    })
                    ->query(function (Builder $query, $state) {
                        if ($state['value'] ?? null) {
                            return $query->whereYear('date', $state['value']);
                        }
                    }),

                SelectFilter::make('type')
                    ->label('Tipe')
                    ->options([
                        Holiday::TYPE_NATIONAL => 'Nasional',
                        Holiday::TYPE_COMPANY => 'Perusahaan',
                        Holiday::TYPE_WEEKEND => 'Weekend',
                    ]),

                SelectFilter::make('official_only')
                    ->label('Hanya Resmi')
                    ->options([
                        '1' => 'Hanya Hari Libur Resmi',
                    ])
                    ->query(fn (Builder $query, $state) => $state['value'] === '1' ? $query->where('is_official', true) : $query),
            ])
            ->recordActions([
                EditAction::make()
                    ->label('Ubah'),
            ])
            ->headerActions([
                Action::make('generate_weekends')
                    ->label('Generate Weekend')
                    ->icon('heroicon-o-calendar-days')
                    ->color('success')
                    ->form([
                        TextInput::make('year')
                            ->label('Tahun')
                            ->required()
                            ->numeric()
                            ->minValue(2020)
                            ->maxValue(2100)
                            ->default(now()->year),
                    ])
                    ->modalHeading('Generate Hari Libur Weekend')
                    ->modalDescription('Ini akan menghasilkan semua hari Sabtu dan Minggu untuk tahun yang dipilih sebagai hari libur weekend.')
                    ->action(function (array $data) {
                        $result = WorkdayCalculator::generateWeekendForYear($data['year']);

                        \Filament\Notifications\Notification::make()
                            ->title('Weekend berhasil di-generate')
                            ->success()
                            ->body("Ditambahkan: {$result['inserted']}, Dilewati: {$result['skipped']}")
                            ->send();
                    }),

                Action::make('quick_add_national')
                    ->label('Tambah Cepat Hari Libur Nasional')
                    ->icon('heroicon-o-plus-circle')
                    ->color('primary')
                    ->form([
                        DatePicker::make('date')
                            ->label('Tanggal')
                            ->required()
                            ->native(false)
                            ->unique(table: 'holidays', column: 'date'),

                        TextInput::make('name')
                            ->label('Nama Hari Libur')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('Contoh: Hari Kemerdekaan'),
                    ])
                    ->modalHeading('Tambah Hari Libur Nasional')
                    ->action(function (array $data) {
                        Holiday::create([
                            'date' => $data['date'],
                            'name' => $data['name'],
                            'type' => Holiday::TYPE_NATIONAL,
                            'is_official' => true,
                        ]);

                        \Filament\Notifications\Notification::make()
                            ->title('Hari libur nasional berhasil ditambahkan')
                            ->success()
                            ->send();
                    }),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->label('Hapus')
                        ->visible(fn () => auth()->user()->role === 'admin' || auth()->user()->role === 'hr'),

                    Action::make('set_official')
                        ->label('Tandai sebagai Resmi')
                        ->icon('heroicon-o-check-badge')
                        ->color('success')
                        ->requiresConfirmation()
                        ->action(function ($records) {
                            $records->each->update(['is_official' => true]);

                            \Filament\Notifications\Notification::make()
                                ->title('Hari libur ditandai sebagai resmi')
                                ->success()
                                ->send();
                        })
                        ->visible(fn () => auth()->user()->role === 'admin' || auth()->user()->role === 'hr'),

                    Action::make('unset_official')
                        ->label('Hapus Tanda Resmi')
                        ->icon('heroicon-o-x-circle')
                        ->color('warning')
                        ->requiresConfirmation()
                        ->action(function ($records) {
                            $records->each->update(['is_official' => false]);

                            \Filament\Notifications\Notification::make()
                                ->title('Tanda resmi hari libur dihapus')
                                ->success()
                                ->send();
                        })
                        ->visible(fn () => auth()->user()->role === 'admin' || auth()->user()->role === 'hr'),
                ]),
            ])
            ->defaultSort('date', 'desc')
            ->striped()
            ->paginated([10, 25, 50]);
    }
}
