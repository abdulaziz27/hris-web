<?php

namespace App\Filament\Widgets;

use App\Models\Attendance;
use App\Models\Location;
use Carbon\Carbon;
use Filament\Widgets\ChartWidget;

class AttendanceChartWidget extends ChartWidget
{
    protected ?string $heading = 'Grafik Absensi 30 Hari Terakhir';

    protected int|string|array $columnSpan = 'full';

    protected static ?int $sort = 2;

    public ?int $locationFilter = null;

    public function getHeading(): ?string
    {
        $locationName = $this->getLocationName();
        
        if ($locationName) {
            return "Grafik Absensi 30 Hari Terakhir - {$locationName}";
        }

        return 'Grafik Absensi 30 Hari Terakhir';
    }

    protected function getData(): array
    {
        $data = [];
        $labels = [];

        // Ambil data 30 hari terakhir
        for ($i = 29; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i);
            $labels[] = $date->format('d M');

            $query = Attendance::whereDate('date', $date)
                ->whereNotNull('time_in');

            if ($this->locationFilter) {
                $query->where('location_id', $this->locationFilter);
            }

            $attendanceCount = $query->count();
            $data[] = $attendanceCount;
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

    protected function getType(): string
    {
        return 'line';
    }

    private function getLocationName(): ?string
    {
        if (! $this->locationFilter) {
            return null;
        }

        return Location::find($this->locationFilter)?->name;
    }
}
