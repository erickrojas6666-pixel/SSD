<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('secuencias', function (Blueprint $table) {
            $table->id();

            $table->foreignId('asignatura_id')->constrained('asignaturas');
            $table->foreignId('especialidad_id')->constrained('especialidades');
            // carrera se puede derivar de especialidad, pero se guarda directo para
            // facilitar búsquedas por carrera sin hacer join extra
            $table->foreignId('carrera_id')->constrained('carreras');

            $table->string('periodo', 30); // ej. "Mayo - Agosto 2026"

            $table->enum('estado', [
                'borrador',
                'enviado_revision',
                'en_revision',
                'en_proceso_validacion',
                'validada',
                'rechazada',
            ])->default('borrador');

            $table->timestamp('fecha_solicitud_revision')->nullable();
            $table->timestamp('fecha_validacion')->nullable();

            $table->boolean('activo')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['carrera_id', 'periodo', 'estado']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('secuencias');
    }
};
