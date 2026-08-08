package com.ssd.mobile.data

import android.content.Context
import com.google.android.gms.wearable.Wearable
import kotlinx.coroutines.tasks.await

/**
 * Resultado de intentar avisarle al reloj. Si no hay ningún nodo conectado
 * (reloj apagado, no emparejado, o app aún no instalada ahí), lo reportamos
 * para que la UI lo muestre en vez de fallar en silencio.
 */
sealed class ResultadoEnvioReloj {
    data object Enviado : ResultadoEnvioReloj()
    data object SinRelojConectado : ResultadoEnvioReloj()
    data class Error(val mensaje: String) : ResultadoEnvioReloj()
}

object WatchPairing {

    private const val RUTA_TOKEN = "/auth-token"
    private const val RUTA_LOGOUT = "/logout"

    suspend fun enviarTokenAlReloj(context: Context, token: String): ResultadoEnvioReloj {
        return enviarMensaje(context, RUTA_TOKEN, token.toByteArray())
    }

    /** Avisa al reloj que debe borrar su sesión y dejar de estar registrado para push. */
    suspend fun avisarLogoutAlReloj(context: Context): ResultadoEnvioReloj {
        return enviarMensaje(context, RUTA_LOGOUT, ByteArray(0))
    }

    private suspend fun enviarMensaje(context: Context, ruta: String, data: ByteArray): ResultadoEnvioReloj {
        return try {
            val nodeClient = Wearable.getNodeClient(context)
            val nodos = nodeClient.connectedNodes.await()

            if (nodos.isEmpty()) {
                return ResultadoEnvioReloj.SinRelojConectado
            }

            val messageClient = Wearable.getMessageClient(context)
            nodos.forEach { nodo ->
                messageClient.sendMessage(nodo.id, ruta, data).await()
            }

            ResultadoEnvioReloj.Enviado
        } catch (e: Exception) {
            ResultadoEnvioReloj.Error(e.message ?: "No se pudo comunicar con el reloj.")
        }
    }
}
