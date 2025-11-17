<?php

namespace App\Filament\Resources\Leaves\Schemas;

use App\Models\User;
use App\Support\WorkdayCalculator;
use Carbon\Carbon;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class LeaveForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Informasi Cuti')
                    ->schema([
                        Select::make('employee_id')
                            ->label('Karyawan')
                            ->required()
                            ->searchable()
                            ->relationship('employee', 'name')
                            ->preload()
                            ->default(auth()->id()),

                        Select::make('leave_type_id')
                            ->label('Tipe Cuti')
                            ->required()
                            ->searchable()
                            ->relationship('leaveType', 'name')
                            ->preload(),

                        DatePicker::make('start_date')
                            ->label('Tanggal Mulai')
                            ->required()
                            ->native(false)
                            ->displayFormat('d/m/Y')
                            ->reactive()
                            ->afterStateUpdated(function ($state, $set, $get) {
                                self::calculateTotalDays($get, $set);
                            }),

                        DatePicker::make('end_date')
                            ->label('Tanggal Selesai')
                            ->required()
                            ->native(false)
                            ->displayFormat('d/m/Y')
                            ->reactive()
                            ->afterStateUpdated(function ($state, $set, $get) {
                                self::calculateTotalDays($get, $set);
                            }),

                        \Filament\Forms\Components\TextInput::make('total_days')
                            ->label('Total Hari Cuti')
                            ->helperText(function ($get) {
                                $startDate = $get('start_date');
                                $endDate = $get('end_date');
                                
                                if (!$startDate || !$endDate) {
                                    return 'Range: - hari. Ini adalah semua hari dalam range. Anda yang paling tahu berapa hari kerja dalam range ini. Edit manual jika perlu.';
                                }
                                
                                try {
                                    $startDateStr = $startDate instanceof \Carbon\Carbon 
                                        ? $startDate->format('Y-m-d') 
                                        : $startDate;
                                    $endDateStr = $endDate instanceof \Carbon\Carbon 
                                        ? $endDate->format('Y-m-d') 
                                        : $endDate;
                                    
                                    $start = Carbon::createFromFormat('Y-m-d', $startDateStr, config('app.timezone'));
                                    $end = Carbon::createFromFormat('Y-m-d', $endDateStr, config('app.timezone'));
                                    
                                    $totalDays = WorkdayCalculator::countTotalDays($start, $end);
                                    $manualDays = $get('total_days');
                                    
                                    if ($manualDays && $manualDays != $totalDays) {
                                        return "Range: {$totalDays} hari. Anda mengubah menjadi {$manualDays} hari. Pastikan sesuai dengan hari kerja Anda.";
                                    }
                                    
                                    return "Range: {$totalDays} hari. Ini adalah semua hari dalam range. Anda yang paling tahu berapa hari kerja dalam range ini. Edit manual jika perlu.";
                                } catch (\Exception $e) {
                                    return 'Range: - hari. Ini adalah semua hari dalam range. Anda yang paling tahu berapa hari kerja dalam range ini. Edit manual jika perlu.';
                                }
                            })
                            ->required()
                            ->numeric()
                            ->default(1)
                            ->minValue(1)
                            ->maxValue(function ($get) {
                                $startDate = $get('start_date');
                                $endDate = $get('end_date');
                                
                                if (!$startDate || !$endDate) {
                                    return 365; // fallback
                                }
                                
                                try {
                                    $startDateStr = $startDate instanceof \Carbon\Carbon 
                                        ? $startDate->format('Y-m-d') 
                                        : $startDate;
                                    $endDateStr = $endDate instanceof \Carbon\Carbon 
                                        ? $endDate->format('Y-m-d') 
                                        : $endDate;
                                    
                                    $start = Carbon::createFromFormat('Y-m-d', $startDateStr, config('app.timezone'));
                                    $end = Carbon::createFromFormat('Y-m-d', $endDateStr, config('app.timezone'));
                                    
                                    return WorkdayCalculator::countTotalDays($start, $end);
                                } catch (\Exception $e) {
                                    return 365; // fallback
                                }
                            })
                            ->helperText(function ($get) {
                                $startDate = $get('start_date');
                                $endDate = $get('end_date');
                                $employeeId = $get('employee_id');
                                $totalDays = $get('total_days') ?? 0;
                                
                                if (!$startDate || !$endDate) {
                                    return "Total hari cuti akan dihitung otomatis setelah memilih tanggal. Anda bisa koreksi manual jika perlu.";
                                }
                                
                                try {
                                    $startDateStr = $startDate instanceof \Carbon\Carbon 
                                        ? $startDate->format('Y-m-d') 
                                        : $startDate;
                                    $endDateStr = $endDate instanceof \Carbon\Carbon 
                                        ? $endDate->format('Y-m-d') 
                                        : $endDate;
                                    
                                    $start = Carbon::createFromFormat('Y-m-d', $startDateStr, config('app.timezone'));
                                    $end = Carbon::createFromFormat('Y-m-d', $endDateStr, config('app.timezone'));
                                    
                                    // Get location from employee
                                    $locationId = null;
                                    if ($employeeId) {
                                        $employee = User::find($employeeId);
                                        if ($employee && $employee->location_id) {
                                            $locationId = $employee->location_id;
                                        }
                                    }
                                    
                                    $rangeDays = WorkdayCalculator::countTotalDays($start, $end);
                                    $weekendDays = WorkdayCalculator::countWeekendDays($start, $end, $locationId);
                                    $autoWorkdays = WorkdayCalculator::countWorkdaysExcludingHolidays($start, $end, $locationId);
                                    
                                    $info = "Range: {$rangeDays} hari | Weekend: {$weekendDays} hari | Auto-calculate: {$autoWorkdays} hari";
                                    
                                    if ($totalDays != $autoWorkdays && $totalDays > 0) {
                                        $info .= " | ⚠️ Anda mengubah dari {$autoWorkdays} hari menjadi {$totalDays} hari";
                                    }
                                    
                                    $info .= ". Anda bisa koreksi manual jika perlu. Admin akan melakukan approval nanti.";
                                    
                                    return $info;
                                } catch (\Exception $e) {
                                    return "Total hari cuti. Anda bisa koreksi manual jika perlu.";
                                }
                            }),

                        Textarea::make('reason')
                            ->label('Alasan')
                            ->rows(3)
                            ->columnSpanFull(),
                    ])
                    ->columns(2),

                // Section::make('Supporting Document')
                //     ->schema([
                //         FileUpload::make('attachment_url')
                //             ->label('Attachment')
                //             ->image()
                //             ->directory('leave-attachments')
                //             ->visibility('private')
                //             ->downloadable()
                //             ->openable()
                //             ->columnSpanFull(),
                //     ]),

                Section::make('Status Persetujuan')
                    ->schema([
                        Select::make('status')
                            ->label('Status')
                            ->options([
                                'pending' => 'Menunggu',
                                'approved' => 'Disetujui',
                                'rejected' => 'Ditolak',
                            ])
                            ->default('pending')
                            ->disabled(fn ($record) => $record === null)
                            ->visible(fn () => auth()->user()->role === 'admin' || auth()->user()->role === 'hr'),

                        Textarea::make('notes')
                            ->label('Catatan')
                            ->rows(2)
                            ->columnSpanFull()
                            ->visible(fn () => auth()->user()->role === 'admin' || auth()->user()->role === 'hr'),
                    ])
                    ->visible(fn ($record) => $record !== null && (auth()->user()->role === 'admin' || auth()->user()->role === 'hr')),
            ]);
    }

    protected static function calculateTotalDays($get, $set): void
    {
        $startDate = $get('start_date');
        $endDate = $get('end_date');

        if ($startDate && $endDate) {
            // Handle both string and Carbon instance
            $startDateStr = $startDate instanceof \Carbon\Carbon 
                ? $startDate->format('Y-m-d') 
                : $startDate;
            $endDateStr = $endDate instanceof \Carbon\Carbon 
                ? $endDate->format('Y-m-d') 
                : $endDate;
            
            // Parse dates as date-only (no timezone conversion)
            // Create in app timezone to avoid timezone shift issues
            $start = Carbon::createFromFormat('Y-m-d', $startDateStr, config('app.timezone'))->startOfDay();
            $end = Carbon::createFromFormat('Y-m-d', $endDateStr, config('app.timezone'))->startOfDay();
            
            // ✅ SIMPLE: Hitung semua hari dalam range (termasuk weekend)
            // User yang tahu berapa hari kerja mereka, bisa edit manual
            $totalDays = WorkdayCalculator::countTotalDays($start, $end);
            $set('total_days', $totalDays);
        }
    }
}
