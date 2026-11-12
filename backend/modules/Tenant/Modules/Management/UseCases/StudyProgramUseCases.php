<?php

namespace Modules\Tenant\Modules\Management\UseCases;

use Exception;
use Illuminate\Http\Request;
use Modules\Shared\Utils\Response;
use Modules\Tenant\Modules\Management\Repositories\StudyProgramRepository;

class StudyProgramUseCases
{
    public static function filters()
    {
        try {
            $result = StudyProgramRepository::filters();
            return Response::success($result);
        } catch (Exception $e) {
            return Response::error($e->getMessage());
        }
    }

    public static function list(Request $request)
    {
        try {
            $result = StudyProgramRepository::list($request);
            return Response::success($result);
        } catch (Exception $e) {
            return Response::error($e->getMessage());
        }
    }
}
