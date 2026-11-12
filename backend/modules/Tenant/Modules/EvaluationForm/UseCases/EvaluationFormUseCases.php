<?php

namespace Modules\Tenant\Modules\EvaluationForm\UseCases;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\Shared\Utils\Response;
use Modules\Tenant\Modules\EvaluationForm\Repositories\EvaluationFormRepository;

class EvaluationFormUseCases
{
    public static function questionTypes()
    {
        try {
            $result = EvaluationFormRepository::questionTypes();
            return Response::success($result);
        } catch (Exception $e) {
            return Response::error($e->getMessage());
        }
    }

    public static function get(string $uuid, int $person_id)
    {
        try {
            $result = EvaluationFormRepository::get($uuid, $person_id);
            return Response::success($result);
        } catch (Exception $e) {
            return Response::error($e->getMessage());
        }
    }

    public static function delivered(Request $request)
    {
        DB::beginTransaction();
        try {
            $result = EvaluationFormRepository::delivered($request);
            DB::commit();
            return Response::success($result);
        } catch (Exception $e) {
            DB::rollBack();
            return Response::error($e->getMessage());
        }
    }
}
