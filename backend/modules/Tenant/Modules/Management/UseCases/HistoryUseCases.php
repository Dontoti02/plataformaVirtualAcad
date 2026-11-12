<?php

namespace Modules\Tenant\Modules\Management\UseCases;

use Exception;
use Illuminate\Http\Request;
use Modules\Shared\Utils\Response;
use Modules\Tenant\Modules\Management\Repositories\HistoryRepository;

class HistoryUseCases
{
    public static function filters()
    {
        try {
            $result = HistoryRepository::filters();
            return Response::success($result);
        } catch (Exception $e) {
            return Response::error($e->getMessage());
        }
    }

    public static function listStudents(Request $request)
    {
        try {
            $result = HistoryRepository::listStudents($request);
            return Response::success($result);
        } catch (Exception $e) {
            return Response::error($e->getMessage());
        }
    }

    public static function list(Request $request)
    {
        try {
            [$result] = HistoryRepository::list($request);
            return Response::success($result);
        } catch (Exception $e) {
            return Response::error($e->getMessage());
        }
    }
}
