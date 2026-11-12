<?php

namespace Modules\Tenant\Middleware;

use Closure;
use Exception;
use Illuminate\Http\Request;
use Modules\Admin\Models\Domain;
use Modules\Shared\Services\CentralConfig;
use Modules\Shared\Utils\Response;

/**
 * Valida que la petición venga del dominio correcto.
 *
 * En modo `single` es un no-op: hay un dominio por VPS, la validación la hace
 * Caddy antes de llegar a PHP, y el parseo de abajo era incompatible con
 * dominios propios por institución (asume que el dominio central tiene
 * exactamente dos etiquetas, así que con `app.instituto.edu.pe` calculaba
 * `edu.pe` y fallaba siempre).  Ver ANALISIS-Y-PLAN.md §1.2.
 *
 * En modo `multi` conserva el comportamiento original, ahora leyendo el
 * parámetro `domain` del snapshot en vez de consultar la central.
 */
class DomainTenantMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        if (CentralConfig::isSingle()) {
            return $next($request);
        }

        try {
            $header = (string) $request->header('x-subdomain');

            $parts = explode('.', $header);

            $domain = implode('.', array_slice($parts, -2));
            $subdomain = implode('.', array_slice($parts, 0, count($parts) - 2));

            $adminDomain = CentralConfig::get('domain');

            if (! $adminDomain) {
                throw new Exception('No puedes acceder desde este dominio');
            }

            if ($domain !== $adminDomain) {
                throw new Exception('No puedes acceder desde este dominio');
            }

            $rowSubdomain = Domain::where('tenant_id', tenant('id'))->first();

            if (! $rowSubdomain) {
                throw new Exception('Tu institución no se encuentra registrada');
            }

            if ($subdomain !== $rowSubdomain->domain) {
                throw new Exception('Tu institución no se encuentra registrada');
            }

            return $next($request);
        } catch (Exception $e) {
            return Response::notFound($e->getMessage());
        }
    }
}
