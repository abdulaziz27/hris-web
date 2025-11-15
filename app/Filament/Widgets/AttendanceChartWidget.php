<?php

namespace App\Filament\Widgets;

use App\Models\Attendance;
use App\Models\Location;
use Carbon\Carbon;
use Filament\Widgets\ChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageFilters;

class AttendanceChartWidget extends ChartWidget
{
    use InteractsWithPageFilters;

    protected ?string $heading = 'Grafik Absensi 30 Hari Terakhir';

    protected int|string|array $columnSpan = 'full';

    protected static ?int $sort = 2;

    public function getHeading(): ?string
    {
        $pageFilters = $this->pageFilters ?? [];
        $locationId = $pageFilters['location'] ?? null;
        $locationId = $locationId ? (int) $locationId : null; // Ensure it's integer or null
        $startDate = $pageFilters['start_date'] ?? null;
        $endDate = $pageFilters['end_date'] ?? null;
        
        $locationName = $locationId ? Location::find($locationId)?->name : null;
        $dateRangeText = $this->getDateRangeText($startDate, $endDate);
        
        $heading = 'Grafik Absensi';
        if ($dateRangeText) {
            $heading .= ' - ' . $dateRangeText;
        } else {
            $heading .= ' 30 Hari Terakhir';
        }
        
        if ($locationName) {
            $heading .= ' - ' . $locationName;
        }

        return $heading;
    }

    protected function getData(): array
    {
        $pageFilters = $this->pageFilters ?? [];
        $locationId = $pageFilters['location'] ?? null;
        $locationId = $locationId ? (int) $locationId : null; // Ensure it's integer or null
        $startDate = $pageFilters['start_date'] ?? null;
        $endDate = $pageFilters['end_date'] ?? null;
        
        $data = [];
        $labels = [];

        // Determine date range
        if ($startDate && $endDate) {
            $start = Carbon::parse($startDate);
            $end = Carbon::parse($endDate);
        } elseif ($startDate) {
            $start = Carbon::parse($startDate);
            $end = Carbon::now();
        } elseif ($endDate) {
            $start = Carbon::parse($endDate)->subDays(29); // Default to 30 days before end date
            $end = Carbon::parse($endDate);
        } else {
            // Default to last 30 days
            $start = Carbon::now()->subDays(29);
            $end = Carbon::now();
        }

        // Generate dates in range
        $currentDate = $start->copy();
        while ($currentDate <= $end) {
            $labels[] = $currentDate->format('d M');
            
            $query = Attendance::whereDate('date', $currentDate->format('Y-m-d'))
                ->whereNotNull('time_in');

            if ($locationId) {
                $query->where('location_id', $locationId);
            }

            $attendanceCount = $query->count();
            $data[] = $attendanceCount;
            
            $currentDate->addDay();
        }

        return [
            'datasets' => [
                [
                    'label' => 'Jumlah Absensi',
                    'data' => $data,
                    'backgroundColor' => '#f59e0b',
                    'borderColor' => '#d97706',
                ],
            ],
            'labels' => $labels,
        ];
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

    protected function getType(): string
    {
        return 'line';
    }
}
