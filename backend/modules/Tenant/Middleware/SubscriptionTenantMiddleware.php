<?php

namespace Modules\Tenant\Middleware;

use Carbon\Carbon;
use Closure;
use Exception;
use Illuminate\Http\Request;
use Modules\Shared\Services\CentralConfig;
use Modules\Shared\Utils\Response;

/**
 * Verifica que la suscripción de la institución esté vigente.
 *
 * Antes hacía 2 consultas a la BD central por petición (domain + institution) y
 * además ESCRIBÍA en central cada vez que `is_active` no coincidía con lo
 * calculado — un UPDATE en el camino crítico de cada request. Ahora lee del
 * snapshot local y no escribe: la vigencia la decide el panel y llega con
 * `central:sync`, que corre a diario en el scheduler.
 *
 * Ver ANALISIS-Y-PLAN.md §3.3.
 */
class SubscriptionTenantMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        try {
            $institution = CentralConfig::institution();

            if (! $institution) {
                throw new Exception('Su institución no se encuentra registrada');
            }

            $now = Carbon::now();

            if ($institution->start_date && $now->lt(Carbon::parse($institution->start_date))) {
                throw new Exception('Su suscripción aun no ha iniciado');
            }

            if ($institution->end_date && $now->gt(Carbon::parse($institution->end_date))) {
                throw new Exception('Su suscripción ha finalizado');
            }

            return $next($request);
        } catch (Exception $e) {
            return Response::noSubscription($e->getMessage());
        }
    }
}
