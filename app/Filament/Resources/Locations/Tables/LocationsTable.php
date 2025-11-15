<?php

namespace App\Filament\Resources\Locations\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class LocationsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Nama Lokasi')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                TextColumn::make('address')
                    ->label('Alamat')
                    ->limit(50)
                    ->searchable()
                    ->toggleable(),

                TextColumn::make('latitude')
                    ->label('Latitude')
                    ->copyable()
                    ->toggleable(),

                TextColumn::make('longitude')
                    ->label('Longitude')
                    ->copyable()
                    ->toggleable(),

                TextColumn::make('radius_km')
                    ->label('Radius')
                    ->suffix(' km')
                    ->badge()
                    ->color('info'),

                TextColumn::make('nilai_hk')
                    ->label('Nilai HK')
                    ->formatStateUsing(function ($state) {
                        return $state ? 'Rp ' . number_format($state, 0, ',', '.') : '-';
                    })
                    ->sortable()
                    ->toggleable(),

                IconColumn::make('is_active')
                    ->label('Status')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('gray'),

                BadgeColumn::make('attendance_type')
                    ->label('Metode Absensi')
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'location_based_only' => 'GPS',
                        'face_recognition_only' => 'Face Recognition',
                        'hybrid' => 'Hybrid',
                        default => $state,
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'location_based_only' => 'primary',
                        'face_recognition_only' => 'success',
                        'hybrid' => 'warning',
                        default => 'gray',
                    }),

                TextColumn::make('users_count')
                    ->label('Karyawan')
                    ->counts('users')
                    ->badge()
                    ->color('primary'),

                TextColumn::make('created_at')
                    ->label('Dibuat Pada')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                TernaryFilter::make('is_active')
                    ->label('Status')
                    ->placeholder('Semua Lokasi')
                    ->trueLabel('Hanya Aktif')
                    ->falseLabel('Hanya Nonaktif'),
            ])
            ->recordActions([
                EditAction::make()
                    ->label('Ubah'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->label('Hapus'),
                ]),
            ])
            ->defaultSort('name', 'asc');
    }
}
