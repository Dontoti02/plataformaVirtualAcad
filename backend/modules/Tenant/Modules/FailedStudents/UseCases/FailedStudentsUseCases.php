<?php

namespace Modules\Tenant\Modules\FailedStudents\UseCases;

use Exception;
use Illuminate\Http\Request;
use Modules\Shared\Utils\Response;
use Modules\Tenant\Modules\FailedStudents\Repositories\FailedStudentsRepository;

class FailedStudentsUseCases
{
    public static function filters(Request $request)
    {
        try {
            $result = FailedStudentsRepository::filters($request);
            return Response::success($result);
        } catch (Exception $e) {
            return Response::error($e->getMessage());
        }
    }

    public static function list(Request $request)
    {
        try {
            $result = FailedStudentsRepository::list($request);
            return Response::success($result);
        } catch (Exception $e) {
            return Response::error($e->getMessage());
        }
    }
}
