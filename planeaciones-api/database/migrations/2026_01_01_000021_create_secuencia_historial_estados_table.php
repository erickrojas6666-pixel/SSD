<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Trazabilidad: registra cada cambio de estado de la secuencia (auditoría)
        Schema::create('secuencia_historial_estados', function (Blueprint $table) {
            $table->id();
            $table->foreignId('secuencia_id')->constrained('secuencias')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users'); // quién hizo el cambio

            $table->string('estado_anterior', 30)->nullable();
            $table->string('estado_nuevo', 30);
            $table->text('comentario')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('secuencia_historial_estados');
    }
};
