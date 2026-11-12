<?php

declare(strict_types=1);

namespace Modules\Tenant\Middleware;

use Closure;
use Illuminate\Http\Request;
use Modules\Admin\Models\Tenant;
use RuntimeException;

/**
 * Inicializa tenancy en modo `single`: una institución por VPS.
 *
 * A diferencia de InitializeTenancyByRequestDomain, este middleware NO lee la
 * cabecera X-subdomain ni consulta la tabla `domain` de la BD central. El tenant
 * se hidrata en memoria a partir del .env, de modo que:
 *
 *   - el dominio con el que se sirva el frontend es irrelevante (el cliente puede
 *     usar app.suinstituto.edu.pe, aula.otro.pe o lo que contrate);
 *   - no hay ninguna consulta remota en el camino crítico de cada petición;
 *   - tenant('id') sigue funcionando en todo el código que ya lo usa.
 *
 * Ver ANALISIS-Y-PLAN.md §1.2 y §3.1.
 */
class InitializeTenancySingle
{
    /** El tenant se hidrata una sola vez por proceso. */
    protected static ?Tenant $tenant = null;

    public function handle(Request $request, Closure $next)
    {
        if (! tenancy()->initialized) {
            tenancy()->initialize(static::tenant());
        }

        return $next($request);
    }

    /**
     * Tenant en memoria, construido desde el .env. No se persiste ni se consulta.
     */
    public static function tenant(): Tenant
    {
        if (static::$tenant instanceof Tenant) {
            return static::$tenant;
        }

        $id = config('tenancy.single.tenant_id');
        $database = config('tenancy.single.database');

        if (empty($id) || empty($database)) {
            throw new RuntimeException(
                'DEPLOY_MODE=single requiere TENANT_ID y DB_TENANT_DATABASE en el .env.'
            );
        }

        $tenant = new Tenant();
        $tenant->exists = true;          // evita que stancl intente crearlo
        $tenant->setAttribute($tenant->getKeyName(), $id);
        $tenant->setAttribute('tenancy_db_name', $database);
        $tenant->syncOriginal();

        return static::$tenant = $tenant;
    }

    /** Para tests: olvida el tenant memorizado. */
    public static function forget(): void
    {
        static::$tenant = null;
    }
}
