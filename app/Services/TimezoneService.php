<?php

namespace App\Services;

use Carbon\Carbon;
use App\Models\Location;

class TimezoneService
{
    /**
     * Get current datetime in location timezone
     */
    public static function nowInLocation(?int $locationId): Carbon
    {
        $timezone = self::getLocationTimezone($locationId);
        return Carbon::now($timezone);
    }

    /**
     * Convert UTC datetime to location timezone
     */
    public static function toLocationTimezone(Carbon $datetime, ?int $locationId): Carbon
    {
        $timezone = self::getLocationTimezone($locationId);
        return $datetime->copy()->setTimezone($timezone);
    }

    /**
     * Convert location datetime to UTC for database storage
     */
    public static function toUTC(Carbon $datetime, ?int $locationId): Carbon
    {
        $timezone = self::getLocationTimezone($locationId);
        return $datetime->copy()->setTimezone('UTC');
    }

    /**
     * Get timezone string for location
     */
    public static function getLocationTimezone(?int $locationId): string
    {
        if (!$locationId) {
            return config('app.timezone', 'Asia/Jakarta');
        }

        $location = Location::find($locationId);
        return $location?->timezone ?? config('app.timezone', 'Asia/Jakarta');
    }

    /**
     * Get date string in location timezone
     */
    public static function getDateInLocation(?int $locationId): string
    {
        return self::nowInLocation($locationId)->toDateString();
    }

    /**
     * Get time string in location timezone
     */
    public static function getTimeInLocation(?int $locationId): string
    {
        return self::nowInLocation($locationId)->toTimeString();
    }

    /**
     * Create Carbon instance from date string in location timezone
     */
    public static function createFromDateString(string $dateString, ?int $locationId): Carbon
    {
        $timezone = self::getLocationTimezone($locationId);
        return Carbon::createFromFormat('Y-m-d', $dateString, $timezone)->startOfDay();
    }

    /**
     * Create Carbon instance from datetime string in location timezone
     */
    public static function createFromDateTimeString(string $dateTimeString, ?int $locationId): Carbon
    {
        $timezone = self::getLocationTimezone($locationId);
        return Carbon::createFromFormat('Y-m-d H:i:s', $dateTimeString, $timezone);
    }
}

