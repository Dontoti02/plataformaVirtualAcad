<?php

declare(strict_types=1);

namespace Modules\Shared\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Modules\Tenant\Middleware\InitializeTenancySingle;
use Throwable;

/**
 * Aplica una versión nueva sobre una institución YA instalada.
 *
 * Es la contraparte de institution:install: aquel siembra una institución nueva,
 * este solo pone al día una existente. No toca usuarios, ni la configuración que
 * la institución haya editado, ni vuelve a sembrar catálogos.
 *
 * Lo ejecuta deploy/scripts/update.sh DESPUÉS de que el backup haya corrido y
 * los contenedores estén con la imagen nueva.  Ver ANALISIS-Y-PLAN.md §3.7.
 */
class InstitutionUpgradeCommand extends Command
{
    protected $signature = 'institution:upgrade
                            {--pending : Solo listar lo que se aplicaría, sin aplicar}
                            {--skip-sync : No ejecutar central:sync}';

    protected $description = 'Aplica una versión nueva sobre una institución ya instalada';

    public function handle(): int
    {
        if (config('tenancy.deploy_mode') !== 'single') {
            $this->error('institution:upgrade solo aplica en DEPLOY_MODE=single.');

            return self::FAILURE;
        }

        tenancy()->initialize(InitializeTenancySingle::tenant());

        // Una institución sin instalar no se "actualiza": se instala.
        if (! $this->isInstalled()) {
            $this->error('Esta institución no está instalada. Usa institution:install.');

            return self::FAILURE;
        }

        $from = $this->currentRelease();

        if ($this->option('pending')) {
            return $this->showPending();
        }

        $this->line("Versión anterior: {$from}");
        $this->line('Versión nueva:    '.config('app.release'));

        $this->newLine();
        $this->comment('1/3  Migraciones');
        $this->call('migrate', [
            '--force' => true,
            '--path'  => $this->tenantMigrationPaths(),
        ]);

        $this->newLine();
        $this->comment('2/3  Configuración global');
        if ($this->option('skip-sync')) {
            $this->line('   omitido (--skip-sync)');
        } else {
            // Un fallo aquí no aborta la actualización: la institución sigue con
            // el snapshot anterior, que es el comportamiento buscado (§3.2).
            $this->call('central:sync');
        }

        $this->newLine();
        $this->comment('3/3  Cachés');
        $this->call('config:cache');
        $this->call('route:cache');
        $this->call('event:cache');

        $this->recordRelease();

        $this->newLine();
        $this->info('Actualizada a '.config('app.release').'.');

        return self::SUCCESS;
    }

    private function showPending(): int
    {
        $this->call('migrate:status', [
            '--path' => $this->tenantMigrationPaths(),
        ]);

        return self::SUCCESS;
    }

    private function isInstalled(): bool
    {
        try {
            return DB::table('user')->exists();
        } catch (Throwable) {
            return false;
        }
    }

    /**
     * Deja constancia de la versión desplegada en la propia BD, para que el
     * heartbeat y /api/health puedan reportarla al panel aunque el contenedor
     * se recree.
     */
    private function recordRelease(): void
    {
        DB::table('central_config')->updateOrInsert(
            ['key' => 'deployed_release'],
            [
                'type'       => 'string',
                'value'      => config('app.release'),
                'synced_at'  => now(),
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );
    }

    private function currentRelease(): string
    {
        return (string) (DB::table('central_config')
            ->where('key', 'deployed_release')
            ->value('value') ?: 'desconocida');
    }

    /** @return array<int, string> */
    private function tenantMigrationPaths(): array
    {
        // En esta línea las migraciones del tenant son archivos sueltos en una
        // sola carpeta, no carpetas por feature como en la otra.
        return ['modules/Tenant/migrations'];
    }
}
