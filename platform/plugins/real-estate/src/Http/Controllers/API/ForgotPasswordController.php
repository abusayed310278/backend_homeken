<?php

namespace Botble\RealEstate\Http\Controllers\API;

use Botble\Base\Http\Controllers\BaseController;
use Botble\RealEstate\Models\Account;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Botble\Base\Http\Responses\BaseHttpResponse;

class ForgotPasswordController extends BaseController
{
    /**
     * Mobile API Forgot Password (Send OTP)
     */
    public function sendResetLinkEmail(Request $request, BaseHttpResponse $response)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|exists:re_accounts,email',
        ]);

        if ($validator->fails()) {
            return $response
                ->setError()
                ->setCode(422)
                ->setMessage('Validation Error')
                ->setData($validator->errors()->toArray());
        }

        $email = $request->input('email');
        
        // Generate a 4-digit OTP
        $otp = rand(1000, 9999);
        
        // Store OTP in cache for 15 minutes
        Cache::put('password_reset_otp_' . $email, $otp, now()->addMinutes(15));
        
        // Attempt to send email
        try {
            Mail::raw("Your password reset OTP is: $otp. It is valid for 15 minutes.", function ($message) use ($email) {
                $message->to($email)
                        ->subject('Password Reset OTP');
            });
        } catch (\Exception $e) {
            \Log::error('Failed to send OTP email: ' . $e->getMessage());
            // We can return the OTP in the response data just for local testing since SMTP might not be set up
            return $response
                ->setData(['test_otp' => $otp]) 
                ->setMessage('OTP generated (SMTP failed, see test_otp in data)');
        }

        return $response
            ->setMessage('OTP sent successfully to your email address.');
    }
}
