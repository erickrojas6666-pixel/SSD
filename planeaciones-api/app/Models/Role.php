<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Role extends Model
{
    protected $table = 'roles';
    
    use SoftDeletes;

    protected $fillable = ['nombre', 'activo'];

    public function users()
    {
        return $this->belongsToMany(User::class, 'role_user');
    }
}
