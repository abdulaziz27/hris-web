<?php

namespace App\Support;

use App\Models\Holiday;
use App\Models\Location;
use App\Models\User;
use App\Services\TimezoneService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class WorkdayCalculator
{
    /**
     * Check if the given date is a weekend based on user workdays_per_week or location pattern.
     * 
     * @param Carbon $date
     * @param int|null $userId Optional user ID for user-specific weekend (prioritas tertinggi)
     * @param int|null $locationId Optional location ID for timezone-aware calculation and location weekend pattern
     * @return bool
     */
    public static function isWeekend(Carbon $date, ?int $userId = null, ?int $locationId = null): bool
    {
        // ✅ Convert date ke timezone lokasi sebelum cek weekend
        if ($locationId) {
            $date = $date->copy()->setTimezone(
                TimezoneService::getLocationTimezone($locationId)
            );
        }
        
        // Prioritas 1: Cek workdays_per_week user jika userId provided
        if ($userId) {
            $user = User::find($userId);
            if ($user && $user->workdays_per_week) {
                $dayOfWeek = $date->dayOfWeek; // 0 = Sunday, 6 = Saturday
                
                if ($user->workdays_per_week == 5) {
                    // 5 hari kerja: Sabtu-Minggu = weekend
                    return $dayOfWeek == 0 || $dayOfWeek == 6;
                } elseif ($user->workdays_per_week == 6) {
                    // 6 hari kerja: Minggu = weekend
                    return $dayOfWeek == 0;
                } elseif ($user->workdays_per_week == 7) {
                    // 7 hari kerja: Tidak ada weekend
                    return false;
                }
            }
        }
        
        // Prioritas 2: Get location weekend pattern if locationId provided (untuk backward compatibility)
        if ($locationId) {
            $location = Location::find($locationId);
            if ($location && $location->weekend_pattern && !empty($location->weekend_pattern)) {
                // Use location-specific weekend pattern
                $dayName = strtolower($date->format('l')); // monday, tuesday, etc.
                
                // Check if this day is in the weekend pattern
                $weekendPattern = array_map('strtolower', $location->weekend_pattern);
                return in_array($dayName, $weekendPattern);
            }
        }
        
        // Default: standard weekend (Saturday and Sunday)
        return $date->isWeekend();
    }

    /**
     * Check if the given date is a holiday.
     * 
     * @param Carbon $date
     * @param int|null $locationId Optional location ID for timezone-aware calculation
     */
    public static function isHoliday(Carbon $date, ?int $locationId = null): bool
    {
        // ✅ Convert date ke timezone lokasi sebelum cek holiday
        if ($locationId) {
            $date = $date->copy()->setTimezone(
                TimezoneService::getLocationTimezone($locationId)
            );
        }
        
        return Holiday::where('date', $date->toDateString())->exists();
    }

    /**
     * Check if the given date is a non-working day (weekend or holiday).
     * 
     * @param Carbon $date
     * @param int|null $userId Optional user ID for user-specific weekend
     * @param int|null $locationId Optional location ID for timezone-aware calculation
     */
    public static function isNonWorkingDay(Carbon $date, ?int $userId = null, ?int $locationId = null): bool
    {
        return self::isWeekend($date, $userId, $locationId) || self::isHoliday($date, $locationId);
    }

    /**
     * Count working days (excluding weekends and holidays) between two dates.
     * 
     * @param Carbon $start
     * @param Carbon $end
     * @param int|null $userId Optional user ID for user-specific weekend
     * @param int|null $locationId Optional location ID for location-specific weekend pattern
     * @return int
     */
    public static function countWorkdaysExcludingHolidays(Carbon $start, Carbon $end, ?int $userId = null, ?int $locationId = null): int
    {
        $workdays = 0;
        $current = $start->copy()->startOfDay();
        $endDate = $end->copy()->startOfDay();

        // Get all holidays in the range for performance
        $holidays = Holiday::whereBetween('date', [$current->toDateString(), $endDate->toDateString()])
            ->pluck('date')
            ->map(fn ($date) => Carbon::parse($date)->toDateString())
            ->toArray();

        while ($current <= $endDate) {
            if (! self::isWeekend($current, $userId, $locationId) && ! in_array($current->toDateString(), $holidays)) {
                $workdays++;
            }
            $current->addDay();
        }

        return $workdays;
    }
    
    /**
     * Count total days in range (including weekends).
     * 
     * @param Carbon $start
     * @param Carbon $end
     * @return int
     */
    public static function countTotalDays(Carbon $start, Carbon $end): int
    {
        return $start->copy()->startOfDay()->diffInDays($end->copy()->startOfDay()) + 1;
    }
    
    /**
     * Count weekend days in range based on user workdays_per_week or location pattern.
     * 
     * @param Carbon $start
     * @param Carbon $end
     * @param int|null $userId Optional user ID for user-specific weekend
     * @param int|null $locationId Optional location ID for location-specific weekend pattern
     * @return int
     */
    public static function countWeekendDays(Carbon $start, Carbon $end, ?int $userId = null, ?int $locationId = null): int
    {
        $weekendDays = 0;
        $current = $start->copy()->startOfDay();
        $endDate = $end->copy()->startOfDay();

        while ($current <= $endDate) {
            if (self::isWeekend($current, $userId, $locationId)) {
                $weekendDays++;
            }
            $current->addDay();
        }

        return $weekendDays;
    }

    /**
     * Count standard workdays for a specific month (exclude weekends and holidays).
     */
    public static function countStandardWorkdaysForMonth(Carbon $date): int
    {
        $start = $date->copy()->startOfMonth();
        $end = $date->copy()->endOfMonth();

        return self::countWorkdaysExcludingHolidays($start, $end);
    }

    /**
     * Generate weekend holidays for a given year.
     *
     * @return array{inserted: int, skipped: int}
     */
    public static function generateWeekendForYear(int $year): array
    {
        $inserted = 0;
        $skipped = 0;

        $start = Carbon::create($year, 1, 1)->startOfDay();
        $end = Carbon::create($year, 12, 31)->endOfDay();

        // Get existing dates to avoid duplicates
        $existingDates = Holiday::whereYear('date', $year)
            ->pluck('date')
            ->map(fn ($date) => Carbon::parse($date)->toDateString())
            ->toArray();

        $weekendDates = [];
        $current = $start->copy();

        while ($current <= $end) {
            if ($current->isWeekend()) {
                $dateString = $current->toDateString();

                if (in_array($dateString, $existingDates)) {
                    $skipped++;
                } else {
                    $weekendDates[] = [
                        'date' => $dateString,
                        'name' => 'Weekend',
                        'type' => Holiday::TYPE_WEEKEND,
                        'is_official' => false,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                    $inserted++;
                }
            }
            $current->addDay();
        }

        if (! empty($weekendDates)) {
            DB::table('holidays')->insert($weekendDates);
        }

        return [
            'inserted' => $inserted,
            'skipped' => $skipped,
        ];
    }
}
