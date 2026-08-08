<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Botble\RealEstate\Models\Account;
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
            'email' => 'required|string|email|max:255|unique:re_accounts,email',
            'password' => 'required|string|min:8',
        ]);

        $account = Account::create([
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        $userData = $account->toArray();
        $userData['token'] = $account->createToken('auth_token')->plainTextToken;

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

        $account = Account::where('email', $request->email)->first();

        if (! $account || ! Hash::check($request->password, $account->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        $userData = $account->toArray();
        $userData['token'] = $account->createToken('auth_token')->plainTextToken;

        return response()->json([
            'error' => false,
            'message' => 'Login successful',
            'data' => $userData,
        ]);
    }

    public function forgotPassword(Request $request)
    {
        $request->validate(['email' => 'required|email']);

        $account = Account::where('email', $request->email)->first();
        if (!$account) {
            return response()->json([
                'error' => false,
                'message' => 'If your email is in our system, an OTP has been sent.',
            ]);
        }

        $otp = rand(100000, 999999);
        \Illuminate\Support\Facades\Cache::put('password_reset_otp_' . $request->email, $otp, now()->addMinutes(10));

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

        $account = Account::where('email', $request->email)->first();
        if ($account) {
            $account->password = Hash::make($request->password);
            $account->save();
        }

        // Clear the OTP
        \Illuminate\Support\Facades\Cache::forget('password_reset_otp_' . $request->email);

        return response()->json([
            'error' => false,
            'message' => 'Password reset successfully.',
        ]);
    }
}
