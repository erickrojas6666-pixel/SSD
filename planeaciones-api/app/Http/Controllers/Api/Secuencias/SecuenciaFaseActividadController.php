<?php

namespace App\Http\Controllers\Api\Secuencias;

use App\Http\Controllers\Api\Concerns\VerificaEdicionSecuencia;
use App\Http\Controllers\Controller;
use App\Models\SecuenciaFaseActividad;
use App\Models\SecuenciaUnidad;
use App\Support\EstrategiasCatalogo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Throwable;

class SecuenciaFaseActividadController extends Controller
{
    use VerificaEdicionSecuencia;

    /**
     * POST /api/docente/unidades/{unidad}/fases/{tipo}/actividades
     * tipo = apertura|desarrollo|cierre
     */
    public function store(Request $request, SecuenciaUnidad $unidad, string $tipo)
    {
        try {
            $this->autorizarEdicion($unidad->secuencia, $request->user());

            if (! in_array($tipo, ['apertura', 'desarrollo', 'cierre'], true)) {
                return response()->json(['message' => 'Tipo de fase inválido.'], 422);
            }

            $fase = $unidad->fases()->firstOrCreate(['fase' => $tipo]);
            $numero = $fase->actividades()->max('numero') + 1;

            $actividad = $fase->actividades()->create([
                'numero' => $numero,
                'metodos_tecnicas' => '',
                'actividades_docente' => '',
                'actividades_estudiante' => '',
                'evidencia_aprendizaje' => null,
                'medios_materiales' => '',
            ]);

            return response()->json($actividad, 201);
        } catch (Throwable $e) {
            Log::error('SecuenciaFaseActividadController@store: error al crear la actividad', [
                'unidad_id' => $unidad->id,
                'tipo' => $tipo,
                'mensaje' => $e->getMessage(),
                'linea' => $e->getLine(),
                'archivo' => $e->getFile(),
            ]);

            return response()->json(['message' => 'No se pudo crear la actividad.'], 500);
        }
    }

    /**
     * PATCH /api/docente/fase-actividades/{actividad}
     */
    public function update(Request $request, SecuenciaFaseActividad $actividad)
    {
        try {
            $this->autorizarEdicion($actividad->fase->unidad->secuencia, $request->user());

            $data = $request->validate([
                'metodos_tecnicas' => ['sometimes', 'nullable', Rule::in(EstrategiasCatalogo::porFase($actividad->fase->fase))],
                'actividades_docente' => ['sometimes', 'string'],
                'actividades_estudiante' => ['sometimes', 'string'],
                'evidencia_aprendizaje' => ['sometimes', 'nullable', 'string'],
                'medios_materiales' => ['sometimes', 'string'],
            ]);

            $actividad->update($data);

            return response()->json($actividad->fresh());
        } catch (ValidationException $e) {
            throw $e;
        } catch (Throwable $e) {
            Log::error('SecuenciaFaseActividadController@update: error al actualizar la actividad', [
                'actividad_id' => $actividad->id,
                'mensaje' => $e->getMessage(),
                'linea' => $e->getLine(),
                'archivo' => $e->getFile(),
            ]);

            return response()->json(['message' => 'No se pudo actualizar la actividad.'], 500);
        }
    }

    /**
     * DELETE /api/docente/fase-actividades/{actividad}
     */
    public function destroy(Request $request, SecuenciaFaseActividad $actividad)
    {
        try {
            $this->autorizarEdicion($actividad->fase->unidad->secuencia, $request->user());
            $actividad->delete();

            return response()->json(['message' => 'Actividad eliminada.']);
        } catch (Throwable $e) {
            Log::error('SecuenciaFaseActividadController@destroy: error al eliminar la actividad', [
                'actividad_id' => $actividad->id,
                'mensaje' => $e->getMessage(),
                'linea' => $e->getLine(),
                'archivo' => $e->getFile(),
            ]);

            return response()->json(['message' => 'No se pudo eliminar la actividad.'], 500);
        }
    }
}
