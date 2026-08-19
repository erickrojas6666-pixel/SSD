<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Notification;
use App\Notifications\SecuenciaCambioEstadoNotification;
use Illuminate\Support\Facades\Log;

class Secuencia extends Model
{
    protected $table = 'secuencias';

    use SoftDeletes;

    protected $fillable = [
        'asignatura_id',
        'especialidad_id',
        'carrera_id',
        'periodo',
        'estado',
        'fecha_solicitud_revision',
        'fecha_validacion',
        'documento_validacion_url',
        'documento_validacion_origen',
        'activo',
    ];

    const ESTADOS = [
        'borrador',
        'enviado_revision',
        'en_revision',
        'en_proceso_validacion',
        'validada',
        'rechazada',
    ];

    public function asignatura()
    {
        return $this->belongsTo(Asignatura::class);
    }

    public function especialidad()
    {
        return $this->belongsTo(Especialidad::class);
    }

    public function carrera()
    {
        return $this->belongsTo(Carrera::class);
    }

    // Autores (docentes) de la secuencia
    public function autores()
    {
        return $this->belongsToMany(User::class, 'secuencia_user');
    }

    // Grupos en los que se imparte (carátula, punto 6: "Grupo(s)")
    public function grupos()
    {
        return $this->hasMany(SecuenciaGrupo::class);
    }

    public function caratula()
    {
        return $this->hasOne(SecuenciaCaratula::class);
    }

    public function unidades()
    {
        return $this->hasMany(SecuenciaUnidad::class)->orderBy('numero');
    }

    public function referencias()
    {
        return $this->hasMany(SecuenciaReferencia::class);
    }

    public function comentarios()
    {
        return $this->hasMany(SecuenciaComentario::class);
    }

    public function evidencias()
    {
        return $this->hasMany(SecuenciaUnidadEvidencia::class, 'unidad_id');
    }

    public function historialEstados()
    {
        return $this->hasMany(SecuenciaHistorialEstado::class)->latest();
    }

    // Regla de negocio: una vez solicitada la revisión, ya no se puede editar ni eliminar
    public function puedeEditarse(): bool
    {
        return $this->estado === 'borrador';
    }

    // Cambia de estado dejando rastro en el historial
    public function cambiarEstado(string $nuevoEstado, User $usuario, ?string $comentario = null): void
    {
        $estadoAnterior = $this->estado;

        $this->historialEstados()->create([
            'estado_anterior' => $this->estado,
            'estado_nuevo' => $nuevoEstado,
            'user_id' => $usuario->id,
            'comentario' => $comentario,
        ]);

        $this->update(['estado' => $nuevoEstado]);

        $destinatarios = $this->autores()->get();

        Log::info("Enviando notificación de cambio de estado a los autores de la secuencia ID {$usuario->id}.");
        Log::info("Destinatarios: " . $destinatarios->pluck('email')->implode(', '));
        Notification::send($destinatarios, new SecuenciaCambioEstadoNotification($this, $estadoAnterior));
    }
}
