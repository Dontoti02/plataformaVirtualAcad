<?php

namespace Modules\Shared\Services;

use Illuminate\Support\Facades\Mail;
use Modules\Tenant\Modules\SystemConfiguration\Models\MailSetting;
use Modules\Tenant\Modules\SystemConfiguration\Helpers\SystemConfigurationHelper;
use Throwable;

/**
 * Aplica a Laravel el correo que la institución configuró en Ajustes.
 *
 * Solo en modo single: en el modelo compartido el correo sigue saliendo del
 * .env. Se llama antes de cada job de la cola (ahí es donde se envían los
 * correos) y antes del correo de prueba.
 */
class MailSettings
{
    public static function apply(): void
    {
        if (config('tenancy.deploy_mode') !== 'single') {
            return;
        }

        try {
            $settings = MailSetting::current();
        } catch (Throwable $e) {
            // Durante una actualización la cola arranca con el código nuevo
            // antes de que se aplique la migración: sin tabla, no se envía.
            config(['mail.default' => 'log']);
            logger()->warning('Correo sin configurar: '.$e->getMessage());

            return;
        }

        if (! self::isUsable($settings)) {
            // Desactivado o incompleto: los correos van al log, no se pierden
            // en un envío que fallaría ni se acumulan en failed_jobs.
            config(['mail.default' => 'log']);

            return;
        }

        $implicitTls = $settings->port === 465 || $settings->encryption === 'ssl';

        config([
            'mail.default'                 => 'smtp',
            'mail.mailers.smtp.scheme'     => $implicitTls ? 'smtps' : 'smtp',
            'mail.mailers.smtp.host'       => $settings->host,
            'mail.mailers.smtp.port'       => $settings->port,
            'mail.mailers.smtp.encryption' => $settings->encryption === 'none' ? null : $settings->encryption,
            'mail.mailers.smtp.username'   => $settings->username,
            'mail.mailers.smtp.password'   => $settings->password,
            'mail.mailers.smtp.timeout'    => 20,
            'mail.from.address'            => $settings->from_address,
            'mail.from.name'               => $settings->from_name ?: self::institutionName(),
        ]);

        // El mailer se construye una vez y se reutiliza: sin esto, un cambio
        // de configuración no se notaría hasta reiniciar el worker.
        Mail::purge('smtp');
    }

    public static function isUsable(MailSetting $settings): bool
    {
        return $settings->enabled && $settings->host && $settings->port && $settings->from_address;
    }

    private static function institutionName(): string
    {
        try {
            return (string) SystemConfigurationHelper::getInstitutionName();
        } catch (Throwable) {
            return (string) config('app.name');
        }
    }
}
