<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Comentarios que el revisor/director dejan y el docente puede ver
        Schema::create('secuencia_comentarios', function (Blueprint $table) {
            $table->id();
            $table->foreignId('secuencia_id')->constrained('secuencias')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users'); // quién comenta (revisor/director)

            $table->text('comentario');
            $table->boolean('visible_docente')->default(true);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('secuencia_comentarios');
    }
};
