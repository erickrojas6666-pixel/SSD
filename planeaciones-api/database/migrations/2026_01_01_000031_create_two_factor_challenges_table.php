<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Se crea al validar correo/contraseña cuando el usuario tiene 2FA activo.
        // El login no queda completo hasta verificar el código con este token.
        Schema::create('two_factor_challenges', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('token', 64)->unique();
            $table->timestamp('expires_at');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('two_factor_challenges');
    }
};
