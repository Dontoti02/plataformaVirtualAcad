<?php

namespace Modules\Tenant\Modules\SystemConfiguration\Repositories;

use App\Mail\NotificationMailable;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Modules\Shared\Services\MailSettings;
use Modules\Tenant\Modules\SystemConfiguration\Models\MailSetting;
use Modules\Tenant\Modules\User\Models\User;
use Modules\Tenant\Modules\User\Enums\RolTenant;
use Throwable;

/**
 * Correo saliente de la institución, desde Ajustes del sistema.
 */
class MailSettingsRepository
{
    private const ROLES = [RolTenant::SECRETARY, RolTenant::ADMINISTRADOR];

    public static function get(): array
    {
        User::authenticated(self::ROLES);

        return self::present(MailSetting::current());
    }

    public static function update(Request $request): array
    {
        User::authenticated(self::ROLES);

        $enabled = filter_var($request->input('enabled'), FILTER_VALIDATE_BOOLEAN);

        $validator = Validator::make($request->all(), [
            'enabled'      => 'required',
            'host'         => [$enabled ? 'required' : 'nullable', 'string', 'max:255'],
            'port'         => [$enabled ? 'required' : 'nullable', 'integer', 'between:1,65535'],
            'encryption'   => 'nullable|in:tls,ssl,none',
            'username'     => 'nullable|string|max:255',
            'password'     => 'nullable|string|max:255',
            'from_address' => [$enabled ? 'required' : 'nullable', 'email', 'max:255'],
            'from_name'    => 'nullable|string|max:255',
        ], [
            'host.required'         => 'Indica el servidor SMTP.',
            'port.required'         => 'Indica el puerto.',
            'from_address.required' => 'Indica el correo remitente.',
            'from_address.email'    => 'El correo remitente no es válido.',
        ]);

        if ($validator->fails()) {
            throw new Exception($validator->errors()->first());
        }

        $settings = MailSetting::current();

        $settings->fill([
            'enabled'      => $enabled,
            'host'         => $request->input('host'),
            'port'         => $request->input('port') ?: null,
            'encryption'   => $request->input('encryption') ?: 'tls',
            'username'     => $request->input('username'),
            'from_address' => $request->input('from_address'),
            'from_name'    => $request->input('from_name'),
        ]);

        // Vacía = conservar la actual: el formulario nunca recibe la contraseña.
        if (filled($request->input('password'))) {
            $settings->password = $request->input('password');
        }

        $settings->save();

        return self::present($settings);
    }

    /**
     * Envía un correo AHORA, sin pasar por la cola, para que el error del
     * servidor SMTP llegue a la pantalla en vez de perderse en el worker.
     */
    public static function test(Request $request): string
    {
        User::authenticated(self::ROLES);

        $to = (string) $request->input('to');

        if (! filter_var($to, FILTER_VALIDATE_EMAIL)) {
            throw new Exception('Indica un correo de destino válido.');
        }

        if (! MailSettings::isUsable(MailSetting::current())) {
            throw new Exception('El envío de correos está desactivado o incompleto. Actívalo, complétalo y guarda antes de probar.');
        }

        MailSettings::apply();

        try {
            Mail::to($to)->send(new NotificationMailable(
                'Correo de prueba',
                '<p>Este es un correo de prueba de la plataforma académica.</p>'
                .'<p>Si lo recibes, el envío de correos de la institución está bien configurado.</p>'
            ));
        } catch (Throwable $e) {
            throw new Exception(self::explain($e->getMessage()));
        }

        return "Correo de prueba enviado a {$to}.";
    }

    private static function present(MailSetting $settings): array
    {
        return [
            'enabled'      => $settings->enabled,
            'host'         => $settings->host,
            'port'         => $settings->port,
            'encryption'   => $settings->encryption ?: 'tls',
            'username'     => $settings->username,
            'has_password' => filled($settings->getRawOriginal('password')),
            'from_address' => $settings->from_address,
            'from_name'    => $settings->from_name,
        ];
    }

    /** Los errores de SMTP son crípticos; los casos habituales, en claro. */
    private static function explain(string $error): string
    {
        $hint = match (true) {
            str_contains($error, '535'), str_contains($error, 'Username and Password not accepted')
                => 'El servidor rechazó el usuario o la contraseña. Con Gmail hay que usar una «contraseña de aplicación» (requiere la verificación en dos pasos), no la contraseña normal de la cuenta.',
            str_contains($error, 'Connection could not be established'), str_contains($error, 'timed out')
                => 'No se pudo conectar con el servidor SMTP. Revisa el servidor y el puerto (Gmail: smtp.gmail.com, 587 con TLS o 465 con SSL).',
            str_contains($error, 'certificate'), str_contains($error, 'SSL'), str_contains($error, 'TLS')
                => 'Falló la conexión segura. Prueba con 587 + TLS o con 465 + SSL.',
            default => null,
        };

        return $hint ? "{$hint} (Detalle: {$error})" : "No se pudo enviar: {$error}";
    }
}
