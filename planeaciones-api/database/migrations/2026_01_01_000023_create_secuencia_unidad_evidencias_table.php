<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Sección C (repetible): una fila por cada evidencia de aprendizaje de la unidad
        Schema::create('secuencia_unidad_evidencias', function (Blueprint $table) {
            $table->id();
            $table->foreignId('unidad_id')->constrained('secuencia_unidades')->cascadeOnDelete();

            $table->string('evidencia_aprendizaje', 300);
            $table->decimal('ponderacion', 5, 2); // suma de todas las evidencias de la unidad = 100%
            $table->string('instrumento_evaluacion', 150); // lista de cotejo, rúbrica, cuestionario, etc.

            $table->unsignedSmallInteger('orden')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('secuencia_unidad_evidencias');
    }
};
