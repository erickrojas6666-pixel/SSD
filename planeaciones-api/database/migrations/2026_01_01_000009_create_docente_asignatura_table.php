<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Un docente puede tener varias asignaturas, de distintas especialidades y carreras
        Schema::create('docente_asignatura', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('asignatura_id')->constrained('asignaturas')->cascadeOnDelete();
            $table->boolean('activo')->default(true);
            $table->timestamps();
            $table->unique(['user_id', 'asignatura_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('docente_asignatura');
    }
};
