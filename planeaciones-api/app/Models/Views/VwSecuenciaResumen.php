<?php

namespace App\Models\Views;

use Illuminate\Database\Eloquent\Model;

// Modelo de solo lectura: apunta a la vista vw_secuencia_resumen
class VwSecuenciaResumen extends Model
{
    protected $table = 'vw_secuencia_resumen';
    public $timestamps = false;
    protected $guarded = ['*']; // no se debe escribir en una vista

    public function getRouteKeyName()
    {
        return 'id';
    }
}
