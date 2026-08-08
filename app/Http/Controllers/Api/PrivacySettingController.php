<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\PrivacySetting;

class PrivacySettingController extends Controller
{
    public function getSettings(Request $request)
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['error' => true, 'message' => 'Unauthenticated'], 401);
        }

        $settings = PrivacySetting::firstOrCreate(
            ['user_id' => $user->id],
            [
                'read_message' => true,
                'search_engine' => false,
                'home_city' => true,
                'trip_type' => true,
                'length_stay' => true,
                'booked_services' => true,
                'ai_features' => true,
            ]
        );

        return response()->json([
            'error' => false,
            'data' => $settings
        ]);
    }

    public function updateSettings(Request $request)
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['error' => true, 'message' => 'Unauthenticated'], 401);
        }

        $validated = $request->validate([
            'read_message' => 'nullable|boolean',
            'search_engine' => 'nullable|boolean',
            'home_city' => 'nullable|boolean',
            'trip_type' => 'nullable|boolean',
            'length_stay' => 'nullable|boolean',
            'booked_services' => 'nullable|boolean',
            'ai_features' => 'nullable|boolean',
        ]);

        $settings = PrivacySetting::firstOrCreate(['user_id' => $user->id]);
        $settings->update($validated);

        return response()->json([
            'error' => false,
            'message' => 'Privacy settings updated successfully.',
            'data' => $settings
        ]);
    }
}
