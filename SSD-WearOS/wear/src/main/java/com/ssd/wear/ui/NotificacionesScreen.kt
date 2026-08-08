package com.ssd.wear.ui

import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.padding
import androidx.compose.runtime.Composable
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.text.style.TextAlign
import androidx.compose.ui.unit.dp
import androidx.wear.compose.material.MaterialTheme
import androidx.wear.compose.material.Text
import com.ssd.wear.data.EstadoRegistro

@Composable
fun NotificacionesScreen(estado: EstadoRegistro) {
    Column(
        modifier = Modifier
            .fillMaxSize()
            .padding(16.dp),
        verticalArrangement = Arrangement.Center,
        horizontalAlignment = Alignment.CenterHorizontally,
    ) {
        Text(
            text = "Te llegarán notificaciones",
            style = MaterialTheme.typography.title3,
            textAlign = TextAlign.Center,
        )

        Text(
            text = subtitulo(estado),
            style = MaterialTheme.typography.caption2,
            textAlign = TextAlign.Center,
            modifier = Modifier.padding(top = 8.dp),
        )
    }
}

private fun subtitulo(estado: EstadoRegistro): String = when (estado) {
    EstadoRegistro.REGISTRADO -> "Reloj conectado ✓"
    EstadoRegistro.EMPAREJANDO -> "Conectando…"
    EstadoRegistro.ERROR -> "No se pudo conectar. Abre la app en tu celular."
    EstadoRegistro.DESCONOCIDO -> "Abre la app en tu celular para emparejar este reloj."
}
