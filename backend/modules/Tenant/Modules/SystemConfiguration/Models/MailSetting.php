<?php

namespace Modules\Tenant\Modules\SystemConfiguration\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Correo saliente de la institución. Una sola fila.
 *
 * La contraseña se guarda cifrada con el APP_KEY de la institución, que el
 * panel genera una vez y conserva entre despliegues.
 */
class MailSetting extends Model
{
    protected $table = 'mail_settings';

    protected $fillable = [
        'enabled',
        'host',
        'port',
        'encryption',
        'username',
        'password',
        'from_address',
        'from_name',
    ];

    protected $casts = [
        'enabled'  => 'boolean',
        'port'     => 'integer',
        'password' => 'encrypted',
    ];

    /** Nunca hacia una respuesta ni un log. */
    protected $hidden = ['password'];

    public static function current(): self
    {
        return static::query()->firstOrCreate([], [
            'enabled'    => false,
            'host'       => 'smtp.gmail.com',
            'port'       => 587,
            'encryption' => 'tls',
        ]);
    }
}
