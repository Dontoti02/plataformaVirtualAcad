<?php

namespace Modules\Tenant\Modules\Classroom\UseCases;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\Shared\Utils\Response;
use Modules\Tenant\Modules\Classroom\Repositories\NoteReportRepository;
use Modules\Tenant\Modules\Classroom\Repositories\NoteRepository;

class NoteUseCases
{
    public static function list(Request $request)
    {
        try {
            $result = NoteRepository::list($request);
            return Response::success($result);
        } catch (Exception $e) {
            return Response::error($e->getMessage());
        }
    }

    public static function report(Request $request)
    {
        try {
            $result = NoteReportRepository::report($request);
            return Response::success($result);
        } catch (Exception $e) {
            return Response::error($e->getMessage());
        }
    }

    public static function saveRecovery(Request $request)
    {
        DB::beginTransaction();
        try {
            $result = NoteReportRepository::saveRecovery($request);
            DB::commit();
            return Response::success($result);
        } catch (Exception $e) {
            DB::rollBack();
            return Response::error($e->getMessage());
        }
    }
}
