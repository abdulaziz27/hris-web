<?php

namespace App\Filament\Widgets;

use App\Models\Location;
use App\Models\Overtime;
use Carbon\Carbon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\TableWidget as BaseWidget;

class PendingOvertimeWidget extends BaseWidget
{
    use InteractsWithPageFilters;

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        // Safely get page filters
        $pageFilters = [];
        if (property_exists($this, 'pageFilters') && isset($this->pageFilters)) {
            $pageFilters = is_array($this->pageFilters) ? $this->pageFilters : [];
        }
        
        $locationId = $pageFilters['location'] ?? null;
        $locationId = $locationId ? (int) $locationId : null; // Ensure it's integer or null
        $startDate = $pageFilters['start_date'] ?? null;
        $endDate = $pageFilters['end_date'] ?? null;
        
        $locationName = $locationId ? Location::find($locationId)?->name : null;
        $dateRangeText = $this->getDateRangeText($startDate, $endDate);
        
        $heading = 'Overtime Menunggu Persetujuan';
        
        $parts = array_filter([$dateRangeText, $locationName]);
        if (!empty($parts)) {
            $heading .= ' - ' . implode(' - ', $parts);
        }
        
        $query = Overtime::with(['user:id,name,location_id', 'user.location:id,name'])
            ->where('status', 'pending')
            ->latest('date');

        if ($locationId) {
            $query->whereHas('user', function ($q) use ($locationId) {
                $q->where('location_id', $locationId);
            });
        } else {
            // When no location filter, also include records where user has no location
            // This ensures all records are shown when "Semua Lokasi" is selected
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
                    ->label('Karyawan')
                    ->searchable(),

                TextColumn::make('user.location.name')
                    ->label('Lokasi')
                    ->badge()
                    ->color('info')
                    ->state(function ($record): ?string {
                        return $record->user?->location?->name ?? null;
                    })
                    ->placeholder('Tidak ada lokasi')
                    ->default('Tidak ada lokasi')
                    ->visible(! $locationId), // Hide location column when filter is active

                TextColumn::make('date')
                    ->label('Tanggal')
                    ->date('d/m/Y'),

                TextColumn::make('start_time')
                    ->label('Jam Mulai')
                    ->time('H:i'),

                TextColumn::make('end_time')
                    ->label('Jam Selesai')
                    ->time('H:i'),

                TextColumn::make('notes')
                    ->label('Catatan')
                    ->limit(50)
                    ->placeholder('Tidak ada catatan'),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'approved' => 'success',
                        'rejected' => 'danger',
                        'pending' => 'warning',
                    }),

                TextColumn::make('created_at')
                    ->label('Diajukan')
                    ->since(),
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
