<?php

declare(strict_types=1);

namespace Modules\Shared\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Modules\Tenant\Middleware\InitializeTenancySingle;

/**
 * Deja una institución nueva lista para usarse, en un VPS recién aprovisionado.
 *
 * Reemplaza al flujo que hoy vive repartido entre InstitutionRepository::create()
 * (que hace un CREATE DATABASE y una consulta cross-database imposible entre
 * servidores distintos) y el botón `createUser` del panel admin.
 *
 * Es IDEMPOTENTE: volver a ejecutarlo no duplica el usuario ni pisa la
 * configuración que la institución ya haya editado.
 *
 * Ver ANALISIS-Y-PLAN.md §3.7.
 */
class InstitutionInstallCommand extends Command
{
    protected $signature = 'institution:install
                            {--institution-id= : Id de la institución en el panel}
                            {--name=           : Nombre visible de la institución}
                            {--admin-email=    : Correo del usuario administrador}
                            {--admin-name=Administrador : Nombre del usuario administrador}
                            {--admin-password= : Contraseña; si se omite se genera una}
                            {--quota-mb=       : Cuota de almacenamiento; por defecto, del disco real}
                            {--skip-sync       : No ejecutar central:sync al final}';

    protected $description = 'Instala una institución nueva: migraciones, catálogos, usuario admin y configuración';

    /** Rol ADMINISTRADOR sembrado por feature-0001-base. */
    private const ROL_ADMINISTRADOR = 4;

    public function handle(): int
    {
        if (config('tenancy.deploy_mode') !== 'single') {
            $this->error('institution:install solo aplica en DEPLOY_MODE=single.');

            return self::FAILURE;
        }

        // El nombre es el único dato realmente obligatorio: es lo que se pide al
        // registrar la institución en el orquestador. Lo demás se deduce del
        // .env que el panel ya generó para este VPS.
        $institutionId = $this->option('institution-id') ?: config('tenancy.single.institution_id');
        $name = $this->option('name');

        if (! $name) {
            $this->error('Falta --name (el nombre de la institución).');

            return self::FAILURE;
        }

        if (! $institutionId) {
            $this->error('Falta --institution-id y no hay INSTITUTION_ID en el .env.');

            return self::FAILURE;
        }

        // Sin correo explícito se deriva del dominio del frontend, que el panel
        // ya escribió en APP_FRONTEND_URL.
        $adminEmail = $this->option('admin-email') ?: $this->deriveAdminEmail();

        if (! $adminEmail) {
            $this->error('Falta --admin-email y no se pudo deducir de APP_FRONTEND_URL.');

            return self::FAILURE;
        }

        tenancy()->initialize(InitializeTenancySingle::tenant());

        $this->step('1/6  Esquema y catálogos de la institución');
        $this->migrateTenant();

        $this->step('2/6  Tablas de autonomía (central_config, institution, …)');
        // Incluidas en el mismo paso anterior; feature-0007 vive junto al resto.

        $this->step('3/6  Nombre de la institución');
        $this->applyName($name);

        $this->step('4/6  Usuario administrador');
        $password = $this->option('admin-password') ?: Str::password(20, true, true, false);
        $created = $this->ensureAdminUser($adminEmail, $this->option('admin-name'), $password);

        $this->step('5/6  Cuota de almacenamiento');
        $this->ensureStorage((int) $institutionId, $name);

        $this->step('6/6  Configuración global');
        if ($this->option('skip-sync')) {
            $this->line('   omitido (--skip-sync)');
        } else {
            $this->call('central:sync');
        }

        // Solo si falta. `storage:link` devuelve ERROR cuando el enlace ya existe,
        // y el orquestador marcaría la fase como fallida en una reinstalación.
        // (--force no basta: en Windows el enlace no es un symlink real.)
        if (! file_exists(public_path('storage'))) {
            $this->call('storage:link');
        }
        $this->call('config:cache');
        $this->call('route:cache');
        $this->call('event:cache');

        // Deja constancia de la versión, igual que institution:upgrade, para que
        // el panel sepa qué corre cada VPS aunque se recree el contenedor.
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

        $this->newLine();
        $this->info('Institución instalada.');

        if ($created) {
            $this->newLine();
            $this->warn('Credenciales del administrador — entrégalas por canal seguro y no las repitas en logs:');
            $this->line("   usuario:    {$adminEmail}");
            $this->line("   contraseña: {$password}");
        } else {
            $this->line('El usuario administrador ya existía; no se tocó su contraseña.');
        }

        return self::SUCCESS;
    }

