<?php

declare(strict_types=1);

namespace Modules\Shared\Console;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Modules\Shared\Services\CentralConfig;
use Throwable;

/**
 * Hornea en local la configuración global que vive en el panel.
 *
 * Se ejecuta en el despliegue, por cron diario y bajo demanda vía webhook.
 * Si el panel no responde, NO borra nada: la institución sigue operando con el
 * último snapshot válido. Esa es la razón de ser de todo el diseño — un VPS no
 * puede depender de que la infraestructura central esté viva.
 *
 * Ver ANALISIS-Y-PLAN.md §3.2 y §5.3 (opción C).
 */
class CentralSyncCommand extends Command
{
    protected $signature = 'central:sync
                            {--from-file= : Lee el snapshot de un archivo JSON en vez de llamar al panel}
                            {--dry-run    : Muestra lo que traería sin escribir nada}';

    protected $description = 'Sincroniza la configuración global desde el panel hacia las tablas locales';

    public function handle(): int
    {
        if (! CentralConfig::isSingle()) {
            $this->warn('central:sync solo aplica en DEPLOY_MODE=single. Nada que hacer.');

            return self::SUCCESS;
        }

        // Modo PUSH: el orquestador deja el snapshot por SSH y solo ejecuta este
        // comando. No hace falta que el VPS pueda alcanzar al panel, lo que
        // permite operarlo desde una máquina local detrás de NAT.
        // Ver ANALISIS-Y-PLAN.md §3.2.
        if ($file = $this->option('from-file')) {
            if (! is_readable($file)) {
                $this->error("No se puede leer {$file}.");

                return self::FAILURE;
            }

            $payload = json_decode((string) file_get_contents($file), true);

            if (! is_array($payload)) {
                $this->error("{$file} no contiene un JSON válido.");

                return self::FAILURE;
            }

            $this->write($payload);
            CentralConfig::flush();
            $this->info('Configuración central sincronizada desde archivo.');

            return self::SUCCESS;
        }

        // Modo PULL: el VPS consulta al panel. Requiere que PANEL_URL sea
        // alcanzable desde el servidor de la institución.
        $url = rtrim((string) config('services.panel.url'), '/');
        $token = (string) config('services.panel.token');
        $institutionId = config('tenancy.single.institution_id');

        if ($url === '' || $token === '') {
            $this->error('Faltan PANEL_URL o PANEL_TOKEN en el .env. Si el panel no es '
                .'alcanzable desde este servidor, usa --from-file.');

            return self::FAILURE;
        }

        try {
            $response = Http::withToken($token)
                ->acceptJson()
                ->timeout(20)
                ->retry(3, 2000)
                ->get("{$url}/api/deploy/institutions/{$institutionId}/config");

            if (! $response->successful()) {
                throw new \RuntimeException("el panel respondió {$response->status()}");
            }

            $payload = $response->json();
        } catch (Throwable $e) {
            // Fallo blando y deliberado: la app sigue con el snapshot anterior.
            $this->error('No se pudo sincronizar: '.$e->getMessage());
            $this->line('Se conserva el snapshot anterior; la institución sigue operando.');

            return self::FAILURE;
        }

        if ($this->option('dry-run')) {
            $this->line(json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

            return self::SUCCESS;
        }

        $this->write($payload);

        CentralConfig::flush();

        $this->info('Configuración central sincronizada.');

        return self::SUCCESS;
    }

    /** Convierte una fecha ISO 8601 a la zona local del despliegue. */
    protected static function localDate(?string $value): ?string
    {
        return $value === null || $value === ''
            ? null
            : Carbon::parse($value)->setTimezone(config('app.timezone'))->toDateTimeString();
    }

    /**
     * Escritura transaccional: o entra todo el snapshot o no entra nada.
     */
    protected function write(array $payload): void
    {
        $now = now();

        DB::transaction(function () use ($payload, $now) {
            foreach ($payload['config'] ?? [] as $key => $entry) {
                $value = is_array($entry) ? ($entry['value'] ?? null) : $entry;
                $type = is_array($entry) ? ($entry['type'] ?? 'string') : 'string';

                DB::table('central_config')->updateOrInsert(
                    ['key' => $key],
                    [
                        'type'       => $type,
                        'value'      => is_scalar($value) || $value === null
                            ? $value
                            : json_encode($value, JSON_UNESCAPED_UNICODE),
                        'synced_at'  => $now,
                        'updated_at' => $now,
                        'created_at' => $now,
                    ]
                );
            }

            if ($institution = $payload['institution'] ?? null) {
                $attributes = collect($institution)->except('id')->all();

                // Las fechas llegan en ISO 8601 con offset. Se convierten a la
                // zona de ESTA institución antes de guardarlas, porque la columna
                // es un DATETIME sin zona y el middleware de suscripción las
                // compara contra `now()` local. Ver ANALISIS-Y-PLAN.md §8.9.
                foreach (['start_date', 'end_date'] as $field) {
                    if (! empty($attributes[$field])) {
                        $attributes[$field] = Carbon::parse($attributes[$field])
                            ->setTimezone(config('app.timezone'))
                            ->toDateTimeString();
                    }
                }

                DB::table('institution')->updateOrInsert(
                    ['id' => $institution['id']],
                    array_merge($attributes, ['updated_at' => $now, 'created_at' => $now])
                );
            }

            if (array_key_exists('modules', $payload)) {
                $keys = [];

                foreach ($payload['modules'] as $module) {
                    $keys[] = $module['module_key'];

                    DB::table('institution_module')->updateOrInsert(
                        [
                            'institution_id' => $module['institution_id'],
                            'module_key'     => $module['module_key'],
                        ],
                        [
                            'name'       => $module['name'] ?? null,
                            'is_active'  => (bool) ($module['is_active'] ?? false),
                            'start_date' => static::localDate($module['start_date'] ?? null),
                            'end_date'   => static::localDate($module['end_date'] ?? null),
                            'settings'   => isset($module['settings'])
                                ? json_encode($module['settings'], JSON_UNESCAPED_UNICODE)
                                : null,
                            'updated_at' => $now,
                            'created_at' => $now,
                        ]
                    );
                }

                // Un módulo que el panel ya no lista es un módulo revocado.
                DB::table('institution_module')
                    ->when($keys !== [], fn ($q) => $q->whereNotIn('module_key', $keys))
                    ->delete();
            }
        });
    }
}
