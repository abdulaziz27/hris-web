<?php

namespace App\Filament\Resources\Payrolls\Pages;

use App\Filament\Resources\Payrolls\PayrollResource;
use App\Services\PayrollCalculator;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Resources\Pages\EditRecord;

class EditPayroll extends EditRecord
{
    protected static string $resource = PayrollResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('approve')
                ->label('Setujui')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->requiresConfirmation()
                ->visible(fn () => $this->record->status === 'draft')
                ->action(function () {
                    $this->record->update([
                        'status' => 'approved',
                        'approved_by' => auth()->id(),
                        'approved_at' => now(),
                    ]);
                    $this->dispatch('notify', type: 'success', message: 'Payroll berhasil disetujui.');
                }),

            Action::make('recalculate')
                ->label('Hitung Ulang')
                ->icon('heroicon-o-arrow-path')
                ->color('warning')
                ->requiresConfirmation()
                ->action(function () {
                    $payrollData = PayrollCalculator::generateMonthlyPayroll(
                        $this->record->user_id,
                        Carbon::parse($this->record->period),
                        $this->record->standard_workdays
                    );

                    // Update dengan data baru, tapi keep hk_review jika sudah di-set manual
                    $this->record->update([
                        'present_days' => $payrollData['present_days'],
                        'nilai_hk' => $payrollData['nilai_hk'],
                        'basic_salary' => $payrollData['basic_salary'],
                        'estimated_salary' => $payrollData['estimated_salary'],
                        'percentage' => $payrollData['percentage'],
                        // Keep hk_review if it was manually set, otherwise update to present_days
                        'hk_review' => $this->record->hk_review ?? $payrollData['hk_review'],
                    ]);

                    // Recalculate final_salary and selisih_hk based on current hk_review
                    $finalSalary = PayrollCalculator::calculateFinalSalary(
                        $this->record->nilai_hk,
                        $this->record->hk_review
                    );
                    $selisihHK = PayrollCalculator::calculateSelisihHK(
                        $this->record->hk_review,
                        $this->record->standard_workdays
                    );

                    $this->record->update([
                        'final_salary' => $finalSalary,
                        'selisih_hk' => $selisihHK,
                    ]);

                    $this->dispatch('notify', type: 'success', message: 'Payroll berhasil dihitung ulang.');
                }),
        ];
    }
}
