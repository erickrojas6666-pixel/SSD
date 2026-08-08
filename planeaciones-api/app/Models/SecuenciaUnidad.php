<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SecuenciaUnidad extends Model
{
    protected $table = 'secuencia_unidades';

    protected $fillable = [
        'secuencia_id',
        'numero',
        'nombre',
        'proposito_esperado',
        'horas_saber',
        'horas_saber_hacer',
        'horas_totales',
        'porcentaje_unidad',
    ];

    public function secuencia()
    {
        return $this->belongsTo(Secuencia::class);
    }

    public function temas()
    {
        return $this->hasMany(SecuenciaUnidadTema::class, 'unidad_id')->orderBy('orden');
    }

    public function evaluacion()
    {
        return $this->hasOne(SecuenciaUnidadEvaluacion::class, 'unidad_id');
    }

    public function revision()
    {
        return $this->morphOne(Revision::class, 'revisable');
    }

    public function evidencias()
    {
        return $this->hasMany(SecuenciaUnidadEvidencia::class, 'unidad_id')->orderBy('orden');
    }

    public function fases()
    {
        return $this->hasMany(SecuenciaUnidadFase::class, 'unidad_id');
    }

    public function faseApertura()
    {
        return $this->hasOne(SecuenciaUnidadFase::class, 'unidad_id')->where('fase', 'apertura');
    }

    public function faseDesarrollo()
    {
        return $this->hasOne(SecuenciaUnidadFase::class, 'unidad_id')->where('fase', 'desarrollo');
    }

    public function faseCierre()
    {
        return $this->hasOne(SecuenciaUnidadFase::class, 'unidad_id')->where('fase', 'cierre');
    }
}
