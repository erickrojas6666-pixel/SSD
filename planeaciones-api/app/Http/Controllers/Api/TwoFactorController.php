<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\TwoFactorChallenge;
use App\Models\TwoFactorCode;
use App\Models\User;
use App\Notifications\TwoFactorCodeNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use PragmaRX\Google2FA\Google2FA;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Throwable;

class TwoFactorController extends Controller
{
    /**
     * POST /api/2fa/enable  (autenticado)
     */
    public function enable(Request $request)
    {
        try {
            $data = $request->validate(['method' => ['required', 'in:app,email']]);
            $user = $request->user();

            if ($data['method'] === 'app') {
                $google2fa = new Google2FA();
                $secret = $google2fa->generateSecretKey();

                $user->forceFill([
                    'two_factor_secret' => encrypt($secret),
                    'two_factor_method' => 'app',
                    'two_factor_confirmed_at' => null,
                    'two_factor_recovery_codes' => null,
                ])->save();

                $qrUrl = $google2fa->getQRCodeUrl(config('app.name'), $user->email, $secret);
                $qrSvg = QrCode::size(200)->generate($qrUrl);

                return response()->json([
                    'method' => 'app',
                    'secret' => $secret,
                    'qr_svg' => $qrSvg,
                    'message' => 'Escanea el código QR con tu app autenticadora y confirma con el código generado.',
                ]);
            }

            $user->forceFill([
                'two_factor_secret' => null,
                'two_factor_method' => 'email',
                'two_factor_confirmed_at' => null,
                'two_factor_recovery_codes' => null,
            ])->save();

            $this->enviarCodigoCorreo($user);

            return response()->json([
                'method' => 'email',
                'message' => 'Te enviamos un código de confirmación a tu correo.',
            ]);
        } catch (ValidationException $e) {
            throw $e;
        } catch (Throwable $e) {
            Log::error('TwoFactorController@enable: error al iniciar la activación de 2FA', [
                'user_id' => $request->user()?->id,
                'mensaje' => $e->getMessage(),
                'linea' => $e->getLine(),
                'archivo' => $e->getFile(),
            ]);

            return response()->json(['message' => 'No se pudo iniciar la activación del 2FA.'], 500);
        }
    }

    /**
     * POST /api/2fa/confirm  (autenticado)
     */
    public function confirm(Request $request)
    {
        try {
            $data = $request->validate(['code' => ['required', 'string']]);
            $user = $request->user();

            if (! $this->codigoValido($user, $data['code'])) {
                return response()->json(['message' => 'El código no es válido o ya expiró.'], 422);
            }

            $codigosRecuperacion = collect(range(1, 8))
                ->map(fn() => Str::upper(Str::random(4) . '-' . Str::random(4)))
                ->values();

            $user->forceFill([
                'two_factor_confirmed_at' => now(),
                'two_factor_recovery_codes' => $codigosRecuperacion->map(fn($c) => Hash::make($c))->toJson(),
            ])->save();

            return response()->json([
                'message' => 'Autenticación en dos pasos activada correctamente.',
                'recovery_codes' => $codigosRecuperacion,
            ]);
        } catch (ValidationException $e) {
            throw $e;
        } catch (Throwable $e) {
            Log::error('TwoFactorController@confirm: error al confirmar la activación de 2FA', [
                'user_id' => $request->user()?->id,
                'mensaje' => $e->getMessage(),
                'linea' => $e->getLine(),
                'archivo' => $e->getFile(),
            ]);

            return response()->json(['message' => 'No se pudo confirmar la activación del 2FA.'], 500);
        }
    }

    /**
     * POST /api/2fa/disable  (autenticado)
     */
    public function disable(Request $request)
    {
        try {
            $data = $request->validate(['password' => ['required', 'string']]);
            $user = $request->user();

            if (! Hash::check($data['password'], $user->password)) {
                return response()->json(['message' => 'Contraseña incorrecta.'], 422);
            }

            $user->forceFill([
                'two_factor_secret' => null,
                'two_factor_method' => null,
                'two_factor_confirmed_at' => null,
                'two_factor_recovery_codes' => null,
            ])->save();

            return response()->json(['message' => 'Autenticación en dos pasos desactivada.']);
        } catch (ValidationException $e) {
            throw $e;
        } catch (Throwable $e) {
            Log::error('TwoFactorController@disable: error al desactivar el 2FA', [
                'user_id' => $request->user()?->id,
                'mensaje' => $e->getMessage(),
                'linea' => $e->getLine(),
                'archivo' => $e->getFile(),
            ]);

            return response()->json(['message' => 'No se pudo desactivar el 2FA.'], 500);
        }
    }

