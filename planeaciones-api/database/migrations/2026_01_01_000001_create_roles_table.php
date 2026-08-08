<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 50)->unique(); // Administrador, Director, Docente, Revisor, Secretario
            $table->boolean('activo')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        // Catálogo fijo de roles del sistema
        DB::table('roles')->insert([
            ['nombre' => 'Administrador', 'activo' => true, 'created_at' => now(), 'updated_at' => now()],
            ['nombre' => 'Director', 'activo' => true, 'created_at' => now(), 'updated_at' => now()],
            ['nombre' => 'Docente', 'activo' => true, 'created_at' => now(), 'updated_at' => now()],
            ['nombre' => 'Revisor', 'activo' => true, 'created_at' => now(), 'updated_at' => now()],
            ['nombre' => 'Secretario', 'activo' => true, 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('roles');
    }
};
