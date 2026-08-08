<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SecuenciaUnidadTema extends Model
{
    protected $table = 'secuencia_unidad_temas';

    protected $fillable = ['unidad_id', 'tema', 'saber', 'saber_hacer', 'ser_convivir', 'orden'];

    public function unidad()
    {
        return $this->belongsTo(SecuenciaUnidad::class, 'unidad_id');
    }

    public function revision()
    {
        return $this->morphOne(Revision::class, 'revisable');
    }
}
