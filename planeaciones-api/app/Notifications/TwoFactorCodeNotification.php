<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TwoFactorCodeNotification extends Notification
{
    use Queueable;

    public function __construct(protected string $codigo)
    {
    }

    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Tu código de verificación — Planeaciones Didácticas UTH')
            ->greeting('Verificación en dos pasos')
            ->line('Usa el siguiente código para completar tu inicio de sesión:')
            ->line("**{$this->codigo}**")
            ->line('Este código expira en 5 minutos.')
            ->line('Si no intentaste iniciar sesión, ignora este correo.');
    }
}
