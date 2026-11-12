<?php

declare(strict_types=1);

namespace Modules\Shared\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Modules\Tenant\Middleware\InitializeTenancySingle;

/**
 * Cambia la contraseña de un usuario de la institución: para cuando el
 * administrador la olvida. La usa el panel desde «Accesos».
 *
 * La contraseña entra por la ENTRADA ESTÁNDAR, nunca como argumento: los
 * argumentos quedan en el log del despliegue y en la lista de procesos.
 *
 *   php artisan institution:admin-password admin@instituto.edu.pe < archivo
 *   php artisan institution:admin-password admin@instituto.edu.pe   (la pide)
 */
class InstitutionAdminPasswordCommand extends Command
{
    protected $signature = 'institution:admin-password
                            {email : Correo del usuario}';

    protected $description = 'Cambia la contraseña de un usuario de la institución (la lee de la entrada estándar)';

    public function handle(): int
    {
        if (config('tenancy.deploy_mode') !== 'single') {
            $this->error('institution:admin-password solo aplica en DEPLOY_MODE=single.');

            return self::FAILURE;
        }

        $email = (string) $this->argument('email');

        $password = stream_isatty(STDIN)
            ? (string) $this->secret('Contraseña nueva')
            : trim((string) stream_get_contents(STDIN));

        if (strlen($password) < 8) {
            $this->error('La contraseña debe tener al menos 8 caracteres.');

            return self::FAILURE;
        }

        tenancy()->initialize(InitializeTenancySingle::tenant());

        $updated = DB::table('user')->where('email', $email)->update([
            'password'   => Hash::make($password),
            'updated_at' => now(),
        ]);

        if ($updated === 0) {
            $this->error("No existe un usuario con el correo {$email}.");

            return self::FAILURE;
        }

        $this->info("Contraseña actualizada para {$email}.");

        return self::SUCCESS;
    }
}
