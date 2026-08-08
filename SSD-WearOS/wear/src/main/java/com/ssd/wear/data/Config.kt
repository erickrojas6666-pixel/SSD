package com.ssd.wear.data

object Config {
    // Misma API que usa la app del celular. Cámbiala por tu dominio real
    // en producción (el reloj también necesita salir a internet directo,
    // por WiFi o LTE, aunque haya recibido el token vía Bluetooth).
    const val API_BASE_URL = "http://10.0.2.2:8000/api/"
}
