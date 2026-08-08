<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TipoEvaluacion extends Model
{
    protected $table = 'tipos_evaluacion';
    
    protected $fillable = ['nombre'];

    public function evidencias()
    {
        return $this->belongsToMany(SecuenciaUnidadEvidencia::class, 'evidencia_tipo_evaluacion', 'tipo_evaluacion_id', 'evidencia_id');
    }
}
