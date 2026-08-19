<?php

namespace App\Http\Controllers\Api\Secuencias;

use App\Http\Controllers\Controller;
use App\Models\Secuencia;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

/**
 * Genera el PDF completo de la "Planeación Didáctica del Programa de
 * Asignatura" (UTH-ACA-DC-F-PSDA/02): carátula, información y secuencia
 * didáctica de cada unidad de aprendizaje, y referencias. No debe
 * confundirse con el formato de "Validación de la Planeación Didáctica"
 * (UTH-ACA-DC-F-PVSD/14) que genera ValidacionDocumentoController.
 */
class PlaneacionDocumentoController extends Controller
{
    private const RELACIONES = [
        'asignatura.cuatrimestre',
        'especialidad',
        'carrera',
        'autores',
        'grupos',
        'caratula',
        'unidades.temas',
        'unidades.evaluacion',
        'unidades.evidencias',
        'unidades.fases.actividades',
        'referencias',
    ];

    /**
     * GET /api/secuencias/{secuencia}/documento-planeacion
     */
    public function descargar(Request $request, Secuencia $secuencia)
    {
        try {
            $this->verificarPermiso($request, $secuencia);

            $secuencia->load(self::RELACIONES);

            $utLogo = public_path('img/ut.png');
            $uthLogo = public_path('img/uth.webp');

            $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.planeacion-secuencia', [
                'secuencia' => $secuencia,
                'utLogoPath' => file_exists($utLogo) ? $utLogo : null,
                'uthLogoPath' => file_exists($uthLogo) ? $uthLogo : null,
            ])->setPaper('a4', 'portrait');

            // El pie de página (CÓDIGO / REVISIÓN / Página X de Y) se dibuja
            // directamente sobre el canvas de Dompdf, no por CSS: es la única
            // forma confiable de que {PAGE_COUNT} se resuelva con el total
            // real de páginas ya generadas (por CSS salía "de 0").
            $pdf->render();
            $canvas = $pdf->getDomPDF()->getCanvas();
            $anchoHoja = $canvas->get_width();
            $altoHoja = $canvas->get_height();
            $y = $altoHoja - 40;

            $canvas->page_text(30, $y, 'CÓDIGO: UTH-ACA-DC-F-PSDA/02', null, 7.5, [0, 0, 0]);
            $canvas->page_text(30, $y + 11, 'REVISIÓN: 3', null, 7.5, [0, 0, 0]);
            $canvas->page_text($anchoHoja - 110, $y + 5, 'Página {PAGE_NUM} de {PAGE_COUNT}', null, 8, [0, 0, 0]);

            $nombre = Str::slug($secuencia->asignatura?->nombre ?? 'secuencia') . '-planeacion.pdf';

            return response($pdf->output(), 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="' . $nombre . '"',
            ]);
        } catch (Throwable $e) {
            Log::error('PlaneacionDocumentoController@descargar: error al generar la planeación', [
                'secuencia_id' => $secuencia->id,
                'mensaje' => $e->getMessage(),
                'linea' => $e->getLine(),
                'archivo' => $e->getFile(),
            ]);

            return response()->json(['message' => 'No se pudo generar el documento de la planeación.'], 500);
        }
    }

    private function verificarPermiso(Request $request, Secuencia $secuencia): void
    {
        $usuario = $request->user();

        $esAutor = $secuencia->autores()->where('users.id', $usuario->id)->exists();
        $esRevisorEnTurno = $usuario->tieneRol('Revisor') && $secuencia->estado === 'en_revision';
        $esDirectorDeLaCarrera = $usuario->tieneRol('Director')
            && $secuencia->carrera_id === $usuario->carreraDirigida()->value('id');

        if (! $esAutor && ! $esRevisorEnTurno && ! $esDirectorDeLaCarrera && ! $usuario->tieneRol('Administrador')) {
            abort(403, 'No tienes acceso a esta secuencia.');
        }
    }
}
