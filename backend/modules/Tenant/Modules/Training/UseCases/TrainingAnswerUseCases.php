<?php

namespace Modules\Tenant\Modules\Training\UseCases;

use Exception;
use Illuminate\Support\Facades\DB;
use Modules\Shared\Utils\Response;
use Modules\Tenant\Modules\Training\Repositories\TrainingAnswerRepository;

class TrainingAnswerUseCases
{
    public static function list(int $training_content_id)
    {
        try {
            $result = TrainingAnswerRepository::list($training_content_id);
            return Response::success($result);
        } catch (Exception $e) {
            return Response::error($e->getMessage());
        }
    }

    public static function delivered(int $id)
    {
        DB::beginTransaction();
        try {
            $result = TrainingAnswerRepository::delivered($id);
            DB::commit();
            return Response::success($result);
        } catch (Exception $e) {
            DB::rollBack();
            return Response::error($e->getMessage());
        }
    }
}
