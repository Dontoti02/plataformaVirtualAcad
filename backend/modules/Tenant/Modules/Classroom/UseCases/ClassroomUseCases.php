<?php

namespace Modules\Tenant\Modules\Classroom\UseCases;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\Shared\Utils\Response;
use Modules\Tenant\Modules\Classroom\Repositories\ClassroomRepository;

class ClassroomUseCases
{
    public static function list(Request $request)
    {
        try {
            $result = ClassroomRepository::list($request);
            return Response::success($result);
        } catch (Exception $e) {
            return Response::error($e->getMessage());
        }
    }

    public static function options(Request $request)
    {
        try {
            $result = ClassroomRepository::options($request);
            return Response::success($result);
        } catch (Exception $e) {
            return Response::error($e->getMessage());
        }
    }

    public static function get(int $id)
    {
        try {
            $result = ClassroomRepository::get($id);
            return Response::success($result);
        } catch (Exception $e) {
            return Response::error($e->getMessage());
        }
    }

    public static function updateImage(Request $request)
    {
        try {
            $result = ClassroomRepository::updateImage($request);
            return Response::success($result);
        } catch (Exception $e) {
            return Response::error($e->getMessage());
        }
    }

    public static function updateFavorite(int $id)
    {
        DB::beginTransaction();
        try {
            $result = ClassroomRepository::updateFavorite($id);
            DB::commit();
            return Response::success($result);
        } catch (Exception $e) {
            DB::rollBack();
            return Response::error($e->getMessage());
        }
    }
}
