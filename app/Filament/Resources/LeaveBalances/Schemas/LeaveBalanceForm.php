<?php

namespace App\Filament\Resources\LeaveBalances\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class LeaveBalanceForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Informasi Saldo Cuti')
                    ->schema([
                        Select::make('employee_id')
                            ->label('Karyawan')
                            ->required()
                            ->searchable()
                            ->relationship('employee', 'name')
                            ->preload()
                            ->disabled(fn ($record) => $record !== null),

                        Select::make('leave_type_id')
                            ->label('Tipe Cuti')
                            ->required()
                            ->searchable()
                            ->relationship('leaveType', 'name')
                            ->preload()
                            ->disabled(fn ($record) => $record !== null),

                        TextInput::make('year')
                            ->label('Tahun')
                            ->required()
                            ->numeric()
                            ->minValue(2020)
                            ->maxValue(2100)
                            ->default(now()->year)
                            ->disabled(fn ($record) => $record !== null),

                        TextInput::make('quota_days')
                            ->label('Kuota Hari')
                            ->required()
                            ->numeric()
                            ->minValue(0)
                            ->reactive()
                            ->afterStateUpdated(function ($state, $set, $get) {
                                $usedDays = $get('used_days') ?? 0;
                                $carryOverDays = $get('carry_over_days') ?? 0;
                                $set('remaining_days', $state + $carryOverDays - $usedDays);
                            }),

                        TextInput::make('used_days')
                            ->label('Hari Terpakai')
                            ->required()
                            ->numeric()
                            ->minValue(0)
                            ->default(0)
                            ->disabled(),

                        // TextInput::make('carry_over_days')
                        //     ->label('Hari Carry Over')
                        //     ->required()
                        //     ->numeric()
                        //     ->minValue(0)
                        //     ->default(0)
                        //     ->reactive()
                        //     ->afterStateUpdated(function ($state, $set, $get) {
                        //         $quotaDays = $get('quota_days') ?? 0;
                        //         $usedDays = $get('used_days') ?? 0;
                        //         $set('remaining_days', $quotaDays + $state - $usedDays);
                        //     }),

                        TextInput::make('remaining_days')
                            ->label('Sisa Hari')
                            ->required()
                            ->numeric()
                            ->minValue(0)
                            ->default(0)
                            ->disabled()
                            ->dehydrated(),
                    ])
                    ->columns(2),
            ]);
    }
}
