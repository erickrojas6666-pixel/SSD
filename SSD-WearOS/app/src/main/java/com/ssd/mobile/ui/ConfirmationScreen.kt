package com.ssd.mobile.ui

import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.padding
import androidx.compose.material3.Button
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.OutlinedButton
import androidx.compose.material3.Text
import androidx.compose.runtime.Composable
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.text.style.TextAlign
import androidx.compose.ui.unit.dp

@Composable
fun ConfirmationScreen(
    relojConectado: Boolean?,
    onReintentar: () -> Unit,
    onCerrarSesion: () -> Unit,
) {
    Column(
        modifier = Modifier
            .fillMaxSize()
            .padding(24.dp),
        verticalArrangement = Arrangement.Center,
        horizontalAlignment = Alignment.CenterHorizontally,
    ) {
        Text(
            text = "¡Listo!",
            style = MaterialTheme.typography.headlineMedium,
            modifier = Modifier.padding(bottom = 12.dp),
        )

        Text(
            text = "A partir de ahora recibirás notificaciones en tu Wear OS " +
                    "cuando cambie el estado de tus secuencias.",
            style = MaterialTheme.typography.bodyLarge,
            textAlign = TextAlign.Center,
            modifier = Modifier.padding(bottom = 24.dp),
        )

        when (relojConectado) {
            true -> Text(
                text = "✓ Reloj conectado correctamente.",
                color = MaterialTheme.colorScheme.primary,
            )
            false -> Text(
                text = "No detectamos un reloj emparejado. Ábrelo cerca del " +
                        "celular con la app instalada e inténtalo de nuevo.",
                color = MaterialTheme.colorScheme.error,
                textAlign = TextAlign.Center,
            )
            null -> Text(
                text = "Buscando tu reloj…",
                style = MaterialTheme.typography.bodyMedium,
            )
        }

        if (relojConectado == false) {
            Button(
                onClick = onReintentar,
                modifier = Modifier
                    .fillMaxWidth()
                    .padding(top = 16.dp),
            ) {
                Text("Reintentar")
            }
        }

        OutlinedButton(
            onClick = onCerrarSesion,
            modifier = Modifier
                .fillMaxWidth()
                .padding(top = 12.dp),
        ) {
            Text("Cerrar sesión")
        }
    }
}
