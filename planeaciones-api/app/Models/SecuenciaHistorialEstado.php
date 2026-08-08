<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SecuenciaHistorialEstado extends Model
{
    protected $table = 'secuencia_historial_estados';

    protected $fillable = ['secuencia_id', 'user_id', 'estado_anterior', 'estado_nuevo', 'comentario'];

    public function secuencia()
    {
        return $this->belongsTo(Secuencia::class);
    }

    public function usuario()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
