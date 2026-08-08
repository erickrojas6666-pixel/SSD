<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SecuenciaUnidadEvaluacion extends Model
{
    protected $table = 'secuencia_unidad_evaluaciones';

    protected $fillable = ['unidad_id', 'periodo_semanas', 'resultado_aprendizaje'];

    public function unidad()
    {
        return $this->belongsTo(SecuenciaUnidad::class, 'unidad_id');
    }

    public function revision()
    {
        return $this->morphOne(Revision::class, 'revisable');
    }
}
