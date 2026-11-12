<?php

namespace Modules\Tenant\Modules\Classroom\UseCases;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\Shared\Utils\Response;
use Modules\Tenant\Modules\Classroom\Repositories\EvaluationGroupRepository;

class EvaluationGroupUseCases
{
    public static function list(int $classroom_id)
    {
        try {
            $result = EvaluationGroupRepository::list($classroom_id);
            return Response::success($result);
        } catch (Exception $e) {
            return Response::error($e->getMessage());
        }
    }

    public static function set(Request $request)
    {
        DB::beginTransaction();
        try {
            $result = EvaluationGroupRepository::set($request);
            DB::commit();
            return Response::success($result);
        } catch (Exception $e) {
            DB::rollBack();
            return Response::error($e->getMessage());
        }
    }
}
