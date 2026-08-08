<?php

namespace App\Http\Controllers\Api\Secuencias;

use App\Http\Controllers\Api\Concerns\VerificaEdicionSecuencia;
use App\Http\Controllers\Controller;
use App\Models\SecuenciaUnidad;
use App\Models\SecuenciaUnidadEvaluacion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Throwable;

class SecuenciaUnidadEvaluacionController extends Controller
{
    use VerificaEdicionSecuencia;

    /**
     * PATCH /api/docente/unidades/{unidad}/evaluacion
     * Crea el registro si aún no existe (igual que el ejemplo: primer campo que se guarda lo genera).
     */
    public function updateOrCreate(Request $request, SecuenciaUnidad $unidad)
    {
        try {
            $this->autorizarEdicion($unidad->secuencia, $request->user());

            $data = $request->validate([
                'periodo_semanas' => ['sometimes', 'integer', 'min:1', 'max:15'],
                'resultado_aprendizaje' => ['sometimes', 'string'],
            ]);

            $evaluacion = $unidad->evaluacion()->updateOrCreate([], $data);

            return response()->json($evaluacion);
        } catch (ValidationException $e) {
            throw $e;
        } catch (Throwable $e) {
            Log::error('SecuenciaUnidadEvaluacionController@updateOrCreate: error al guardar la evaluación', [
                'unidad_id' => $unidad->id,
                'mensaje' => $e->getMessage(), 'linea' => $e->getLine(), 'archivo' => $e->getFile(),
            ]);

            return response()->json(['message' => 'No se pudo guardar la evaluación.'], 500);
        }
    }

    /**
     * PATCH /api/docente/evaluaciones/{evaluacion}  (una vez que ya existe)
     */
    public function update(Request $request, SecuenciaUnidadEvaluacion $evaluacion)
    {
        try {
            $this->autorizarEdicion($evaluacion->unidad->secuencia, $request->user());

            $data = $request->validate([
                'periodo_semanas' => ['sometimes', 'integer', 'min:1', 'max:15'],
                'resultado_aprendizaje' => ['sometimes', 'string'],
            ]);

            $evaluacion->update($data);

            return response()->json($evaluacion->fresh());
        } catch (ValidationException $e) {
            throw $e;
        } catch (Throwable $e) {
            Log::error('SecuenciaUnidadEvaluacionController@update: error al actualizar la evaluación', [
                'evaluacion_id' => $evaluacion->id,
                'mensaje' => $e->getMessage(), 'linea' => $e->getLine(), 'archivo' => $e->getFile(),
            ]);

            return response()->json(['message' => 'No se pudo actualizar la evaluación.'], 500);
        }
    }
}
