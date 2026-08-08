<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DeviceToken;
use Illuminate\Http\Request;

class DeviceTokenController extends Controller
{
    /**
     * POST /api/dispositivo/fcm-token
     * El reloj (o cualquier dispositivo) llama esto tras emparejarse,
     * usando el token Sanctum que recibió del celular.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'fcm_token' => ['required', 'string'],
            'plataforma' => ['nullable', 'string', 'in:wearos,android,web'],
        ]);

        $token = DeviceToken::updateOrCreate(
            ['fcm_token' => $data['fcm_token']],
            [
                'user_id' => $request->user()->id,
                'plataforma' => $data['plataforma'] ?? 'wearos',
            ]
        );

        return response()->json($token, 201);
    }

    /**
     * DELETE /api/dispositivo/fcm-token
     * Al hacer logout en el reloj, para dejar de recibir notificaciones.
     */
    public function destroy(Request $request)
    {
        $request->validate([
            'fcm_token' => ['required', 'string'],
        ]);

        DeviceToken::where('fcm_token', $request->fcm_token)
            ->where('user_id', $request->user()->id)
            ->delete();

        return response()->json(['message' => 'Token eliminado.']);
    }
}
