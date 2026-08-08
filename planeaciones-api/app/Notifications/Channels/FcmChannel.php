<?php

namespace App\Notifications\Channels;

use App\Models\DeviceToken;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;
use Kreait\Firebase\Contract\Messaging;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification as FcmNotification;

class FcmChannel
{
    public function __construct(private Messaging $messaging) {}

    /**
     * Envía la notificación a todos los dispositivos (fcm_token) del usuario.
     * Si el notifiable no tiene tokens registrados (nunca emparejó un reloj),
     * simplemente no hace nada.
     */
    public function send($notifiable, Notification $notification): void
    {
        if (! method_exists($notification, 'toFcm')) {
            return;
        }

        $tokens = $notifiable->deviceTokens()->pluck('fcm_token');

        if ($tokens->isEmpty()) {
            return;
        }

        $payload = $notification->toFcm($notifiable);

        $mensajeBase = CloudMessage::new()
            ->withNotification(FcmNotification::create(
                $payload['notification']['title'],
                $payload['notification']['body'],
            ))
            ->withData($payload['data'] ?? []);

        foreach ($tokens as $token) {
            try {
                $this->messaging->send($mensajeBase->withChangedTarget('token', $token));
            } catch (\Throwable $e) {
                Log::warning('FcmChannel: no se pudo enviar el push', [
                    'user_id' => $notifiable->id,
                    'token' => $token,
                    'error' => $e->getMessage(),
                ]);

                // Si el token ya no es válido (app desinstalada, etc.), lo limpiamos
                // para no seguir intentando enviarle en cada cambio de estado.
                if (
                    str_contains($e->getMessage(), 'not-found')
                    || str_contains($e->getMessage(), 'not a valid FCM registration token')
                    || str_contains($e->getMessage(), 'Unregistered')
                ) {
                    DeviceToken::where('fcm_token', $token)->delete();
                }
            }
        }
    }
}
