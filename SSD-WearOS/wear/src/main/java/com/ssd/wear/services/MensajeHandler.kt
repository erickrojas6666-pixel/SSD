package com.ssd.wear.services

import android.content.Context
import android.util.Log
import com.google.android.gms.wearable.MessageEvent
import com.ssd.wear.data.DeviceRegistrationRepository
import kotlinx.coroutines.CoroutineScope
import kotlinx.coroutines.launch

object MensajeHandler {

    const val RUTA_TOKEN = "/auth-token"
    const val RUTA_LOGOUT = "/logout"

    // Tu función original intacta para cuando usas MessageEvent
    fun procesar(context: Context, scope: CoroutineScope, event: MessageEvent) {
        try {
            when (event.path) {
                RUTA_TOKEN -> {
                    val token = String(event.data, Charsets.UTF_8)
                    procesarTokenLogica(context, scope, token)
                }
                RUTA_LOGOUT -> {
                    procesarLogoutLogica(context, scope)
                }
                else -> {
                    Log.w("MensajeHandler", "Ruta de mensaje desconocida: ${event.path}")
                }
            }
        } catch (e: Exception) {
            Log.e("MensajeHandler", "Excepción general al procesar mensaje entrante", e)
        }
    }

    // Nueva sobrecarga con el mismo nombre "procesar" para recibir el token de DataLayer
    fun procesar(context: Context, scope: CoroutineScope, path: String, token: String) {
        if (path == RUTA_TOKEN) {
            procesarTokenLogica(context, scope, token)
        }
    }

    // Lógica compartida para evitar duplicar código
    private fun procesarTokenLogica(context: Context, scope: CoroutineScope, token: String) {
        Log.d("MensajeHandler", "Token de sesión recibido")
        scope.launch {
            try {
                DeviceRegistrationRepository.registrarConToken(context, token)
            } catch (e: Exception) {
                Log.e("MensajeHandler", "Error al registrar el token en el repositorio", e)
            }
        }
    }

    private fun procesarLogoutLogica(context: Context, scope: CoroutineScope) {
        Log.d("MensajeHandler", "Logout recibido")
        scope.launch {
            try {
                DeviceRegistrationRepository.cerrarSesion(context)
            } catch (e: Exception) {
                Log.e("MensajeHandler", "Error al procesar el cierre de sesión en el repositorio", e)
            }
        }
    }
}