    /**
     * POST /api/2fa/verify  (público — segundo paso del login)
     */
    public function verify(Request $request)
    {
        try {
            $data = $request->validate([
                'challenge_token' => ['required', 'string'],
                'code' => ['required', 'string'],
            ]);

            $challenge = TwoFactorChallenge::where('token', $data['challenge_token'])
                ->where('expires_at', '>', now())
                ->first();

            if (! $challenge) {
                return response()->json(['message' => 'La verificación expiró, inicia sesión de nuevo.'], 422);
            }

            $user = $challenge->user;

            if (! $this->codigoValido($user, $data['code']) && ! $this->esCodigoRecuperacion($user, $data['code'])) {
                return response()->json(['message' => 'Código incorrecto.'], 422);
            }

            $challenge->delete();
            TwoFactorCode::where('user_id', $user->id)->delete();

            $user->tokens()->delete();
            $token = $user->createToken('api-token')->plainTextToken;

            return response()->json([
                'requires_2fa' => false,
                'token' => $token,
                'user' => [
                    'id' => $user->id,
                    'nombre_completo' => $user->nombre_completo,
                    'email' => $user->email,
                ],
                'roles' => $user->roles()->pluck('nombre'),
            ]);
        } catch (ValidationException $e) {
            throw $e;
        } catch (Throwable $e) {
            Log::error('TwoFactorController@verify: error al verificar el código de 2FA', [
                'mensaje' => $e->getMessage(),
                'linea' => $e->getLine(),
                'archivo' => $e->getFile(),
            ]);

            return response()->json(['message' => 'No se pudo verificar el código.'], 500);
        }
    }

    /**
     * POST /api/2fa/resend  (público, solo método email)
     */
    public function resend(Request $request)
    {
        try {
            $data = $request->validate(['challenge_token' => ['required', 'string']]);

            $challenge = TwoFactorChallenge::where('token', $data['challenge_token'])
                ->where('expires_at', '>', now())
                ->first();

            if (! $challenge || $challenge->user->two_factor_method !== 'email') {
                return response()->json(['message' => 'No se puede reenviar el código.'], 422);
            }

            $this->enviarCodigoCorreo($challenge->user);

            return response()->json(['message' => 'Código reenviado.']);
        } catch (ValidationException $e) {
            throw $e;
        } catch (Throwable $e) {
            Log::error('TwoFactorController@resend: error al reenviar el código de 2FA', [
                'mensaje' => $e->getMessage(),
                'linea' => $e->getLine(),
                'archivo' => $e->getFile(),
            ]);

            return response()->json(['message' => 'No se pudo reenviar el código.'], 500);
        }
    }

    // ── helpers ──────────────────────────────────────────────

    private function enviarCodigoCorreo(User $user): void
    {
        $codigo = (string) random_int(100000, 999999);

        TwoFactorCode::updateOrCreate(
            ['user_id' => $user->id],
            ['code' => Hash::make($codigo), 'expires_at' => now()->addMinutes(5)]
        );

        $user->notify(new TwoFactorCodeNotification($codigo));
    }

    private function codigoValido(User $user, string $code): bool
    {
        if ($user->two_factor_method === 'app') {
            $google2fa = new Google2FA();

            return $google2fa->verifyKey(decrypt($user->two_factor_secret), $code);
        }

        $registro = TwoFactorCode::where('user_id', $user->id)
            ->where('expires_at', '>', now())
            ->latest()
            ->first();

        return $registro && Hash::check($code, $registro->code);
    }

    private function esCodigoRecuperacion(User $user, string $code): bool
    {
        if (! $user->two_factor_recovery_codes) {
            return false;
        }

        $codigos = json_decode($user->two_factor_recovery_codes, true) ?? [];

        foreach ($codigos as $index => $hash) {
            if (Hash::check($code, $hash)) {
                unset($codigos[$index]);
                $user->forceFill(['two_factor_recovery_codes' => json_encode(array_values($codigos))])->save();

                return true;
            }
        }

        return false;
    }
}
