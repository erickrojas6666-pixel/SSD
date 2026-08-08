package com.ssd.wear.data

import com.google.firebase.messaging.FirebaseMessaging
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.tasks.await

enum class EstadoRegistro { DESCONOCIDO, EMPAREJANDO, REGISTRADO, ERROR }

/**
 * Objeto compartido en memoria entre el servicio que recibe el token por
 * Bluetooth y la Activity, para que la pantalla "Te llegarán notificaciones"
 * pueda reflejar en vivo si el reloj ya quedó registrado.
 */
object DeviceRegistrationRepository {

    private val _estado = MutableStateFlow(EstadoRegistro.DESCONOCIDO)
    val estado: StateFlow<EstadoRegistro> = _estado

    suspend fun registrarConToken(context: android.content.Context, authToken: String) {
        _estado.value = EstadoRegistro.EMPAREJANDO

        val tokenStore = SecureTokenStore(context)
        tokenStore.guardarToken(authToken)

        try {
            val fcmToken = FirebaseMessaging.getInstance().token.await()
            val respuesta = ApiClient.deviceApi.registrarToken(
                bearer = "Bearer $authToken",
                body = DeviceTokenRequest(fcm_token = fcmToken),
            )

            if (respuesta.isSuccessful) {
                tokenStore.marcarRegistrado(true)
                _estado.value = EstadoRegistro.REGISTRADO
            } else {
                _estado.value = EstadoRegistro.ERROR
            }
        } catch (e: Exception) {
            _estado.value = EstadoRegistro.ERROR
        }
    }

    /** Se llama cuando Firebase rota el fcm_token (onNewToken), reutilizando el auth token ya guardado. */
    suspend fun reregistrarConNuevoFcmToken(context: android.content.Context, nuevoFcmToken: String) {
        val tokenStore = SecureTokenStore(context)
        val authToken = tokenStore.obtenerToken() ?: return

        try {
            val respuesta = ApiClient.deviceApi.registrarToken(
                bearer = "Bearer $authToken",
                body = DeviceTokenRequest(fcm_token = nuevoFcmToken),
            )
            if (respuesta.isSuccessful) {
                tokenStore.marcarRegistrado(true)
                _estado.value = EstadoRegistro.REGISTRADO
            }
        } catch (e: Exception) {
            // Se reintentará en el siguiente onNewToken o apertura de la app.
        }
    }

    fun cargarEstadoInicial(context: android.content.Context) {
        val tokenStore = SecureTokenStore(context)
        _estado.value = if (tokenStore.estaRegistrado()) EstadoRegistro.REGISTRADO else EstadoRegistro.DESCONOCIDO
    }

    /** Se llama cuando el celular avisa /logout: borra la sesión del reloj y su registro de push. */
    suspend fun cerrarSesion(context: android.content.Context) {
        val tokenStore = SecureTokenStore(context)
        val authToken = tokenStore.obtenerToken()

        if (authToken != null) {
            try {
                val fcmToken = FirebaseMessaging.getInstance().token.await()
                ApiClient.deviceApi.eliminarToken(
                    bearer = "Bearer $authToken",
                    body = DeviceTokenRequest(fcm_token = fcmToken),
                )
            } catch (e: Exception) {
                // Aunque falle la llamada (sin conexión), igual limpiamos localmente.
            }
        }

        tokenStore.limpiar()
        _estado.value = EstadoRegistro.DESCONOCIDO
    }
}
