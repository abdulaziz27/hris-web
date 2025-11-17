<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\Holiday;
use App\Models\Leave;
use App\Models\User;
use App\Services\TimezoneService;
use App\Support\WorkdayCalculator;
use Carbon\Carbon;

class PayrollCalculator
{
    /**
     * Calculate basic salary from nilai_hk × standard_workdays (untuk informasi/reporting)
     */
    public static function calculateBasicSalary(float $nilaiHK, int $standardWorkdays): float
    {
        if ($standardWorkdays <= 0) {
            return 0;
        }

        return round($nilaiHK * $standardWorkdays, 2);
    }

    /**
     * Calculate standard workdays for a month (exclude weekend/holiday)
     * 
     * @param Carbon $start
     * @param Carbon $end
     * @param int|null $userId Optional user ID for user-specific weekend and standard_workdays_per_month
     * @param int|null $defaultDays Fallback default days if cannot calculate
     * @return int
     */
    public static function calculateStandardWorkdays(Carbon $start, Carbon $end, ?int $userId = null, ?int $defaultDays = 21): int
    {
        // Prioritas 1: standard_workdays_per_month user (manual override)
        if ($userId) {
            $user = User::find($userId);
            if ($user && $user->standard_workdays_per_month !== null) {
                return $user->standard_workdays_per_month;
            }
        }
        
        // Prioritas 2: Hitung dari workdays_per_week user
        if ($userId) {
            $user = User::find($userId);
            if ($user && $user->workdays_per_week) {
                // Jika start dan end adalah awal dan akhir bulan yang sama
                if ($start->isSameMonth($end) && $start->day === 1 && $end->isLastOfMonth()) {
                    return self::calculateStandardWorkdaysFromWeekly($start, $user->workdays_per_week);
                }
            }
        }
        
        // Prioritas 3: Hitung otomatis (exclude weekend standard + holiday)
        if ($start->isSameMonth($end) && $start->day === 1 && $end->isLastOfMonth()) {
            return WorkdayCalculator::countWorkdaysExcludingHolidays($start, $end, $userId);
        }

        // Fallback ke defaultDays jika tidak bisa dihitung otomatis
        return $defaultDays;
    }

    /**
     * Calculate standard workdays from workdays_per_week for a specific month
     * 
     * @param Carbon $month First day of month
     * @param int $workdaysPerWeek Number of workdays per week (5, 6, or 7)
     * @return int
     */
    public static function calculateStandardWorkdaysFromWeekly(Carbon $month, int $workdaysPerWeek): int
    {
        $start = $month->copy()->startOfMonth();
        $end = $month->copy()->endOfMonth();
        
        $totalDays = 0;
        $current = $start->copy();
        
        // Get holidays in month
        $holidays = Holiday::whereBetween('date', [$start->toDateString(), $end->toDateString()])
            ->pluck('date')
            ->map(fn ($date) => Carbon::parse($date)->toDateString())
            ->toArray();
        
        while ($current <= $end) {
            $dayOfWeek = $current->dayOfWeek; // 0 = Sunday, 6 = Saturday
            
            // Check berdasarkan workdays_per_week
            $isWorkday = false;
            if ($workdaysPerWeek == 5) {
                // 5 hari: Senin-Jumat (1-5)
                $isWorkday = $dayOfWeek >= 1 && $dayOfWeek <= 5;
            } elseif ($workdaysPerWeek == 6) {
                // 6 hari: Senin-Sabtu (1-6)
                $isWorkday = $dayOfWeek >= 1 && $dayOfWeek <= 6;
            } elseif ($workdaysPerWeek == 7) {
                // 7 hari: Semua hari
                $isWorkday = true;
            }
            
            // Exclude holiday
            if ($isWorkday && !in_array($current->toDateString(), $holidays)) {
                $totalDays++;
            }
            
            $current->addDay();
        }
        
        return $totalDays;
    }

