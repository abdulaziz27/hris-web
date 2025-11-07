<?php

namespace App\Filament\Resources\Holidays\Schemas;

use App\Models\Holiday;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Validation\Rule;

class HolidayForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Informasi Hari Libur')
                    ->schema([
                        DatePicker::make('date')
                            ->label('Tanggal')
                            ->required()
                            ->native(false)
                            ->unique(table: 'holidays', column: 'date', ignoreRecord: true)
                            ->rules([
                                fn ($record) => $record
                                    ? Rule::unique('holidays', 'date')->ignore($record->id)
                                    : Rule::unique('holidays', 'date'),
                            ]),

                        TextInput::make('name')
                            ->label('Nama')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('Contoh: Hari Kemerdekaan'),

                        Select::make('type')
                            ->label('Tipe')
                            ->required()
                            ->options([
                                Holiday::TYPE_NATIONAL => 'Nasional',
                                Holiday::TYPE_COMPANY => 'Perusahaan',
                                Holiday::TYPE_WEEKEND => 'Weekend',
                            ])
                            ->default(Holiday::TYPE_NATIONAL),

                        Toggle::make('is_official')
                            ->label('Hari Libur Resmi')
                            ->default(false)
                            ->helperText('Centang jika ini adalah hari libur resmi pemerintah'),
                    ])
                    ->columns(2),
            ]);
    }
}
