package com.ssd.wear.services

import android.app.NotificationChannel
import android.app.NotificationManager
import android.os.Build
import androidx.core.app.NotificationCompat
import androidx.core.app.NotificationManagerCompat
import androidx.core.content.ContextCompat
import com.google.firebase.messaging.FirebaseMessagingService
import com.google.firebase.messaging.RemoteMessage
import com.ssd.wear.data.DeviceRegistrationRepository
import kotlinx.coroutines.CoroutineScope
import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.launch

class SsdFirebaseMessagingService : FirebaseMessagingService() {

    private val scope = CoroutineScope(Dispatchers.IO)

    companion object {
        private const val CHANNEL_ID = "cambios_secuencia"
    }

    override fun onNewToken(token: String) {
        // Firebase puede rotar el token en cualquier momento; si ya teníamos
        // sesión iniciada, lo re-registramos sin pedirle nada al usuario.
        scope.launch {
            DeviceRegistrationRepository.reregistrarConNuevoFcmToken(applicationContext, token)
        }
    }

    override fun onMessageReceived(message: RemoteMessage) {
        crearCanalSiHaceFalta()

        val titulo = message.notification?.title ?: "Cambio de estado"
        val cuerpo = message.notification?.body ?: message.data["estado_nuevo"].orEmpty()

        val notificacion = NotificationCompat.Builder(this, CHANNEL_ID)
            .setSmallIcon(android.R.drawable.ic_dialog_info)
            .setContentTitle(titulo)
            .setContentText(cuerpo)
            .setStyle(NotificationCompat.BigTextStyle().bigText(cuerpo))
            .setPriority(NotificationCompat.PRIORITY_HIGH)
            .setVibrate(longArrayOf(0, 250, 100, 250))
            .setAutoCancel(true)
            .build()

        if (ContextCompat.checkSelfPermission(
                this,
                android.Manifest.permission.POST_NOTIFICATIONS
            ) == android.content.pm.PackageManager.PERMISSION_GRANTED
        ) {
            NotificationManagerCompat.from(this).notify(System.currentTimeMillis().toInt(), notificacion)
        }
    }

    private fun crearCanalSiHaceFalta() {
        if (Build.VERSION.SDK_INT < Build.VERSION_CODES.O) return

        val manager = getSystemService(NotificationManager::class.java)
        val canalExistente = manager.getNotificationChannel(CHANNEL_ID)
        if (canalExistente != null) return

        val canal = NotificationChannel(
            CHANNEL_ID,
            "Cambios de estado de secuencias",
            NotificationManager.IMPORTANCE_HIGH,
        ).apply {
            description = "Avisa cuando cambia el estado de una secuencia de la que eres autor."
        }

        manager.createNotificationChannel(canal)
    }
}
