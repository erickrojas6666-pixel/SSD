<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Revision extends Model
{
    protected $table = 'revisiones';
    
    protected $fillable = ['revisable_type', 'revisable_id', 'revisor_id', 'comentario', 'aprobado', 'fecha_revision'];

    protected $casts = [
        'aprobado' => 'boolean',
        'fecha_revision' => 'datetime',
    ];

    public function revisable()
    {
        return $this->morphTo();
    }

    public function revisor()
    {
        return $this->belongsTo(User::class, 'revisor_id');
    }
}
