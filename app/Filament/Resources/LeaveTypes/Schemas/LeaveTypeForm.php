<?php

namespace App\Filament\Resources\LeaveTypes\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class LeaveTypeForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Informasi Tipe Cuti')
                    ->schema([
                        TextInput::make('name')
                            ->label('Nama')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255),

                        TextInput::make('quota_days')
                            ->label('Kuota Hari')
                            ->required()
                            ->numeric()
                            ->minValue(0)
                            ->helperText('Masukkan jumlah hari yang diizinkan per tahun. Gunakan 0 untuk tidak terbatas.'),

                        Toggle::make('is_paid')
                            ->label('Cuti Dibayar')
                            ->default(true)
                            ->helperText('Centang jika tipe cuti ini dibayar'),
                    ])
                    ->columns(2),
            ]);
    }
}
