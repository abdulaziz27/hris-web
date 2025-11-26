<?php

namespace App\Filament\Pages;

use App\Exports\LaporanAbsensiExport;
use App\Models\Attendance;
use App\Models\Location;
use App\Models\User;
use BackedEnum;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Pages\Page;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Facades\Excel;
use UnitEnum;

class LaporanAbsensi extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-document-chart-bar';

    protected static UnitEnum|string|null $navigationGroup = 'Laporan';

    protected static ?int $navigationSort = 2;

    protected static ?string $title = 'Laporan Absensi';

    protected string $view = 'filament.pages.laporan-absensi';

    public function table(Table $table): Table
    {
        return $table
            ->query($this->getTableQuery())
            ->columns([
                TextColumn::make('user.name')
                    ->label('Nama Karyawan')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('location.name')
                    ->label('Lokasi')
                    ->sortable()
                    ->searchable()
                    ->placeholder('-'),

                TextColumn::make('user.position')
                    ->label('Jabatan')
                    ->sortable(),

                TextColumn::make('user.department')
                    ->label('Departemen')
                    ->sortable(),

                TextColumn::make('date')
                    ->label('Tanggal')
                    ->date('d/m/Y')
                    ->sortable(),

                TextColumn::make('time_in')
                    ->label('Jam Masuk')
                    ->time('H:i')
                    ->placeholder('-'),

                TextColumn::make('time_out')
                    ->label('Jam Keluar')
                    ->time('H:i')
                    ->placeholder('-'),

                TextColumn::make('working_hours')
                    ->label('Jam Kerja')
                    ->state(function (Attendance $record): string {
                        if (! $record->time_in || ! $record->time_out) {
                            return '-';
                        }

                        $timeIn = Carbon::parse($record->time_in);
                        $timeOut = Carbon::parse($record->time_out);
                        $diff = $timeIn->diff($timeOut);

                        return sprintf('%d jam %d menit', $diff->h, $diff->i);
                    }),

                TextColumn::make('status')
                    ->label('Status')
                    ->state(function (Attendance $record): string {
                        if (! $record->time_in) {
                            return 'Tidak Masuk';
                        }

                        if (! $record->time_out) {
                            return 'Belum Pulang';
                        }

                        return 'Hadir';
                    })
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Hadir' => 'success',
                        'Belum Pulang' => 'warning',
                        'Tidak Masuk' => 'danger',
                    }),
            ])
            ->filters([
                Filter::make('date_range')
                    ->form([
                        DatePicker::make('start_date')
                            ->label('Tanggal Mulai'),
                        DatePicker::make('end_date')
                            ->label('Tanggal Selesai'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['start_date'] ?? null,
                                fn (Builder $query, $date): Builder => $query->whereDate('date', '>=', $date),
                            )
                            ->when(
                                $data['end_date'] ?? null,
                                fn (Builder $query, $date): Builder => $query->whereDate('date', '<=', $date),
                            );
                    })
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];
                        if ($data['start_date'] ?? null) {
                            $indicators[] = 'Dari: '.Carbon::parse($data['start_date'])->format('d/m/Y');
                        }
                        if ($data['end_date'] ?? null) {
                            $indicators[] = 'Sampai: '.Carbon::parse($data['end_date'])->format('d/m/Y');
                        }

                        return $indicators;
                    }),

                SelectFilter::make('location_id')
                    ->label('Lokasi')
                    ->options(Location::where('is_active', true)->orderBy('name')->pluck('name', 'id'))
                    ->searchable()
                    ->preload()
                    ->placeholder('Semua Lokasi'),

                SelectFilter::make('user_id')
                    ->label('Karyawan')
                    ->options(User::all()->pluck('name', 'id'))
                    ->searchable(),

                Filter::make('today')
                    ->label('Hari Ini')
                    ->query(fn (Builder $query): Builder => $query->whereDate('date', now()))
                    ->toggle(),

                Filter::make('this_week')
                    ->label('Minggu Ini')
                    ->query(fn (Builder $query): Builder => $query->whereBetween('date', [
                        now()->startOfWeek(),
                        now()->endOfWeek(),
                    ]))
                    ->toggle(),

                Filter::make('this_month')
                    ->label('Bulan Ini')
                    ->query(fn (Builder $query): Builder => $query->whereBetween('date', [
                        now()->startOfMonth(),
                        now()->endOfMonth(),
                    ]))
                    ->toggle(),
            ])
            ->actions([
                Action::make('detail')
                    ->label('Detail')
                    ->icon('heroicon-o-eye')
                    ->modalHeading(fn (Attendance $record): string => "Detail Absensi - {$record->user->name}")
                    ->modalContent(function (Attendance $record) {
                        $timeIn = $record->time_in ? Carbon::parse($record->time_in)->format('H:i') : '-';
                        $timeOut = $record->time_out ? Carbon::parse($record->time_out)->format('H:i') : '-';
                        $workingHours = '-';

                        if ($record->time_in && $record->time_out) {
                            $diff = Carbon::parse($record->time_in)->diff(Carbon::parse($record->time_out));
                            $workingHours = sprintf('%d jam %d menit', $diff->h, $diff->i);
                        }

                        return view('filament.modals.attendance-detail', [
                            'record' => $record,
                            'timeIn' => $timeIn,
                            'timeOut' => $timeOut,
                            'workingHours' => $workingHours,
                        ]);
                    }),
            ])
            ->defaultSort('date', 'desc')
            ->striped()
            ->paginated([10, 25, 50])
            ->defaultPaginationPageOption(10);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('export_pdf')
                ->label('Ekspor PDF')
                ->icon('heroicon-o-document-arrow-down')
                ->color('success')
                ->action(function () {
                    $query = $this->getFilteredTableQuery();
                    $attendances = $query->with(['user', 'location'])->get();

                    // Create PDF using blade view
                    $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('filament.pages.laporan-absensi-pdf', [
                        'attendances' => $attendances,
                        'exported_at' => now()->format('d/m/Y H:i'),
                        'total_records' => $attendances->count(),
                    ])
                        ->setPaper('A4', 'landscape')
                        ->setOptions([
                            'dpi' => 150,
                            'defaultFont' => 'sans-serif',
                            'isHtml5ParserEnabled' => true,
                            'isRemoteEnabled' => true,
                        ]);

                    $filename = 'laporan-absensi-'.now()->format('d-m-Y-H-i-s').'.pdf';

                    return response()->streamDownload(function () use ($pdf) {
                        echo $pdf->output();
                    }, $filename);
                }),

            Action::make('export_excel')
                ->label('Ekspor Excel')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('success')
                ->action(function () {
                    $query = $this->getFilteredTableQuery();
                    $attendances = $query->with(['user', 'location'])->get();

                    $export = new LaporanAbsensiExport($attendances);
                    $filename = 'laporan-absensi-' . now()->format('d-m-Y-H-i-s') . '.xlsx';

                    return Excel::download($export, $filename);
                }),
        ];
    }

    protected function getTableQuery(): Builder
    {
        return Attendance::query()
            ->with(['user:id,name,position,department', 'location:id,name'])
            ->select(
                'id',
                'user_id',
                'location_id',
                'date',
                'time_in',
                'time_out',
                'latlon_in',
                'latlon_out',
                'status',       // status ketepatan waktu (on_time, late, absent, ...)
                'created_at',
                'updated_at',
            )
            ->orderBy('date', 'desc');
    }

    public function getFilteredTableQuery(): Builder
    {
        return $this->getTableQuery()->where(function (Builder $query) {
            $this->applyFiltersToTableQuery($query);
        });
    }
}
