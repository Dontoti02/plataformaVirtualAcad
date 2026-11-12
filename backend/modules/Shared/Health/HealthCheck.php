<?php

declare(strict_types=1);

namespace Modules\Shared\Health;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Modules\Shared\Services\JWTService;
use Modules\Tenant\Middleware\InitializeTenancySingle;
use Throwable;

class HealthCheck
{
    public static function run(): array
    {
        $checks = [
            'database'       => self::check(fn () => self::database()),
            'cache'          => self::check(fn () => self::cache()),
            'storage'        => self::check(fn () => self::storage()),
            'central_config' => self::check(fn () => self::centralConfig()),
            'jwt'            => self::check(fn () => self::jwt()),
        ];

        $failed = array_keys(array_filter($checks, fn ($c) => $c['ok'] === false));

        return [
            'status'      => $failed === [] ? 'ok' : 'degraded',
            'deploy_mode' => config('tenancy.deploy_mode'),
            'release'     => config('app.release', 'dev'),
            'checks'      => $checks,
            'failed'      => $failed,
        ];
    }

    private static function check(callable $fn): array
    {
        $start = microtime(true);

        try {
            $detail = $fn();
            $ok = true;
        } catch (Throwable $e) {
            $detail = $e->getMessage();
            $ok = false;
        }

        return [
            'ok'     => $ok,
            'detail' => $detail,
            'ms'     => (int) round((microtime(true) - $start) * 1000),
        ];
    }

    /** La BD local de la institución debe responder y tener el esquema aplicado. */
    private static function database(): string
    {
        if (config('tenancy.deploy_mode') === 'single' && ! tenancy()->initialized) {
            tenancy()->initialize(InitializeTenancySingle::tenant());
        }

        $count = DB::table('migrations')->count();

        return "{$count} migraciones aplicadas";
    }

    private static function cache(): string
    {
        $key = 'health:'.bin2hex(random_bytes(4));

        Cache::put($key, '1', 10);

        if (Cache::get($key) !== '1') {
            throw new \RuntimeException('la caché no devuelve lo que se escribió');
        }

        Cache::forget($key);

        return config('cache.default');
    }

    /** El volumen de archivos tiene que estar montado y ser escribible. */
    private static function storage(): string
    {
        $disk = config('filesystems.default');
        $probe = '.health-probe';

        Storage::disk($disk)->put($probe, (string) time());
        Storage::disk($disk)->delete($probe);

        // Las restaura el entrypoint en cada arranque. Sin ellas el logo, los
        // banners y el fondo de los certificados salen rotos.
        if (! is_file(storage_path('app/public/default/logo.png'))) {
            throw new \RuntimeException('faltan las imágenes por defecto (storage/app/public/default)');
        }

        return $disk;
    }

    /**
     * Sin JWT_SECRET nadie puede iniciar sesión aunque todo lo demás responda:
     * sin este check el postflight daría por bueno un despliegue inservible.
     */
    private static function jwt(): string
    {
        JWTService::valid(JWTService::sign(['health' => true]));

        return 'firma y verifica';
    }

    /**
     * En modo single la configuración global está horneada en local.
     * Que esté vieja no es un fallo — la app sigue operando — pero se reporta
     * para que el panel avise.
     */
    private static function centralConfig(): string
    {
        if (config('tenancy.deploy_mode') !== 'single') {
            return 'n/a (modo multi)';
        }

        $syncedAt = DB::table('central_config')->max('synced_at');

        if ($syncedAt === null) {
            throw new \RuntimeException('central_config vacía: falta ejecutar central:sync');
        }

        $days = (int) round(now()->diffInDays($syncedAt));

        return "sincronizada hace {$days} día(s)";
    }
}
