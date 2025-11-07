<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Location;
use Illuminate\Http\Request;

class LocationController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        // Get active locations only
        $query = Location::where('is_active', true);

        // If user has assigned location, prioritize it
        if ($user->location_id) {
            $query->orderByRaw('id = ? DESC', [$user->location_id]);
        }

        $locations = $query->orderBy('name')->get();

        return response([
            'message' => 'Success',
            'locations' => $locations,
            'user_default_location_id' => $user->location_id,
        ], 200);
    }

    public function show(Request $request, $id)
    {
        $location = Location::where('is_active', true)->findOrFail($id);

        return response([
            'message' => 'Success',
            'location' => $location,
        ], 200);
    }
}
