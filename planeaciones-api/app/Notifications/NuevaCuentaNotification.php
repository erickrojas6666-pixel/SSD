<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NuevaCuentaNotification extends Notification
{
    use Queueable;

    public function __construct(
        protected string $passwordTemporal,
        protected ?string $linkConfirmacion = null,
    ) {
    }

    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        $mensaje = (new MailMessage)
            ->subject('Tu cuenta en Planeaciones Didácticas UTH')
            ->greeting("¡Hola, {$notifiable->nombre}!")
            ->line('Se creó una cuenta para ti en el Sistema de Gestión de Planeaciones Didácticas de la UTH.')
            ->line("**Correo:** {$notifiable->email}")
            ->line("**Contraseña temporal:** {$this->passwordTemporal}")
            ->line('Te recomendamos cambiarla después de tu primer inicio de sesión.');

        if ($this->linkConfirmacion) {
            $mensaje->action('Confirmar mi cuenta', $this->linkConfirmacion)
                ->line('Este enlace de confirmación expira en 7 días.');
        }

        return $mensaje;
    }
}
