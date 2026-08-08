package com.ssd.mobile

import android.app.Application
import android.util.Log
import androidx.lifecycle.AndroidViewModel
import androidx.lifecycle.viewModelScope
import com.ssd.mobile.data.ApiClient
import com.ssd.mobile.data.LoginRequest
import com.ssd.mobile.data.ResultadoEnvioReloj
import com.ssd.mobile.data.SecureTokenStore
import com.ssd.mobile.data.TwoFactorVerifyRequest
import com.ssd.mobile.data.WatchPairing
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asStateFlow
import kotlinx.coroutines.launch

enum class Pantalla { LOGIN, DOS_FACTORES, CONFIRMACION }

data class AuthUiState(
    val pantalla: Pantalla = Pantalla.LOGIN,
    val cargando: Boolean = false,
    val error: String? = null,
    val challengeToken: String? = null,
    val relojConectado: Boolean? = null,
)

class AuthViewModel(application: Application) : AndroidViewModel(application) {

    private val tokenStore = SecureTokenStore(application)

    private val _uiState = MutableStateFlow(AuthUiState())
    val uiState: StateFlow<AuthUiState> = _uiState.asStateFlow()

    fun login(email: String, password: String) {
        _uiState.value = _uiState.value.copy(cargando = true, error = null)

        viewModelScope.launch {
            try {
                val respuesta = ApiClient.authApi.login(LoginRequest(email, password))
                val body = respuesta.body()

                if (!respuesta.isSuccessful || body == null) {
                    _uiState.value = _uiState.value.copy(
                        cargando = false,
                        error = "Correo o contraseña incorrectos.",
                    )
                    return@launch
                }

                if (body.requires_2fa) {
                    _uiState.value = _uiState.value.copy(
                        cargando = false,
                        pantalla = Pantalla.DOS_FACTORES,
                        challengeToken = body.challenge_token,
                    )
                } else {
                    onSesionIniciada(body.token)
                }
            } catch (e: Exception) {
                Log.e("AuthViewModel", "Excepción en login", e)
                _uiState.value = _uiState.value.copy(
                    cargando = false,
                    error = "No se pudo conectar con el servidor. Verifica tu conexión.",
                )
            }
        }
    }

    fun verificarCodigo(codigo: String) {
        val challengeToken = _uiState.value.challengeToken ?: return
        _uiState.value = _uiState.value.copy(cargando = true, error = null)

        viewModelScope.launch {
            try {
                val respuesta = ApiClient.authApi.verificarCodigo(
                    TwoFactorVerifyRequest(challengeToken, codigo)
                )
                val body = respuesta.body()

                if (!respuesta.isSuccessful || body == null) {
                    _uiState.value = _uiState.value.copy(cargando = false, error = "Código incorrecto.")
                    return@launch
                }

                onSesionIniciada(body.token)
            } catch (e: Exception) {
                Log.e("AuthViewModel", "Excepción en verificarCodigo", e)
                _uiState.value = _uiState.value.copy(
                    cargando = false,
                    error = "No se pudo verificar el código. Intenta de nuevo.",
                )
            }
        }
    }

    private fun onSesionIniciada(token: String?) {
        try {
            if (token == null) {
                _uiState.value = _uiState.value.copy(cargando = false, error = "Respuesta inválida del servidor.")
                return
            }

            tokenStore.guardarToken(token)
            _uiState.value = _uiState.value.copy(cargando = false, pantalla = Pantalla.CONFIRMACION)
            enviarTokenAlReloj(token)
        } catch (e: Exception) {
            Log.e("AuthViewModel", "Excepción en onSesionIniciada", e)
            _uiState.value = _uiState.value.copy(cargando = false, error = "Error al guardar la sesión.")
        }
    }

    fun enviarTokenAlReloj(token: String = tokenStore.obtenerToken().orEmpty()) {
        if (token.isEmpty()) return

        viewModelScope.launch {
            try {
                when (WatchPairing.enviarTokenAlReloj(getApplication(), token)) {
                    is ResultadoEnvioReloj.Enviado ->
                        _uiState.value = _uiState.value.copy(relojConectado = true)
                    is ResultadoEnvioReloj.SinRelojConectado, is ResultadoEnvioReloj.Error ->
                        _uiState.value = _uiState.value.copy(relojConectado = false)
                }
            } catch (e: Exception) {
                Log.e("AuthViewModel", "Excepción al enviar token al reloj", e)
                _uiState.value = _uiState.value.copy(relojConectado = false)
            }
        }
    }

    fun logout() {
        viewModelScope.launch {
            try {
                val token = tokenStore.obtenerToken()
                _uiState.value = _uiState.value.copy(cargando = true)

                if (token != null) {
                    try {
                        ApiClient.authApi.logout("Bearer $token")
                    } catch (e: Exception) {
                        Log.w("AuthViewModel", "No se pudo notificar logout al servidor, continuando localmente", e)
                    }
                }

                try {
                    WatchPairing.avisarLogoutAlReloj(getApplication())
                } catch (e: Exception) {
                    Log.w("AuthViewModel", "No se pudo avisar logout al reloj", e)
                }

                tokenStore.limpiar()
                _uiState.value = AuthUiState()
            } catch (e: Exception) {
                Log.e("AuthViewModel", "Excepción crítica en logout", e)
                tokenStore.limpiar()
                _uiState.value = AuthUiState()
            }
        }
    }
}