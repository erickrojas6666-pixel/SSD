<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Sección C (encabezado): datos únicos por unidad.
        // Las evidencias (repetibles) van en secuencia_unidad_evidencias.
        Schema::create('secuencia_unidad_evaluaciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('unidad_id')->unique()->constrained('secuencia_unidades')->cascadeOnDelete();

            $table->unsignedTinyInteger('periodo_semanas'); // la suma de todas las unidades debe ser 15
            $table->text('resultado_aprendizaje'); // uno solo por unidad temática

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('secuencia_unidad_evaluaciones');
    }
};
