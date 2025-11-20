<?php

namespace App\Filament\Widgets;

use App\Models\Attendance;
use App\Models\Holiday;
use App\Models\Leave;
use App\Models\Location;
use App\Models\User;
use App\Support\WorkdayCalculator;
use Carbon\Carbon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class AbsentEmployeesWidget extends BaseWidget
{
    use InteractsWithPageFilters;

    protected int|string|array $columnSpan = 'full';

    protected static ?int $sort = 4;

    protected static bool $isLazy = true;

    protected ?Collection $absentData = null;
    protected ?int $locationId = null;
    protected ?string $startDate = null;
    protected ?string $endDate = null;

    public function mount(): void
    {
        $this->ensureTableInitialized();
    }

    public function hydrate(): void
    {
        $this->ensureTableInitialized();
    }

    public function bootedInteractsWithTable(): void
    {
        $this->ensureTableInitialized();
        parent::bootedInteractsWithTable();
    }

    public function getTable(): Table
    {
        $this->ensureTableInitialized();
        return $this->table;
    }

    public function render(): View
    {
        $this->ensureTableInitialized();
        return parent::render();
    }

    public function __get($property): mixed
    {
        if ($property === 'table') {
            $this->ensureTableInitialized();
            return $this->table;
        }
        
        return parent::__get($property);
    }

    private function ensureTableInitialized(): void
    {
        try {
            $test = $this->table;
        } catch (\Error $e) {
            $this->table = $this->table($this->makeTable());
        }
    }

    public function table(Table $table): Table
    {
        $pageFilters = $this->getPageFiltersSafe();
        $locationId = $pageFilters['location'] ?? null;
        $locationId = $locationId ? (int) $locationId : null;
        $startDate = $pageFilters['start_date'] ?? null;
        $endDate = $pageFilters['end_date'] ?? null;
        
        $locationName = $locationId ? Location::find($locationId)?->name : null;
        $dateRangeText = $this->getDateRangeText($startDate, $endDate);
        
        $heading = 'Karyawan Tidak Hadir';
        
        $parts = array_filter([$dateRangeText, $locationName]);
        if (!empty($parts)) {
            $heading .= ' - ' . implode(' - ', $parts);
        }

        // Store filters for later use
        $this->locationId = $locationId;
        $this->startDate = $startDate;
        $this->endDate = $endDate;

        // Use records() method for custom data instead of query()
        // For pagination to work, we need to inject $page and $recordsPerPage and return LengthAwarePaginator
        return $table
            ->heading($heading)
            ->records(function (int $page, int $recordsPerPage) use ($locationId, $startDate, $endDate): LengthAwarePaginator {
                // Get absent employees data
                $absentData = $this->getAbsentEmployeesData($locationId, $startDate, $endDate);
                
                // Paginate the collection
                $total = $absentData->count();
                $items = $absentData->forPage($page, $recordsPerPage)->values();
                
                // Return LengthAwarePaginator for Filament to handle pagination
                return new LengthAwarePaginator(
                    items: $items->toArray(),
                    total: $total,
                    perPage: $recordsPerPage,
                    currentPage: $page,
                );
            })
            ->columns([
                TextColumn::make('user_name')
                    ->label('Nama Karyawan')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('date')
                    ->label('Tanggal')
                    ->date('d/m/Y')
                    ->sortable(),

                TextColumn::make('location_name')
                    ->label('Lokasi')
                    ->badge()
                    ->color('info')
                    ->state(function ($record): ?string {
                        // When using records(), $record is an array
                        return is_array($record) ? ($record['location_name'] ?? null) : ($record->location_name ?? null);
                    })
                    ->placeholder('Tidak ada lokasi')
                    ->default('Tidak ada lokasi')
                    ->visible(! $locationId),

                TextColumn::make('position')
                    ->label('Jobdesk')
                    ->placeholder('-'),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color('danger')
                    ->default('Tidak Hadir'),
            ])
            ->defaultSort('date', 'desc')
            ->paginated(true)
            ->defaultPaginationPageOption(10);
    }

    private function getAbsentEmployeesData(?int $locationId, ?string $startDate, ?string $endDate): Collection
    {
        // Tentukan periode tanggal
        if ($startDate && $endDate) {
            $start = Carbon::parse($startDate)->startOfDay();
            $end = Carbon::parse($endDate)->endOfDay();
        } else {
            // Default to this month if no date range
            $start = Carbon::now()->startOfMonth()->startOfDay();
            $end = Carbon::now()->endOfMonth()->endOfDay();
        }

        // Get users yang seharusnya absen (berdasarkan lokasi)
        // Exclude user dengan role 'admin' atau tanpa location_id dari perhitungan
        $usersQuery = User::query()
            ->where('role', '!=', 'admin')
            ->whereNotNull('location_id');
        if ($locationId) {
            $usersQuery->where('location_id', $locationId);
        }
        $users = $usersQuery->get();

        if ($users->isEmpty()) {
            return collect([]);
        }

        // Get all holidays in the range for performance
        $holidays = Holiday::whereBetween('date', [$start->toDateString(), $end->toDateString()])
            ->pluck('date')
            ->map(fn ($date) => Carbon::parse($date)->toDateString())
            ->toArray();

        // Get all approved leaves in the range for performance
        // Format: ['date' => [user_id1, user_id2, ...]]
        $approvedLeaves = Leave::where('status', 'approved')
            ->where(function ($query) use ($start, $end) {
                $query->whereBetween('start_date', [$start->toDateString(), $end->toDateString()])
                    ->orWhereBetween('end_date', [$start->toDateString(), $end->toDateString()])
                    ->orWhere(function ($q) use ($start, $end) {
                        $q->where('start_date', '<=', $start->toDateString())
                          ->where('end_date', '>=', $end->toDateString());
                    });
            })
            ->get()
            ->flatMap(function ($leave) use ($start, $end) {
                // Generate all dates in leave range that overlap with our date range
                $leaveStart = Carbon::parse($leave->start_date);
                $leaveEnd = Carbon::parse($leave->end_date);
                $overlapStart = $leaveStart->greaterThan($start) ? $leaveStart : $start;
                $overlapEnd = $leaveEnd->lessThan($end) ? $leaveEnd : $end;
                
                $dates = [];
                $current = $overlapStart->copy();
                while ($current <= $overlapEnd) {
                    $dates[] = [
                        'date' => $current->toDateString(),
                        'user_id' => $leave->employee_id,
                    ];
                    $current->addDay();
                }
                return $dates;
            })
            ->groupBy('date')
            ->map(function ($group) {
                return $group->pluck('user_id')->unique()->toArray();
            })
            ->toArray();

        // Get all attendance records in the range for performance
        $attendanceQuery = Attendance::whereBetween('date', [$start->toDateString(), $end->toDateString()])
            ->whereNotNull('time_in');
        
        if ($locationId) {
            $attendanceQuery->where('location_id', $locationId);
        }
        
        // Group by date string (Y-m-d format) and user_id to ensure consistent key format
        $attendances = $attendanceQuery->get()
            ->groupBy(function ($attendance) {
                // Ensure date is formatted as Y-m-d string for consistent grouping
                return Carbon::parse($attendance->date)->toDateString();
            })
            ->map(function ($dateGroup) {
                // Then group by user_id
                return $dateGroup->groupBy('user_id');
            });

        $absentList = collect([]);
        $current = $start->copy();

        // Loop setiap hari dalam periode
        while ($current <= $end) {
            $dateString = $current->toDateString();
            
            // Cek apakah hari kerja (bukan weekend, bukan holiday)
            $isWeekend = WorkdayCalculator::isWeekend($current, null, $locationId);
            $isHoliday = in_array($dateString, $holidays);
            
            if (!$isWeekend && !$isHoliday) {
                // Hari kerja - cek karyawan yang tidak hadir
                foreach ($users as $user) {
                    // Cek apakah user sudah absen di hari ini
                    $hasAttended = isset($attendances[$dateString][$user->id]);
                    
                    // Cek apakah user sedang cuti (approved leave)
                    $isOnLeave = isset($approvedLeaves[$dateString]) && in_array($user->id, $approvedLeaves[$dateString]);
                    
                    // User tidak hadir jika: tidak absen DAN tidak sedang cuti
                    if (!$hasAttended && !$isOnLeave) {
                        // User tidak hadir - tambahkan ke list
                        // Use unique key: user_id + date for Filament tracking
                        $uniqueKey = $user->id . '_' . $dateString;
                        $absentList->put($uniqueKey, [
                            'user_id' => $user->id,
                            'user_name' => $user->name,
                            'date' => $dateString,
                            'location_name' => $user->location?->name ?? null,
                            'position' => $user->position ?? null,
                            'status' => 'Tidak Hadir',
                        ]);
                    }
                }
            }
            
            $current->addDay();
        }

        // Return collection of arrays (Filament records() will convert to array)
        return $absentList;
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

