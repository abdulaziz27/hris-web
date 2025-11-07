<?php

namespace App\Filament\Resources\Companies\Schemas;

use App\Models\ShiftKerja;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class CompanyInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Informasi Perusahaan')
                    ->schema([
                        TextEntry::make('name')
                            ->label('Nama Perusahaan')
                            ->weight('bold')
                            ->size('lg'),

                        TextEntry::make('email')
                            ->label('Alamat Email')
                            ->icon('heroicon-o-envelope')
                            ->copyable(),

                        TextEntry::make('address')
                            ->label('Alamat')
                            ->icon('heroicon-o-map-pin')
                            ->columnSpanFull(),
                    ])
                    ->columns(2),

                Section::make('Shift Kerja yang Tersedia')
                    ->description('Shift yang dikonfigurasi untuk perusahaan ini')
                    ->schema([
                        TextEntry::make('shifts')
                            ->label('')
                            ->state(function () {
                                return ShiftKerja::where('is_active', true)
                                    ->orderBy('start_time')
                                    ->get()
                                    ->map(function ($shift) {
                                        $crossDay = $shift->is_cross_day ? ' 🌙' : '';
                                        $grace = $shift->grace_period_minutes.' menit tenggang';
                                        $employees = $shift->users()->count();

                                        return sprintf(
                                            '%s: %s - %s%s (%s, %d karyawan)',
                                            $shift->name,
                                            $shift->start_time->format('H:i'),
                                            $shift->end_time->format('H:i'),
                                            $crossDay,
                                            $grace,
                                            $employees
                                        );
                                    })
                                    ->toArray();
                            })
                            ->listWithLineBreaks()
                            ->bulleted()
                            ->placeholder('Tidak ada shift yang dikonfigurasi'),
                    ])
                    ->collapsible(),
            ]);
    }
}
