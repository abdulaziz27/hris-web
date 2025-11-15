<?php

namespace App\Services;

use App\Models\Attendance;
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
     * Default: 21 hari, tapi bisa dihitung otomatis atau manual input
     */
    public static function calculateStandardWorkdays(Carbon $start, Carbon $end, ?int $defaultDays = 21): int
    {
        // Jika start dan end adalah awal dan akhir bulan yang sama, hitung otomatis
        if ($start->isSameMonth($end) && $start->day === 1 && $end->isLastOfMonth()) {
            return WorkdayCalculator::countWorkdaysExcludingHolidays($start, $end);
        }

        // Fallback ke defaultDays jika tidak bisa dihitung otomatis
        return $defaultDays;
    }

    /**
     * Calculate total present days (include weekend kerja)
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
        // Check if it's a weekend
        if (WorkdayCalculator::isWeekend($date)) {
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
            $standardWorkdays = self::calculateStandardWorkdays($start, $end);
        }

        // Get Nilai HK from master data (prioritas: user.nilai_hk OR location.nilai_hk)
        $user = User::with('location')->find($userId);
        $nilaiHK = self::getNilaiHK($userId, $user->location_id ?? null);

        // Allow nilai_hk = 0 (akan diisi nanti, tidak throw exception)
        // Salary fields akan dihitung sebagai 0 jika nilai_hk = 0

        // Calculate present days (with location timezone awareness)
        $presentDays = self::calculatePresentDays($userId, $start, $end, $user->location_id ?? null);

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

