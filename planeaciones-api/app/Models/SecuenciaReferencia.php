<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SecuenciaReferencia extends Model
{
    protected $table = 'secuencia_referencias';

    protected $fillable = ['secuencia_id', 'autor', 'titulo', 'referencia', 'orden'];

    public function secuencia()
    {
        return $this->belongsTo(Secuencia::class);
    }

    public function revision()
    {
        return $this->morphOne(Revision::class, 'revisable');
    }
}
