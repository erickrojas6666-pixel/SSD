# Base de datos — Sistema de Gestión de Planeaciones Didácticas UTH

## Cómo usar estos archivos

1. Copia el contenido de `migrations/` a `database/migrations/` de tu proyecto Laravel.
2. Copia el contenido de `models/` a `app/Models/` de tu proyecto Laravel.
3. Copia `seeders/CuatrimestreSeeder.php` a `database/seeders/`.
4. Corre:
   ```bash
   php artisan migrate
   php artisan db:seed --class=CuatrimestreSeeder
   ```

## Estructura general

### Catálogos base
- `roles` — Administrador, Director, Docente, Revisor, Secretario (con seed inicial).
- `cuatrimestres` — catálogo del 1° al 10°.
- `users` — nombre **seccionado** (nombre, apellido_paterno, apellido_materno), auth con email/password, campos para 2FA, `activo` + soft delete.
- `role_user` — pivote N:M entre usuarios y roles.

### Catálogos académicos
- `carreras` — nombre, clave, `director_id` (único → un director solo dirige una carrera, y una carrera solo tiene un director).
- `especialidades` — nombre, clave, pertenece a una `carrera`.
- `asignaturas` — nombre, clave, `plan_estudio_url` (PDF opcional en la nube), pertenece a **un solo** `cuatrimestre`.
- `asignatura_especialidad` — pivote N:M (una asignatura puede estar en varias especialidades).
- `docente_asignatura` — pivote N:M (un docente puede tener varias asignaturas, de distintas especialidades/carreras).

### Núcleo: Secuencia (planeación didáctica)
- `secuencias` — tabla principal. Guarda asignatura, especialidad, carrera, periodo y `estado` (enum: borrador, enviado_revision, en_revision, en_proceso_validacion, validada, rechazada).
- `secuencia_user` — pivote N:M (una secuencia puede tener varios autores docentes).

### Secciones del documento oficial "Planeación Didáctica del Programa de Asignatura" (UTH)
Corregido tras revisar el formato real: cada `n)` numerado dentro de una tabla repetible es **un registro**, no un campo colapsado.

| Sección | Tabla | Cardinalidad |
|---|---|---|
| A. Carátula (incluye horas del saber/saber hacer/totales/semana) | `secuencia_caratulas` | 1:1 con `secuencias` |
| A. Grupo(s) | `secuencia_grupos` | 1:N con `secuencias` |
| B. Unidad de aprendizaje (incluye `porcentaje_unidad`, punto 21) | `secuencia_unidades` | 1:N con `secuencias` |
| B. Temas (Saber / Saber Hacer / Saber Ser-convivir) | `secuencia_unidad_temas` | 1:N por unidad |
| C. Encabezado de evaluación (periodo en semanas, resultado de aprendizaje) | `secuencia_unidad_evaluaciones` | 1:1 por unidad |
| C. Evidencias de aprendizaje (una fila por evidencia) | `secuencia_unidad_evidencias` | 1:N por unidad |
| C. Tipos de evaluación por evidencia (auto/co/hetero, combinables) | `tipos_evaluacion` + `evidencia_tipo_evaluacion` | N:M por evidencia |
| D. Fase de la secuencia (apertura, desarrollo, cierre) | `secuencia_unidad_fases` | 1:N por unidad (siempre 3) |
| D. Actividades de la fase (numeradas consecutivamente) | `secuencia_fase_actividades` | 1:N por fase |
| Referencias bibliográficas y digitales (unificadas, el documento no las distingue) | `secuencia_referencias` | 1:N con `secuencias` |

**Nota**: se eliminó la sección "Perfil idóneo del docente" — esa sección pertenecía a un documento distinto (Programa de Asignatura DGUTyP) y no existe en el formato real de Planeación Didáctica de la UTH.

### Seguimiento y control
- `secuencia_comentarios` — comentarios del revisor/director que el docente puede visualizar.
- `secuencia_historial_estados` — auditoría: registra cada cambio de estado, quién lo hizo y cuándo.

## Reglas de negocio ya resueltas en el modelo `Secuencia`
- `puedeEditarse()` — regresa `false` si el estado ya no es `borrador` (una vez solicitada la revisión no se puede editar/eliminar).
- `cambiarEstado()` — cambia el estado y automáticamente crea el registro en el historial.

## Comentarios y estatus de aceptación por registro (secciones B, C y D)
Cada registro de las secciones B (unidad, tema), C (evaluación, evidencia) y D (fase, actividad) puede recibir del revisor un comentario y un estatus de aceptación, sin duplicar columnas en 6 tablas distintas: se usa una tabla polimórfica `revisiones` + relación `morphOne` en cada modelo.

- `aprobado = null` → pendiente de revisión
- `aprobado = true` → aceptado
- `aprobado = false` → rechazado, con `comentario` explicando por qué

**Paso obligatorio**: copia el contenido de `app_providers_morphmap_snippet.php` dentro del método `boot()` de `app/Providers/AppServiceProvider.php`. Esto hace que `revisable_type` guarde alias cortos (`secuencia_unidad`, `secuencia_unidad_tema`, etc.) en vez del nombre completo de la clase — y es justo lo que usan las vistas SQL de abajo para el join.

Ejemplo de uso en el modelo:
```php
$unidad->revision; // -> Revision|null
$unidad->revision()->updateOrCreate([], [
    'revisor_id' => auth()->id(),
    'aprobado' => true,
    'comentario' => 'Correcto, sin observaciones.',
    'fecha_revision' => now(),
]);
```

## Vistas SQL (evitan joins manuales en el backend)
La migración `2026_01_01_000029_create_views.php` crea 6 vistas de solo lectura, cada una con su modelo en `models/Views/`:

| Vista | Para qué sirve |
|---|---|
| `vw_secuencia_resumen` | Listados/búsqueda de planeaciones (carrera, especialidad, asignatura, cuatrimestre, docentes, estatus) sin joins en el controlador |
| `vw_unidad_detalle` | Unidad + encabezado de evaluación (Sección C) + estatus de revisión, ya resuelto |
| `vw_tema_detalle` | Temas de la unidad con su estatus de revisión |
| `vw_evidencia_detalle` | Evidencias con sus tipos de evaluación combinados (`GROUP_CONCAT`) y estatus de revisión |
| `vw_fase_detalle` | Encabezado de cada fase (apertura/desarrollo/cierre), conteo de actividades y estatus de revisión |
| `vw_actividad_detalle` | Actividades numeradas de cada fase, con el nombre de la fase y estatus de revisión |

Uso típico en un controlador (ya no hay que hacer `->with(['unidad.evaluacion', 'unidad.revision'])`):
```php
use App\Models\Views\VwUnidadDetalle;

$unidades = VwUnidadDetalle::where('secuencia_id', $secuenciaId)->orderBy('numero')->get();
```

**Importante**: las vistas son de **solo lectura**. Para crear/editar registros sigue usando los modelos normales (`SecuenciaUnidad`, `SecuenciaUnidadEvidencia`, etc.) — las vistas solo son para consultar/mostrar información ya combinada.
Interpreté tu corrección ("solo se sube el plan") como: el único archivo del sistema es el **plan de estudio de la asignatura** (`asignaturas.plan_estudio_url`), y la secuencia en sí **no** requiere subir un archivo adicional. Si en realidad la secuencia también necesita adjuntar un PDF propio (ej. el documento final generado), dime y agrego el campo correspondiente en `secuencias`.
