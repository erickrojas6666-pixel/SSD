<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Eliminar la vista dependiente primero para liberar la tabla
        DB::statement("DROP VIEW IF EXISTS vw_evidencia_detalle CASCADE;");

        // 2. Ahora sí, puedes borrar o modificar la tabla sin problemas
        Schema::dropIfExists('evidencia_tipo_evaluacion');
        // Se simplifica: un solo campo de texto con el tipo (o combinación de tipos)
        // de evaluación, en vez de la relación N:M anterior.
        Schema::table('secuencia_unidad_evidencias', function (Blueprint $table) {
            $table->string('tipo_evaluacion', 60)->nullable()->after('evidencia_aprendizaje');
        });

        Schema::dropIfExists('evidencia_tipo_evaluacion');
        Schema::dropIfExists('tipos_evaluacion');
    }

    public function down(): void
    {
        Schema::create('tipos_evaluacion', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 30)->unique();
            $table->timestamps();
        });

        Schema::create('evidencia_tipo_evaluacion', function (Blueprint $table) {
            $table->id();
            $table->foreignId('evidencia_id')->constrained('secuencia_unidad_evidencias')->cascadeOnDelete();
            $table->foreignId('tipo_evaluacion_id')->constrained('tipos_evaluacion');
            $table->timestamps();
            $table->unique(['evidencia_id', 'tipo_evaluacion_id']);
        });

        Schema::table('secuencia_unidad_evidencias', function (Blueprint $table) {
            $table->dropColumn('tipo_evaluacion');
        });
    }
};
