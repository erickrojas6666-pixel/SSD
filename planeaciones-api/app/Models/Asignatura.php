<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Asignatura extends Model
{
    protected $table = 'asignaturas';

    use SoftDeletes;

    protected $fillable = [
        'nombre',
        'nombre_normalizado',
        'clave',
        'cuatrimestre_id',
        'plan_estudio_url',
        'activo',
    ];

    protected static function booted(): void
    {
        // Cada vez que cambia el nombre, se recalcula su versión normalizada
        // (mayúsculas, sin acentos, sin espacios dobles) para poder detectar duplicados.
        static::saving(function (Asignatura $asignatura) {
            if ($asignatura->isDirty('nombre')) {
                $asignatura->nombre_normalizado = self::normalizar($asignatura->nombre);
            }
        });
    }

    public static function normalizar(string $texto): string
    {
        $texto = mb_strtoupper(trim($texto), 'UTF-8');
        $texto = strtr($texto, [
            'Á' => 'A',
            'É' => 'E',
            'Í' => 'I',
            'Ó' => 'O',
            'Ú' => 'U',
            'Ü' => 'U',
            'Ñ' => 'N',
        ]);

        return preg_replace('/\s+/', ' ', $texto);
    }

    public function cuatrimestre()
    {
        return $this->belongsTo(Cuatrimestre::class);
    }

    public function especialidades()
    {
        return $this->belongsToMany(Especialidad::class, 'asignatura_especialidad');
    }

    public function docentes()
    {
        return $this->belongsToMany(User::class, 'docente_asignatura');
    }

    public function secuencias()
    {
        return $this->hasMany(Secuencia::class);
    }
}
