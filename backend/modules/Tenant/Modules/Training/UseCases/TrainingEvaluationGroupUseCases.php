<?php

namespace Modules\Tenant\Modules\Training\UseCases;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\Shared\Utils\Response;
use Modules\Tenant\Modules\Training\Repositories\TrainingEvaluationGroupRepository;

class TrainingEvaluationGroupUseCases
{
    public static function list(int $classroom_id)
    {
        try {
            $result = TrainingEvaluationGroupRepository::list($classroom_id);
            return Response::success($result);
        } catch (Exception $e) {
            return Response::error($e->getMessage());
        }
    }

    public static function set(Request $request)
    {
        DB::beginTransaction();
        try {
            $result = TrainingEvaluationGroupRepository::set($request);
            DB::commit();
            return Response::success($result);
        } catch (Exception $e) {
            DB::rollBack();
            return Response::error($e->getMessage());
        }
    }
}
