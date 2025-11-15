<?php

namespace App\Filament\Resources\Locations\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class LocationForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Informasi Lokasi Kebun')
                    ->schema([
                        TextInput::make('name')
                            ->label('Nama Lokasi/Kebun')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('Contoh: Kebun Sawit Purwokerto Timur'),

                        Textarea::make('address')
                            ->label('Alamat')
                            ->rows(3)
                            ->placeholder('Alamat lengkap lokasi kebun'),

                        Textarea::make('description')
                            ->label('Deskripsi')
                            ->rows(2)
                            ->placeholder('Deskripsi singkat tentang lokasi ini'),

                        TextInput::make('nilai_hk')
                            ->label('Nilai HK Default Lokasi')
                            ->numeric()
                            ->prefix('Rp')
                            ->placeholder('0')
                            ->required()
                            ->helperText('Nilai HK (rate bayaran per hari kerja) default untuk semua karyawan di lokasi ini. Gaji akan dihitung per hari kerja: Nilai HK × Hari Kerja. Akan digunakan otomatis untuk perhitungan payroll, kecuali jika karyawan memiliki nilai HK khusus (override).'),

                        Select::make('timezone')
                            ->label('Timezone')
                            ->required()
                            ->default('Asia/Jakarta')
                            ->options([
                                'Asia/Jakarta' => 'WIB (UTC+7) - Jakarta, Sumatera, Kalimantan Barat',
                                'Asia/Makassar' => 'WITA (UTC+8) - Makassar, Bali, Kalimantan Tengah/Timur, Sulawesi, NTT',
                                'Asia/Jayapura' => 'WIT (UTC+9) - Jayapura, Papua, Maluku',
                            ])
                            ->native(false)
                            ->helperText('Pilih timezone sesuai lokasi geografis kebun/kantor. Penting untuk perhitungan waktu absensi yang akurat.'),
                    ])
                    ->columns(1),

                Section::make('Pengaturan GPS & Geofence')
                    ->description('Koordinat GPS dan radius validasi untuk absensi')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                TextInput::make('latitude')
                                    ->label('Latitude')
                                    ->required()
                                    ->numeric()
                                    ->placeholder('-7.424154')
                                    ->helperText('Koordinat GPS latitude lokasi (negatif untuk selatan)'),

                                TextInput::make('longitude')
                                    ->label('Longitude')
                                    ->required()
                                    ->numeric()
                                    ->placeholder('109.242088')
                                    ->helperText('Koordinat GPS longitude lokasi'),

                                TextInput::make('radius_km')
                                    ->label('Radius (km)')
                                    ->required()
                                    ->numeric()
                                    ->default(0.5)
                                    ->step(0.1)
                                    ->minValue(0.1)
                                    ->maxValue(1000)
                                    ->helperText('Radius validasi absensi dalam kilometer (maksimal 1000km untuk testing)'),
                            ]),

                        Toggle::make('is_active')
                            ->label('Lokasi Aktif')
                            ->default(true)
                            ->helperText('Hanya lokasi aktif yang dapat digunakan untuk absensi'),
                    ]),

                Section::make('Metode Absensi')
                    ->description('Pilih cara karyawan melakukan absensi di lokasi ini')
                    ->schema([
                        Select::make('attendance_type')
                            ->label('Metode Absensi')
                            ->required()
                            ->options([
                                'location_based_only' => 'Hanya Berbasis Lokasi (GPS)',
                                'face_recognition_only' => 'Hanya Face Recognition',
                                'hybrid' => 'Hybrid (GPS + Face Recognition)',
                            ])
                            ->default('location_based_only')
                            ->helperText('Pilih cara karyawan check in/out di lokasi ini')
                            ->native(false),
                    ]),
            ]);
    }
}
