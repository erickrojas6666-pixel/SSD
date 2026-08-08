<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tipos_evaluacion', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 30)->unique(); // Autoevaluación, Coevaluación, Heteroevaluación
            $table->timestamps();
        });

        DB::table('tipos_evaluacion')->insert([
            ['nombre' => 'Autoevaluación', 'created_at' => now(), 'updated_at' => now()],
            ['nombre' => 'Coevaluación', 'created_at' => now(), 'updated_at' => now()],
            ['nombre' => 'Heteroevaluación', 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('tipos_evaluacion');
    }
};
