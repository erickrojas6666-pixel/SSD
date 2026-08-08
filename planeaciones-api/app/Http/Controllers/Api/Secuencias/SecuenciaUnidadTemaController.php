<?php

namespace App\Http\Controllers\Api\Secuencias;

use App\Http\Controllers\Api\Concerns\VerificaEdicionSecuencia;
use App\Http\Controllers\Controller;
use App\Models\SecuenciaUnidad;
use App\Models\SecuenciaUnidadTema;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Throwable;

class SecuenciaUnidadTemaController extends Controller
{
    use VerificaEdicionSecuencia;

    /**
     * POST /api/docente/unidades/{unidad}/temas
     */
    public function store(Request $request, SecuenciaUnidad $unidad)
    {
        try {
            $this->autorizarEdicion($unidad->secuencia, $request->user());

            $orden = $unidad->temas()->max('orden') + 1;

            $tema = $unidad->temas()->create([
                'tema' => '', 'saber' => '', 'saber_hacer' => '', 'ser_convivir' => null, 'orden' => $orden,
            ]);

            return response()->json($tema, 201);
        } catch (Throwable $e) {
            Log::error('SecuenciaUnidadTemaController@store: error al crear el tema', [
                'unidad_id' => $unidad->id,
                'mensaje' => $e->getMessage(), 'linea' => $e->getLine(), 'archivo' => $e->getFile(),
            ]);

            return response()->json(['message' => 'No se pudo crear el tema.'], 500);
        }
    }

    /**
     * PATCH /api/docente/temas/{tema}
     */
    public function update(Request $request, SecuenciaUnidadTema $tema)
    {
        try {
            $this->autorizarEdicion($tema->unidad->secuencia, $request->user());

            $data = $request->validate([
                'tema' => ['sometimes', 'string'],
                'saber' => ['sometimes', 'string'],
                'saber_hacer' => ['sometimes', 'string'],
                'ser_convivir' => ['sometimes', 'nullable', 'string'],
            ]);

            $tema->update($data);

            return response()->json($tema->fresh());
        } catch (ValidationException $e) {
            throw $e;
        } catch (Throwable $e) {
            Log::error('SecuenciaUnidadTemaController@update: error al actualizar el tema', [
                'tema_id' => $tema->id,
                'mensaje' => $e->getMessage(), 'linea' => $e->getLine(), 'archivo' => $e->getFile(),
            ]);

            return response()->json(['message' => 'No se pudo actualizar el tema.'], 500);
        }
    }

    /**
     * DELETE /api/docente/temas/{tema}
     */
    public function destroy(Request $request, SecuenciaUnidadTema $tema)
    {
        try {
            $this->autorizarEdicion($tema->unidad->secuencia, $request->user());
            $tema->delete();

            return response()->json(['message' => 'Tema eliminado.']);
        } catch (Throwable $e) {
            Log::error('SecuenciaUnidadTemaController@destroy: error al eliminar el tema', [
                'tema_id' => $tema->id,
                'mensaje' => $e->getMessage(), 'linea' => $e->getLine(), 'archivo' => $e->getFile(),
            ]);

            return response()->json(['message' => 'No se pudo eliminar el tema.'], 500);
        }
    }
}
