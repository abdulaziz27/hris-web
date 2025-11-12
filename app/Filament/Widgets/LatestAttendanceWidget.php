<?php

namespace App\Filament\Widgets;

use App\Models\Attendance;
use App\Models\Location;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class LatestAttendanceWidget extends BaseWidget
{
    protected static ?string $heading = 'Absensi Terbaru';

    protected int|string|array $columnSpan = 'full';

    protected static ?int $sort = 3;

    public ?int $locationFilter = null;

    public function getHeading(): ?string
    {
        $locationName = $this->getLocationName();
        
        if ($locationName) {
            return "Absensi Terbaru - {$locationName}";
        }

        return 'Absensi Terbaru';
    }

    public function table(Table $table): Table
    {
        $query = Attendance::with(['user:id,name,position,location_id', 'location:id,name'])
            ->latest('created_at');

        if ($this->locationFilter) {
            $query->where('location_id', $this->locationFilter);
        }

        return $table
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
                    ->visible(! $this->locationFilter), // Hide location column when filter is active

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

    private function getLocationName(): ?string
    {
        if (! $this->locationFilter) {
            return null;
        }

        return Location::find($this->locationFilter)?->name;
    }
}
