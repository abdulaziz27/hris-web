<?php

namespace App\Filament\Widgets;

use App\Models\Attendance;
use App\Models\Departemen;
use App\Models\Holiday;
use App\Models\Jabatan;
use App\Models\Leave;
use App\Models\Location;
use App\Models\ShiftKerja;
use App\Models\User;
use App\Support\WorkdayCalculator;
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
        $pageFilters = $this->getPageFiltersSafe();
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

            Stat::make('Total Departemen', Departemen::count())
                ->description('Jumlah departemen tersedia')
                ->descriptionIcon('heroicon-m-building-office')
                ->color('info'),

            Stat::make('Total Jabatan', Jabatan::count())
                ->description('Jumlah jabatan tersedia')
                ->descriptionIcon('heroicon-m-briefcase')
                ->color('info'),

            Stat::make('Total Shift Kerja', ShiftKerja::count())
                ->description('Jumlah shift kerja')
                ->descriptionIcon('heroicon-m-calendar')
                ->color('primary'),

            Stat::make('Hadir Tepat Waktu', $this->getOnTimeAttendance($locationId, $startDate, $endDate))
                ->description($this->getAttendanceStatsDescription($locationName, $dateRangeDescription, 'Absensi tepat waktu'))
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('success'),

            Stat::make('Terlambat', $this->getLateAttendance($locationId, $startDate, $endDate))
                ->description($this->getAttendanceStatsDescription($locationName, $dateRangeDescription, 'Absensi terlambat'))
                ->descriptionIcon('heroicon-m-clock')
                ->color('warning'),

            Stat::make('Tidak Hadir', $this->getAbsentAttendance($locationId, $startDate, $endDate))
                ->description($this->getAttendanceStatsDescription($locationName, $dateRangeDescription, 'Tidak hadir'))
                ->descriptionIcon('heroicon-m-x-circle')
                ->color('danger'),

            Stat::make('Cuti Disetujui', $this->getApprovedLeave($locationId, $startDate, $endDate))
                ->description($this->getLeaveDescription($locationName, $dateRangeDescription))
                ->descriptionIcon('heroicon-m-calendar-days')
                ->color('success'),
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

    private function getOnTimeAttendance(?int $locationId, ?string $startDate, ?string $endDate): int
    {
        $query = Attendance::where('status', 'on_time');

        // Jika kedua tanggal kosong, default ke hari ini
        if (! $startDate && ! $endDate) {
            $query->whereDate('date', today());
        } else {
            if ($startDate) {
                $query->whereDate('date', '>=', $startDate);
            }
            if ($endDate) {
                $query->whereDate('date', '<=', $endDate);
            }
        }

        if ($locationId) {
            $query->where('location_id', $locationId);
        }

        return $query->count();
    }

    private function getLateAttendance(?int $locationId, ?string $startDate, ?string $endDate): int
    {
        $query = Attendance::where('status', 'late');

        // Jika kedua tanggal kosong, default ke hari ini
        if (! $startDate && ! $endDate) {
            $query->whereDate('date', today());
        } else {
            if ($startDate) {
                $query->whereDate('date', '>=', $startDate);
            }
            if ($endDate) {
                $query->whereDate('date', '<=', $endDate);
            }
        }

        if ($locationId) {
            $query->where('location_id', $locationId);
        }

        return $query->count();
    }

    private function getAbsentAttendance(?int $locationId, ?string $startDate, ?string $endDate): int
    {
        // Tentukan periode tanggal
        if ($startDate && $endDate) {
            $start = Carbon::parse($startDate)->startOfDay();
            $end = Carbon::parse($endDate)->endOfDay();
        } else {
            // Default ke hari ini jika tidak ada date range
            $start = Carbon::today()->startOfDay();
            $end = Carbon::today()->endOfDay();
        }

        // Get total users yang seharusnya absen (berdasarkan lokasi)
        // Exclude user dengan role 'admin' atau tanpa location_id dari perhitungan
        $usersQuery = User::query()
            ->where('role', '!=', 'admin')
            ->whereNotNull('location_id');
        if ($locationId) {
            $usersQuery->where('location_id', $locationId);
        }
        $totalUsers = $usersQuery->count();

        if ($totalUsers == 0) {
            return 0;
        }

        // Hitung total tidak hadir per hari kerja
        $totalAbsent = 0;
        $current = $start->copy();

        // Get all holidays in the range for performance
        $holidays = Holiday::whereBetween('date', [$start->toDateString(), $end->toDateString()])
            ->pluck('date')
            ->map(fn ($date) => Carbon::parse($date)->toDateString())
            ->toArray();

        // Get all approved leaves in the range for performance
        // Format: ['date' => [user_id1, user_id2, ...]]
        $approvedLeaves = \App\Models\Leave::where('status', 'approved')
            ->where(function ($query) use ($start, $end) {
                $query->whereBetween('start_date', [$start->toDateString(), $end->toDateString()])
                    ->orWhereBetween('end_date', [$start->toDateString(), $end->toDateString()])
                    ->orWhere(function ($q) use ($start, $end) {
                        $q->where('start_date', '<=', $start->toDateString())
                          ->where('end_date', '>=', $end->toDateString());
                    });
            })
            ->get()
            ->flatMap(function ($leave) use ($start, $end) {
                // Generate all dates in leave range that overlap with our date range
                $leaveStart = Carbon::parse($leave->start_date);
                $leaveEnd = Carbon::parse($leave->end_date);
                $overlapStart = $leaveStart->greaterThan($start) ? $leaveStart : $start;
                $overlapEnd = $leaveEnd->lessThan($end) ? $leaveEnd : $end;
                
                $dates = [];
                $current = $overlapStart->copy();
                while ($current <= $overlapEnd) {
                    $dates[] = [
                        'date' => $current->toDateString(),
                        'user_id' => $leave->employee_id,
                    ];
                    $current->addDay();
                }
                return $dates;
            })
            ->groupBy('date')
            ->map(function ($group) {
                return $group->pluck('user_id')->unique()->toArray();
            })
            ->toArray();

        // Get all attendance records in the range for performance
        $attendanceQuery = Attendance::whereBetween('date', [$start->toDateString(), $end->toDateString()])
            ->whereNotNull('time_in');
        
        if ($locationId) {
            $attendanceQuery->where('location_id', $locationId);
        }
        
        // Group by date string (Y-m-d format) to ensure consistent key format
        $attendances = $attendanceQuery->get()
            ->groupBy(function ($attendance) {
                // Ensure date is formatted as Y-m-d string for consistent grouping
                return Carbon::parse($attendance->date)->toDateString();
            })
            ->map(function ($group) {
                return $group->pluck('user_id')->unique()->count();
            });

        while ($current <= $end) {
            $dateString = $current->toDateString();
            
            // Cek apakah hari kerja (bukan weekend, bukan holiday)
            $isWeekend = WorkdayCalculator::isWeekend($current, null, $locationId);
            $isHoliday = in_array($dateString, $holidays);
            
            if (!$isWeekend && !$isHoliday) {
                // Hari kerja - hitung yang tidak absen
                // Hitung yang sudah absen (ada record attendance dengan time_in tidak null)
                $attendedCount = $attendances->get($dateString, 0);
                
                // Get users on approved leave for this date
                $usersOnLeave = isset($approvedLeaves[$dateString]) ? count($approvedLeaves[$dateString]) : 0;
                
                // Tidak hadir = Total karyawan - yang sudah absen - yang sedang cuti
                // (User yang sedang cuti tidak dihitung sebagai tidak hadir)
                $absentToday = $totalUsers - $attendedCount - $usersOnLeave;
                
                if ($absentToday > 0) {
                    $totalAbsent += $absentToday;
                }
            }
            
            $current->addDay();
        }

        return $totalAbsent;
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

    private function getLeaveDescription(?string $locationName, string $dateRangeDescription): string
    {
        $parts = array_filter([$locationName, $dateRangeDescription]);
        return !empty($parts) ? implode(' - ', $parts) : 'Bulan ini yang disetujui';
    }

    private function getAttendanceStatsDescription(?string $locationName, string $dateRangeDescription, string $suffix): string
    {
        $parts = array_filter([$locationName, $dateRangeDescription]);
        return !empty($parts) ? implode(' - ', $parts) : $suffix . ' bulan ini';
    }

    private function getPageFiltersSafe(): array
    {
        try {
            if (method_exists($this, 'getPageFilters')) {
                return $this->getPageFilters() ?? [];
            } elseif (property_exists($this, 'pageFilters')) {
                return is_array($this->pageFilters ?? null) ? $this->pageFilters : [];
            }
        } catch (\Exception $e) {
            // If pageFilters is not initialized yet, return empty array
        }
        return [];
    }
}