    /**
     * Calculate total present days from attendance only (include weekend kerja)
     * 
     * @param int $userId
     * @param Carbon $start Start date (should be in location timezone)
     * @param Carbon $end End date (should be in location timezone)
     * @param int|null $locationId Optional location ID for timezone-aware query
     */
    public static function calculatePresentDays(int $userId, Carbon $start, Carbon $end, ?int $locationId = null): int
    {
        // ✅ Convert start/end ke timezone lokasi untuk query yang akurat
        if ($locationId) {
            $locationTimezone = TimezoneService::getLocationTimezone($locationId);
            $startInLocation = $start->copy()->setTimezone($locationTimezone);
            $endInLocation = $end->copy()->setTimezone($locationTimezone);
        } else {
            $startInLocation = $start;
            $endInLocation = $end;
        }
        
        return Attendance::where('user_id', $userId)
            ->whereBetween('date', [
                $startInLocation->toDateString(), 
                $endInLocation->toDateString()
            ])
            ->whereNotNull('time_in') // Hanya yang sudah check-in
            ->count();
    }

    /**
     * Calculate paid leave days for a period by leave type name pattern
     * Only counts approved leaves with is_paid = true
     * Excludes days where user has attendance (prioritize attendance over leave)
     * 
     * @param int $userId
     * @param Carbon $start Start date
     * @param Carbon $end End date
     * @param string|null $leaveTypeNamePattern Optional pattern to filter leave type name (e.g., 'Cuti', 'Sakit')
     * @return int Total paid leave days
     */
    public static function calculatePaidLeaveDays(int $userId, Carbon $start, Carbon $end, ?string $leaveTypeNamePattern = null): int
    {
        // Get all approved paid leaves that overlap with the period
        $leaves = Leave::where('employee_id', $userId)
            ->where('status', 'approved')
            ->whereHas('leaveType', function($query) use ($leaveTypeNamePattern) {
                $query->where('is_paid', true);
                // Filter by leave type name pattern if provided
                if ($leaveTypeNamePattern) {
                    $query->where('name', 'like', '%' . $leaveTypeNamePattern . '%');
                }
            })
            ->where(function($query) use ($start, $end) {
                // Leave overlaps with period if:
                // 1. Leave start is within period
                // 2. Leave end is within period
                // 3. Leave covers entire period
                $query->where(function($q) use ($start, $end) {
                    $q->whereBetween('start_date', [$start->toDateString(), $end->toDateString()]);
                })->orWhere(function($q) use ($start, $end) {
                    $q->whereBetween('end_date', [$start->toDateString(), $end->toDateString()]);
                })->orWhere(function($q) use ($start, $end) {
                    $q->where('start_date', '<=', $start->toDateString())
                      ->where('end_date', '>=', $end->toDateString());
                });
            })
            ->with('leaveType')
            ->get();

        if ($leaves->isEmpty()) {
            return 0;
        }

        // Get dates where user has attendance (to exclude from leave calculation)
        $attendanceDates = Attendance::where('user_id', $userId)
            ->whereBetween('date', [$start->toDateString(), $end->toDateString()])
            ->whereNotNull('time_in')
            ->pluck('date')
            ->map(function($date) {
                return Carbon::parse($date)->format('Y-m-d');
            })
            ->toArray();

        $totalPaidLeaveDays = 0;

        foreach ($leaves as $leave) {
            $leaveStart = Carbon::parse($leave->start_date);
            $leaveEnd = Carbon::parse($leave->end_date);
            
            // Calculate overlap between leave and period
            $overlapStart = $leaveStart->greaterThan($start) ? $leaveStart : $start;
            $overlapEnd = $leaveEnd->lessThan($end) ? $leaveEnd : $end;

            // Count workdays in overlap, excluding dates with attendance
            $leaveDaysInPeriod = 0;
            $current = $overlapStart->copy();

            while ($current->lte($overlapEnd)) {
                $dateStr = $current->format('Y-m-d');
                
                // Only count workdays (exclude weekend/holiday)
                if (!WorkdayCalculator::isWeekend($current) && !WorkdayCalculator::isHoliday($current)) {
                    // Only count if no attendance on this date (prioritize attendance)
                    if (!in_array($dateStr, $attendanceDates)) {
                        $leaveDaysInPeriod++;
                    }
                }
                
                $current->addDay();
            }

            $totalPaidLeaveDays += max(0, $leaveDaysInPeriod);
        }

        return $totalPaidLeaveDays;
    }

