<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Una secuencia puede tener varios autores (docentes), y un docente varias secuencias
        Schema::create('secuencia_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('secuencia_id')->constrained('secuencias')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['secuencia_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('secuencia_user');
    }
};
