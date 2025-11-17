<?php

namespace App\Filament\Resources\Payrolls\Pages;

use App\Filament\Resources\Payrolls\PayrollResource;
use App\Services\PayrollCalculator;
use Carbon\Carbon;
use Filament\Resources\Pages\CreateRecord;

class CreatePayroll extends CreateRecord
{
    protected static string $resource = PayrollResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Set created_by
        $data['created_by'] = auth()->id();

        // Auto-calculate if not set
        if (isset($data['user_id']) && isset($data['period'])) {
            $period = Carbon::parse($data['period'])->startOfMonth();
            // Pass null jika standard_workdays tidak diisi, agar dihitung per-user
            $standardWorkdays = isset($data['standard_workdays']) && $data['standard_workdays'] !== null 
                ? (int) $data['standard_workdays'] 
                : null;

            // Generate payroll data (akan dihitung per-user jika standardWorkdays null)
            $payrollData = PayrollCalculator::generateMonthlyPayroll(
                $data['user_id'],
                $period,
                $standardWorkdays
            );

            // Merge calculated data
            $data = array_merge($data, $payrollData);
        }

        return $data;
    }
}
