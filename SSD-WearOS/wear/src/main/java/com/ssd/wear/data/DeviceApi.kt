package com.ssd.wear.data

import retrofit2.Response
import retrofit2.http.Body
import retrofit2.http.HTTP
import retrofit2.http.Header
import retrofit2.http.POST

data class DeviceTokenRequest(
    val fcm_token: String,
    val plataforma: String = "wearos",
)

interface DeviceApi {
    @POST("dispositivo/fcm-token")
    suspend fun registrarToken(
        @Header("Authorization") bearer: String,
        @Body body: DeviceTokenRequest,
    ): Response<Unit>

    // Retrofit no permite @Body directo con @DELETE, por eso se usa @HTTP
    // con hasBody = true (Laravel requiere el fcm_token en el request).
    @HTTP(method = "DELETE", path = "dispositivo/fcm-token", hasBody = true)
    suspend fun eliminarToken(
        @Header("Authorization") bearer: String,
        @Body body: DeviceTokenRequest,
    ): Response<Unit>
}
