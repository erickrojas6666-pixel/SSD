<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SecuenciaCaratula extends Model
{
    protected $table = 'secuencia_caratulas';

    protected $fillable = [
        'secuencia_id', 'programa_educativo', 'proposito_aprendizaje',
        'competencia', 'tipo_competencia', 'creditos', 'modalidad',
        'horas_saber', 'horas_saber_hacer', 'horas_totales', 'horas_semana',
    ];

    public function secuencia()
    {
        return $this->belongsTo(Secuencia::class);
    }
}
