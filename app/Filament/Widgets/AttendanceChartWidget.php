<?php

namespace App\Filament\Widgets;

use App\Models\Attendance;
use Carbon\Carbon;
use Filament\Widgets\ChartWidget;

class AttendanceChartWidget extends ChartWidget
{
    protected ?string $heading = 'Grafik Absensi 30 Hari Terakhir';

    protected int|string|array $columnSpan = 'full';

    protected static ?int $sort = 2;

    protected function getData(): array
    {
        $data = [];
        $labels = [];

        // Ambil data 30 hari terakhir
        for ($i = 29; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i);
            $labels[] = $date->format('d M');

            $attendanceCount = Attendance::whereDate('date', $date)
                ->whereNotNull('time_in')
                ->count();

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
}
