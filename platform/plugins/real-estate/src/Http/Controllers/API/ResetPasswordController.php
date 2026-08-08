<?php

namespace Botble\RealEstate\Http\Controllers\API;

use Botble\Base\Http\Controllers\BaseController;
use Botble\RealEstate\Models\Account;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Botble\Base\Http\Responses\BaseHttpResponse;

class ResetPasswordController extends BaseController
{
    /**
     * Mobile API Reset Password (Verify OTP)
     */
    public function reset(Request $request, BaseHttpResponse $response)
    {
        $validator = Validator::make($request->all(), [
            'email'    => 'required|email|exists:re_accounts,email',
            'otp'      => 'required|numeric',
            'password' => 'required|string|min:6|confirmed',
        ]);

        if ($validator->fails()) {
            return $response
                ->setError()
                ->setCode(422)
                ->setMessage('Validation Error')
                ->setData($validator->errors()->toArray());
        }

        $email = $request->input('email');
        $otp   = $request->input('otp');

        $cachedOtp = Cache::get('password_reset_otp_' . $email);

        if (!$cachedOtp || $cachedOtp != $otp) {
            return $response
                ->setError()
                ->setCode(400)
                ->setMessage('Invalid or expired OTP.');
        }

        $account = Account::where('email', $email)->first();
        $account->password = Hash::make($request->input('password'));
        $account->save();

        // Invalidate OTP
        Cache::forget('password_reset_otp_' . $email);

        return $response
            ->setMessage('Password has been successfully reset. You can now login.');
    }
}
