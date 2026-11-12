<?php

namespace Modules\Tenant\Modules\Management\UseCases;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\Shared\Utils\Response;
use Modules\Tenant\Modules\Management\Repositories\PeriodRepository;

class PeriodUseCases
{
    public static function list()
    {
        try {
            $result = PeriodRepository::list();
            return Response::success($result);
        } catch (Exception $e) {
            return Response::error($e->getMessage());
        }
    }

    public static function current()
    {
        try {
            $result = PeriodRepository::current();
            return Response::success($result);
        } catch (Exception $e) {
            return Response::error($e->getMessage());
        }
    }

    public static function disable(int $id)
    {
        DB::beginTransaction();
        try {
            $result = PeriodRepository::disable($id);
            DB::commit();
            return Response::success($result);
        } catch (Exception $e) {
            DB::rollBack();
            return Response::error($e->getMessage());
        }
    }

    public static function filtersClassrooms()
    {
        try {
            $result = PeriodRepository::filtersClassrooms();
            return Response::success($result);
        } catch (Exception $e) {
            return Response::error($e->getMessage());
        }
    }

    public static function listClassrooms(Request $request)
    {
        try {
            $result = PeriodRepository::listClassrooms($request);
            return Response::success($result);
        } catch (Exception $e) {
            return Response::error($e->getMessage());
        }
    }

    public static function updateDate(Request $request)
    {
        DB::beginTransaction();
        try {
            $result = PeriodRepository::updateDate($request);
            DB::commit();
            return Response::success($result);
        } catch (Exception $e) {
            DB::rollBack();
            return Response::error($e->getMessage());
        }
    }
}
