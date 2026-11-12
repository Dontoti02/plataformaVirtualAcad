<?php

namespace Modules\Admin\Helpers;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class AuthHelper
{
    public static function validateLoginRequest(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email'     => 'required|string|email',
            'password'  => 'required|string',
            'remember'  => 'required|boolean',
        ]);

        if ($validator->fails()) {
            throw new Exception($validator->errors()->first());
        }
    }

    public static function validateResetPasswordRequest(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email'     => 'required|string|email|exists:user,email',
        ]);

        if ($validator->fails()) {
            throw new Exception($validator->errors()->first());
        }
    }

    public static function validateChangePasswordRequest(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email'     => 'required|string|email|exists:user,email',
            "password"  => "required|string|min:8",
            "token"     => "required|string",
        ]);

        if ($validator->fails()) {
            throw new Exception($validator->errors()->first());
        }
    }
}
