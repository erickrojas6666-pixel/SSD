<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ResetPasswordNotification extends Notification
{
    use Queueable;

    public $token; // Esta propiedad debe existir

    public function __construct($token) // Debe recibir el parámetro
    {
        $this->token = $token; // Debe asignarlo
    }
    
    public function via($notifiable)
    {
        return ['mail'];
    }

    public function toMail($notifiable)
    {
        // URL exacta de tu frontend con la ruta /restablecer-password y los parámetros requeridos
        $url = "http://localhost:5173/restablecer-password?token={$this->token}&email=" . urlencode($notifiable->email);

        return (new MailMessage)
            ->subject('Recuperación de Contraseña')
            ->greeting('¡Hola!')
            ->line('Estás recibiendo este correo porque recibimos una solicitud de restablecimiento de contraseña para tu cuenta.')
            ->action('Restablecer Contraseña', $url)
            ->line('Este enlace de restablecimiento de contraseña expirará en 60 minutos.')
            ->line('Si no solicitaste un restablecimiento de contraseña, no se requiere ninguna otra acción.')
            ->salutation('Atentamente, El equipo');
    }
}