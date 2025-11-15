<?php

namespace App\Filament\Widgets;

use App\Models\Attendance;
use App\Models\Departemen;
use App\Models\Jabatan;
use App\Models\Leave;
use App\Models\Location;
use App\Models\Overtime;
use App\Models\ShiftKerja;
use App\Models\User;
use Carbon\Carbon;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class DashboardStatsWidget extends BaseWidget
{
    use InteractsWithPageFilters;

    protected int|string|array $columnSpan = 'full';

    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $pageFilters = $this->pageFilters ?? [];
        $locationId = $pageFilters['location'] ?? null;
        $locationId = $locationId ? (int) $locationId : null; // Ensure it's integer or null
        $startDate = $pageFilters['start_date'] ?? null;
        $endDate = $pageFilters['end_date'] ?? null;
        
        $locationName = $locationId ? Location::find($locationId)?->name : null;
        $dateRangeDescription = $this->getDateRangeDescription($startDate, $endDate);

        return [
            Stat::make('Total Pegawai', $this->getTotalUsers($locationId))
                ->description($locationName ? "Pegawai di {$locationName}" : 'Jumlah seluruh pegawai')
                ->descriptionIcon('heroicon-m-users')
                ->color('primary'),

            Stat::make('Total Jabatan', Jabatan::count())
                ->description('Jumlah jabatan tersedia')
                ->descriptionIcon('heroicon-m-briefcase')
                ->color('success'),

            Stat::make('Total Departemen', Departemen::count())
                ->description('Jumlah departemen tersedia')
                ->descriptionIcon('heroicon-m-building-office')
                ->color('info'),

            Stat::make('Total Shift Kerja', ShiftKerja::count())
                ->description('Jumlah shift kerja')
                ->descriptionIcon('heroicon-m-clock')
                ->color('warning'),

            Stat::make('Overtime Disetujui', $this->getApprovedOvertime($locationId, $startDate, $endDate))
                ->description($this->getOvertimeDescription($locationName, $dateRangeDescription))
                ->descriptionIcon('heroicon-m-plus-circle')
                ->color('success'),

            Stat::make('Cuti Disetujui', $this->getApprovedLeave($locationId, $startDate, $endDate))
                ->description($this->getLeaveDescription($locationName, $dateRangeDescription))
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('success'),

            Stat::make('Absensi Lengkap', $this->getCompleteAttendance($locationId, $startDate, $endDate))
                ->description($this->getAttendanceDescription($locationName, $dateRangeDescription))
                ->descriptionIcon('heroicon-m-calendar-days')
                ->color('primary'),
        ];
    }

    private function getTotalUsers(?int $locationId): int
    {
        $query = User::query();

        if ($locationId) {
            $query->where('location_id', $locationId);
        }

        return $query->count();
    }

    private function getApprovedOvertime(?int $locationId, ?string $startDate, ?string $endDate): int
    {
        $query = Overtime::where('status', 'approved');

        if ($startDate) {
            $query->whereDate('date', '>=', $startDate);
        } else {
            // Default to this month if no date range
            $query->whereMonth('date', now()->month)
                ->whereYear('date', now()->year);
        }

        if ($endDate) {
            $query->whereDate('date', '<=', $endDate);
        }

        if ($locationId) {
            $query->whereHas('user', function ($q) use ($locationId) {
                $q->where('location_id', $locationId);
            });
        }

        return $query->count();
    }

    private function getApprovedLeave(?int $locationId, ?string $startDate, ?string $endDate): int
    {
        $query = Leave::where('status', 'approved')
            ->whereNotNull('approved_at');

        if ($startDate) {
            $query->whereDate('approved_at', '>=', $startDate);
        } else {
            // Default to this month if no date range
            $query->whereMonth('approved_at', now()->month)
                ->whereYear('approved_at', now()->year);
        }

        if ($endDate) {
            $query->whereDate('approved_at', '<=', $endDate);
        }

        if ($locationId) {
            $query->whereHas('employee', function ($q) use ($locationId) {
                $q->where('location_id', $locationId);
            });
        }

        return $query->count();
    }

    private function getCompleteAttendance(?int $locationId, ?string $startDate, ?string $endDate): int
    {
        $query = Attendance::whereNotNull('time_in')
            ->whereNotNull('time_out');

        if ($startDate) {
            $query->whereDate('date', '>=', $startDate);
        } else {
            // Default to this month if no date range
            $query->whereMonth('date', now()->month)
                ->whereYear('date', now()->year);
        }

        if ($endDate) {
            $query->whereDate('date', '<=', $endDate);
        }

        if ($locationId) {
            $query->where('location_id', $locationId);
        }

        return $query->count();
    }

    private function getDateRangeDescription(?string $startDate, ?string $endDate): string
    {
        if ($startDate && $endDate) {
            return Carbon::parse($startDate)->format('d/m/Y') . ' - ' . Carbon::parse($endDate)->format('d/m/Y');
        } elseif ($startDate) {
            return 'Mulai ' . Carbon::parse($startDate)->format('d/m/Y');
        } elseif ($endDate) {
            return 'Sampai ' . Carbon::parse($endDate)->format('d/m/Y');
        }

        return 'Bulan ini';
    }

    private function getOvertimeDescription(?string $locationName, string $dateRangeDescription): string
    {
        $parts = array_filter([$locationName, $dateRangeDescription]);
        return !empty($parts) ? implode(' - ', $parts) : 'Bulan ini yang disetujui';
    }

    private function getLeaveDescription(?string $locationName, string $dateRangeDescription): string
    {
        $parts = array_filter([$locationName, $dateRangeDescription]);
        return !empty($parts) ? implode(' - ', $parts) : 'Bulan ini yang disetujui';
    }

    private function getAttendanceDescription(?string $locationName, string $dateRangeDescription): string
    {
        $parts = array_filter([$locationName, $dateRangeDescription]);
        return !empty($parts) ? implode(' - ', $parts) : 'Masuk & keluar bulan ini';
    }
}