    /**
     * Calculate cuti (annual leave) days for a period
     * Only counts approved paid leaves with name containing "Cuti" (but not "Sakit")
     * 
     * @param int $userId
     * @param Carbon $start Start date
     * @param Carbon $end End date
     * @return int Total cuti days
     */
    public static function calculateCutiDays(int $userId, Carbon $start, Carbon $end): int
    {
        // Get leaves with name containing "Cuti" but not "Sakit"
        $leaves = Leave::where('employee_id', $userId)
            ->where('status', 'approved')
            ->whereHas('leaveType', function($query) {
                $query->where('is_paid', true)
                      ->where('name', 'like', '%Cuti%')
                      ->where('name', 'not like', '%Sakit%');
            })
            ->where(function($query) use ($start, $end) {
                $query->where(function($q) use ($start, $end) {
                    $q->whereBetween('start_date', [$start->toDateString(), $end->toDateString()]);
                })->orWhere(function($q) use ($start, $end) {
                    $q->whereBetween('end_date', [$start->toDateString(), $end->toDateString()]);
                })->orWhere(function($q) use ($start, $end) {
                    $q->where('start_date', '<=', $start->toDateString())
                      ->where('end_date', '>=', $end->toDateString());
                });
            })
            ->with('leaveType')
            ->get();

        if ($leaves->isEmpty()) {
            return 0;
        }

        // Get dates where user has attendance (to exclude from leave calculation)
        $attendanceDates = Attendance::where('user_id', $userId)
            ->whereBetween('date', [$start->toDateString(), $end->toDateString()])
            ->whereNotNull('time_in')
            ->pluck('date')
            ->map(function($date) {
                return Carbon::parse($date)->format('Y-m-d');
            })
            ->toArray();

        $totalCutiDays = 0;

        foreach ($leaves as $leave) {
            $leaveStart = Carbon::parse($leave->start_date);
            $leaveEnd = Carbon::parse($leave->end_date);
            
            // Calculate overlap between leave and period
            $overlapStart = $leaveStart->greaterThan($start) ? $leaveStart : $start;
            $overlapEnd = $leaveEnd->lessThan($end) ? $leaveEnd : $end;

            // Count workdays in overlap, excluding dates with attendance
            $cutiDaysInPeriod = 0;
            $current = $overlapStart->copy();

            while ($current->lte($overlapEnd)) {
                $dateStr = $current->format('Y-m-d');
                
                // Only count workdays (exclude weekend/holiday)
                if (!WorkdayCalculator::isWeekend($current) && !WorkdayCalculator::isHoliday($current)) {
                    // Only count if no attendance on this date (prioritize attendance)
                    if (!in_array($dateStr, $attendanceDates)) {
                        $cutiDaysInPeriod++;
                    }
                }
                
                $current->addDay();
            }

            $totalCutiDays += max(0, $cutiDaysInPeriod);
        }

        return $totalCutiDays;
    }

    /**
     * Calculate sakit (sick leave) days for a period
     * Only counts approved paid leaves with name containing "Sakit"
     * 
     * @param int $userId
     * @param Carbon $start Start date
     * @param Carbon $end End date
     * @return int Total sakit days
     */
    public static function calculateSakitDays(int $userId, Carbon $start, Carbon $end): int
    {
        // Get leaves with name containing "Sakit"
        $leaves = Leave::where('employee_id', $userId)
            ->where('status', 'approved')
            ->whereHas('leaveType', function($query) {
                $query->where('is_paid', true)
                      ->where('name', 'like', '%Sakit%');
            })
            ->where(function($query) use ($start, $end) {
                $query->where(function($q) use ($start, $end) {
                    $q->whereBetween('start_date', [$start->toDateString(), $end->toDateString()]);
                })->orWhere(function($q) use ($start, $end) {
                    $q->whereBetween('end_date', [$start->toDateString(), $end->toDateString()]);
                })->orWhere(function($q) use ($start, $end) {
                    $q->where('start_date', '<=', $start->toDateString())
                      ->where('end_date', '>=', $end->toDateString());
                });
            })
            ->with('leaveType')
            ->get();

        if ($leaves->isEmpty()) {
            return 0;
        }

        // Get dates where user has attendance (to exclude from leave calculation)
        $attendanceDates = Attendance::where('user_id', $userId)
            ->whereBetween('date', [$start->toDateString(), $end->toDateString()])
            ->whereNotNull('time_in')
            ->pluck('date')
            ->map(function($date) {
                return Carbon::parse($date)->format('Y-m-d');
            })
            ->toArray();

        $totalSakitDays = 0;

        foreach ($leaves as $leave) {
            $leaveStart = Carbon::parse($leave->start_date);
            $leaveEnd = Carbon::parse($leave->end_date);
            
            // Calculate overlap between leave and period
            $overlapStart = $leaveStart->greaterThan($start) ? $leaveStart : $start;
            $overlapEnd = $leaveEnd->lessThan($end) ? $leaveEnd : $end;

            // Count workdays in overlap, excluding dates with attendance
            $sakitDaysInPeriod = 0;
            $current = $overlapStart->copy();

            while ($current->lte($overlapEnd)) {
                $dateStr = $current->format('Y-m-d');
                
                // Only count workdays (exclude weekend/holiday)
                if (!WorkdayCalculator::isWeekend($current) && !WorkdayCalculator::isHoliday($current)) {
                    // Only count if no attendance on this date (prioritize attendance)
                    if (!in_array($dateStr, $attendanceDates)) {
                        $sakitDaysInPeriod++;
                    }
                }
                
                $current->addDay();
            }

            $totalSakitDays += max(0, $sakitDaysInPeriod);
        }

        return $totalSakitDays;
    }

