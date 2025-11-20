<?php

namespace App\Filament\Widgets;

use App\Models\Leave;
use App\Models\Location;
use Carbon\Carbon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Contracts\View\View;

class PendingApprovalsWidget extends BaseWidget
{
    use InteractsWithPageFilters;

    protected int|string|array $columnSpan = 'full';

    protected static ?int $sort = 4;

    protected static bool $isLazy = true;

    public function mount(): void
    {
        // Initialize table early in mount() to prevent view from accessing uninitialized property
        $this->ensureTableInitialized();
    }

    public function hydrate(): void
    {
        // Ensure table is initialized on hydration (when Livewire updates)
        $this->ensureTableInitialized();
    }

    public function bootedInteractsWithTable(): void
    {
        // Ensure table is initialized before parent method tries to access it
        $this->ensureTableInitialized();
        
        parent::bootedInteractsWithTable();
    }

    public function getTable(): Table
    {
        // Ensure table is always initialized
        $this->ensureTableInitialized();
        return $this->table;
    }

    /**
     * Override render to ensure table is initialized before view is rendered
     */
    public function render(): View
    {
        // CRITICAL: Initialize table before view tries to access it
        $this->ensureTableInitialized();
        
        return parent::render();
    }

    /**
     * Magic method to intercept property access and ensure table is initialized
     */
    public function __get($property): mixed
    {
        if ($property === 'table') {
            $this->ensureTableInitialized();
            return $this->table;
        }
        
        return parent::__get($property);
    }

    /**
     * Ensure table property is initialized
     */
    private function ensureTableInitialized(): void
    {
        try {
            // Try to access table property - if it throws error, it's not initialized
            $test = $this->table;
        } catch (\Error $e) {
            // Property not initialized yet, initialize it now
            $this->table = $this->table($this->makeTable());
        }
    }

    public function table(Table $table): Table
    {
        $pageFilters = $this->getPageFiltersSafe();
        $locationId = $pageFilters['location'] ?? null;
        $locationId = $locationId ? (int) $locationId : null; // Ensure it's integer or null
        $startDate = $pageFilters['start_date'] ?? null;
        $endDate = $pageFilters['end_date'] ?? null;
        
        $locationName = $locationId ? Location::find($locationId)?->name : null;
        $dateRangeText = $this->getDateRangeText($startDate, $endDate);
        
        $heading = 'Izin Cuti/Sakit Menunggu Persetujuan';
        
        $parts = array_filter([$dateRangeText, $locationName]);
        if (!empty($parts)) {
            $heading .= ' - ' . implode(' - ', $parts);
        }
        
        $query = Leave::with(['employee:id,name,location_id', 'employee.location:id,name'])
            ->where('status', 'pending')
            ->latest('created_at');

        if ($locationId) {
            $query->whereHas('employee', function ($q) use ($locationId) {
                $q->where('location_id', $locationId);
            });
        } else {
            // When no location filter, also include records where employee has no location
            // This ensures all records are shown when "Semua Lokasi" is selected
        }

        // Filter by date range - match if leave period overlaps with filter range
        if ($startDate || $endDate) {
            $query->where(function ($q) use ($startDate, $endDate) {
                // Leave starts before or on end date and ends on or after start date
                if ($startDate && $endDate) {
                    $q->where(function ($subQ) use ($startDate, $endDate) {
                        $subQ->where(function ($q1) use ($startDate, $endDate) {
                            // Leave start_date is within range
                            $q1->whereDate('start_date', '>=', $startDate)
                                ->whereDate('start_date', '<=', $endDate);
                        })->orWhere(function ($q2) use ($startDate, $endDate) {
                            // Leave end_date is within range
                            $q2->whereDate('end_date', '>=', $startDate)
                                ->whereDate('end_date', '<=', $endDate);
                        })->orWhere(function ($q3) use ($startDate, $endDate) {
                            // Leave spans the entire range
                            $q3->whereDate('start_date', '<=', $startDate)
                                ->whereDate('end_date', '>=', $endDate);
                        });
                    });
                } elseif ($startDate) {
                    // Only start date filter - show leaves that end on or after start date
                    $q->whereDate('end_date', '>=', $startDate);
                } elseif ($endDate) {
                    // Only end date filter - show leaves that start on or before end date
                    $q->whereDate('start_date', '<=', $endDate);
                }
            });
        }

        return $table
            ->heading($heading)
            ->query($query->limit(10))
            ->columns([
                TextColumn::make('employee.name')
                    ->label('Karyawan')
                    ->searchable(),

                TextColumn::make('employee.location.name')
                    ->label('Lokasi')
                    ->badge()
                    ->color('info')
                    ->state(function ($record): ?string {
                        return $record->employee?->location?->name ?? null;
                    })
                    ->placeholder('Tidak ada lokasi')
                    ->default('Tidak ada lokasi')
                    ->visible(! $locationId), // Hide location column when filter is active

                TextColumn::make('start_date')
                    ->label('Mulai')
                    ->date('d/m/Y'),

                TextColumn::make('end_date')
                    ->label('Selesai')
                    ->date('d/m/Y'),

                TextColumn::make('reason')
                    ->label('Alasan')
                    ->limit(50)
                    ->tooltip(function (TextColumn $column): ?string {
                        $state = $column->getState();

                        if (strlen($state) <= 50) {
                            return null;
                        }

                        return $state;
                    }),

                TextColumn::make('created_at')
                    ->label('Diajukan')
                    ->since(),

                TextColumn::make('status')
                    ->label('Status')
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'approved' => 'Disetujui',
                        'rejected' => 'Ditolak',
                        'cancelled' => 'Dibatalkan',
                        default => 'Menunggu',
                    })
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'approved' => 'success',
                        'rejected' => 'danger',
                        'cancelled' => 'gray',
                        default => 'warning',
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

    private function getPageFiltersSafe(): array
    {
        try {
            if (method_exists($this, 'getPageFilters')) {
                return $this->getPageFilters() ?? [];
            } elseif (property_exists($this, 'pageFilters')) {
                return is_array($this->pageFilters ?? null) ? $this->pageFilters : [];
            }
        } catch (\Exception $e) {
            // If pageFilters is not initialized yet, return empty array
        }
        return [];
    }
}
