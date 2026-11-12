<?php

namespace Modules\Tenant\Modules\Management\UseCases;

use Exception;
use Illuminate\Http\Request;
use Modules\Shared\Utils\Response;
use Modules\Tenant\Modules\Management\Repositories\RegistrationRepository;

class RegistrationUseCases
{
    public static function filters()
    {
        try {
            $result = RegistrationRepository::filters();
            return Response::success($result);
        } catch (Exception $e) {
            return Response::error($e->getMessage());
        }
    }

    public static function listStudents(Request $request)
    {
        try {
            $result = RegistrationRepository::listStudents($request);
            return Response::success($result);
        } catch (Exception $e) {
            return Response::error($e->getMessage());
        }
    }

    public static function list(Request $request)
    {
        try {
            [$result] = RegistrationRepository::list($request);
            return Response::success($result);
        } catch (Exception $e) {
            return Response::error($e->getMessage());
        }
    }
}
