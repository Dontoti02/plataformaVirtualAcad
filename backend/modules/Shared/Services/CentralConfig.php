<?php

declare(strict_types=1);

namespace Modules\Shared\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Modules\Admin\Models\Institution;
use Modules\Admin\Models\InstitutionModule;
use Modules\Admin\Models\SystemConfiguration;

/**
 * Punto único de acceso a la configuración que antes vivía en la BD central.
 *
 *   modo multi  → lee de la conexión `central`, como siempre.
 *   modo single → lee de las tablas locales `central_config`, `institution`,
 *                 `institution_module` e `institution_storage`, horneadas en el
 *                 despliegue por `central:sync`.
 *
 * En ambos modos memoriza por petición: los middlewares consultan lo mismo
 * varias veces y antes eso eran 4-6 viajes a la central por request.
 *
 * Ver ANALISIS-Y-PLAN.md §2 y §3.2.
 */
class CentralConfig
{
    /** Caché por petición. */
    protected static array $memo = [];

    public static function isSingle(): bool
    {
        return config('tenancy.deploy_mode') === 'single';
    }

    public static function flush(): void
    {
        static::$memo = [];
    }

    /**
     * Un parámetro global (los que en central viven en system_configuration).
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        return static::remember("param:{$key}", function () use ($key, $default) {
            if (! static::isSingle()) {
                $row = SystemConfiguration::where('key', $key)->first();

                return $row?->value ?? $default;
            }

            $row = DB::table('central_config')->where('key', $key)->first();

            if ($row === null) {
                return $default;
            }

            return static::cast($row->value, $row->type);
        });
    }

    /**
     * Datos de la institución de este despliegue.
     */
    public static function institution(): ?object
    {
        return static::remember('institution', function () {
            if (! static::isSingle()) {
                return Institution::query()
                    ->whereHas('domain', fn ($q) => $q->where('tenant_id', tenant('id')))
                    ->with('storage')
                    ->first();
            }

            return DB::table('institution')
                ->where('id', config('tenancy.single.institution_id'))
                ->first();
        });
    }

    public static function institutionId(): int|string|null
    {
        if (static::isSingle()) {
            return config('tenancy.single.institution_id');
        }

        return static::institution()?->id;
    }

    /**
     * Módulo contratado, o null si la institución no lo tiene.
     */
    public static function module(string $moduleKey): ?object
    {
        return static::remember("module:{$moduleKey}", function () use ($moduleKey) {
            $institutionId = static::institutionId();

            if ($institutionId === null) {
                return null;
            }

            if (! static::isSingle()) {
                return InstitutionModule::where('institution_id', $institutionId)
                    ->where('module_key', $moduleKey)
                    ->first();
            }

            return DB::table('institution_module')
                ->where('institution_id', $institutionId)
                ->where('module_key', $moduleKey)
                ->first();
        });
    }

    /**
     * ¿Está el módulo contratado, activo y dentro de sus fechas?
     *
     * Una sola regla para el menú y para las rutas. Antes el menú solo ocultaba
     * los módulos que existían marcados como inactivos, y las rutas exigían que
     * existieran y estuvieran activos: con la tabla vacía, «Bolsa Laboral»
     * aparecía en el menú y al entrar devolvía 403.
     */
    public static function moduleEnabled(string $moduleKey): bool
    {
        $module = static::module($moduleKey);

        if (! $module || ! $module->is_active) {
            return false;
        }

        $now = Carbon::now();

        return (! $module->start_date || $now->gte(Carbon::parse($module->start_date)->startOfDay()))
            && (! $module->end_date || $now->lte(Carbon::parse($module->end_date)->endOfDay()));
    }

    /**
     * Todos los módulos de la institución.
     */
    public static function modules(): array
    {
        return static::remember('modules', function () {
            $institutionId = static::institutionId();

            if ($institutionId === null) {
                return [];
            }

            if (! static::isSingle()) {
                return InstitutionModule::where('institution_id', $institutionId)->get()->all();
            }

            return DB::table('institution_module')
                ->where('institution_id', $institutionId)
                ->get()
                ->all();
        });
    }

    /**
     * Actualiza datos de la institución (nombre, logo) desde la propia app.
     * En modo single escribe en la tabla local; el panel es la fuente de verdad
     * y `central:sync` puede volver a pisarlo, así que se usa solo para lo que
     * la institución edita por su cuenta.
     */
    public static function updateInstitution(array $attributes): void
    {
        $institutionId = static::institutionId();

        if ($institutionId === null) {
            return;
        }

        $connection = static::isSingle() ? null : 'central';

        DB::connection($connection)
            ->table('institution')
            ->where('id', $institutionId)
            ->update($attributes + ['updated_at' => now()]);

        static::flush();
    }

    /**
     * URL pública del frontend de esta institución.
     *
     * Antes se componía como `<subdominio>.<dominio central>`, lo que solo
     * funciona en el modelo compartido. En un VPS por institución el dominio lo
     * pone el cliente y viene del .env.  Ver ANALISIS-Y-PLAN.md §1.2.
     */
    public static function frontendUrl(): string
    {
        if (static::isSingle()) {
            return rtrim((string) config('app.frontend_url'), '/');
        }

        $subdomain = \Modules\Admin\Models\Domain::where('tenant_id', tenant('id'))->value('domain');

        return 'https://'.$subdomain.'.'.static::get('domain');
    }

    /**
     * Cuota y consumo de almacenamiento.
     */
    public static function storage(): ?object
    {
        return static::remember('storage', function () {
            $institutionId = static::institutionId();

            if ($institutionId === null) {
                return null;
            }

            $connection = static::isSingle() ? null : 'central';

            return DB::connection($connection)
                ->table('institution_storage')
                ->where('institution_id', $institutionId)
                ->first();
        });
    }

    /**
     * Marca de la última sincronización. El panel la usa para saber si un VPS
     * quedó con configuración vieja.
     */
    public static function syncedAt(): ?string
    {
        if (! static::isSingle()) {
            return null;
        }

        return DB::table('central_config')->max('synced_at');
    }

    protected static function remember(string $key, callable $fn): mixed
    {
        if (! array_key_exists($key, static::$memo)) {
            static::$memo[$key] = $fn();
        }

        return static::$memo[$key];
    }

    protected static function cast(?string $value, string $type): mixed
    {
        if ($value === null) {
            return null;
        }

        return match ($type) {
            'number' => str_contains($value, '.') ? (float) $value : (int) $value,
            'bool'   => filter_var($value, FILTER_VALIDATE_BOOLEAN),
            'json', 'array' => json_decode($value, false),
            default  => $value,
        };
    }
}
