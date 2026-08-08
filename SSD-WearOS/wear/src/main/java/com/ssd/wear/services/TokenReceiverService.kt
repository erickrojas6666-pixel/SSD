package com.ssd.wear.services

import com.google.android.gms.wearable.DataEventBuffer
import com.google.android.gms.wearable.DataMapItem
import com.google.android.gms.wearable.WearableListenerService
import kotlinx.coroutines.CoroutineScope
import kotlinx.coroutines.Dispatchers

class TokenReceiverService : WearableListenerService() {

    private val scope = CoroutineScope(Dispatchers.IO)

    override fun onDataChanged(dataEvents: DataEventBuffer) {
        super.onDataChanged(dataEvents)

        for (event in dataEvents) {
            val path = event.dataItem.uri.path
            if (path == MensajeHandler.RUTA_TOKEN) {
                val dataMapItem = DataMapItem.fromDataItem(event.dataItem)
                val token = dataMapItem.dataMap.getString("token_key")

                if (!token.isNullOrEmpty()) {
                    // Llamamos a tu función "procesar" respetando tu arquitectura
                    MensajeHandler.procesar(applicationContext, scope, path, token)
                }
            }
        }
    }
}