    /**
     * Calculate effective present days (attendance + paid leave)
     * This is the actual days that should be used for payroll calculation
     * 
     * @param int $userId
     * @param Carbon $start Start date (should be in location timezone)
     * @param Carbon $end End date (should be in location timezone)
     * @param int|null $locationId Optional location ID for timezone-aware query
     * @return int Effective present days (attendance + paid leave)
     */
    public static function calculateEffectivePresentDays(int $userId, Carbon $start, Carbon $end, ?int $locationId = null): int
    {
        $attendanceDays = self::calculatePresentDays($userId, $start, $end, $locationId);
        $cutiDays = self::calculateCutiDays($userId, $start, $end);
        $sakitDays = self::calculateSakitDays($userId, $start, $end);
        
        return $attendanceDays + $cutiDays + $sakitDays;
    }

    /**
     * Get Nilai HK from master data
     * 
     * Prioritas:
     * 1. user.nilai_hk (jika ada, untuk override kasus khusus)
     * 2. location.nilai_hk (default dari lokasi)
     * 
     * Return 0 jika tidak ada di master data
     */
    public static function getNilaiHK(int $userId, ?int $locationId = null): float
    {
        $user = User::with('location')->find($userId);
        
        if (! $user) {
            return 0;
        }

        // Prioritas 1: User punya nilai_hk sendiri (override untuk kasus khusus)
        if ($user->nilai_hk !== null && $user->nilai_hk > 0) {
            return (float) $user->nilai_hk;
        }

        // Prioritas 2: Pakai nilai_hk dari location (default)
        if ($user->location && $user->location->nilai_hk !== null && $user->location->nilai_hk > 0) {
            return (float) $user->location->nilai_hk;
        }

        // Jika tidak ada data, return 0
        return 0;
    }

    /**
     * Calculate Nilai HK = Basic Salary / Standard Workdays (DEPRECATED - use getNilaiHK instead)
     * @deprecated Use getNilaiHK() instead which gets from master data
     */
    public static function calculateNilaiHK(float $basicSalary, int $standardWorkdays): float
    {
        if ($standardWorkdays <= 0) {
            return 0;
        }

        return round($basicSalary / $standardWorkdays, 2);
    }

    /**
     * Calculate Estimated Salary = Nilai HK × Present Days
     */
    public static function calculateEstimatedSalary(float $nilaiHK, int $presentDays): float
    {
        return round($nilaiHK * $presentDays, 2);
    }

    /**
     * Calculate Final Salary = Nilai HK × HK Review
     */
    public static function calculateFinalSalary(float $nilaiHK, int $hkReview): float
    {
        return round($nilaiHK * $hkReview, 2);
    }

    /**
     * Calculate Percentage = (Present Days / Standard Workdays) × 100%
     */
    public static function calculatePercentage(int $presentDays, int $standardWorkdays): float
    {
        if ($standardWorkdays <= 0) {
            return 0;
        }

        return round(($presentDays / $standardWorkdays) * 100, 2);
    }

    /**
     * Calculate Selisih HK = HK Review - Standard Workdays
     */
    public static function calculateSelisihHK(int $hkReview, int $standardWorkdays): int
    {
        return $hkReview - $standardWorkdays;
    }

