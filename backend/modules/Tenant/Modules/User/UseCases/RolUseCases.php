<?php

namespace Modules\Tenant\Modules\User\UseCases;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\Shared\Utils\Response;
use Modules\Tenant\Modules\User\Repositories\RolRepository;

class RolUseCases
{
    public static function list(Request $request)
    {
        try {
            $result = RolRepository::list($request);
            return Response::success($result);
        } catch (Exception $e) {
            return Response::error($e->getMessage());
        }
    }

    public static function change(int $id)
    {
        DB::beginTransaction();
        try {
            $result = RolRepository::change($id);
            DB::commit();
            return Response::success($result);
        } catch (Exception $e) {
            DB::rollBack();
            return Response::error($e->getMessage());
        }
    }
}