    /**
     * Las migraciones de tenant viven en carpetas por feature; hay que pasarlas
     * todas explícitamente. No se usa `tenants:migrate` porque itera la tabla
     * `tenant` de la BD central, que en este modo no existe.
     */
    private function migrateTenant(): void
    {
        // Una sola carpeta: en esta línea las migraciones no van por features.
        $paths = ['modules/Tenant/migrations'];
        $this->call('migrate', [
            '--force' => true,
            '--path'  => $paths,
        ]);
    }

    /**
     * admin@<dominio del frontend>. Es un punto de partida razonable cuando el
     * orquestador aún no tiene el correo real de la dirección del instituto.
     */
    private function deriveAdminEmail(): ?string
    {
        $host = parse_url((string) config('app.frontend_url'), PHP_URL_HOST);

        if (! $host) {
            return null;
        }

        // app.instituto.edu.pe → instituto.edu.pe
        $parts = explode('.', $host);
        $domain = count($parts) > 2 ? implode('.', array_slice($parts, 1)) : $host;

        return "admin@{$domain}";
    }

    private function applyName(string $name): void
    {
        DB::table('system_configuration')
            ->where('key', 'application_name')
            ->update(['value' => $name, 'updated_at' => now()]);

        $this->line("   application_name = {$name}");
    }

    /** @return bool true si lo creó, false si ya existía */
    private function ensureAdminUser(string $email, string $names, string $password): bool
    {
        $existing = DB::table('user')->where('email', $email)->first();

        if ($existing) {
            return false;
        }

        return DB::transaction(function () use ($email, $names, $password) {
            // `person` no tiene email (vive en `user`) y document_number/phone son
            // NOT NULL. Se replican los marcadores que ya usaba el panel admin.
            $personId = DB::table('person')->insertGetId([
                // En esta línea `document_type` es obligatorio y se guarda así.
                'document_type'   => 'D.N.I.',
                'document_number' => 'xxxxxxxx',
                'names'           => $names,
                'email'           => $email,
                'phone'           => 'xxxxxxxxx',
                'created_at'      => now(),
                'updated_at'      => now(),
            ]);

            $userId = DB::table('user')->insertGetId([
                'person_id'    => $personId,
                'email'        => $email,
                'rol_id'       => self::ROL_ADMINISTRADOR,
                'password'     => Hash::make($password),
                'default_user' => true,
                'created_at'   => now(),
                'updated_at'   => now(),
            ]);

            // rol_user no tiene timestamps.
            DB::table('rol_user')->insert([
                'user_id' => $userId,
                'rol_id'  => self::ROL_ADMINISTRADOR,
            ]);

            return true;
        });
    }

    /**
     * La cuota se deriva del disco real del VPS en vez de heredar el 51200 fijo
     * del modelo compartido: con archivos en disco local, prometer 50 GB en un
     * disco de 80 GB que además aloja MySQL y los backups no sale.
     */
    private function ensureStorage(int $institutionId, string $name): void
    {
        $quota = $this->option('quota-mb');

        if (! $quota) {
            $free = @disk_free_space(storage_path()) ?: 0;
            $freeMb = (int) floor($free / 1048576);
            // Reserva la mitad para MySQL, imágenes de Docker, logs y backups.
            $quota = max(1024, (int) floor($freeMb * 0.5));
            $this->line("   derivada del disco: {$quota} MB libres asignados de {$freeMb} MB");
        }

        $folder = 'institution_'.$institutionId;

        $exists = DB::table('institution_storage')->where('institution_id', $institutionId)->exists();

        if ($exists) {
            DB::table('institution_storage')
                ->where('institution_id', $institutionId)
                ->update(['limit_space_mb' => $quota, 'updated_at' => now()]);
        } else {
            DB::table('institution_storage')->insert([
                'institution_id' => $institutionId,
                'folder_name'    => $folder,
                'limit_space_mb' => $quota,
                'used_space_mb'  => 0,
                'free_space_mb'  => $quota,
                'created_at'     => now(),
                'updated_at'     => now(),
            ]);
        }
    }

    private function step(string $text): void
    {
        $this->newLine();
        $this->comment($text);
    }
}
