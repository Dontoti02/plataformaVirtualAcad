<?php

namespace Modules\Admin\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Admin\UseCases\AuthUseCases;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        return AuthUseCases::login($request);
    }

    public function changeRol(int $rol_id)
    {
        return AuthUseCases::changeRol($rol_id);
    }

    public function resetPassword(Request $request)
    {
        return AuthUseCases::resetPassword($request);
    }

    public function checkResetPassword(Request $request)
    {
        return AuthUseCases::checkResetPassword($request);
    }

    public function changePassword(Request $request)
    {
        return AuthUseCases::changePassword($request);
    }
}
