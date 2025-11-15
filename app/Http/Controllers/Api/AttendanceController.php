<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\ShiftAssignment;
use App\Models\ShiftKerja;
use App\Services\TimezoneService;
use App\Support\WorkdayCalculator;
use Carbon\Carbon;
use Illuminate\Http\Request;

class AttendanceController extends Controller
{
    // checkin
    public function checkin(Request $request)
    {
        // validate lat and long
        $request->validate([
            'latitude' => 'required',
            'longitude' => 'required',
            'location_id' => 'nullable|exists:locations,id',
        ]);

        $currentUser = $request->user();

        // Determine location for geofence validation
        $locationId = $request->location_id ?? $currentUser->location_id;

        if (! $locationId) {
            return response([
                'message' => 'Lokasi kerja belum ditentukan. Hubungi admin untuk menetapkan lokasi Anda.',
            ], 400);
        }

        $location = \App\Models\Location::find($locationId);

        if (! $location || ! $location->is_active) {
            return response([
                'message' => 'Lokasi tidak valid atau tidak aktif.',
            ], 400);
        }

        // Validate geofence: calculate distance (Haversine formula)
        $distance = $this->calculateDistance(
            (float) $request->latitude,
            (float) $request->longitude,
            (float) $location->latitude,
            (float) $location->longitude
        );

        if ($distance > (float) $location->radius_km) {
            return response([
                'message' => 'Anda berada di luar radius lokasi kerja. Jarak Anda: '.number_format($distance, 2).' km, Maksimal: '.$location->radius_km.' km',
                'distance' => $distance,
                'max_radius' => $location->radius_km,
                'location_name' => $location->name,
            ], 400);
        }
        
        // ✅ Gunakan timezone lokasi, bukan app timezone
        $currentDateTime = TimezoneService::nowInLocation($location->id);

        $scheduledShiftId = ShiftAssignment::query()
            ->forUser($currentUser->id)
            ->forDate($currentDateTime)
            ->scheduled()
            ->value('shift_id');

        $resolvedShiftId = $scheduledShiftId ?? $currentUser->shift_kerja_id;
        $activeShift = $resolvedShiftId ? ShiftKerja::query()->find($resolvedShiftId) : null;

        // ✅ Pass location_id untuk timezone-aware calculation
        $isWeekend = WorkdayCalculator::isWeekend($currentDateTime->copy(), $location->id);
        $isHoliday = WorkdayCalculator::isHoliday($currentDateTime->copy(), $location->id);

        $status = 'on_time';
        $lateMinutes = 0;

        if ($activeShift) {
            $startTimeString = $activeShift->getRawOriginal('start_time') ?? $activeShift->start_time?->format('H:i:s');

            if ($startTimeString) {
                $normalizedStartTime = strlen($startTimeString) === 5 ? $startTimeString.':00' : $startTimeString;
                
                // ✅ Buat shift start dalam timezone lokasi
                $shiftStart = Carbon::createFromFormat(
                    'Y-m-d H:i:s',
                    $currentDateTime->toDateString().' '.$normalizedStartTime,
                    TimezoneService::getLocationTimezone($location->id)
                );

                if ($activeShift->is_cross_day && $currentDateTime->lessThan($shiftStart)) {
                    $shiftStart->subDay();
                }

                $graceMinutes = (int) ($activeShift->grace_period_minutes ?? 0);
                $lateThreshold = $shiftStart->copy()->addMinutes($graceMinutes);

                if ($currentDateTime->greaterThan($lateThreshold)) {
                    $status = 'late';
                    $lateMinutes = (int) $lateThreshold->diffInMinutes($currentDateTime);
                }
            }
        }

        $attendance = new Attendance;
        $attendance->user_id = $currentUser->id;
        $attendance->shift_id = $activeShift?->id;
        $attendance->location_id = $location->id;
        $attendance->date = $currentDateTime->toDateString();
        $attendance->time_in = $currentDateTime->toTimeString();
        $attendance->latlon_in = $request->latitude.','.$request->longitude;
        $attendance->status = $status;
        $attendance->is_weekend = $isWeekend;
        $attendance->is_holiday = $isHoliday;
        $attendance->holiday_work = $activeShift ? ($isWeekend || $isHoliday) : false;
        $attendance->late_minutes = $lateMinutes;
        $attendance->save();

        return response([
            'message' => 'Checkin success',
            'attendance' => $attendance,
        ], 200);
    }

    // checkout
    public function checkout(Request $request)
    {
        // validate lat and long
        $request->validate([
            'latitude' => 'required',
            'longitude' => 'required',
        ]);

        // ✅ Get today attendance using user's location timezone
        $user = $request->user();
        $today = TimezoneService::nowInLocation($user->location_id);

        $attendance = Attendance::where('user_id', $user->id)
            ->whereDate('date', $today->toDateString())
            ->first();

        // check if attendance not found
        if (! $attendance) {
            return response(['message' => 'Checkin first'], 400);
        }

        // ✅ Save checkout using location timezone
        $user = $request->user();
        $currentTime = TimezoneService::nowInLocation($user->location_id);
        $attendance->time_out = $currentTime->toTimeString();
        $attendance->latlon_out = $request->latitude.','.$request->longitude;
        $attendance->save();

        return response([
            'message' => 'Checkout success',
            'attendance' => $attendance,
        ], 200);
    }

    // check is checkedin
    public function isCheckedin(Request $request)
    {
        // ✅ Get today attendance using user's location timezone
        $user = $request->user();
        $today = TimezoneService::nowInLocation($user->location_id);
        
        $attendance = Attendance::where('user_id', $user->id)
            ->whereDate('date', $today->toDateString())
            ->first();

        $isCheckout = $attendance ? $attendance->time_out : false;

        return response([
            'checkedin' => $attendance ? true : false,
            'checkedout' => $isCheckout ? true : false,
        ], 200);
    }

    // index
    public function index(Request $request)
    {
        $date = $request->input('date');

        $currentUser = $request->user();

        // Admin dan HR bisa lihat semua attendance, user biasa hanya miliknya sendiri
        $query = Attendance::query();
        
        if ($currentUser->role !== 'admin' && $currentUser->role !== 'hr' && $currentUser->role !== 'manager') {
            $query->where('user_id', $currentUser->id);
        }

        if ($date) {
            $query->where('date', $date);
        }

        $attendance = $query->orderBy('date', 'desc')->get();

        return response([
            'message' => 'Success',
            'data' => $attendance,
        ], 200);
    }

    /**
     * Calculate distance between two GPS coordinates using Haversine formula.
     * Returns distance in kilometers.
     */
    private function calculateDistance($lat1, $lon1, $lat2, $lon2): float
    {
        $earthRadius = 6371; // Earth radius in kilometers

        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);

        $a = sin($dLat / 2) * sin($dLat / 2) +
            cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
            sin($dLon / 2) * sin($dLon / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }
}
