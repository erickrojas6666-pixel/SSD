<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * El director ya no "valida" con un solo clic: descarga el formato
     * UTH-ACA-DC-F-PVSD/14 prellenado, lo firma (subiendo el archivo ya
     * firmado, o dibujando una firma digital) y ese documento final (PDF
     * real, con encabezado y pie de página) queda como comprobante de la
     * validación.
     */
    public function up(): void
    {
        Schema::table('secuencias', function (Blueprint $table) {
            $table->string('documento_validacion_url')->nullable()->after('fecha_validacion');
            $table->enum('documento_validacion_origen', ['archivo_subido', 'firma_digital'])
                ->nullable()->after('documento_validacion_url');
        });
    }

    public function down(): void
    {
        Schema::table('secuencias', function (Blueprint $table) {
            $table->dropColumn(['documento_validacion_url', 'documento_validacion_origen']);
        });
    }
};
