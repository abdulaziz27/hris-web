<?php

namespace App\Filament\Widgets;

use App\Models\Leave;
use App\Models\Location;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class PendingApprovalsWidget extends BaseWidget
{
    protected static ?string $heading = 'Leave Menunggu Persetujuan';

    protected int|string|array $columnSpan = 'full';

    protected static ?int $sort = 4;

    public ?int $locationFilter = null;

    public function getHeading(): ?string
    {
        $locationName = $this->getLocationName();
        
        if ($locationName) {
            return "Leave Menunggu Persetujuan - {$locationName}";
        }

        return 'Leave Menunggu Persetujuan';
    }

    public function table(Table $table): Table
    {
        $query = Leave::with(['employee:id,name,location_id', 'employee.location:id,name'])
            ->where('status', 'pending')
            ->latest('created_at');

        if ($this->locationFilter) {
            $query->whereHas('employee', function ($q) {
                $q->where('location_id', $this->locationFilter);
            });
        }

        return $table
            ->query($query->limit(10))
            ->columns([
                TextColumn::make('employee.name')
                    ->label('Karyawan')
                    ->searchable(),

                TextColumn::make('employee.location.name')
                    ->label('Lokasi')
                    ->badge()
                    ->color('info')
                    ->visible(! $this->locationFilter), // Hide location column when filter is active

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

    private function getLocationName(): ?string
    {
        if (! $this->locationFilter) {
            return null;
        }

        return Location::find($this->locationFilter)?->name;
    }
}
