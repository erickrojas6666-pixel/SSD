<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Token de confirmación de cuenta enviado por correo al crear un usuario.
        // Al confirmar, se marca users.email_verified_at y se borra este registro.
        Schema::create('confirmaciones_cuenta', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('token', 64)->unique();
            $table->timestamp('expires_at');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('confirmaciones_cuenta');
    }
};
