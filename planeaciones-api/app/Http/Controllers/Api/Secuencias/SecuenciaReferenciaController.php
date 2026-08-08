<?php

namespace App\Http\Controllers\Api\Secuencias;

use App\Http\Controllers\Api\Concerns\VerificaEdicionSecuencia;
use App\Http\Controllers\Controller;
use App\Models\Secuencia;
use App\Models\SecuenciaReferencia;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Throwable;

class SecuenciaReferenciaController extends Controller
{
    use VerificaEdicionSecuencia;

    /**
     * POST /api/docente/secuencias/{secuencia}/referencias
     */
    public function store(Request $request, Secuencia $secuencia)
    {
        try {
            $this->autorizarEdicion($secuencia, $request->user());

            $orden = $secuencia->referencias()->max('orden') + 1;

            $referencia = $secuencia->referencias()->create([
                'autor' => '', 'titulo' => '', 'referencia' => '', 'orden' => $orden,
            ]);

            return response()->json($referencia, 201);
        } catch (Throwable $e) {
            Log::error('SecuenciaReferenciaController@store: error al crear la referencia', [
                'secuencia_id' => $secuencia->id,
                'mensaje' => $e->getMessage(), 'linea' => $e->getLine(), 'archivo' => $e->getFile(),
            ]);

            return response()->json(['message' => 'No se pudo crear la referencia.'], 500);
        }
    }

    /**
     * PATCH /api/docente/referencias/{referencia}
     */
    public function update(Request $request, SecuenciaReferencia $referencia)
    {
        try {
            $this->autorizarEdicion($referencia->secuencia, $request->user());

            $data = $request->validate([
                'autor' => ['sometimes', 'string', 'max:300'],
                'titulo' => ['sometimes', 'string', 'max:300'],
                'referencia' => ['sometimes', 'string'],
            ]);

            $referencia->update($data);

            return response()->json($referencia->fresh());
        } catch (ValidationException $e) {
            throw $e;
        } catch (Throwable $e) {
            Log::error('SecuenciaReferenciaController@update: error al actualizar la referencia', [
                'referencia_id' => $referencia->id,
                'mensaje' => $e->getMessage(), 'linea' => $e->getLine(), 'archivo' => $e->getFile(),
            ]);

            return response()->json(['message' => 'No se pudo actualizar la referencia.'], 500);
        }
    }

    /**
     * DELETE /api/docente/referencias/{referencia}
     */
    public function destroy(Request $request, SecuenciaReferencia $referencia)
    {
        try {
            $this->autorizarEdicion($referencia->secuencia, $request->user());
            $referencia->delete();

            return response()->json(['message' => 'Referencia eliminada.']);
        } catch (Throwable $e) {
            Log::error('SecuenciaReferenciaController@destroy: error al eliminar la referencia', [
                'referencia_id' => $referencia->id,
                'mensaje' => $e->getMessage(), 'linea' => $e->getLine(), 'archivo' => $e->getFile(),
            ]);

            return response()->json(['message' => 'No se pudo eliminar la referencia.'], 500);
        }
    }
}
