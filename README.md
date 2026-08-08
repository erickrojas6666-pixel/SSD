# SSD — Sistema de gestión de Secuencias Didácticas (UTH)

Sistema web para que la **Universidad Tecnológica de Huejotzingo (UTH)** administre el ciclo de vida completo de las secuencias didácticas: creación por parte de los docentes, revisión, validación por dirección y control académico (carreras, especialidades, asignaturas y usuarios).

El proyecto está dividido en dos aplicaciones independientes dentro del mismo repositorio:

```
SSD/
├── planeaciones-api/         # Backend — API REST (Laravel 13 / PHP 8.3)
└── planeaciones-frontend/    # Frontend — SPA (Vue 3 / Vuetify / Vite)
```

## Descripción funcional

El sistema gira en torno a las **secuencias didácticas**, documentos estructurados en unidades, temas, evaluaciones, evidencias y actividades por fase, que un docente elabora para una asignatura y que después pasa por un flujo de revisión y validación:

1. **Docente** — crea y edita sus secuencias (carátula, unidades, temas, evaluaciones, evidencias, actividades, referencias), y las envía a revisión.
2. **Revisor** — recibe la cola de secuencias enviadas, valida o rechaza cada elemento y comenta.
3. **Director** — recibe las secuencias ya revisadas, ve un resumen y las valida o rechaza de forma definitiva.
4. **Administrador** — gestiona el catálogo académico: carreras, especialidades, asignaturas y usuarios (con reenvío de credenciales, activación/desactivación, etc.).

Además incluye:
- Autenticación con **Laravel Sanctum** (tokens) y **2FA** (Google2FA / correo).
- Confirmación de cuenta por enlace enviado por correo al crear un usuario.
- Recuperación de contraseña.

## Roles del sistema

| Rol | Función principal |
|---|---|
| Administrador | Gestión de carreras, especialidades, asignaturas y usuarios |
| Docente | Creación y edición de secuencias didácticas propias |
| Revisor | Revisión y validación de elementos de la secuencia |
| Director | Validación final y resumen de la secuencia |
| Secretario | Rol de apoyo administrativo (vista propia en el frontend) |

## Stack tecnológico

| Capa | Tecnología |
|---|---|
| Backend | PHP 8.3, Laravel 13, Sanctum, L5-Swagger, Firebase (Kreait), Google2FA |
| Frontend | Vue 3, Vuetify, Vue Router, Pinia, Axios, Firebase, Vite |
| Base de datos | SQLite por defecto (configurable a MySQL/otros vía `.env`) |

## Puesta en marcha rápida

El proyecto requiere levantar **backend** y **frontend** por separado (cada uno tiene su propio README con el detalle):

1. Backend → ver [`planeaciones-api/README.md`](../planeaciones-api/README.md) (o el README específico generado para esta parte).
2. Frontend → ver [`planeaciones-frontend/README.md`](../planeaciones-frontend/README.md).

En resumen:

```bash
# 1. Backend (API en http://localhost:8000)
cd planeaciones-api
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
php artisan serve

# 2. Frontend (SPA en http://localhost:5173)
cd planeaciones-frontend
npm install
npm run dev
```

El frontend consume la API mediante Axios; asegúrate de que la URL base configurada en `planeaciones-frontend/src/services/api.js` / variables de entorno apunte al backend levantado.

## Estructura de carpetas (alto nivel)

```
planeaciones-api/
├── app/
│   ├── Http/Controllers/Api/   # Controladores REST (Auth, Admin, Secuencias, 2FA, Usuarios)
│   ├── Http/Middleware/        # EnsureUserHasRole (control de acceso por rol)
│   └── Models/                 # Eloquent models (Secuencia, Carrera, Asignatura, User, etc.)
├── database/migrations/        # 35 migraciones: esquema completo del dominio académico
├── database/seeders/           # Seeders de roles, cuatrimestres y usuarios
└── routes/api.php              # Definición de endpoints, agrupados por rol

planeaciones-frontend/
├── src/views/                  # Vistas por rol (admin, docente, revisor, director, secretario)
├── src/components/             # Componentes reutilizables (AppShell, Modal, DocHeader, etc.)
├── src/stores/                 # Pinia (auth.js)
├── src/services/               # Cliente Axios (api.js)
└── src/router/                 # Vue Router
```
