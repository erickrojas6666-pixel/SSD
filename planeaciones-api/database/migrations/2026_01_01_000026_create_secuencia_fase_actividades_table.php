<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Sección D (repetible): una fila por cada actividad de la fase,
        // numeradas consecutivamente por el docente (referenciadas luego en el SII)
        Schema::create('secuencia_fase_actividades', function (Blueprint $table) {
            $table->id();
            $table->foreignId('fase_id')->constrained('secuencia_unidad_fases')->cascadeOnDelete();

            $table->unsignedSmallInteger('numero'); // numeración consecutiva del docente

            $table->text('metodos_tecnicas');
            $table->text('actividades_docente');
            $table->text('actividades_estudiante');
            $table->string('evidencia_aprendizaje', 300)->nullable();
            $table->text('medios_materiales');

            $table->timestamps();
            $table->unique(['fase_id', 'numero']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('secuencia_fase_actividades');
    }
};
