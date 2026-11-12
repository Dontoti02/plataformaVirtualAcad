<?php

namespace Modules\Tenant\Modules\Import\UseCases;

use Exception;
use Illuminate\Http\Request;
use Modules\Shared\Utils\Response;
use Modules\Tenant\Modules\Import\Repositories\ImportRepository;

class ImportUseCases
{
    public static function list()
    {
        try {
            $result = ImportRepository::list();
            return Response::success($result);
        } catch (Exception $e) {
            return Response::error($e->getMessage());
        }
    }

    public static function get(int $id)
    {
        try {
            $result = ImportRepository::get($id);
            return Response::success($result);
        } catch (Exception $e) {
            return Response::error($e->getMessage());
        }
    }
    public static function finish(Request $request)
    {
        try {
            $result = ImportRepository::finish($request);
            return Response::success($result);
        } catch (Exception $e) {
            return Response::error($e->getMessage());
        }
    }

    public static function finishAll(){
        try {
            $result = ImportRepository::finishAll();
            return Response::success($result);
        } catch (Exception $e) {
            return Response::error($e->getMessage());
        }
    }

    public static function currently()
    {
        try {
            $result = ImportRepository::currently();
            return Response::success($result);
        } catch (Exception $e) {
            return Response::error($e->getMessage());
        }
    }

    public static function history(int $id)
    {
        try {
            $result = ImportRepository::history($id);
            return Response::success($result);
        } catch (Exception $e) {
            return Response::error($e->getMessage());
        }
    }

    public static function execute(string $key, Request $request)
    {
        try {
            $result = ImportRepository::execute($key, $request);
            return Response::success($result);
        } catch (Exception $e) {
            return Response::error($e->getMessage());
        }
    }
}
