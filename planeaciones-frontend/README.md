# planeaciones-frontend (Frontend)

SPA del sistema SSD construida con **Vue 3** + **Vuetify** + **Vite**. Consume la API REST de `planeaciones-api` y ofrece una vista distinta según el rol del usuario autenticado (Administrador, Docente, Revisor, Director, Secretario).

## Stack y dependencias clave

| Paquete | Uso |
|---|---|
| `vue` ^3.5 | Framework UI |
| `vuetify` | Sistema de componentes/Material Design |
| `vue-router` | Enrutamiento SPA |
| `pinia` | Estado global (store de autenticación) |
| `axios` | Cliente HTTP hacia la API |
| `firebase` | Integración con Firebase (cliente) |
| `lucide-vue-next` | Iconos |
| `vite` | Bundler / dev server |
| `eslint` + `oxlint` | Lint |

Requiere Node.js `^22.18.0` o `>=24.12.0` (ver `engines` en `package.json`).

## Instalación

```bash
cd planeaciones-frontend
npm install
```

## Desarrollo

```bash
npm run dev
# SPA disponible por defecto en http://localhost:5173
```

## Build de producción

```bash
npm run build
npm run preview   # para previsualizar el build localmente
```

## Lint

```bash
npm run lint        # corre oxlint y eslint con --fix
```

## Conexión con el backend

El cliente HTTP vive en `src/services/api.js` (Axios). Debe apuntar a la URL donde corre `planeaciones-api` (por defecto `http://localhost:8000`). Revisa/ajusta esa configuración (o las variables de entorno de Vite, típicamente `VITE_*` en un `.env` propio del frontend) antes de levantar el proyecto contra tu backend local.

## Estructura de carpetas

```
src/
├── assets/                     # Estilos e imágenes estáticas
├── components/
│   ├── AppShell.vue            # Layout principal (shell de la app autenticada)
│   ├── DocHeader.vue           # Cabecera de documentos/secuencia
│   ├── IconButton.vue
│   ├── InfoTooltip.vue
│   ├── Modal.vue                # Modal reutilizable
│   └── ValidacionElemento.vue   # Componente de revisión/validación de un elemento
├── config/                     # Configuración (p. ej. Firebase, constantes)
├── router/
│   └── index.js                # Definición de rutas y guards por rol
├── services/
│   └── api.js                  # Instancia de Axios / llamadas a la API
├── stores/
│   └── auth.js                 # Store Pinia de autenticación (usuario, token, rol)
└── views/
    ├── LoginView.vue
    ├── ForgotPasswordView.vue
    ├── ResetPasswordView.vue
    ├── ConfirmarCuentaView.vue
    ├── TwoFactorChallengeView.vue
    ├── TwoFactorSettingsView.vue
    ├── admin/
    │   ├── AcademicoView.vue
    │   ├── CarrerasPanel.vue
    │   ├── EspecialidadesPanel.vue
    │   ├── AsignaturasPanel.vue
    │   ├── AsignaturaFormModal.vue
    │   ├── AsignaturaMasivaModal.vue
    │   ├── UsuariosView.vue
    │   └── UsuarioFormModal.vue
    ├── homes/                   # Pantalla de inicio por rol
    │   ├── AdminHome.vue
    │   ├── DocenteHome.vue
    │   ├── RevisorHome.vue
    │   ├── DirectorHome.vue
    │   └── SecretarioHome.vue
    └── secuencias/
        ├── SecuenciaEditorView.vue        # Editor principal de una secuencia
        ├── NuevaSecuenciaModal.vue
        ├── DuplicarSecuenciaModal.vue
        ├── EditarGruposAutoresModal.vue
        ├── DirectorResumenModal.vue
        ├── docente/
        │   ├── SecuenciasDocenteView.vue
        │   ├── BorradoresPanel.vue
        │   └── HistorialPanel.vue
        ├── revisor/SecuenciasRevisorView.vue
        └── director/SecuenciasDirectorView.vue
```

## Flujos principales de la UI

- **Login / recuperación / 2FA**: `LoginView`, `ForgotPasswordView`, `ResetPasswordView`, `TwoFactorChallengeView`, `TwoFactorSettingsView`, `ConfirmarCuentaView`.
- **Panel de administración**: gestión de carreras, especialidades y asignaturas (`admin/*Panel.vue` + sus modales) y de usuarios (`UsuariosView` + `UsuarioFormModal`).
- **Docente**: creación/edición de secuencias mediante `SecuenciaEditorView`, con paneles de borradores e historial (`docente/BorradoresPanel.vue`, `docente/HistorialPanel.vue`).
- **Revisor**: cola de revisión en `SecuenciasRevisorView` apoyada en `ValidacionElemento.vue` para validar/rechazar cada parte de la secuencia.
- **Director**: cola y resumen final en `SecuenciasDirectorView` / `DirectorResumenModal`.
- Cada rol aterriza en su propia vista "home" (`views/homes/`), y el enrutamiento (`router/index.js`) filtra el acceso según el rol guardado en el store `auth.js`.
