<?php

namespace App\Filament\Widgets;

use App\Models\Attendance;
use App\Models\Location;
use Carbon\Carbon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\TableWidget as BaseWidget;

class LatestAttendanceWidget extends BaseWidget
{
    use InteractsWithPageFilters;

    protected int|string|array $columnSpan = 'full';

    protected static ?int $sort = 3;

    public function table(Table $table): Table
    {
        $pageFilters = $this->pageFilters ?? [];
        $locationId = $pageFilters['location'] ?? null;
        $locationId = $locationId ? (int) $locationId : null; // Ensure it's integer or null
        $startDate = $pageFilters['start_date'] ?? null;
        $endDate = $pageFilters['end_date'] ?? null;
        
        $locationName = $locationId ? Location::find($locationId)?->name : null;
        $dateRangeText = $this->getDateRangeText($startDate, $endDate);
        
        $heading = 'Absensi Terbaru';
        
        $parts = array_filter([$dateRangeText, $locationName]);
        if (!empty($parts)) {
            $heading .= ' - ' . implode(' - ', $parts);
        }
        
        $query = Attendance::with(['user:id,name,position,location_id', 'location:id,name'])
            ->latest('date');

        if ($locationId) {
            $query->where('location_id', $locationId);
        }

        if ($startDate) {
            $query->whereDate('date', '>=', $startDate);
        }

        if ($endDate) {
            $query->whereDate('date', '<=', $endDate);
        }

        return $table
            ->heading($heading)
            ->query($query->limit(10))
            ->columns([
                TextColumn::make('user.name')
                    ->label('Nama Karyawan')
                    ->searchable(),

                TextColumn::make('user.position')
                    ->label('Jabatan'),

                TextColumn::make('location.name')
                    ->label('Lokasi')
                    ->badge()
                    ->color('info')
                    ->state(function ($record): ?string {
                        return $record->location?->name ?? null;
                    })
                    ->placeholder('Tidak ada lokasi')
                    ->default('Tidak ada lokasi')
                    ->visible(! $locationId), // Hide location column when filter is active

                TextColumn::make('date')
                    ->label('Tanggal')
                    ->date('d/m/Y'),

                TextColumn::make('time_in')
                    ->label('Jam Masuk')
                    ->time('H:i')
                    ->placeholder('-'),

                TextColumn::make('time_out')
                    ->label('Jam Keluar')
                    ->time('H:i')
                    ->placeholder('-'),

                TextColumn::make('status')
                    ->label('Status')
                    ->state(function (Attendance $record): string {
                        if (! $record->time_in) {
                            return 'Belum Masuk';
                        }
                        if (! $record->time_out) {
                            return 'Belum Pulang';
                        }

                        return 'Selesai';
                    })
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Selesai' => 'success',
                        'Belum Pulang' => 'warning',
                        'Belum Masuk' => 'danger',
                    }),
            ])
            ->paginated(false);
    }

    private function getDateRangeText(?string $startDate, ?string $endDate): string
    {
        if ($startDate && $endDate) {
            return Carbon::parse($startDate)->format('d/m/Y') . ' - ' . Carbon::parse($endDate)->format('d/m/Y');
        } elseif ($startDate) {
            return 'Mulai ' . Carbon::parse($startDate)->format('d/m/Y');
        } elseif ($endDate) {
            return 'Sampai ' . Carbon::parse($endDate)->format('d/m/Y');
        }

        return '';
    }
}
