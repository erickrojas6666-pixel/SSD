<?php

namespace App\Http\Controllers\Api\Secuencias;

use App\Http\Controllers\Api\Concerns\VerificaEdicionSecuencia;
use App\Http\Controllers\Controller;
use App\Models\SecuenciaUnidad;
use App\Models\SecuenciaUnidadEvidencia;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Throwable;

class SecuenciaUnidadEvidenciaController extends Controller
{
    use VerificaEdicionSecuencia;

    /**
     * POST /api/docente/unidades/{unidad}/evidencias
     * La evidencia pertenece directamente a la unidad (unidad_id), no a la evaluación.
     */
    public function store(Request $request, SecuenciaUnidad $unidad)
    {
        try {
            $this->autorizarEdicion($unidad->secuencia, $request->user());

            $orden = $unidad->evidencias()->max('orden') + 1;

            $evidencia = $unidad->evidencias()->create([
                'evidencia_aprendizaje' => '',
                'tipo_evaluacion' => null,
                'ponderacion' => 0,
                'instrumento_evaluacion' => '',
                'orden' => $orden,
            ]);

            return response()->json($evidencia, 201);
        } catch (Throwable $e) {
            Log::error('SecuenciaUnidadEvidenciaController@store: error al crear la evidencia', [
                'unidad_id' => $unidad->id,
                'mensaje' => $e->getMessage(),
                'linea' => $e->getLine(),
                'archivo' => $e->getFile(),
            ]);

            return response()->json(['message' => 'No se pudo crear la evidencia.'], 500);
        }
    }

    /**
     * PATCH /api/docente/evidencias/{evidencia}
     */
    public function update(Request $request, SecuenciaUnidadEvidencia $evidencia)
    {
        try {
            $this->autorizarEdicion($evidencia->unidad->secuencia, $request->user());

            $data = $request->validate([
                'evidencia_aprendizaje' => ['sometimes', 'string'],
                'tipo_evaluacion' => ['sometimes', 'nullable', 'string', 'max:60'],
                'ponderacion' => ['sometimes', 'numeric', 'min:0', 'max:100'],
                'instrumento_evaluacion' => ['sometimes', 'nullable', 'string', 'max:150'],
            ]);

            $evidencia->update($data);

            return response()->json($evidencia->fresh());
        } catch (ValidationException $e) {
            throw $e;
        } catch (Throwable $e) {
            Log::error('SecuenciaUnidadEvidenciaController@update: error al actualizar la evidencia', [
                'evidencia_id' => $evidencia->id,
                'mensaje' => $e->getMessage(),
                'linea' => $e->getLine(),
                'archivo' => $e->getFile(),
            ]);

            return response()->json(['message' => 'No se pudo actualizar la evidencia.'], 500);
        }
    }

    /**
     * DELETE /api/docente/evidencias/{evidencia}
     */
    public function destroy(Request $request, SecuenciaUnidadEvidencia $evidencia)
    {
        try {
            $this->autorizarEdicion($evidencia->unidad->secuencia, $request->user());
            $evidencia->delete();

            return response()->json(['message' => 'Evidencia eliminada.']);
        } catch (Throwable $e) {
            Log::error('SecuenciaUnidadEvidenciaController@destroy: error al eliminar la evidencia', [
                'evidencia_id' => $evidencia->id,
                'mensaje' => $e->getMessage(),
                'linea' => $e->getLine(),
                'archivo' => $e->getFile(),
            ]);

            return response()->json(['message' => 'No se pudo eliminar la evidencia.'], 500);
        }
    }
}
