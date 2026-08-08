<?php

namespace App\Http\Controllers\Api\Concerns;

use App\Models\Secuencia;
use App\Models\User;
use Illuminate\Http\Exceptions\HttpResponseException;

trait VerificaEdicionSecuencia
{
    /**
     * Solo el autor puede editar, y solo mientras la secuencia está en "borrador".
     */
    private function autorizarEdicion(Secuencia $secuencia, User $usuario): void
    {
        $esAutor = $secuencia->autores()->where('users.id', $usuario->id)->exists();

        if (! $esAutor || $secuencia->estado !== 'borrador') {
            throw new HttpResponseException(response()->json([
                'message' => 'Esta secuencia ya no se puede editar (no eres autor o ya no está en borrador).',
            ], 403));
        }
    }
}
