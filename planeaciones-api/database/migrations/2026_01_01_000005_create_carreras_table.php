<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('carreras', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 150);
            $table->string('clave', 20)->unique();

            // Un director solo puede tener una carrera, y una carrera solo un director.
            // La unicidad de director_id garantiza ambas restricciones.
            $table->foreignId('director_id')->nullable()
                ->constrained('users')->nullOnDelete();
            $table->unique('director_id');

            $table->boolean('activo')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('carreras');
    }
};
