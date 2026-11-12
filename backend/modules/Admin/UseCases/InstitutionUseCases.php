<?php

namespace Modules\Admin\UseCases;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\Admin\Repositories\InstitutionRepository;
use Modules\Shared\Utils\Response;

class InstitutionUseCases
{
    public static function config()
    {
        try {
            $result = InstitutionRepository::config();
            return Response::success($result);
        } catch (Exception $e) {
            return Response::error($e->getMessage());
        }
    }

    public static function list(Request $request)
    {
        try {
            $result = InstitutionRepository::list($request);
            return Response::success($result);
        } catch (Exception $e) {
            return Response::error($e->getMessage());
        }
    }

    public static function get(int $id)
    {
        try {
            $result = InstitutionRepository::get($id);
            return Response::success($result);
        } catch (Exception $e) {
            return Response::error($e->getMessage());
        }
    }

    public static function create(Request $request)
    {
        try {
            $result = InstitutionRepository::create($request);
            return Response::success($result, 'Institución creada correctamente.');
        } catch (Exception $e) {
            return Response::error($e->getMessage());
        }
    }

    public static function update(int $id, Request $request)
    {
        DB::beginTransaction();
        try {
            $result = InstitutionRepository::update($id, $request);
            DB::commit();
            return Response::success($result, 'Institución actualizada correctamente.');
        } catch (Exception $e) {
            DB::rollBack();
            return Response::error($e->getMessage());
        }
    }

    public static function disable(int $id)
    {
        DB::beginTransaction();
        try {
            $result = InstitutionRepository::disable($id);
            DB::commit();
            return Response::success($result);
        } catch (Exception $e) {
            DB::rollBack();
            return Response::error($e->getMessage());
        }
    }

    public static function subscription(int $id, Request $request)
    {
        DB::beginTransaction();
        try {
            $result = InstitutionRepository::subscription($id, $request);
            DB::commit();
            return Response::success($result);
        } catch (Exception $e) {
            DB::rollBack();
            return Response::error($e->getMessage());
        }
    }

    public static function existSubdomain(string $subdomain,Request $request)
    {
        DB::beginTransaction();
        try {
            $result = InstitutionRepository::existSubdomain($subdomain,$request);
            DB::commit();
            return Response::success($result);
        } catch (Exception $e) {
            DB::rollBack();
            return Response::error($e->getMessage());
        }
    }

    public static function createUser(Request $request)
    {
        DB::beginTransaction();
        try {
            $result = InstitutionRepository::createUser($request);
            DB::commit();
            return Response::success($result);
        } catch (Exception $e) {
            DB::rollBack();
            return Response::error($e->getMessage());
        }
    }

    public static function detail(int $id)
    {
        DB::beginTransaction();
        try {
            $result = InstitutionRepository::detail($id);
            DB::commit();
            return Response::success($result);
        } catch (Exception $e) {
            Log::error($e);
            DB::rollBack();
            return Response::error($e->getMessage());
        }
    }


    public static function resizeStorage(Request $request)
    {
        DB::beginTransaction();
        try {
            $result = InstitutionRepository::resizeStorage($request);
            DB::commit();
            return Response::success($result);
        } catch (Exception $e) {
            DB::rollBack();
            return Response::error($e->getMessage());
        }
    }

    public static function updateSubdomain(Request $request)
    {
        DB::beginTransaction();
        try {
            $result = InstitutionRepository::updateSubdomain($request);
            DB::commit();
            return Response::success($result);
        } catch (Exception $e) {
            DB::rollBack();
            return Response::error($e->getMessage());
        }
    }

    public static function delete(int $id,Request $request)
    {
        try {
            $result = InstitutionRepository::delete($id,$request);
            return Response::success($result);
        } catch (Exception $e) {
            return Response::error($e->getMessage());
        }
    }
}
