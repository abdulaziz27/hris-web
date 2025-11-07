<?php

namespace App\Filament\Resources\PublicHolidays\Schemas;

use App\Models\Holiday;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Validation\Rule;

class PublicHolidayForm
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
                            ->label('Nama Hari Libur')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('Contoh: Hari Kemerdekaan, Idul Fitri'),

                        Select::make('type')
                            ->label('Tipe')
                            ->required()
                            ->options([
                                Holiday::TYPE_NATIONAL => 'Hari Libur Nasional',
                                Holiday::TYPE_COMPANY => 'Hari Libur Perusahaan',
                            ])
                            ->default(Holiday::TYPE_NATIONAL)
                            ->helperText('Nasional = hari libur pemerintah, Perusahaan = hari libur spesifik perusahaan'),

                        Toggle::make('is_official')
                            ->label('Hari Libur Resmi')
                            ->default(true)
                            ->helperText('Tandai sebagai hari libur resmi pemerintah/perusahaan'),
                    ])
                    ->columns(2),
            ]);
    }
}
