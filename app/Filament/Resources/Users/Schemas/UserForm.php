<?php

namespace App\Filament\Resources\Users\Schemas;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([
                TextInput::make('name')
                    ->label('Nama')
                    ->required()
                    ->maxLength(255),
                TextInput::make('email')
                    ->label('Alamat Email')
                    ->email()
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->maxLength(255),
                TextInput::make('password')
                    ->label('Kata Sandi')
                    ->password()
                    ->required(fn (string $context): bool => $context === 'create')
                    ->dehydrated(fn ($state) => filled($state))
                    ->minLength(8),
                TextInput::make('phone')
                    ->label('Telepon')
                    ->tel()
                    ->maxLength(20),
                Select::make('role')
                    ->label('Peran')
                    ->options([
                        'admin' => 'Admin',
                        'manager' => 'Manager',
                        'employee' => 'Karyawan',
                    ])
                    ->required()
                    ->default('employee'),
                Select::make('jabatan_id')
                    ->label('Jabatan')
                    ->relationship('jabatan', 'name')
                    ->required()
                    ->searchable()
                    ->preload()
                    ->helperText('Pilih 1 jabatan untuk karyawan'),
                Select::make('departemen_id')
                    ->label('Departemen')
                    ->relationship('departemen', 'name')
                    ->required()
                    ->searchable()
                    ->preload()
                    ->helperText('Pilih 1 departemen untuk karyawan'),
                Select::make('shift_kerja_id')
                    ->label('Shift Kerja')
                    ->relationship('shiftKerja', 'name')
                    ->required()
                    ->searchable()
                    ->preload()
                    ->helperText('Pilih 1 shift kerja untuk karyawan'),
                TextInput::make('position')
                    ->label('Jobdesk / Posisi')
                    ->maxLength(255)
                    ->placeholder('Contoh: Security, Frunning Tanaman, Emplasment, dll')
                    ->helperText(function () {
                        // Get some common examples for helper text
                        $examples = \App\Models\User::whereNotNull('position')
                            ->distinct()
                            ->orderBy('position')
                            ->pluck('position')
                            ->take(10)
                            ->implode(', ');
                        
                        return $examples 
                            ? 'Contoh yang sudah ada: ' . $examples . ' (bisa ketik bebas)'
                            : 'Deskripsi pekerjaan spesifik karyawan. Bisa ketik bebas sesuai kebutuhan.';
                    })
                    ->autocomplete('off'),
                Select::make('location_id')
                    ->label('Lokasi Kerja')
                    ->relationship('location', 'name')
                    ->searchable()
                    ->preload()
                    ->helperText('Pilih lokasi kerja default untuk karyawan (opsional)')
                    ->reactive(),
                TextInput::make('nilai_hk')
                    ->label('Nilai HK (Override)')
                    ->numeric()
                    ->prefix('Rp')
                    ->placeholder('Kosongkan untuk pakai default lokasi')
                    ->helperText(function ($record, $get) {
                        $locationId = $get('location_id') ?? $record?->location_id;
                        if ($locationId) {
                            $location = \App\Models\Location::find($locationId);
                            if ($location?->nilai_hk) {
                                return '⚠️ Kosongkan untuk menggunakan nilai HK default lokasi: Rp ' . number_format($location->nilai_hk, 0, ',', '.') . '. Hanya isi jika karyawan ini memiliki nilai HK berbeda (kasus khusus).';
                            }
                        }
                        return '⚠️ Kosongkan jika ingin menggunakan nilai HK default dari lokasi. Hanya isi jika karyawan ini memiliki nilai HK berbeda dari lokasinya (kasus khusus).';
                    })
                    ->visible(fn ($get, $record) => ($get('location_id') ?? $record?->location_id) !== null)
                    ->reactive(),
                Select::make('salary_type')
                    ->label('Tipe Gaji')
                    ->options([
                        'monthly' => 'Bulanan',
                        'daily' => 'Harian',
                    ])
                    ->default('monthly')
                    ->helperText('Tipe perhitungan gaji')
                    ->hidden() // Hidden karena belum diimplementasikan, sistem masih menggunakan nilai_hk
                    ->dehydrated(), // Tetap simpan ke database jika diisi
                FileUpload::make('image_url')
                    ->label('Foto Profil')
                    ->image()
                    ->imageEditor()
                    ->directory('avatars')
                    ->visibility('public')
                    ->disk('public')
                    ->columnSpanFull(),
                Textarea::make('face_embedding')
                    ->label('Data Face Embedding')
                    ->hidden()
                    ->columnSpanFull(),
                TextInput::make('fcm_token')
                    ->label('Token FCM')
                    ->hidden()
                    ->columnSpanFull(),
            ]);
    }
}
