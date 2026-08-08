<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SecuenciaFaseActividad extends Model
{
    protected $table = 'secuencia_fase_actividades';

    protected $fillable = [
        'fase_id', 'numero', 'metodos_tecnicas', 'actividades_docente',
        'actividades_estudiante', 'evidencia_aprendizaje', 'medios_materiales',
    ];

    public function fase()
    {
        return $this->belongsTo(SecuenciaUnidadFase::class, 'fase_id');
    }

    public function revision()
    {
        return $this->morphOne(Revision::class, 'revisable');
    }
}
