<?php

namespace Modules\Tenant\Middleware;

use Closure;
use Exception;
use Illuminate\Http\Request;
use Modules\Shared\Services\CentralConfig;
use Modules\Shared\Utils\Response;

/**
 * Comprueba que la institución tenga contratado y vigente el módulo indicado.
 *
 * Lee los módulos contratados de la tabla local, no de la BD central.
 * no existe. PHP lanza un Error, que no hereda de Exception, así que el catch de
 * abajo no lo atrapaba y las 3 rutas que usan este middleware devolvían un 500
 * sin controlar.  Ver ANALISIS-Y-PLAN.md §1.10.
 */
class CheckInstitutionModuleMiddleware
{
    public function handle(Request $request, Closure $next, $moduleKey)
    {
        try {
            if (! CentralConfig::moduleEnabled($moduleKey)) {
                throw new Exception('Módulo no habilitado o fuera de vigencia.');
            }

            return $next($request);
        } catch (Exception $e) {
            return Response::forbidden($e->getMessage());
        }
    }
}
