<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Una evidencia puede combinar dos o más tipos de evaluación (N:M)
        Schema::create('evidencia_tipo_evaluacion', function (Blueprint $table) {
            $table->id();
            $table->foreignId('evidencia_id')->constrained('secuencia_unidad_evidencias')->cascadeOnDelete();
            $table->foreignId('tipo_evaluacion_id')->constrained('tipos_evaluacion');
            $table->timestamps();
            $table->unique(['evidencia_id', 'tipo_evaluacion_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('evidencia_tipo_evaluacion');
    }
};
