<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Cuatrimestre extends Model
{
    protected $table = 'cuatrimestres';
    
    protected $fillable = ['numero', 'nombre', 'activo'];

    public function asignaturas()
    {
        return $this->hasMany(Asignatura::class);
    }
}
