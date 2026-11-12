<?php
namespace Modules\Tenant\Modules\JobOpportunities\Company\Application\Notifications;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class CompanyAccessCredentials extends Notification
{
    public function __construct(
        private string $email,
        private string $password
    ) {}

    public function via($notifiable)
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Tus credenciales de acceso')
            ->greeting('Hola ' . $notifiable->name)
            ->line('Gracias por registrar tu empresa.')
            ->line('Estas son tus credenciales de acceso:')
            ->line('Email: ' . $this->email)
            ->line('Contraseña: ' . $this->password)
            ->action('Iniciar sesión', url('/login'))
            ->line('Por seguridad, te recomendamos cambiar tu contraseña al ingresar.');
    }
}
