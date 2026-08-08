<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('asignaturas', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 150);
            $table->string('clave', 20)->nullable(); // ej. E-FDP-1

            // La asignatura pertenece a un solo cuatrimestre
            $table->foreignId('cuatrimestre_id')->constrained('cuatrimestres');

            // Plan de estudio: PDF opcional almacenado en la nube, se guarda solo la URL
            $table->string('plan_estudio_url', 500)->nullable();

            $table->boolean('activo')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('asignaturas');
    }
};
