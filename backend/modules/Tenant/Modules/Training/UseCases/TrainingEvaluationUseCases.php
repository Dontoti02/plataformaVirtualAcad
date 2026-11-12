<?php

namespace Modules\Tenant\Modules\Training\UseCases;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\Shared\Utils\Response;
use Modules\Tenant\Modules\Training\Repositories\TrainingEvaluationRepository;

class TrainingEvaluationUseCases
{
    public static function list(int $training_id, int $person_id)
    {
        try {
            $result = TrainingEvaluationRepository::list($training_id, $person_id);
            return Response::success($result);
        } catch (Exception $e) {
            return Response::error($e->getMessage());
        }
    }

    public static function evaluate(Request $request)
    {
        DB::beginTransaction();
        try {
            $result = TrainingEvaluationRepository::evaluate($request);
            DB::commit();
            return Response::success($result);
        } catch (Exception $e) {
            DB::rollBack();
            return Response::error($e->getMessage());
        }
    }
}
