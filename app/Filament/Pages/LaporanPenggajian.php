<?php

namespace App\Filament\Pages;

use App\Exports\LaporanPenggajianExport;
use App\Models\Location;
use App\Models\User;
use App\Services\PayrollCalculator;
use BackedEnum;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Pages\Page;
use Filament\Pages\Dashboard\Concerns\HasFiltersForm;
use Maatwebsite\Excel\Facades\Excel;
use UnitEnum;

class LaporanPenggajian extends Page
{
    use HasFiltersForm;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-calculator';

    protected static UnitEnum|string|null $navigationGroup = 'Laporan';

    protected static ?int $navigationSort = 3;

    protected static ?string $title = 'Laporan Penggajian';

    protected static bool $shouldRegisterNavigation = false;

    protected string $view = 'filament.pages.laporan-penggajian';

    public function mount(): void
    {
        // Initialize filters with default values
        if (empty($this->filters)) {
            $this->filters = [
                'period' => now()->startOfMonth(),
                'location' => null,
                'user' => null,
            ];
        }
    }

    public function updatedFiltersLocation(): void
    {
        // Reset user when location changes
        if (isset($this->filters['user'])) {
            $this->filters['user'] = null;
        }
    }


    public function getPayrollData(): array
    {
        $filters = $this->filters ?? [];
        $periodInput = $filters['period'] ?? now()->startOfMonth();
        $locationId = $filters['location'] ?? null;
        $userId = $filters['user'] ?? null;

        // Parse period - could be Carbon instance or string
        if (is_string($periodInput)) {
            // Try to parse as Y-m format first (month input)
            try {
                $period = Carbon::createFromFormat('Y-m', $periodInput)->startOfMonth();
            } catch (\Exception $e) {
                // Fallback to Carbon parse
                $period = Carbon::parse($periodInput)->startOfMonth();
            }
        } else {
            $period = Carbon::parse($periodInput)->startOfMonth();
        }
        $start = $period->copy()->startOfMonth();
        $end = $period->copy()->endOfMonth();

        // Get users based on filter
        $query = User::where('role', 'employee');
        
        if ($locationId) {
            $query->where('location_id', $locationId);
        }
        
        if ($userId) {
            $query->where('id', $userId);
        }

        $users = $query->with('location', 'jabatan', 'departemen')->get();

        $payrollData = [];
        $standardWorkdays = 21; // Default, bisa diubah

        foreach ($users as $user) {
            $data = PayrollCalculator::generateMonthlyPayroll($user->id, $period, $standardWorkdays);
            
            $payrollData[] = [
                'user' => $user,
                'data' => $data,
                'daily_status' => $data['daily_status'],
            ];
        }

        return [
            'period' => $period,
            'start' => $start,
            'end' => $end,
            'standard_workdays' => $standardWorkdays,
            'payrolls' => $payrollData,
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('export')
                ->label('Export Excel')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('success')
                ->action(function () {
                    $payrollData = $this->getPayrollData();
                    
                    $filters = $this->filters ?? [];
                    $locationId = $filters['location'] ?? null;
                    $locationName = null;
                    
                    if ($locationId) {
                        $location = Location::find($locationId);
                        $locationName = $location ? $location->name : null;
                    }
                    
                    $export = new LaporanPenggajianExport(
                        $payrollData['payrolls'],
                        $payrollData['period'],
                        $payrollData['start'],
                        $payrollData['end'],
                        $payrollData['standard_workdays'],
                        $locationId,
                        $locationName
                    );
                    
                    $monthNames = [
                        1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
                        5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
                        9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
                    ];
                    
                    $filename = 'laporan-penggajian-' . strtolower($monthNames[$payrollData['period']->month]) . '-' . $payrollData['period']->year;
                    if ($locationName) {
                        $filename .= '-' . strtolower(str_replace(' ', '-', $locationName));
                    }
                    $filename .= '-' . now()->format('Y-m-d-H-i-s') . '.xlsx';
                    
                    return Excel::download($export, $filename);
                }),
        ];
    }
}
