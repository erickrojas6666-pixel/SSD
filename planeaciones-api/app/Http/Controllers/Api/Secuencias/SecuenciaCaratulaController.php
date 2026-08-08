<?php

namespace App\Http\Controllers\Api\Secuencias;

use App\Http\Controllers\Api\Concerns\VerificaEdicionSecuencia;
use App\Http\Controllers\Controller;
use App\Models\Secuencia;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Throwable;

class SecuenciaCaratulaController extends Controller
{
    use VerificaEdicionSecuencia;

    /**
     * PATCH /api/docente/secuencias/{secuencia}/caratula
     * Autoguardado por campo (igual que temas/fases): se manda solo el campo que cambió.
     */
    public function update(Request $request, Secuencia $secuencia)
    {
        try {
            $this->autorizarEdicion($secuencia, $request->user());

            $data = $request->validate([
                'programa_educativo' => ['sometimes', 'string'],
                'proposito_aprendizaje' => ['sometimes', 'string'],
                'competencia' => ['sometimes', 'string'],
                'tipo_competencia' => ['sometimes', 'string', 'max:50'],
                'creditos' => ['sometimes', 'numeric'],
                'modalidad' => ['sometimes', 'string', 'max:30'],
                'horas_saber' => ['sometimes', 'integer'],
                'horas_saber_hacer' => ['sometimes', 'integer'],
                'horas_totales' => ['sometimes', 'integer'],
                'horas_semana' => ['sometimes', 'integer'],
            ]);

            $secuencia->caratula()->updateOrCreate([], $data);

            return response()->json($secuencia->caratula()->first());
        } catch (ValidationException $e) {
            throw $e;
        } catch (Throwable $e) {
            Log::error('SecuenciaCaratulaController@update: error al actualizar la carátula', [
                'secuencia_id' => $secuencia->id,
                'mensaje' => $e->getMessage(), 'linea' => $e->getLine(), 'archivo' => $e->getFile(),
            ]);

            return response()->json(['message' => 'No se pudo guardar la carátula.'], 500);
        }
    }

    /**
     * PATCH /api/docente/secuencias/{secuencia}/grupos-autores
     * Editar grupos y coautores (accesible aunque ya no sea borrador, punto que
     * pediste explícitamente: "se pueden editar más adelante").
     */
    public function actualizarGruposAutores(Request $request, Secuencia $secuencia)
    {
        try {
            $usuario = $request->user();
            $esAutor = $secuencia->autores()->where('users.id', $usuario->id)->exists();

            if (! $esAutor) {
                return response()->json(['message' => 'No eres autor de esta secuencia.'], 403);
            }

            $data = $request->validate([
                'coautor_ids' => ['required', 'array', 'min:1'],
                'coautor_ids.*' => ['exists:users,id'],
                'grupos' => ['required', 'array', 'min:1'],
                'grupos.*' => ['string', 'max:30'],
            ]);

            $secuencia->autores()->sync(array_unique(array_merge([$usuario->id], $data['coautor_ids'])));

            $secuencia->grupos()->delete();
            foreach ($data['grupos'] as $grupo) {
                $secuencia->grupos()->create(['grupo' => $grupo]);
            }

            return response()->json($secuencia->fresh(['autores', 'grupos']));
        } catch (ValidationException $e) {
            throw $e;
        } catch (Throwable $e) {
            Log::error('SecuenciaCaratulaController@actualizarGruposAutores: error al actualizar grupos/autores', [
                'secuencia_id' => $secuencia->id,
                'mensaje' => $e->getMessage(), 'linea' => $e->getLine(), 'archivo' => $e->getFile(),
            ]);

            return response()->json(['message' => 'No se pudieron actualizar los grupos y coautores.'], 500);
        }
    }
}
