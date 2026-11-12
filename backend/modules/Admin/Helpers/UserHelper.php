<?php

namespace Modules\Admin\Helpers;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Modules\Admin\Models\User;
use Modules\Shared\Helpers\SessionManager;

class UserHelper
{
    public static function validateListRequest(Request $request)
    {
        $validator = Validator::make($request->all(), [
            "page"              => "required|integer|gt:0",
            "size"              => "required|integer|gt:0",
            "rol_ids"           => "required|array",
            "rol_ids.*"         => "required|numeric|exists:rol,id",
            "search"            => "nullable|string",
        ]);

        if ($validator->fails()) {
            throw new Exception($validator->errors()->first());
        }
    }

    public static function validateCreateRequest(Request $request)
    {
        $validator = Validator::make($request->all(), [
            "names"         => "required|string",
            "email"         => "required|email|unique:user,email",
            "password"      => "required|string|min:8",
            "rol_ids"       => "required|array",
            "rol_ids.*"     => "required|numeric|exists:rol,id",
        ]);

        if ($validator->fails()) {
            throw new Exception($validator->errors()->first());
        }
    }

    public static function validateAccess(int $id)
    {
        $session = SessionManager::get();

        $user_login = User::find($session->id);

        $user = User::byKey('id', $id);

        if ($user_login->id != $user->id) {
            throw new Exception("No tienes permisos para realizar esta acción");
        }

        return $user;
    }

    public static function validateUpdateRequest(Request $request, $exceptId)
    {
        $validator = Validator::make($request->all(), [
            "names"         => "required|string",
            "email"         => "required|email|unique:user,email,$exceptId",
            "rol_ids"       => "required|array",
            "rol_ids.*"     => "required|numeric|exists:rol,id",
        ]);

        if ($validator->fails()) {
            throw new Exception($validator->errors()->first());
        }
    }

    public static function validateChangePasswordRequest(Request $request)
    {
        $validator = Validator::make($request->all(), [
            "password" => "required|string|min:8",
        ]);

        if ($validator->fails()) {
            throw new Exception($validator->errors()->first());
        }
    }

    public static function validateChangePhotoRequest(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'file' => 'required|file|mimes:jpg,jpeg,png,gif',
        ]);

        if ($validator->fails()) {
            throw new Exception($validator->errors()->first());
        }
    }

    public static function validateDisableOrDeleteAccess(int $id)
    {
        $session = SessionManager::get();

        $user_login = User::find($session->id);

        $user = User::byKey('id', $id);

        if ($user_login->id == $user->id) {
            throw new Exception("No puedes desactivar o eliminar tu propio usuario");
        }

        return $user;
    }
}
