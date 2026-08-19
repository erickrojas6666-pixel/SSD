<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ResetPasswordNotification extends Notification
{
    use Queueable;

    public $token;

    public function __construct($token)
    {
        $this->token = $token;
    }
    
    public function via($notifiable)
    {
        return ['mail'];
    }

    public function toMail($notifiable)
    {
        // Usar una variable de entorno para el frontend (recomendado) 
        // O coloca directamente la URL de producción de tu frontend si ya la tienes fija
        $frontendUrl = env('FRONTEND_URL', 'https://ssd-psi.vercel.app'); // Ajusta si tu frontend está en otro dominio de Vercel

        $url = "{$frontendUrl}/restablecer-password?token={$this->token}&email=" . urlencode($notifiable->email);

        return (new MailMessage)
            ->subject('Recuperación de Contraseña')
            ->greeting('¡Hola!')
            ->line('Estás recibiendo este correo porque recibimos una solicitud de restablecimiento de contraseña para tu cuenta.')
            ->action('Restablecer Contraseña', $url)
            ->line('Este enlace de restablecimiento de contraseña expirará en 60 minutos.')
            ->line('Si no solicitaste un restablecimiento de contraseña, no se requiere ninguna otra acción.')
            ->salutation('Atentamente, El equipo de soporte.');
    }
}