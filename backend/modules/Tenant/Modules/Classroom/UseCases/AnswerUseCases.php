<?php

namespace Modules\Tenant\Modules\Classroom\UseCases;

use Exception;
use Illuminate\Support\Facades\DB;
use Modules\Shared\Utils\Response;
use Modules\Tenant\Modules\Classroom\Repositories\AnswerRepository;

class AnswerUseCases
{
    public static function list(int $content_id)
    {
        try {
            $result = AnswerRepository::list($content_id);
            return Response::success($result);
        } catch (Exception $e) {
            return Response::error($e->getMessage());
        }
    }

    public static function delivered(int $id)
    {
        DB::beginTransaction();
        try {
            $result = AnswerRepository::delivered($id);
            DB::commit();
            return Response::success($result);
        } catch (Exception $e) {
            DB::rollBack();
            return Response::error($e->getMessage());
        }
    }
}
