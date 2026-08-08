<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SecuenciaUnidadFase extends Model
{
    protected $table = 'secuencia_unidad_fases';

    protected $fillable = ['unidad_id', 'fase']; // apertura, desarrollo, cierre

    public function unidad()
    {
        return $this->belongsTo(SecuenciaUnidad::class, 'unidad_id');
    }

    public function actividades()
    {
        return $this->hasMany(SecuenciaFaseActividad::class, 'fase_id')->orderBy('numero');
    }

    public function revision()
    {
        return $this->morphOne(Revision::class, 'revisable');
    }
}
