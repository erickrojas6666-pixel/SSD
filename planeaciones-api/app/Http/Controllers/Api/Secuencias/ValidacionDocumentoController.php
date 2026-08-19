<?php

namespace App\Http\Controllers\Api\Secuencias;

use App\Http\Controllers\Controller;
use App\Models\Secuencia;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;

/**
 * El director ya no valida con un solo clic: descarga el formato oficial
 * "Validación de la Planeación Didáctica" (UTH-ACA-DC-F-PVSD/14) prellenado,
 * lo firma (imprimiéndolo y escaneándolo, o dibujando una firma digital) y
 * sube ese documento firmado. Ese archivo (un PDF real, con encabezado y pie
 * de página) queda guardado como comprobante de la validación.
 */
class ValidacionDocumentoController extends Controller
{
    /**
     * GET /api/director/secuencias/{secuencia}/formato-validacion
     * Genera y descarga el PDF prellenado (sin firmas) para que el director
     * lo imprima/firme, o lo use como base visual antes de firmar digital.
     */
    public function descargar(Request $request, Secuencia $secuencia)
    {
        try {
            $this->verificarPermiso($request, $secuencia);

            $pdf = $this->generarPdf($secuencia);

            $nombre = Str::slug($secuencia->asignatura?->nombre ?? 'secuencia') . '-validacion.pdf';

            return $pdf->download($nombre);
        } catch (ValidationException $e) {
            throw $e;
        } catch (Throwable $e) {
            Log::error('ValidacionDocumentoController@descargar: error al generar el formato', [
                'secuencia_id' => $secuencia->id,
                'mensaje' => $e->getMessage(),
                'linea' => $e->getLine(),
                'archivo' => $e->getFile(),
            ]);

            return response()->json(['message' => 'No se pudo generar el formato de validación.'], 500);
        }
    }

    /**
     * POST /api/director/secuencias/{secuencia}/validar
     * multipart/form-data con UNO de los dos:
     *   - documento: el formato ya firmado (PDF, JPG o PNG), o
     *   - firma_digital: PNG en base64 (data URL) dibujado en el navegador,
     *     con el que se genera el PDF final ya con la firma estampada.
     * + comentario (opcional).
     */
    public function subir(Request $request, Secuencia $secuencia)
    {
        try {
            $this->verificarPermiso($request, $secuencia);

            if ($secuencia->estado !== 'en_proceso_validacion') {
                return response()->json(['message' => "La secuencia ya no está en estado 'en_proceso_validacion'."], 422);
            }

            $data = $request->validate([
                'documento' => ['required_without:firma_digital', 'nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],
                'firma_digital' => ['required_without:documento', 'nullable', 'string'],
                'comentario' => ['nullable', 'string', 'max:1000'],
            ]);

            $carpeta = "validaciones/secuencia-{$secuencia->id}";

            if ($request->hasFile('documento')) {
                $ruta = $request->file('documento')->store($carpeta, 'public');
                $origen = 'archivo_subido';
            } else {
                $ruta = $this->guardarPdfConFirmaDigital($secuencia, $data['firma_digital'], $carpeta);
                $origen = 'firma_digital';
            }

            $secuencia->cambiarEstado('validada', $request->user(), $data['comentario'] ?? null);
            $secuencia->update([
                'fecha_validacion' => now(),
                'documento_validacion_url' => Storage::disk('public')->url($ruta),
                'documento_validacion_origen' => $origen,
            ]);

            return response()->json($secuencia->fresh(['asignatura', 'especialidad', 'carrera']));
        } catch (ValidationException $e) {
            throw $e;
        } catch (Throwable $e) {
            Log::error('ValidacionDocumentoController@subir: error al validar la secuencia', [
                'secuencia_id' => $secuencia->id,
                'mensaje' => $e->getMessage(),
                'linea' => $e->getLine(),
                'archivo' => $e->getFile(),
            ]);

            return response()->json(['message' => 'No se pudo guardar la validación.'], 500);
        }
    }

    private function verificarPermiso(Request $request, Secuencia $secuencia): void
    {
        if ($secuencia->carrera_id !== $request->user()->carreraDirigida()->value('id')) {
            abort(403, 'No diriges la carrera de esta secuencia.');
        }
    }

    /**
     * Arma el PDF real (con membrete UTH y pie de página con código/revisión)
     * a partir de la plantilla Blade. Requiere barryvdh/laravel-dompdf
     * (composer require barryvdh/laravel-dompdf).
     */
    private function generarPdf(Secuencia $secuencia, ?string $firmaBase64 = null)
    {
        $secuencia->loadMissing(['asignatura.cuatrimestre', 'especialidad', 'carrera', 'autores', 'grupos']);

        // El campo "periodo" se guarda como "Mayo - Agosto 2026": separamos
        // el cuatrimestre (para marcar el checkbox correcto) del año.
        $partes = preg_match('/^(.*)\s(\d{4})$/', trim($secuencia->periodo ?? ''), $m) ? $m : null;
        $cuatrimestreLabel = $partes[1] ?? null;
        $anioPeriodo = $partes[2] ?? null;

        $logo = public_path('img/uth.webp');
        $logoPath = file_exists($logo) ? $logo : null;

        return \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.validacion-secuencia', [
            'secuencia' => $secuencia,
            'firmaBase64' => $firmaBase64,
            'cuatrimestreLabel' => $cuatrimestreLabel,
            'anioPeriodo' => $anioPeriodo,
            'logoPath' => $logoPath,
        ])->setPaper('letter');
    }

    private function guardarPdfConFirmaDigital(Secuencia $secuencia, string $firmaBase64, string $carpeta): string
    {
        $pdf = $this->generarPdf($secuencia, $firmaBase64);

        $ruta = "{$carpeta}/" . uniqid('validacion_firma_') . '.pdf';
        Storage::disk('public')->put($ruta, $pdf->output());

        return $ruta;
    }
}
