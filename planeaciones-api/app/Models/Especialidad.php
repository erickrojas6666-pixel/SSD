<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Especialidad extends Model
{
    protected $table = 'especialidades';
    
    use SoftDeletes;

    protected $fillable = ['nombre', 'clave', 'carrera_id', 'activo'];

    public function carrera()
    {
        return $this->belongsTo(Carrera::class);
    }

    public function asignaturas()
    {
        return $this->belongsToMany(Asignatura::class, 'asignatura_especialidad');
    }
}
