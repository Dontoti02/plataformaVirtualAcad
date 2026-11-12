<?php

namespace Modules\Tenant\Modules\Classroom\UseCases;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\Shared\Utils\Response;
use Modules\Tenant\Modules\Classroom\Repositories\ContentRepository;

class ContentUseCases
{
    public static function list(int $classroom_id)
    {
        try {
            $result = ContentRepository::list($classroom_id);
            return Response::success($result);
        } catch (Exception $e) {
            return Response::error($e->getMessage());
        }
    }

    public static function detail(int $id)
    {
        try {
            $result = ContentRepository::detail($id);
            return Response::success($result);
        } catch (Exception $e) {
            return Response::error($e->getMessage());
        }
    }


    public static function create(Request $request)
    {
        DB::beginTransaction();
        try {
            $result = ContentRepository::create($request);
            DB::commit();
            return Response::success($result);
        } catch (Exception $e) {
            DB::rollBack();
            return Response::error($e->getMessage());
        }
    }

    public static function update(int $id, Request $request)
    {
        DB::beginTransaction();
        try {
            $result = ContentRepository::update($id, $request);
            DB::commit();
            return Response::success($result);
        } catch (Exception $e) {
            DB::rollBack();
            return Response::error($e->getMessage());
        }
    }

    public static function updateVisibility(int $id)
    {
        DB::beginTransaction();
        try {
            $result = ContentRepository::updateVisibility($id);
            DB::commit();
            return Response::success($result);
        } catch (Exception $e) {
            DB::rollBack();
            return Response::error($e->getMessage());
        }
    }

    public static function updateStatus(int $id)
    {
        DB::beginTransaction();
        try {
            $result = ContentRepository::updateStatus($id);
            DB::commit();
            return Response::success($result);
        } catch (Exception $e) {
            DB::rollBack();
            return Response::error($e->getMessage());
        }
    }

    public static function delete(int $id)
    {
        try {
            $result = ContentRepository::delete($id);
            return Response::success($result);
        } catch (Exception $e) {
            return Response::error($e->getMessage());
        }
    }
}
