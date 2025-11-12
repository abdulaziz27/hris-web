<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\AttendanceChartWidget;
use App\Filament\Widgets\CustomAccountWidget;
use App\Filament\Widgets\DashboardStatsWidget;
use App\Filament\Widgets\LatestAttendanceWidget;
use App\Filament\Widgets\PendingApprovalsWidget;
use App\Filament\Widgets\PendingOvertimeWidget;
use App\Models\Location;
use BackedEnum;
use Filament\Forms\Components\Select;
use Filament\Pages\Dashboard as BaseDashboard;
use Livewire\Attributes\Url;

class Dashboard extends BaseDashboard
{
    protected static ?string $title = 'Dashboard Absensi';

    protected static ?int $navigationSort = 1;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-home';

    protected string $view = 'filament.pages.dashboard';

    #[Url]
    public ?int $locationFilter = null;

    public function getWidgets(): array
    {
        return [
            // DashboardStatsWidget::class,
            AttendanceChartWidget::class,
            LatestAttendanceWidget::class,
            PendingApprovalsWidget::class,
            PendingOvertimeWidget::class,
            CustomAccountWidget::class,
        ];
    }

    public function getColumns(): int|array
    {
        return [
            'sm' => 1,
            'md' => 2,
            'xl' => 3,
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            DashboardStatsWidget::class,
        ];
    }

    public function getHeaderWidgetsData(): array
    {
        return [
            'locationFilter' => $this->locationFilter,
        ];
    }

    public function getWidgetsData(): array
    {
        return [
            'locationFilter' => $this->locationFilter,
        ];
    }

    public function getLocations(): array
    {
        return [
            null => 'Semua Lokasi',
        ] + Location::where('is_active', true)
            ->orderBy('name')
            ->pluck('name', 'id')
            ->toArray();
    }

    public function updatedLocationFilter(): void
    {
        // Trigger widget refresh when filter changes
        $this->dispatch('locationFilterChanged', locationFilter: $this->locationFilter);
    }
}
