<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // Asegura que el rol Administrador ya exista (viene sembrado en la migración de roles)
        $rolAdmin = Role::where('nombre', 'Administrador')->first();

        $user = User::updateOrCreate(
            ['email' => 'miguel7angel12morales2005@gmail.com'],
            [
                'nombre' => 'Miguel Angel',
                'apellido_paterno' => 'Morales',
                'apellido_materno' => null,
                'password' => Hash::make('password'), // ⚠️ cambia esto antes de producción
                'activo' => true,
                'email_verified_at' => now(),
            ]
        );

        if ($rolAdmin) {
            $user->roles()->syncWithoutDetaching([$rolAdmin->id]);
        }
    }
}