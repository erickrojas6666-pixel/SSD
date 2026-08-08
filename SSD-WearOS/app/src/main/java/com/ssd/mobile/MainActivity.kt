package com.ssd.mobile

import android.os.Bundle
import androidx.activity.ComponentActivity
import androidx.activity.compose.setContent
import androidx.activity.viewModels
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.Surface
import androidx.compose.runtime.collectAsState
import androidx.compose.runtime.getValue
import androidx.compose.ui.Modifier
import com.ssd.mobile.ui.ConfirmationScreen
import com.ssd.mobile.ui.LoginScreen
import com.ssd.mobile.ui.TwoFactorScreen

class MainActivity : ComponentActivity() {

    private val viewModel: AuthViewModel by viewModels()

    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)

        setContent {
            MaterialTheme {
                Surface(modifier = Modifier.fillMaxSize()) {
                    val estado by viewModel.uiState.collectAsState()

                    when (estado.pantalla) {
                        Pantalla.LOGIN -> LoginScreen(
                            cargando = estado.cargando,
                            error = estado.error,
                            onLogin = viewModel::login,
                        )

                        Pantalla.DOS_FACTORES -> TwoFactorScreen(
                            cargando = estado.cargando,
                            error = estado.error,
                            onVerificar = viewModel::verificarCodigo,
                        )

                        Pantalla.CONFIRMACION -> ConfirmationScreen(
                            relojConectado = estado.relojConectado,
                            onReintentar = { viewModel.enviarTokenAlReloj() },
                            onCerrarSesion = { viewModel.logout() },
                        )
                    }
                }
            }
        }
    }
}
