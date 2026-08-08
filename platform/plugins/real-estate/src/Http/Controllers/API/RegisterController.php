<?php

namespace Botble\RealEstate\Http\Controllers\API;

use Botble\Base\Http\Controllers\BaseController;
use Botble\RealEstate\Models\Account;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Botble\Base\Http\Responses\BaseHttpResponse;

class RegisterController extends BaseController
{
    /**
     * Mobile API Register
     */
    public function register(Request $request, BaseHttpResponse $response)
    {
        $validator = Validator::make($request->all(), [
            'first_name' => 'required|string|max:120',
            'last_name'  => 'required|string|max:120',
            'email'      => 'required|string|email|max:255|unique:re_accounts',
            'password'   => 'required|string|min:6|confirmed',
        ]);

        if ($validator->fails()) {
            return $response
                ->setError()
                ->setCode(422)
                ->setMessage('Validation Error')
                ->setData($validator->errors()->toArray());
        }

        $account = Account::create([
            'first_name' => $request->input('first_name'),
            'last_name'  => $request->input('last_name'),
            'email'      => $request->input('email'),
            'password'   => Hash::make($request->input('password')),
        ]);

        $token = $account->createToken('MobileApp')->plainTextToken;

        return $response
            ->setData([
                'token'   => $token,
                'account' => $account,
            ])
            ->setMessage('Registration successful');
    }
}
