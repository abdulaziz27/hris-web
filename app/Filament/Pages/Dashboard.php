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
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Pages\Dashboard as BaseDashboard;
use Filament\Pages\Dashboard\Concerns\HasFiltersForm;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class Dashboard extends BaseDashboard
{
    use HasFiltersForm;

    protected static ?string $title = 'Dashboard Absensi';

    protected static ?int $navigationSort = 1;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-home';

    public function getWidgets(): array
    {
        return [
            DashboardStatsWidget::class, // Stats cards - moved from header widgets
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
        // Empty - stats moved to main widgets so filter can be above them
        return [];
    }

    public function content(Schema $schema): Schema
    {
        // Override content to ensure filter form is rendered BEFORE widgets
        // This makes filter appear above stat cards
        return $schema
            ->components([
                // Filter form first (above all widgets)
                ...(method_exists($this, 'getFiltersForm') ? [$this->getFiltersFormContentComponent()] : []),
                // Then widgets (including stats cards)
                $this->getWidgetsContentComponent(),
            ]);
    }

    public function getFiltersFormContentComponent(): \Filament\Schemas\Components\Component
    {
        return EmbeddedSchema::make('filtersForm');
    }

    public function getFiltersForm(): Schema
    {
        if ((! $this->isCachingSchemas) && $this->hasCachedSchema('filtersForm')) {
            return $this->getSchema('filtersForm');
        }

        // Create schema with 1 column for full width (overrides default columns constraint)
        $schema = $this->makeSchema()
            ->columns(1) // Set to 1 column for full width
            ->extraAttributes(['wire:partial' => 'table-filters-form'])
            ->live()
            ->statePath('filters');

        return $this->filtersForm($schema);
    }

    public function filtersForm(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Filter Lokasi Kebun')
                    ->description('Pilih lokasi dan tanggal untuk melihat data spesifik')
                    ->icon('heroicon-o-map-pin')
                    ->schema([
                        Select::make('location')
                            ->label('Lokasi Kebun')
                            ->placeholder('Semua Lokasi')
                            ->options(Location::where('is_active', true)
                                ->orderBy('name')
                                ->pluck('name', 'id')
                                ->toArray())
                            ->searchable()
                            ->preload()
                            ->live(),
                        
                        DatePicker::make('start_date')
                            ->label('Tanggal Mulai')
                            ->native(false)
                            ->displayFormat('d/m/Y')
                            ->live()
                            ->placeholder('Pilih tanggal mulai'),
                        
                        DatePicker::make('end_date')
                            ->label('Tanggal Akhir')
                            ->native(false)
                            ->displayFormat('d/m/Y')
                            ->live()
                            ->placeholder('Pilih tanggal akhir')
                            ->after('start_date'),
                    ])
                    ->columns(3), // 3 columns: location, start_date, end_date
            ]);
    }
}
