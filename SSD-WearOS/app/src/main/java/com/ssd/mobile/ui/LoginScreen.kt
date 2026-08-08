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
import androidx.compose.ui.text.input.PasswordVisualTransformation
import androidx.compose.ui.unit.dp
import androidx.compose.ui.text.style.TextAlign

@Composable
fun LoginScreen(
    cargando: Boolean,
    error: String?,
    onLogin: (email: String, password: String) -> Unit,
) {
    var email by remember { mutableStateOf("") }
    var password by remember { mutableStateOf("") }

    Column(
        modifier = Modifier
            .fillMaxSize()
            .padding(24.dp),
        verticalArrangement = Arrangement.Center,
        horizontalAlignment = Alignment.CenterHorizontally,
    ) {
        Text(
            text = "Iniciar sesión",
            style = MaterialTheme.typography.headlineMedium,
            modifier = Modifier.padding(bottom = 24.dp),
        )

        OutlinedTextField(
            value = email,
            onValueChange = { email = it },
            label = { Text("Correo") },
            keyboardOptions = KeyboardOptions(keyboardType = KeyboardType.Email),
            modifier = Modifier
                .fillMaxWidth()
                .padding(bottom = 12.dp),
        )

        OutlinedTextField(
            value = password,
            onValueChange = { password = it },
            label = { Text("Contraseña") },
            visualTransformation = PasswordVisualTransformation(),
            keyboardOptions = KeyboardOptions(keyboardType = KeyboardType.Password),
            modifier = Modifier
                .fillMaxWidth()
                .padding(bottom = 12.dp),
        )

        if (error != null) {
            Text(
                text = error,
                color = MaterialTheme.colorScheme.error,
                textAlign = TextAlign.Start,
                modifier = Modifier.padding(bottom = 12.dp),
            )
        }

        Button(
            onClick = { onLogin(email.trim(), password) },
            enabled = !cargando && email.isNotBlank() && password.isNotBlank(),
            modifier = Modifier
                .fillMaxWidth()
                .padding(top = 8.dp),
            content = {
                if (cargando) {
                    CircularProgressIndicator(modifier = Modifier.padding(4.dp))
                } else {
                    Text("Entrar")
                }
            },
        )
    }
}
