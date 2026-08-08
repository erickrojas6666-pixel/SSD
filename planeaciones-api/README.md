# planeaciones-api (Backend)

API REST del sistema SSD, construida con **Laravel 13** sobre **PHP 8.3**. Expone toda la lógica de negocio: autenticación, gestión académica (carreras, especialidades, asignaturas, usuarios) y el flujo completo de secuencias didácticas (creación, revisión, validación).

## Stack y dependencias clave

| Paquete | Uso |
|---|---|
| `laravel/framework` ^13.8 | Framework base |
| `laravel/sanctum` | Autenticación por token (SPA/API) |
| `pragmarx/google2fa` + `google2fa-laravel` | Autenticación en dos pasos (2FA) |
| `kreait/laravel-firebase` | Integración con Firebase |
| `darkaonline/l5-swagger` | Documentación OpenAPI/Swagger de la API |
| `simplesoftwareio/simple-qrcode` | Generación de QR (probablemente para 2FA) |
| `smalot/pdfparser` | Lectura/parseo de PDFs |
| `laravel/pint`, `phpunit`, `mockery`, `fakerphp/faker` | Calidad de código y pruebas (dev) |

Base de datos por defecto: **SQLite** (`DB_CONNECTION=sqlite` en `.env.example`), fácilmente cambiable a MySQL/PostgreSQL.

## Requisitos

- PHP ^8.3 con las extensiones habituales de Laravel
- Composer
- Node.js + npm (solo para compilar assets con Vite/Tailwind, no es el frontend principal de la app)
- SQLite (o el motor de BD que configures)

## Instalación

```bash
cd planeaciones-api
composer install
cp .env.example .env
php artisan key:generate

# si usas sqlite (por defecto) crea el archivo de base de datos:
touch database/database.sqlite

php artisan migrate --seed
```

El comando `composer setup` automatiza gran parte de esto (install, copiar `.env`, generar key, migrar, instalar/compilar assets npm).

## Ejecución en desarrollo

```bash
composer dev
```

Este script (`composer.json`) levanta en paralelo, vía `concurrently`:
- `php artisan serve` — servidor de la API
- `php artisan queue:listen` — worker de colas
- `php artisan pail` — logs en tiempo real
- `npm run dev` — Vite (assets internos del backend, p. ej. vistas de correo)

O de forma manual solo la API:

```bash
php artisan serve
# API disponible en http://localhost:8000
```

## Autenticación y roles

- Autenticación basada en **Sanctum** (tokens Bearer).
- Middleware propio `EnsureUserHasRole` (`role:NombreDelRol`) protege rutas por rol; acepta múltiples roles: `role:Administrador,Director`.
- Soporta **2FA** (activar/confirmar/deshabilitar, verificación y reenvío de código en el login).
- Flujo de **confirmación de cuenta** por correo al crear un usuario, y **recuperación de contraseña**.

Roles usados en las rutas: `Administrador`, `Docente`, `Revisor`, `Director` (y `Secretario` a nivel de dominio/frontend).

## Estructura de endpoints (`routes/api.php`)

**Públicas**
- `POST /login`, `POST /forgot-password`, `POST /reset-password`
- `POST /2fa/verify`, `POST /2fa/resend`
- `POST /confirmar-cuenta`

**Protegidas (`auth:sanctum`)**
- `POST /logout`, `GET /me`
- `POST /2fa/enable|confirm|disable`

**Admin** (`role:Administrador`, prefijo `/admin`)
- CRUD de `carreras`, `especialidades`, `asignaturas`, `usuarios` (+ catálogos, toggle-activo, vinculaciones, alta masiva de asignaturas, reenvío de credenciales)

**Secuencias didácticas**
- Compartido: `GET /secuencias/catalogos`, `GET /secuencias/{secuencia}`
- **Docente** (prefijo `/docente`): crear/duplicar secuencia, enviar/cancelar revisión, carátula, grupos de autores, unidades, temas, evaluaciones, evidencias, actividades por fase, referencias (CRUD completo de cada submódulo).
- **Revisor** (prefijo `/revisor`): cola de revisión, enviar a validación, rechazar, actualizar validación de un elemento.
- **Director** (prefijo `/director`): cola de secuencias, resumen, validar/rechazar definitivamente.

## Modelo de datos (resumen)

35 migraciones definen el esquema, entre ellas:
- **Identidad/roles**: `users`, `roles`, `role_user`, `two_factor_challenges`, `two_factor_codes`, `confirmaciones_cuenta`, `personal_access_tokens`
- **Académico**: `carreras`, `especialidades`, `asignaturas`, `asignatura_especialidad`, `docente_asignatura`, `cuatrimestres`
- **Secuencias**: `secuencias`, `secuencia_user`, `secuencia_caratulas`, `secuencia_unidades`, `secuencia_unidad_temas`, `secuencia_unidad_evaluaciones`, `secuencia_unidad_evidencias`, `evidencia_tipo_evaluacion`, `secuencia_unidad_fases`, `secuencia_fase_actividades`, `secuencia_referencias`, `secuencia_grupos`, `secuencia_comentarios`, `secuencia_historial_estados`, `revisiones`, `tipos_evaluacion`

Modelos Eloquent equivalentes en `app/Models/`.

## Estructura de carpetas relevante

```
app/
├── Exceptions/                          # Excepciones de dominio (p. ej. PlanEstudioInvalidoException)
├── Http/Controllers/Api/
│   ├── AuthController.php
│   ├── TwoFactorController.php
│   ├── Asignatura/AsignaturaController.php
│   ├── Carreras/CarreraController.php, EspecialidadController.php
│   ├── Usuario/UserController.php, ConfirmacionCuentaController.php
│   └── Secuencias/                      # Controladores del flujo de secuencias
│       ├── SecuenciaController.php
│       ├── SecuenciaCaratulaController.php
│       ├── SecuenciaUnidadController.php
│       ├── SecuenciaUnidadTemaController.php
│       ├── SecuenciaUnidadEvaluacionController.php
│       ├── SecuenciaUnidadEvidenciaController.php
│       ├── SecuenciaFaseActividadController.php
│       ├── SecuenciaReferenciaController.php
│       └── RevisionController.php
├── Http/Middleware/EnsureUserHasRole.php
└── Models/                              # Un modelo Eloquent por tabla de dominio
database/
├── migrations/                          # Esquema completo (35 archivos)
└── seeders/                             # DatabaseSeeder, CuatrimestreSeeder, UserSeeder
routes/api.php                           # Todas las rutas de la API
```

## Pruebas

```bash
composer test
```
(limpia config y ejecuta `php artisan test`, sobre PHPUnit)

## Documentación de la API

El paquete `l5-swagger` está instalado; una vez configurado, la documentación Swagger/OpenAPI suele quedar disponible en una ruta como `/api/documentation` (revisar `config/l5-swagger.php` y generar con `php artisan l5-swagger:generate`).

## Variables de entorno relevantes

Además de las típicas de Laravel (`APP_*`, `DB_*`, `MAIL_*`), revisa `.env.example` para: sesión, colas (`QUEUE_CONNECTION=database`), caché, AWS (si se usa almacenamiento externo) y credenciales de Firebase (no incluidas en `.env.example`, deben añadirse según `kreait/laravel-firebase`).