<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Tabla polimórfica: un comentario + estatus de aceptación del revisor
        // por cada registro de las secciones B, C y D (unidad, tema, evaluación,
        // evidencia, fase, actividad). Un solo registro por elemento revisado
        // (se sobreescribe si el revisor vuelve a revisar).
        Schema::create('revisiones', function (Blueprint $table) {
            $table->id();

            $table->string('revisable_type'); // usar morphMap (ver README) en vez del nombre completo de la clase
            $table->unsignedBigInteger('revisable_id');

            $table->foreignId('revisor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('comentario')->nullable();

            // null = pendiente de revisión, true = aceptado, false = rechazado
            $table->boolean('aprobado')->nullable();

            $table->timestamp('fecha_revision')->nullable();
            $table->timestamps();

            $table->unique(['revisable_type', 'revisable_id']);
            $table->index(['revisable_type', 'revisable_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('revisiones');
    }
};
