<?php

namespace App\Http\Controllers\Api\Secuencias;

use App\Http\Controllers\Controller;
use App\Models\Revision;
use App\Models\Secuencia;
use App\Models\SecuenciaFaseActividad;
use App\Models\SecuenciaReferencia;
use App\Models\SecuenciaUnidad;
use App\Models\SecuenciaUnidadEvaluacion;
use App\Models\SecuenciaUnidadEvidencia;
use App\Models\SecuenciaUnidadTema;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Throwable;

class RevisionController extends Controller
{
    /**
     * Mapa de alias cortos (los mismos que usa la app de Vue y el morphMap)
     * hacia el modelo Eloquent real que se está validando.
     */
    private const MODELOS = [
        'unidad' => SecuenciaUnidad::class,
        'tema' => SecuenciaUnidadTema::class,
        'evaluacion' => SecuenciaUnidadEvaluacion::class,
        'evidencia' => SecuenciaUnidadEvidencia::class,
        'fase' => SecuenciaFaseActividad::class,
        'referencia' => SecuenciaReferencia::class,
    ];

    /**
     * PATCH /api/revisor/validacion/{tipo}/{id}
     * body: { aprobado: true|false, comentario?: string }
     */
    public function actualizar(Request $request, string $tipo, string $id)
    {
        try {
            if (! isset(self::MODELOS[$tipo])) {
                return response()->json(['message' => 'Tipo de elemento no reconocido.'], 422);
            }

            $data = $request->validate([
                'aprobado' => ['required', 'boolean'],
                'comentario' => ['nullable', 'string'],
            ]);

            $modeloClase = self::MODELOS[$tipo];
            $elemento = $modeloClase::findOrFail($id);
            $secuencia = $this->obtenerSecuencia($tipo, $elemento);

            $usuario = $request->user();
            if (! $usuario->tieneRol('Revisor') || $secuencia->estado !== 'en_revision') {
                return response()->json(['message' => 'No puedes validar elementos de esta secuencia en este momento.'], 403);
            }

            $revision = $elemento->revision()->updateOrCreate([], [
                'revisor_id' => $usuario->id,
                'aprobado' => $data['aprobado'],
                'comentario' => $data['comentario'] ?? null,
                'fecha_revision' => now(),
            ]);

            return response()->json($revision);
        } catch (ValidationException $e) {
            throw $e;
        } catch (Throwable $e) {
            Log::error('RevisionController@actualizar: error al validar el elemento', [
                'tipo' => $tipo, 'id' => $id,
                'mensaje' => $e->getMessage(), 'linea' => $e->getLine(), 'archivo' => $e->getFile(),
            ]);

            return response()->json(['message' => 'No se pudo actualizar la validación.'], 500);
        }
    }

    private function obtenerSecuencia(string $tipo, $elemento): Secuencia
    {
        return match ($tipo) {
            'unidad' => $elemento->secuencia,
            'tema' => $elemento->unidad->secuencia,
            'evaluacion' => $elemento->unidad->secuencia,
            'evidencia' => $elemento->unidad->secuencia,
            'fase' => $elemento->fase->unidad->secuencia,
            'referencia' => $elemento->secuencia,
        };
    }
}
