<?php

namespace App\Filament\Resources\Weekends\Tables;

use App\Support\WorkdayCalculator;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class WeekendsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('date')
                    ->label('Tanggal')
                    ->date('d/m/Y (D)')
                    ->sortable()
                    ->badge()
                    ->color('gray'),

                TextColumn::make('name')
                    ->label('Nama')
                    ->default('Weekend'),

                TextColumn::make('created_at')
                    ->label('Di-generate Pada')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('year')
                    ->label('Tahun')
                    ->options(function () {
                        $years = DB::table('holidays')
                            ->where('type', 'weekend')
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
                    ->modalDescription('Ini akan otomatis menghasilkan semua hari Sabtu dan Minggu untuk tahun yang dipilih.')
                    ->action(function (array $data) {
                        $result = WorkdayCalculator::generateWeekendForYear($data['year']);

                        \Filament\Notifications\Notification::make()
                            ->title('Weekend berhasil di-generate')
                            ->success()
                            ->body("Ditambahkan: {$result['inserted']}, Dilewati: {$result['skipped']}")
                            ->send();
                    }),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->label('Hapus')
                        ->visible(fn () => auth()->user()->role === 'admin' || auth()->user()->role === 'hr'),
                ]),
            ])
            ->defaultSort('date', 'desc')
            ->striped()
            ->paginated([10, 25, 50, 100]);
    }
}
