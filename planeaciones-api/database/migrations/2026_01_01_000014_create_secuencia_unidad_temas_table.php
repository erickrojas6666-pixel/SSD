<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Sección C: Temas por unidad -> Saber / Saber Hacer / Ser y Convivir
        Schema::create('secuencia_unidad_temas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('unidad_id')->constrained('secuencia_unidades')->cascadeOnDelete();

            $table->string('tema', 200);
            $table->text('saber');         // dimensión conceptual
            $table->text('saber_hacer');   // dimensión actuacional
            $table->text('ser_convivir')->nullable(); // dimensión socioafectiva (no siempre aplica a cada tema)

            $table->unsignedSmallInteger('orden')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('secuencia_unidad_temas');
    }
};
