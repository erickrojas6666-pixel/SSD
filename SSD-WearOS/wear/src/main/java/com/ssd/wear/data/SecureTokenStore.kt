package com.ssd.wear.data

import android.content.Context
import androidx.security.crypto.EncryptedSharedPreferences
import androidx.security.crypto.MasterKey

class SecureTokenStore(context: Context) {

    private val masterKey = MasterKey.Builder(context)
        .setKeyScheme(MasterKey.KeyScheme.AES256_GCM)
        .build()

    private val prefs = EncryptedSharedPreferences.create(
        context,
        "ssd_wear_secure_prefs",
        masterKey,
        EncryptedSharedPreferences.PrefKeyEncryptionScheme.AES256_SIV,
        EncryptedSharedPreferences.PrefValueEncryptionScheme.AES256_GCM,
    )

    fun guardarToken(token: String) {
        prefs.edit().putString("auth_token", token).apply()
    }

    fun obtenerToken(): String? = prefs.getString("auth_token", null)

    fun marcarRegistrado(registrado: Boolean) {
        prefs.edit().putBoolean("registrado", registrado).apply()
    }

    fun estaRegistrado(): Boolean = prefs.getBoolean("registrado", false)

    fun limpiar() {
        prefs.edit().clear().apply()
    }
}
