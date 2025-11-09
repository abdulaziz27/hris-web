<?php

namespace App\Filament\Resources\Leaves\Tables;

use App\Models\Leave;
use App\Models\LeaveBalance;
use App\Support\WorkdayCalculator;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Textarea;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class LeavesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(function (Builder $query) {
                return $query->with(['employee.location', 'leaveType', 'approver']);
            })
            ->columns([
                TextColumn::make('employee.name')
                    ->label('Karyawan')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('employee.location.name')
                    ->label('Lokasi')
                    ->sortable()
                    ->searchable()
                    ->placeholder('Tidak Ada Lokasi')
                    ->badge()
                    ->color('info'),

                TextColumn::make('leaveType.name')
                    ->label('Tipe Cuti')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('start_date')
                    ->label('Tanggal Mulai')
                    ->date('d/m/Y')
                    ->sortable(),

                TextColumn::make('end_date')
                    ->label('Tanggal Selesai')
                    ->date('d/m/Y')
                    ->sortable(),

                TextColumn::make('total_days')
                    ->label('Total Hari')
                    ->sortable(),

                IconColumn::make('attachment_url')
                    ->label('Lampiran')
                    ->icon(fn ($record) => $record->attachment_url ? 'heroicon-o-paper-clip' : null)
                    ->color('primary')
                    ->url(fn ($record) => $record->attachment_url ? Storage::url($record->attachment_url) : null)
                    ->openUrlInNewTab()
                    ->alignCenter()
                    ->tooltip(fn ($record) => $record->attachment_url ? 'Lihat Lampiran' : 'Tidak Ada Lampiran'),

                BadgeColumn::make('status')
                    ->label('Status')
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'pending' => 'Menunggu',
                        'approved' => 'Disetujui',
                        'rejected' => 'Ditolak',
                        default => $state,
                    })
                    ->colors([
                        'warning' => 'pending',
                        'success' => 'approved',
                        'danger' => 'rejected',
                    ])
                    ->sortable(),

                TextColumn::make('approver.name')
                    ->label('Disetujui Oleh')
                    ->sortable()
                    ->searchable()
                    ->placeholder('-'),

                TextColumn::make('approved_at')
                    ->label('Disetujui Pada')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->placeholder('-'),

                TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('employee_id')
                    ->label('Karyawan')
                    ->relationship('employee', 'name')
                    ->searchable(),

                SelectFilter::make('leave_type_id')
                    ->label('Tipe Cuti')
                    ->relationship('leaveType', 'name')
                    ->searchable(),

                SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'pending' => 'Menunggu',
                        'approved' => 'Disetujui',
                        'rejected' => 'Ditolak',
                    ]),

                Filter::make('date_range')
                    ->label('Rentang Tanggal')
                    ->form([
                        DatePicker::make('start_date')
                            ->label('Dari'),
                        DatePicker::make('end_date')
                            ->label('Sampai'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['start_date'],
                                fn (Builder $query, $date): Builder => $query->where('start_date', '>=', $date),
                            )
                            ->when(
                                $data['end_date'],
                                fn (Builder $query, $date): Builder => $query->where('end_date', '<=', $date),
                            );
                    }),
            ])
            ->recordActions([
                ViewAction::make()
                    ->label('Lihat'),

                EditAction::make()
                    ->label('Ubah')
                    ->visible(fn (Leave $record) => $record->status === 'pending'),

                Action::make('approve')
                    ->label('Setujui')
                    ->color('success')
                    ->icon('heroicon-o-check')
                    ->visible(fn (Leave $record) => $record->status === 'pending' && (auth()->user()->role === 'admin' || auth()->user()->role === 'hr'))
                    ->requiresConfirmation()
                    ->modalHeading('Setujui Pengajuan Cuti')
                    ->modalDescription(fn ($record) => 'Karyawan: '.$record->employee->name."\nTipe Cuti: ".$record->leaveType->name."\nTanggal: ".$record->start_date->format('d/m/Y').' - '.$record->end_date->format('d/m/Y'))
                    ->action(function (Leave $record) {
                        try {
                            DB::beginTransaction();

                            // Recalculate total days to ensure consistency with holidays
                            $totalDays = WorkdayCalculator::countWorkdaysExcludingHolidays(
                                Carbon::parse($record->start_date),
                                Carbon::parse($record->end_date)
                            );

                            $year = $record->start_date->year;
                            $leaveBalance = LeaveBalance::where('employee_id', $record->employee_id)
                                ->where('leave_type_id', $record->leave_type_id)
                                ->where('year', $year)
                                ->first();

                            // Check if leave balance exists
                            if (! $leaveBalance) {
                                DB::rollBack();

                                \Filament\Notifications\Notification::make()
                                    ->title('Tidak dapat menyetujui pengajuan cuti')
                                    ->danger()
                                    ->body('Saldo cuti tidak ditemukan untuk karyawan dan tipe cuti ini.')
                                    ->send();

                                return;
                            }

                            // Check if remaining days is sufficient
                            if ($leaveBalance->remaining_days < $totalDays) {
                                DB::rollBack();

                                \Filament\Notifications\Notification::make()
                                    ->title('Tidak dapat menyetujui pengajuan cuti')
                                    ->danger()
                                    ->body("Saldo cuti tidak mencukupi. Diperlukan: {$totalDays} hari, Tersedia: {$leaveBalance->remaining_days} hari.")
                                    ->send();

                                return;
                            }

                            $record->update([
                                'status' => 'approved',
                                'approved_by' => auth()->id(),
                                'approved_at' => now(),
                                'total_days' => $totalDays,
                            ]);

                            $leaveBalance->update([
                                'used_days' => $leaveBalance->used_days + $totalDays,
                                'remaining_days' => $leaveBalance->remaining_days - $totalDays,
                                'last_updated' => now(),
                            ]);

                            DB::commit();

                            \Filament\Notifications\Notification::make()
                                ->title('Pengajuan cuti berhasil disetujui')
                                ->success()
                                ->send();
                        } catch (\Exception $e) {
                            DB::rollBack();

                            \Filament\Notifications\Notification::make()
                                ->title('Gagal menyetujui pengajuan cuti')
                                ->danger()
                                ->body($e->getMessage())
                                ->send();
                        }
                    }),

                Action::make('reject')
                    ->label('Tolak')
                    ->color('danger')
                    ->icon('heroicon-o-x-circle')
                    ->visible(fn (Leave $record) => $record->status === 'pending' && (auth()->user()->role === 'admin' || auth()->user()->role === 'hr'))
                    ->form([
                        Textarea::make('notes')
                            ->label('Catatan Penolakan')
                            ->rows(3)
                            ->required(),
                    ])
                    ->modalHeading('Tolak Pengajuan Cuti')
                    ->modalDescription(fn ($record) => 'Karyawan: '.$record->employee->name."\nTipe Cuti: ".$record->leaveType->name)
                    ->action(function (Leave $record, array $data) {
                        $record->update([
                            'status' => 'rejected',
                            'approved_by' => auth()->id(),
                            'approved_at' => now(),
                            'notes' => $data['notes'],
                        ]);

                        \Filament\Notifications\Notification::make()
                            ->title('Pengajuan cuti ditolak')
                            ->success()
                            ->send();
                    }),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->label('Hapus')
                        ->visible(fn () => auth()->user()->role === 'admin' || auth()->user()->role === 'hr'),
                ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->striped()
            ->paginated([10, 25, 50]);
    }
}
