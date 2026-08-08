<?php

namespace App\Http\Controllers\Api\Usuario;

use App\Http\Controllers\Controller;
use App\Models\ConfirmacionCuenta;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Throwable;

class ConfirmacionCuentaController extends Controller
{
    /**
     * POST /api/confirmar-cuenta  (público)
     * body: { token }
     */
    public function confirmar(Request $request)
    {
        try {
            $data = $request->validate(['token' => ['required', 'string']]);

            $confirmacion = ConfirmacionCuenta::where('token', $data['token'])
                ->where('expires_at', '>', now())
                ->first();

            if (! $confirmacion) {
                return response()->json(['message' => 'El enlace de confirmación no es válido o ya expiró.'], 422);
            }

            $usuario = $confirmacion->user;
            $usuario->forceFill(['email_verified_at' => now()])->save();
            $confirmacion->delete();

            return response()->json(['message' => 'Tu cuenta quedó confirmada. Ya puedes iniciar sesión.']);
        } catch (ValidationException $e) {
            throw $e;
        } catch (Throwable $e) {
            Log::error('ConfirmacionCuentaController@confirmar: error al confirmar la cuenta', [
                'mensaje' => $e->getMessage(), 'linea' => $e->getLine(), 'archivo' => $e->getFile(),
            ]);

            return response()->json(['message' => 'No se pudo confirmar la cuenta.'], 500);
        }
    }
}