    /**
     * Get daily attendance status for a specific date
     * Returns: 'H' (Hadir), 'A' (Absen), 'L' (Libur), 'W' (Weekend)
     */
    public static function getDailyAttendanceStatus(int $userId, Carbon $date): string
    {
        // Check if it's a weekend (based on user workdays_per_week)
        if (WorkdayCalculator::isWeekend($date, $userId)) {
            // Check if user worked on weekend
            $attendance = Attendance::where('user_id', $userId)
                ->whereDate('date', $date->toDateString())
                ->whereNotNull('time_in')
                ->first();
            
            if ($attendance) {
                return 'H'; // Hadir di weekend
            }
            
            return 'W'; // Weekend (tidak kerja)
        }

        // Check if it's a holiday
        if (WorkdayCalculator::isHoliday($date)) {
            // Check if user worked on holiday
            $attendance = Attendance::where('user_id', $userId)
                ->whereDate('date', $date->toDateString())
                ->whereNotNull('time_in')
                ->first();
            
            if ($attendance) {
                return 'H'; // Hadir di hari libur
            }
            
            return 'L'; // Libur
        }

        // Regular workday - check attendance
        $attendance = Attendance::where('user_id', $userId)
            ->whereDate('date', $date->toDateString())
            ->whereNotNull('time_in')
            ->first();

        return $attendance ? 'H' : 'A'; // Hadir or Absen
    }

    /**
     * Generate complete monthly payroll data for a user
     */
    public static function generateMonthlyPayroll(int $userId, Carbon $period, ?int $standardWorkdays = null): array
    {
        // Ensure period is first day of month
        $start = $period->copy()->startOfMonth();
        $end = $period->copy()->endOfMonth();

        // Calculate standard workdays (use provided or auto-calculate)
        if ($standardWorkdays === null) {
            $standardWorkdays = self::calculateStandardWorkdays($start, $end, $userId);
        }

        // Get Nilai HK from master data (prioritas: user.nilai_hk OR location.nilai_hk)
        $user = User::with('location')->find($userId);
        $nilaiHK = self::getNilaiHK($userId, $user->location_id ?? null);

        // Allow nilai_hk = 0 (akan diisi nanti, tidak throw exception)
        // Salary fields akan dihitung sebagai 0 jika nilai_hk = 0

        // Calculate effective present days (attendance + paid leave)
        // This includes both actual attendance and approved paid leave days
        $presentDays = self::calculateEffectivePresentDays($userId, $start, $end, $user->location_id ?? null);

        // Calculate basic salary from nilai_hk × standard_workdays (untuk informasi/reporting)
        // If nilai_hk = 0, basic_salary = 0
        $basicSalary = $nilaiHK > 0 ? self::calculateBasicSalary($nilaiHK, $standardWorkdays) : 0;

        // Calculate estimated salary
        // If nilai_hk = 0, estimated_salary = 0
        $estimatedSalary = $nilaiHK > 0 ? self::calculateEstimatedSalary($nilaiHK, $presentDays) : 0;

        // Default HK Review = Present Days
        $hkReview = $presentDays;

        // Calculate final salary
        // If nilai_hk = 0, final_salary = 0
        $finalSalary = $nilaiHK > 0 ? self::calculateFinalSalary($nilaiHK, $hkReview) : 0;

        // Calculate percentage
        $percentage = self::calculatePercentage($presentDays, $standardWorkdays);

        // Calculate selisih HK
        $selisihHK = self::calculateSelisihHK($hkReview, $standardWorkdays);

        // Get daily status for all days in month
        $dailyStatus = [];
        $current = $start->copy();
        while ($current <= $end) {
            $dailyStatus[$current->format('Y-m-d')] = self::getDailyAttendanceStatus($userId, $current);
            $current->addDay();
        }

        return [
            'user_id' => $userId,
            'period' => $start->toDateString(),
            'standard_workdays' => $standardWorkdays,
            'present_days' => $presentDays,
            'hk_review' => $hkReview,
            'nilai_hk' => $nilaiHK,
            'basic_salary' => $basicSalary,
            'estimated_salary' => $estimatedSalary,
            'final_salary' => $finalSalary,
            'selisih_hk' => $selisihHK,
            'percentage' => $percentage,
            'daily_status' => $dailyStatus,
        ];
    }
}

