<?php

namespace Botble\RealEstate\Http\Controllers\API;

use Botble\Base\Http\Controllers\BaseController;
use Botble\RealEstate\Models\Account;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Botble\Base\Http\Responses\BaseHttpResponse;

class LoginController extends BaseController
{
    /**
     * Mobile API Login
     */
    public function login(Request $request, BaseHttpResponse $response)
    {
        $validator = Validator::make($request->all(), [
            'email'    => 'required|email',
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            return $response
                ->setError()
                ->setCode(422)
                ->setMessage('Validation Error')
                ->setData($validator->errors()->toArray());
        }

        $account = Account::where('email', $request->input('email'))->first();

        if (! $account || ! Hash::check($request->input('password'), $account->password)) {
            return $response
                ->setError()
                ->setCode(401)
                ->setMessage(trans('auth.failed'));
        }

        $token = $account->createToken('MobileApp')->plainTextToken;

        return $response
            ->setData([
                'token'   => $token,
                'account' => $account,
            ])
            ->setMessage('Login successful');
    }

    /**
     * Mobile API Logout
     */
    public function logout(Request $request, BaseHttpResponse $response)
    {
        $request->user()->currentAccessToken()->delete();

        return $response
            ->setMessage('Logout successful');
    }
}
