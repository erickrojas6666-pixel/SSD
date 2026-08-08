<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Sección A: Carátula del documento
        Schema::create('secuencia_caratulas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('secuencia_id')->unique()->constrained('secuencias')->cascadeOnDelete();

            $table->string('programa_educativo', 200); // ej. Ing. en TI e Innovación Digital
            $table->text('proposito_aprendizaje');
            $table->text('competencia');
            $table->string('tipo_competencia', 50); // Específica / Genérica
            $table->decimal('creditos', 5, 2);
            $table->string('modalidad', 30); // Escolarizada, etc.
            $table->unsignedSmallInteger('horas_saber');
            $table->unsignedSmallInteger('horas_saber_hacer');
            $table->unsignedSmallInteger('horas_totales');
            $table->unsignedSmallInteger('horas_semana');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('secuencia_caratulas');
    }
};
