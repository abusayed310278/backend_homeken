<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Botble\Media\Facades\RvMedia;
use Illuminate\Support\Facades\Validator;

class ProfileController extends Controller
{
    public function updateProfile(Request $request)
    {
        $account = $request->user();

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'contact' => 'nullable|string|max:20',
            'dob' => 'nullable|date',
            'gender' => 'nullable|string',
            'bio' => 'nullable|string',
            'photo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:5120',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => true,
                'message' => $validator->errors()->first(),
            ], 422);
        }

        // We split name into first_name and last_name roughly
        $nameParts = explode(' ', $request->name, 2);
        $account->first_name = $nameParts[0];
        if (isset($nameParts[1])) {
            $account->last_name = $nameParts[1];
        }

        if ($request->has('contact')) $account->phone = $request->contact;
        if ($request->has('dob')) $account->dob = $request->dob;
        if ($request->has('gender')) $account->gender = $request->gender;
        if ($request->has('bio')) $account->description = $request->bio;

        if ($request->hasFile('photo')) {
            $result = RvMedia::handleUpload($request->file('photo'), 0, $account->upload_folder);
            if ($result['error'] == false) {
                $account->avatar_id = $result['data']->id;
            } else {
                return response()->json([
                    'error' => true,
                    'message' => $result['message']
                ], 400);
            }
        }

        $account->save();
        
        $userData = $account->toArray();
        $userData['avatar_url'] = $account->avatar_url; // append the full URL

        return response()->json([
            'error' => false,
            'message' => 'Profile updated successfully',
            'data' => $userData,
        ]);
    }
}
