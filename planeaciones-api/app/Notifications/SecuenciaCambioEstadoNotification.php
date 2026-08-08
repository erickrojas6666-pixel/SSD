<?php

namespace App\Notifications;

use App\Models\Secuencia;
use App\Notifications\Channels\FcmChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class SecuenciaCambioEstadoNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private Secuencia $secuencia,
        private string $estadoAnterior,
    ) {}

    public function via($notifiable): array
    {
        return [FcmChannel::class];
    }

    /**
     * Payload que consume FcmChannel. Se mantiene simple: título/cuerpo para
     * la notificación del sistema y "data" para que la app del reloj pueda
     * navegar directo a la secuencia si el usuario toca la notificación.
     */
    public function toFcm($notifiable): array
    {
        $asignatura = $this->secuencia->asignatura->nombre ?? 'tu secuencia';

        return [
            'notification' => [
                'title' => 'Cambio de estado',
                'body' => sprintf(
                    '%s pasó de "%s" a "%s".',
                    $asignatura,
                    $this->estadoLegible($this->estadoAnterior),
                    $this->estadoLegible($this->secuencia->estado),
                ),
            ],
            'data' => [
                'secuencia_id' => (string) $this->secuencia->id,
                'estado_anterior' => $this->estadoAnterior,
                'estado_nuevo' => $this->secuencia->estado,
            ],
        ];
    }

    private function estadoLegible(string $estado): string
    {
        return str_replace('_', ' ', $estado);
    }
}
