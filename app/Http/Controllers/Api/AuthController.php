<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8',
        ]);

        $user = User::create([
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        $userData = $user->toArray();
        $userData['token'] = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'error' => false,
            'message' => 'User registered successfully',
            'data' => $userData,
        ], 200);
    }

    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $user = User::where('email', $request->email)->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        $userData = $user->toArray();
        $userData['token'] = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'error' => false,
            'message' => 'Login successful',
            'data' => $userData,
        ]);
    }

    public function forgotPassword(Request $request)
    {
        $request->validate(['email' => 'required|email']);

        $user = User::where('email', $request->email)->first();
        if (!$user) {
            // Return error false anyway for security (don't leak emails)
            return response()->json([
                'error' => false,
                'message' => 'If your email is in our system, an OTP has been sent.',
            ]);
        }

        // Generate a 6-digit OTP
        $otp = rand(100000, 999999);
        
        // Store in cache for 10 minutes
        \Illuminate\Support\Facades\Cache::put('password_reset_otp_' . $request->email, $otp, now()->addMinutes(10));

        // In a real app, send this via email/SMS. For now, we return it in debug if needed, or just let it succeed.
        // Uncomment to debug: \Illuminate\Support\Facades\Log::info("OTP for {$request->email} is $otp");
        
        // For development purposes, let's include it in the response so the user can test the UI without an email service configured.
        return response()->json([
            'error' => false,
            'message' => "OTP sent successfully! (Dev mode OTP: $otp)",
        ]);
    }

    public function resetPassword(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'otp' => 'required|string',
            'password' => 'required|string|min:8',
        ]);

        $cachedOtp = \Illuminate\Support\Facades\Cache::get('password_reset_otp_' . $request->email);

        if (!$cachedOtp || $cachedOtp != $request->otp) {
            return response()->json([
                'error' => true,
                'message' => 'Invalid or expired OTP.',
            ], 400);
        }

        $user = User::where('email', $request->email)->first();
        if ($user) {
            $user->password = Hash::make($request->password);
            $user->save();
        }

        // Clear the OTP
        \Illuminate\Support\Facades\Cache::forget('password_reset_otp_' . $request->email);

        return response()->json([
            'error' => false,
            'message' => 'Password reset successfully.',
        ]);
    }
}
