<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Sección B: Unidades de aprendizaje (I, II, III...)
        Schema::create('secuencia_unidades', function (Blueprint $table) {
            $table->id();
            $table->foreignId('secuencia_id')->constrained('secuencias')->cascadeOnDelete();

            $table->unsignedTinyInteger('numero'); // 1, 2, 3...
            $table->string('nombre', 150);
            $table->text('proposito_esperado');

            $table->unsignedSmallInteger('horas_saber');
            $table->unsignedSmallInteger('horas_saber_hacer');
            $table->unsignedSmallInteger('horas_totales');

            // Porcentaje de la unidad (punto 21 del instructivo): se calcula por
            // regla de 3 a partir de horas_totales de la unidad vs el total de la asignatura
            $table->decimal('porcentaje_unidad', 5, 2)->default(0);

            $table->timestamps();
            $table->unique(['secuencia_id', 'numero']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('secuencia_unidades');
    }
};
