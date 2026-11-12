<?php

namespace Modules\Tenant\Modules\Classroom\UseCases;

use Closure;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\Shared\Utils\Response;
use Modules\Tenant\Modules\Classroom\Repositories\CapacityRepository;

class CapacityUseCases
{
    public static function list(int $classroomId)
    {
        try {
            $result = CapacityRepository::list($classroomId);
            return Response::success($result);
        } catch (Exception $e) {
            return Response::error($e->getMessage());
        }
    }

    public static function create(Request $request)
    {
        return self::transaction(fn () => CapacityRepository::create($request));
    }

    public static function createWithIndicators(Request $request)
    {
        return self::transaction(fn () => CapacityRepository::createWithIndicators($request));
    }

    public static function update(int $id, Request $request)
    {
        return self::transaction(fn () => CapacityRepository::update($id, $request));
    }

    public static function updateWithIndicators(int $id, Request $request)
    {
        return self::transaction(fn () => CapacityRepository::updateWithIndicators($id, $request));
    }

    public static function sort(int $id, int $position)
    {
        return self::transaction(fn () => CapacityRepository::sort($id, $position));
    }

    public static function delete(int $id)
    {
        return self::transaction(fn () => CapacityRepository::delete($id));
    }

    public static function createIndicator(Request $request)
    {
        return self::transaction(fn () => CapacityRepository::createIndicator($request));
    }

    public static function updateIndicator(int $id, Request $request)
    {
        return self::transaction(fn () => CapacityRepository::updateIndicator($id, $request));
    }

    public static function sortIndicator(int $id, int $position)
    {
        return self::transaction(fn () => CapacityRepository::sortIndicator($id, $position));
    }

    public static function deleteIndicator(int $id)
    {
        return self::transaction(fn () => CapacityRepository::deleteIndicator($id));
    }

    private static function transaction(Closure $action)
    {
        DB::beginTransaction();
        try {
            $result = $action();
            DB::commit();
            return Response::success($result);
        } catch (Exception $e) {
            DB::rollBack();
            return Response::error($e->getMessage());
        }
    }
}
