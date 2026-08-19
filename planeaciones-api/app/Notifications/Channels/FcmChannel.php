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

        foreach ($tokens as $token) {
            try {
                $message = CloudMessage::new()
                    ->withToken($token)
                    ->withNotification(FcmNotification::create(
                        $payload['notification']['title'],
                        $payload['notification']['body']
                    ))
                    ->withData($payload['data'] ?? []);

                $this->messaging->send($message);

            } catch (\Throwable $e) {
                Log::warning('FcmChannel: no se pudo enviar el push', [
                    'user_id' => $notifiable->id,
                    'token' => $token,
                    'error' => $e->getMessage(),
                ]);

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