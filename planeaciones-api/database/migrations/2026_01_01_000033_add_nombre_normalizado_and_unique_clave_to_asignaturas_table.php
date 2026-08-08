<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('asignaturas', function (Blueprint $table) {
            // Para detectar duplicados sin importar mayúsculas/acentos/espacios extra
            $table->string('nombre_normalizado', 150)->nullable()->after('nombre')->index();
        });

        Schema::table('asignaturas', function (Blueprint $table) {
            // La clave ahora se genera automáticamente y debe ser única
            $table->unique('clave');
        });
    }

    public function down(): void
    {
        Schema::table('asignaturas', function (Blueprint $table) {
            $table->dropUnique(['clave']);
            $table->dropColumn('nombre_normalizado');
        });
    }
};
