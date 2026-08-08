<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // El documento no distingue entre referencia bibliográfica y digital,
        // ambas van en el mismo apartado ("Referencias bibliográficas y digitales")
        Schema::create('secuencia_referencias', function (Blueprint $table) {
            $table->id();
            $table->foreignId('secuencia_id')->constrained('secuencias')->cascadeOnDelete();

            $table->string('autor', 300);
            $table->string('titulo', 300);
            $table->text('referencia'); // editorial/lugar/año, o el vínculo, según aplique

            $table->unsignedSmallInteger('orden')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('secuencia_referencias');
    }
};
