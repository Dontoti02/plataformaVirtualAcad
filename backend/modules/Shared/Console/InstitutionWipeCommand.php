<?php

declare(strict_types=1);

namespace Modules\Shared\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Modules\Tenant\Middleware\InitializeTenancySingle;

/**
 * Vacía la institución para reinstalarla como el primer día.
 *
 * Borra TODAS las tablas de su base de datos y los
 * archivos subidos (conserva las imágenes por defecto). No reinstala: eso lo
 * hace `institution:install` justo después. Lo encadena el orquestador con un
 * backup previo, desde «Zona de peligro» → «Resetear base de datos».
 */
class InstitutionWipeCommand extends Command
{
    protected $signature = 'institution:wipe
                            {--force : Obligatorio: sin él no se borra nada}';

    protected $description = 'Borra todos los datos de la institución (BD y archivos) para reinstalarla desde cero';

    public function handle(): int
    {
        if (config('tenancy.deploy_mode') !== 'single') {
            $this->error('institution:wipe solo aplica en DEPLOY_MODE=single.');

            return self::FAILURE;
        }

        if (! $this->option('force')) {
            $this->error('Borra TODOS los datos de la institución. Repite con --force si es lo que quieres.');

            return self::FAILURE;
        }

        tenancy()->initialize(InitializeTenancySingle::tenant());

        $this->line('1/2  Base de datos de la institución');
        $this->call('db:wipe', ['--force' => true, '--drop-views' => true]);

        $this->line('2/2  Archivos subidos');
        $this->line('   '.$this->wipeFiles().' elementos borrados (se conservan las imágenes por defecto)');

        $this->newLine();
        $this->info('Institución vaciada. Falta reinstalarla con institution:install.');

        return self::SUCCESS;
    }

    /**
     * Todo storage/app salvo public/default: ahí están el logo, los banners y
     * el fondo de certificados que referencian los parámetros recién sembrados.
     */
    private function wipeFiles(): int
    {
        $root = storage_path('app');
        $deleted = 0;

        foreach (File::directories($root) as $dir) {
            if (basename($dir) !== 'public') {
                File::deleteDirectory($dir);
                $deleted++;

                continue;
            }

            foreach (File::directories($dir) as $sub) {
                if (basename($sub) !== 'default') {
                    File::deleteDirectory($sub);
                    $deleted++;
                }
            }

            foreach (File::files($dir) as $file) {
                File::delete($file->getPathname());
                $deleted++;
            }
        }

        foreach (File::files($root) as $file) {
            File::delete($file->getPathname());
            $deleted++;
        }

        return $deleted;
    }
}
