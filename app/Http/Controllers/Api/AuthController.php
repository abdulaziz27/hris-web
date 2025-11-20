<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Services\TimezoneService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    // login
    public function login(Request $request)
    {
        $loginData = $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $user = User::where('email', $loginData['email'])->first();

        // check user exist
        if (! $user) {
            return response(['message' => 'Invalid credentials'], 401);
        }

        // check password
        if (! Hash::check($loginData['password'], $user->password)) {
            return response(['message' => 'Invalid credentials'], 401);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        // Load relationships
        $user->load(['shiftKerja', 'departemen', 'jabatan', 'location']);

        $response = [
            'user' => new UserResource($user),
            'token' => $token,
            'role' => $user->role,
            'position' => $user->jabatan ? [
                'id' => $user->jabatan->id,
                'name' => $user->jabatan->name,
            ] : null,
            'default_shift' => $user->shiftKerja ? [
                'id' => $user->shiftKerja->id,
                'name' => $user->shiftKerja->name,
            ] : null,
            'default_shift_detail' => $user->shiftKerja ? [
                'id' => $user->shiftKerja->id,
                'name' => $user->shiftKerja->name,
                'start_time' => $this->formatTimeWithLocationTimezone(
                    $user->shiftKerja->start_time,
                    $user->location_id
                ),
                'end_time' => $this->formatTimeWithLocationTimezone(
                    $user->shiftKerja->end_time,
                    $user->location_id
                ),
            ] : null,
            'department' => $user->departemen ? [
                'id' => $user->departemen->id,
                'name' => $user->departemen->name,
            ] : null,
            'location' => $user->location ? [
                'id' => $user->location->id,
                'name' => $user->location->name,
                'latitude' => $user->location->latitude,
                'longitude' => $user->location->longitude,
                'radius_km' => $user->location->radius_km,
                'attendance_type' => $user->location->attendance_type,
            ] : null,
        ];

        return response($response, 200);
    }

    // logout
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response(['message' => 'Logged out'], 200);
    }

    // update image profile & face_embedding
    public function updateProfile(Request $request)
    {
        $request->validate([
            // 'image' => 'required|image|mimes:jpeg,png,jpg|max:2048',
            'face_embedding' => 'required',
        ]);

        $user = $request->user();
        // $image = $request->file('image');
        $face_embedding = $request->face_embedding;

        // //save image
        // $image->storeAs('public/images', $image->hashName());
        // $user->image_url = $image->hashName();
        $user->face_embedding = $face_embedding;
        $user->save();

        return response([
            'message' => 'Profile updated',
            'user' => new UserResource($user),
        ], 200);
    }

    // update fcm token
    public function updateFcmToken(Request $request)
    {
        $request->validate([
            'fcm_token' => 'required',
        ]);

        $user = $request->user();
        $user->fcm_token = $request->fcm_token;
        $user->save();

        return response([
            'message' => 'FCM token updated',
        ], 200);
    }

    // get current user data
    public function me(Request $request)
    {
        $user = $request->user();

        // Load relationships
        $user->load(['shiftKerja', 'departemen', 'jabatan', 'location']);

        $response = [
            'user' => new UserResource($user),
            'role' => $user->role,
            'position' => $user->jabatan ? [
                'id' => $user->jabatan->id,
                'name' => $user->jabatan->name,
            ] : null,
            'default_shift' => $user->shiftKerja ? [
                'id' => $user->shiftKerja->id,
                'name' => $user->shiftKerja->name,
            ] : null,
            'default_shift_detail' => $user->shiftKerja ? [
                'id' => $user->shiftKerja->id,
                'name' => $user->shiftKerja->name,
                'start_time' => $this->formatTimeWithLocationTimezone(
                    $user->shiftKerja->start_time,
                    $user->location_id
                ),
                'end_time' => $this->formatTimeWithLocationTimezone(
                    $user->shiftKerja->end_time,
                    $user->location_id
                ),
            ] : null,
            'department' => $user->departemen ? [
                'id' => $user->departemen->id,
                'name' => $user->departemen->name,
            ] : null,
        ];

        return response($response, 200);
    }

    /**
     * Format waktu sebagai ISO 8601 datetime TANPA timezone offset
     * Format: "2000-01-01T07:30:00" (datetime tanpa timezone)
     * 
     * Menggunakan format datetime TANPA timezone offset karena:
     * 1. Flutter DateTime.parse() akan mengkonversi waktu dengan timezone offset ke UTC
     * 2. Tanpa timezone offset, Flutter akan parse sebagai local time device (tidak di-convert)
     * 3. Waktu shift di database sudah dalam format lokal universal, tidak perlu timezone offset
     * 
     * Catatan: Waktu shift di database adalah waktu lokal universal yang sama untuk semua timezone.
     * Flutter akan menampilkan waktu sesuai timezone device, yang sudah benar karena waktu shift
     * adalah waktu lokal universal (07:00 berarti 07:00 di semua timezone).
     * 
     * @param mixed $carbonTime Carbon instance atau null
     * @param int|null $locationId ID lokasi user (tidak digunakan, tapi tetap di-parameter untuk konsistensi)
     * @return string|null Format datetime "Y-m-d\TH:i:s" tanpa timezone offset atau null
     */
    private function formatTimeWithLocationTimezone($carbonTime, ?int $locationId): ?string
    {
        if (!$carbonTime) {
            return null;
        }

        // Ambil waktu dari Carbon instance (H:i:s format)
        $timeString = $carbonTime->format('H:i:s');

        // Format sebagai datetime tanpa timezone offset
        // Flutter akan parse sebagai local time device (tidak di-convert ke UTC)
        return '2000-01-01T' . $timeString;
    }
}
