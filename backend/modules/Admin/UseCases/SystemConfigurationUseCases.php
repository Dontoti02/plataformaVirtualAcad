<?php

namespace Modules\Admin\UseCases;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\Admin\Repositories\SystemConfigurationRepository;
use Modules\Shared\Utils\Response;

class SystemConfigurationUseCases
{
    public static function general()
    {
        try {
            $result = SystemConfigurationRepository::general();
            return Response::success($result);
        } catch (Exception $e) {
            return Response::error($e->getMessage());
        }
    }

    public static function list()
    {
        try {
            $result = SystemConfigurationRepository::list();
            return Response::success($result);
        } catch (Exception $e) {
            return Response::error($e->getMessage());
        }
    }

    public static function update(string $key, Request $request)
    {
        DB::beginTransaction();
        try {
            $result = SystemConfigurationRepository::update($key, $request);
            DB::commit();
            return Response::success($result);
        } catch (Exception $e) {
            DB::rollBack();
            return Response::error($e->getMessage());
        }
    }
}
