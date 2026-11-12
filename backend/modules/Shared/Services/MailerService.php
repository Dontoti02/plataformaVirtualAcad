<?php

namespace Modules\Shared\Services;

use App\Mail\NotificationMailable;
use Exception;

use Illuminate\Support\Facades\Mail;

class MailerService
{
  public static function send(object $data)
  {
    try {
      // Al encolar, Laravel ata el correo al mailer activo EN ESTE momento: si
      // aquí siguiera el `log` del .env, el worker lo mandaría al log aunque la
      // institución tenga el SMTP configurado. El worker vuelve a aplicarlo
      // antes de enviar, con la configuración vigente.
      MailSettings::apply();

      $subject = $data->subject;
      $body = $data->body;
      $to = $data->to;
      $cc = $data->cc ?? [];

      $template = new NotificationMailable($subject, $body);

      // En un VPS por institución el remitente es la cuenta que ella configura
      // en Ajustes (config mail.from, lo fija MailSettings). Un remitente de otro
      // dominio lo reescribe o lo marca como suplantación el propio Gmail.
      if (config('tenancy.deploy_mode') !== 'single') {
        $template->from('notificaciones@latinaeduca.com', 'Notificaciones Latina Educa');
      }

      Mail::to($to)->cc($cc)->queue($template);
    } catch (Exception $e) {
      logger('Error al enviar el correo: ' . $e->getMessage());
    }
  }
}
