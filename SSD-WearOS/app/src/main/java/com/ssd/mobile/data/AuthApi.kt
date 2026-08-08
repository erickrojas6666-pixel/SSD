package com.ssd.mobile.data

import retrofit2.Response
import retrofit2.http.Body
import retrofit2.http.Header
import retrofit2.http.POST

data class LoginRequest(val email: String, val password: String)

data class TwoFactorVerifyRequest(
    val challenge_token: String,
    val code: String,
)

data class UsuarioDto(
    val id: Int,
    val nombre_completo: String,
    val email: String,
)

// La API responde distinto según si el usuario tiene 2FA activo o no,
// así que todos los campos "finales" quedan nullable.
data class AuthResponse(
    val requires_2fa: Boolean,
    val method: String? = null,
    val challenge_token: String? = null,
    val token: String? = null,
    val user: UsuarioDto? = null,
    val roles: List<String>? = null,
)

interface AuthApi {
    @POST("login")
    suspend fun login(@Body body: LoginRequest): Response<AuthResponse>

    @POST("2fa/verify")
    suspend fun verificarCodigo(@Body body: TwoFactorVerifyRequest): Response<AuthResponse>

    @POST("logout")
    suspend fun logout(@Header("Authorization") bearer: String): Response<Unit>
}
