<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SecuenciaUnidadEvidencia extends Model
{
    protected $table = 'secuencia_unidad_evidencias';

    protected $fillable = [
        'unidad_id',
        'evidencia_aprendizaje',
        'tipo_evaluacion',
        'ponderacion',
        'instrumento_evaluacion',
        'orden',
    ];

    public function unidad()
    {
        return $this->belongsTo(SecuenciaUnidad::class, 'unidad_id');
    }

    public function revision()
    {
        return $this->morphOne(Revision::class, 'revisable');
    }
}
