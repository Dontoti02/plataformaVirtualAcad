<?php

namespace Modules\Tenant\Modules\SystemConfiguration\UseCases;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\Shared\Utils\Response;
use Modules\Tenant\Modules\SystemConfiguration\Repositories\MailSettingsRepository;
use Modules\Tenant\Modules\SystemConfiguration\Repositories\SystemConfigurationRepository;

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

    public static function landingPage()
    {
        try {
            $result = SystemConfigurationRepository::landingPage();
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

    public static function uploadImage(Request $request)
    {
        try {
            $result = SystemConfigurationRepository::uploadImage($request);
            return Response::success($result);
        } catch (Exception $e) {
            return Response::error($e->getMessage());
        }
    }

    public static function deleteImage(Request $request)
    {
        try {
            $result = SystemConfigurationRepository::deleteImage($request);
            return Response::success($result);
        } catch (Exception $e) {
            return Response::error($e->getMessage());
        }
    }

    /**
     * Correo saliente de la institución: lo configura ella misma desde Ajustes.
     * Arranca desactivado, así que nada sale hasta que lo enciendan.
     */
    public static function mail()
    {
        try {
            return Response::success(MailSettingsRepository::get());
        } catch (Exception $e) {
            return Response::error($e->getMessage());
        }
    }

    public static function updateMail(Request $request)
    {
        try {
            return Response::success(MailSettingsRepository::update($request), 'Configuración de correo guardada');
        } catch (Exception $e) {
            return Response::error($e->getMessage());
        }
    }

    public static function testMail(Request $request)
    {
        try {
            return Response::success(MailSettingsRepository::test($request));
        } catch (Exception $e) {
            return Response::error($e->getMessage());
        }
    }
}
