<?php

namespace App\Support;

use App\Models\Holiday;
use App\Services\TimezoneService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class WorkdayCalculator
{
    /**
     * Check if the given date is a weekend (Saturday or Sunday).
     * 
     * @param Carbon $date
     * @param int|null $locationId Optional location ID for timezone-aware calculation
     */
    public static function isWeekend(Carbon $date, ?int $locationId = null): bool
    {
        // ✅ Convert date ke timezone lokasi sebelum cek weekend
        if ($locationId) {
            $date = $date->copy()->setTimezone(
                TimezoneService::getLocationTimezone($locationId)
            );
        }
        
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
     * @param int|null $locationId Optional location ID for timezone-aware calculation
     */
    public static function isNonWorkingDay(Carbon $date, ?int $locationId = null): bool
    {
        return self::isWeekend($date, $locationId) || self::isHoliday($date, $locationId);
    }

    /**
     * Count working days (excluding weekends and holidays) between two dates.
     */
    public static function countWorkdaysExcludingHolidays(Carbon $start, Carbon $end): int
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
            if (! $current->isWeekend() && ! in_array($current->toDateString(), $holidays)) {
                $workdays++;
            }
            $current->addDay();
        }

        return $workdays;
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
