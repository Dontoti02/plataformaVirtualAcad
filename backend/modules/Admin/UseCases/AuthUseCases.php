<?php

namespace Modules\Admin\UseCases;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\Admin\Repositories\AuthRepository;
use Modules\Shared\Utils\Response;

class AuthUseCases
{
    public static function login(Request $request)
    {
        try {
            $result = AuthRepository::login($request);
            return Response::success($result);
        } catch (Exception $e) {
            return Response::error($e->getMessage());
        }
    }

    public static function changeRol(int $rol_id)
    {
        DB::beginTransaction();
        try {
            $result = AuthRepository::changeRol($rol_id);
            DB::commit();
            return Response::success($result);
        } catch (Exception $e) {
            DB::rollBack();
            return Response::error($e->getMessage());
        }
    }

    public static function resetPassword(Request $request)
    {
        DB::beginTransaction();
        try {
            $result = AuthRepository::resetPassword($request);
            DB::commit();
            return Response::success($result);
        } catch (Exception $e) {
            DB::rollBack();
            return Response::error($e->getMessage());
        }
    }

    public static function checkResetPassword(Request $request)
    {
        try {
            $result = AuthRepository::checkResetPassword($request);
            return Response::success($result);
        } catch (Exception $e) {
            return Response::error($e->getMessage());
        }
    }

    public static function changePassword(Request $request)
    {
        DB::beginTransaction();
        try {
            $result = AuthRepository::changePassword($request);
            DB::commit();
            return Response::success($result);
        } catch (Exception $e) {
            DB::rollBack();
            return Response::error($e->getMessage());
        }
    }
}
