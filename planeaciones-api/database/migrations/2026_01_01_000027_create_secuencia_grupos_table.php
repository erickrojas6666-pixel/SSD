<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Carátula: "Grupo(s)" -- una secuencia puede impartirse en varios grupos
        Schema::create('secuencia_grupos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('secuencia_id')->constrained('secuencias')->cascadeOnDelete();
            $table->string('grupo', 30);

            $table->timestamps();
            $table->unique(['secuencia_id', 'grupo']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('secuencia_grupos');
    }
};
