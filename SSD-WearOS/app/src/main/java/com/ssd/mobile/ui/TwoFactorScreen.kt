package com.ssd.mobile.ui

import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.text.KeyboardOptions
import androidx.compose.material3.Button
import androidx.compose.material3.CircularProgressIndicator
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.OutlinedTextField
import androidx.compose.material3.Text
import androidx.compose.runtime.Composable
import androidx.compose.runtime.getValue
import androidx.compose.runtime.mutableStateOf
import androidx.compose.runtime.remember
import androidx.compose.runtime.setValue
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.text.input.KeyboardType
import androidx.compose.ui.unit.dp

@Composable
fun TwoFactorScreen(
    cargando: Boolean,
    error: String?,
    onVerificar: (codigo: String) -> Unit,
) {
    var codigo by remember { mutableStateOf("") }

    Column(
        modifier = Modifier
            .fillMaxSize()
            .padding(24.dp),
        verticalArrangement = Arrangement.Center,
        horizontalAlignment = Alignment.CenterHorizontally,
    ) {
        Text(
            text = "Verificación en dos pasos",
            style = MaterialTheme.typography.headlineSmall,
            modifier = Modifier.padding(bottom = 8.dp),
        )
        Text(
            text = "Ingresa el código que te enviamos.",
            style = MaterialTheme.typography.bodyMedium,
            modifier = Modifier.padding(bottom = 24.dp),
        )

        OutlinedTextField(
            value = codigo,
            onValueChange = { codigo = it },
            label = { Text("Código de 6 dígitos") },
            keyboardOptions = KeyboardOptions(keyboardType = KeyboardType.NumberPassword),
            modifier = Modifier
                .fillMaxWidth()
                .padding(bottom = 12.dp),
        )

        if (error != null) {
            Text(
                text = error,
                color = MaterialTheme.colorScheme.error,
                modifier = Modifier.padding(bottom = 12.dp),
            )
        }

        Button(
            onClick = { onVerificar(codigo.trim()) },
            enabled = !cargando && codigo.isNotBlank(),
            modifier = Modifier.fillMaxWidth(),
            content = {
                if (cargando) {
                    CircularProgressIndicator(modifier = Modifier.padding(4.dp))
                } else {
                    Text("Verificar")
                }
            },
        )
    }
}
