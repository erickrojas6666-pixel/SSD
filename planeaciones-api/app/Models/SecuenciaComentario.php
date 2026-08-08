<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SecuenciaComentario extends Model
{
    protected $table = 'secuencia_comentarios';

    protected $fillable = ['secuencia_id', 'user_id', 'comentario', 'visible_docente'];

    public function secuencia()
    {
        return $this->belongsTo(Secuencia::class);
    }

    public function autor()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
