<?php

namespace Modules\Tenant\Modules\File\UseCases;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\Shared\Utils\Response;
use Modules\Tenant\Modules\File\Repositories\LinkRepository;

class LinkUseCases
{
    public static function create(Request $request)
    {
        DB::beginTransaction();
        try {
            $result = LinkRepository::create($request);
            DB::commit();
            return Response::success($result);
        } catch (Exception $e) {
            DB::rollBack();
            return Response::error($e->getMessage());
        }
    }

    public static function delete(int $id, string $model)
    {
        DB::beginTransaction();
        try {
            $result = LinkRepository::delete($id, $model);
            DB::commit();
            return Response::success($result);
        } catch (Exception $e) {
            DB::rollBack();
            return Response::error($e->getMessage());
        }
    }
}
