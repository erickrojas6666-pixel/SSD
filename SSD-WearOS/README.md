# SSD Notificaciones — app móvil + Wear OS

Proyecto Android Studio con dos módulos:

- **`app`** — app del celular. Solo hace login (con soporte de 2FA, igual
  que tu web) y le pasa el token de sesión al reloj emparejado por
  Bluetooth. No tiene ninguna otra funcionalidad.
- **`wear`** — app del reloj. Muestra "Te llegarán notificaciones",
  recibe el token del celular, se registra contra tu API para recibir
  push, y muestra las notificaciones cuando cambia el estado de una
  secuencia.

## 1. Abrir el proyecto

Abre la carpeta `SSD-WearOS/` completa en Android Studio (Ladybug o más
reciente, con soporte de Wear OS). Deja que sincronice Gradle.

## 2. Configurar Firebase

1. Entra a [Firebase Console](https://console.firebase.google.com) y crea
   (o reusa) un proyecto.
2. Agrega **dos** apps Android al proyecto:
   - Package name `com.ssd.mobile` (opcional, solo si más adelante
     quieres push también en el celular; hoy no es indispensable).
   - Package name `com.ssd.wear` (obligatorio, es la que recibe el push).
3. Descarga el `google-services.json` (incluye ambas apps) y colócalo en:
   - `wear/google-services.json`
   - `app/google-services.json` (si registraste también esa app)
4. En Configuración del proyecto → Cuentas de servicio, genera una clave
   privada. Ese JSON es para el **backend Laravel**, no para Android (ver
   `CAMBIOS_EN_ARCHIVOS_EXISTENTES.md` del backend).

## 3. Apuntar a tu API

Cambia `API_BASE_URL` en:
- `app/src/main/java/com/ssd/mobile/data/Config.kt`
- `wear/src/main/java/com/ssd/wear/data/Config.kt`

Por defecto apunta a `http://10.0.2.2:8000/api/`, que es cómo el
emulador de Android ve el `localhost` de tu máquina corriendo
`php artisan serve`. Para dispositivos físicos o producción, usa tu
dominio real (con HTTPS).

## 4. Probar con emuladores

Android Studio te deja crear un emulador de reloj (Wear OS) y emparejarlo
con un emulador de celular directamente desde el "Pair" del propio
Device Manager. Corre primero la app `wear` en el emulador del reloj, y
luego `app` en el del celular — así el reloj ya está "escuchando" cuando
el celular intenta mandarle el token.

## 5. Flujo esperado

1. Abres la app del reloj → ves "Te llegarán notificaciones" y un aviso
   de que falta emparejar.
2. Abres la app del celular → inicias sesión (con 2FA si tu cuenta lo
   tiene activo).
3. El celular manda el token al reloj automáticamente.
4. El reloj se registra solo contra `/api/dispositivo/fcm-token`.
5. Cuando alguien cambia el estado de una secuencia de la que eres autor,
   te llega la notificación directo al reloj.

## Notas

- El código de red está deliberadamente duplicado entre `app` y `wear`
  (cada módulo tiene su propio `ApiClient`) para mantener el proyecto
  simple. Si crece, vale la pena moverlo a un tercer módulo `:core`.
- El manejo de errores de red es básico (mensajes genéricos); ajústalo
  según lo que necesites mostrarle al usuario.
- Falta el ícono de la app (`ic_launcher`) en ambos módulos — Android
  Studio te lo genera con el asistente *Image Asset* con un par de
  clics.
