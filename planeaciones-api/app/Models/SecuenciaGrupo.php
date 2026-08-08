<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SecuenciaGrupo extends Model
{
    protected $table = 'secuencia_grupos';

    protected $fillable = ['secuencia_id', 'grupo'];

    public function secuencia()
    {
        return $this->belongsTo(Secuencia::class);
    }
}
