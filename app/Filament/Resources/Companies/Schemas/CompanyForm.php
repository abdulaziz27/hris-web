<?php

namespace App\Filament\Resources\Companies\Schemas;

use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class CompanyForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Informasi Perusahaan')
                    ->schema([
                        TextInput::make('name')
                            ->label('Nama Perusahaan')
                            ->required()
                            ->maxLength(255),

                        TextInput::make('email')
                            ->label('Alamat Email')
                            ->email()
                            ->required()
                            ->maxLength(255),

                        Textarea::make('address')
                            ->label('Alamat')
                            ->required()
                            ->rows(3)
                            ->columnSpanFull(),
                    ])
                    ->columns(2),

                // GPS settings moved to Locations (multi-location support)
                // Old fields hidden but kept in database for backward compatibility
            ]);
    }
}
