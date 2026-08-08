<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Sección D: cada unidad tiene exactamente 3 fases (apertura, desarrollo, cierre)
        Schema::create('secuencia_unidad_fases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('unidad_id')->constrained('secuencia_unidades')->cascadeOnDelete();
            $table->enum('fase', ['apertura', 'desarrollo', 'cierre']);

            $table->timestamps();
            $table->unique(['unidad_id', 'fase']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('secuencia_unidad_fases');
    }
};
