<?php

namespace App\Http\Controllers\Api\Secuencias;

use App\Exceptions\PlanEstudioInvalidoException;
use App\Http\Controllers\Controller;
use App\Models\Asignatura;
use App\Models\Secuencia;
use App\Models\User;
use App\Services\PeriodoAcademicoService;
use App\Services\SecuenciaService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Throwable;

class SecuenciaController extends Controller
{
    private const RELACIONES_COMPLETAS = [
        'asignatura.cuatrimestre',
        'especialidad',
        'carrera',
        'autores',
        'grupos',
        'caratula',
        'unidades.temas.revision',
        'unidades.revision',
        'unidades.evaluacion.revision',
        'unidades.evidencias.revision',
        'unidades.fases.actividades.revision',
        'referencias.revision',
    ];

    public function __construct(
        private SecuenciaService $secuenciaService,
        private PeriodoAcademicoService $periodoAcademico,
    ) {}

    /**
     * GET /api/docente/secuencias?estado=borrador|validada
     */
    public function misSecuencias(Request $request)
    {
        try {
            $usuario = $request->user();
            $estado = $request->get('estado', 'borrador');

            $secuencias = Secuencia::query()
                ->with(['asignatura', 'especialidad', 'carrera', 'autores'])
                ->whereHas('autores', fn($q) => $q->where('users.id', $usuario->id))
                ->when($estado === 'borrador', fn($q) => $q->where('estado', 'borrador'))
                ->when($estado === 'validada', fn($q) => $q->where('estado', 'validada'))
                ->when($estado === 'todas', fn($q) => $q)
                ->orderByDesc('created_at')
                ->get();

            return response()->json($secuencias);
        } catch (Throwable $e) {
            Log::error('SecuenciaController@misSecuencias: error al listar secuencias del docente', [
                'mensaje' => $e->getMessage(),
                'linea' => $e->getLine(),
                'archivo' => $e->getFile(),
            ]);

            return response()->json(['message' => 'No se pudieron cargar tus secuencias.'], 500);
        }
    }

    /**
     * GET /api/revisor/secuencias
     */
    public function colaRevisor(Request $request)
    {
        try {

            /* Cambiar todas las secuencias rechazadas a en revision */

            $secuenciasRechazadas = Secuencia::where('estado', 'rechazada')->get();
            if ($secuenciasRechazadas->isNotEmpty()) {
                foreach ($secuenciasRechazadas as $secuencia) {
                    $this->cambiarEstado($request, $secuencia, 'rechazada', 'en_revision');
                }
            }

            $secuencias = Secuencia::query()
                ->with(['asignatura', 'especialidad', 'carrera', 'autores'])
                ->where('estado', 'en_revision')
                ->orderBy('fecha_solicitud_revision')
                ->get();

            return response()->json($secuencias);
        } catch (Throwable $e) {
            Log::error('SecuenciaController@colaRevisor: error al listar la cola del revisor', [
                'mensaje' => $e->getMessage(),
                'linea' => $e->getLine(),
                'archivo' => $e->getFile(),
            ]);

            return response()->json(['message' => 'No se pudo cargar la cola de revisión.'], 500);
        }
    }

    /**
     * GET /api/director/secuencias?periodo=Mayo - Agosto 2026
     * Todas las secuencias (en cualquier estado) de la carrera que dirige,
     * del periodo indicado. Si no se manda "periodo", usa el periodo actual.
     * Antes solo mostraba las pendientes de validación; ahora el director
     * necesita ver el panorama completo del periodo.
     */
    public function colaDirector(Request $request)
    {
        try {
            $usuario = $request->user();
            $carreraId = $usuario->carreraDirigida()->value('id');
            $periodo = $request->get('periodo') ?: $this->periodoAcademico->actual();

            $ordenEstados = ['en_proceso_validacion', 'en_revision', 'borrador', 'rechazada', 'validada'];
            $casoOrden = 'CASE estado ' . implode(' ', array_map(
                fn($estado, $i) => "WHEN '{$estado}' THEN {$i}",
                $ordenEstados,
                array_keys($ordenEstados)
            )) . ' ELSE 99 END';

            $secuencias = Secuencia::query()
                ->with(['asignatura', 'especialidad', 'carrera', 'autores'])
                ->where('periodo', $periodo)
                ->when($carreraId, fn($q) => $q->where('carrera_id', $carreraId), fn($q) => $q->whereRaw('1 = 0'))
                ->orderByRaw($casoOrden)
                ->orderBy('updated_at')
                ->get();

            return response()->json([
                'periodo' => $periodo,
                'secuencias' => $secuencias,
            ]);
        } catch (Throwable $e) {
            Log::error('SecuenciaController@colaDirector: error al listar la cola del director', [
                'mensaje' => $e->getMessage(),
                'linea' => $e->getLine(),
                'archivo' => $e->getFile(),
            ]);

            return response()->json(['message' => 'No se pudo cargar la cola del director.'], 500);
        }
    }

    /**
     * GET /api/secuencias/catalogos
     * Para el formulario de creación (docente).
     * Solo se listan las asignaturas que el docente autenticado imparte
     * (tabla docente_asignatura), no todas las del sistema.
     */
    public function catalogos(Request $request)
    {
        try {
            $usuario = $request->user();

            $asignaturasQuery = Asignatura::with('especialidades.carrera')
                ->where('activo', true);

            if ($usuario->tieneRol('Docente')) {
                $asignaturasQuery->whereHas('docentes', fn($q) => $q->where('users.id', $usuario->id));
            }

            return response()->json([
                'asignaturas' => $asignaturasQuery->orderBy('nombre')->get(['id', 'nombre', 'clave', 'plan_estudio_url']),
                'docentes' => User::whereHas('roles', fn($q) => $q->where('nombre', 'Docente'))
                    ->where('activo', true)
                    ->orderBy('nombre')
                    ->get(['id', 'nombre', 'apellido_paterno', 'apellido_materno']),
            ]);
        } catch (Throwable $e) {
            Log::error('SecuenciaController@catalogos: error al cargar catálogos', [
                'mensaje' => $e->getMessage(),
                'linea' => $e->getLine(),
                'archivo' => $e->getFile(),
            ]);

            return response()->json(['message' => 'No se pudieron cargar los catálogos.'], 500);
        }
    }

    /**
     * POST /api/docente/secuencias  (multipart/form-data si sube plan_estudio)
     */
    public function store(Request $request)
    {
        try {
            $data = $request->validate([
                'asignatura_id' => ['required', 'exists:asignaturas,id'],
                'especialidad_id' => ['required', 'exists:especialidades,id'],
                'carrera_id' => ['required', 'exists:carreras,id'],
                'periodo' => ['required', 'string', 'max:30'],
                'coautor_ids' => ['nullable', 'array'],
                'coautor_ids.*' => ['exists:users,id'],
                'grupos' => ['nullable', 'array'],
                'grupos.*' => ['string', 'max:30'],
                'plan_estudio' => ['nullable', 'file', 'mimes:pdf', 'max:10240'],
            ]);

            $autorPrincipal = $request->user();
            $imparteAsignatura = Asignatura::whereKey($data['asignatura_id'])
                ->whereHas('docentes', fn($q) => $q->where('users.id', $autorPrincipal->id))
                ->exists();

            if (! $imparteAsignatura) {
                return response()->json(['message' => 'No tienes asignada esa materia, no puedes crear una secuencia para ella.'], 403);
            }

            $secuencia = $this->secuenciaService->crear($data, $request->file('plan_estudio'), $autorPrincipal);

            return response()->json($secuencia, 201);
        } catch (PlanEstudioInvalidoException $e) {
            return response()->json([
                'message' => $e->getMessage(),
                'errores' => $e->errores,
                'detalles' => $e->detalles,
            ], 422);
        } catch (ValidationException $e) {
            throw $e;
        } catch (Throwable $e) {
            Log::error('SecuenciaController@store: error al crear la secuencia', [
                'datos' => $request->except('plan_estudio'),
                'mensaje' => $e->getMessage(),
                'linea' => $e->getLine(),
                'archivo' => $e->getFile(),
            ]);

            return response()->json(['message' => 'No se pudo crear la secuencia.'], 500);
        }
    }

    /**
     * POST /api/docente/secuencias/{secuencia}/duplicar
     */
    public function duplicar(Request $request, Secuencia $secuencia)
    {
        try {
            $data = $request->validate([
                'periodo' => ['required', 'string', 'max:30'],
                'especialidad_id' => ['nullable', 'exists:especialidades,id'],
                'carrera_id' => ['nullable', 'exists:carreras,id'],
                'coautor_ids' => ['nullable', 'array'],
                'coautor_ids.*' => ['exists:users,id'],
                'grupos' => ['nullable', 'array'],
                'grupos.*' => ['string', 'max:30'],
            ]);

            $nueva = $this->secuenciaService->duplicar($secuencia, $request->user(), $data);

            return response()->json($nueva, 201);
        } catch (ValidationException $e) {
            throw $e;
        } catch (Throwable $e) {
            Log::error('SecuenciaController@duplicar: error al duplicar la secuencia', [
                'secuencia_id' => $secuencia->id,
                'mensaje' => $e->getMessage(),
                'linea' => $e->getLine(),
                'archivo' => $e->getFile(),
            ]);

            return response()->json(['message' => 'No se pudo duplicar la secuencia.'], 500);
        }
    }

    /**
     * GET /api/secuencias/{secuencia}
     * Vista completa del editor (docente autor, o revisor mientras está en revisión).
     */
    public function show(Request $request, string $id)
    {
        try {
            $secuencia = Secuencia::with(self::RELACIONES_COMPLETAS)->findOrFail($id);
            $usuario = $request->user();

            $esAutor = $secuencia->autores->contains('id', $usuario->id);
            $esRevisor = $usuario->tieneRol('Revisor') && $secuencia->estado === 'en_revision';

            if (! $esAutor && ! $esRevisor && ! $usuario->tieneRol('Administrador')) {
                return response()->json(['message' => 'No tienes acceso a esta secuencia.'], 403);
            }

            return response()->json([
                'secuencia' => $secuencia,
                'es_autor' => $esAutor,
                'es_revisor' => $usuario->tieneRol('Revisor'),
                'editable' => $esAutor && $secuencia->estado === 'borrador',
                'puede_validar_elementos' => $esRevisor,
                'completitud' => $this->secuenciaService->completitud($secuencia),
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => 'La secuencia no existe.'], 404);
        } catch (Throwable $e) {
            Log::error('SecuenciaController@show: error al obtener la secuencia', [
                'secuencia_id' => $id,
                'mensaje' => $e->getMessage(),
                'linea' => $e->getLine(),
                'archivo' => $e->getFile(),
            ]);

            return response()->json(['message' => 'No se pudo cargar la secuencia.'], 500);
        }
    }

    /**
     * GET /api/secuencias/{secuencia}/completitud
     * Recalcula solo el checklist (sin traer toda la secuencia otra vez),
     * para refrescar la lista de verificación después de cada cambio.
     */
    public function completitud(Request $request, Secuencia $secuencia)
    {
        try {
            $usuario = $request->user();
            $esAutor = $secuencia->autores()->where('users.id', $usuario->id)->exists();

            if (! $esAutor && ! $usuario->tieneRol('Administrador')) {
                return response()->json(['message' => 'No tienes acceso a esta secuencia.'], 403);
            }

            return response()->json($this->secuenciaService->completitud($secuencia));
        } catch (Throwable $e) {
            Log::error('SecuenciaController@completitud: error al recalcular la completitud', [
                'secuencia_id' => $secuencia->id,
                'mensaje' => $e->getMessage(),
                'linea' => $e->getLine(),
                'archivo' => $e->getFile(),
            ]);

            return response()->json(['message' => 'No se pudo recalcular la lista de verificación.'], 500);
        }
    }

    /**
     * GET /api/director/secuencias/{secuencia}/resumen
     * Para el modal del director: solo un resumen, no el editor completo.
     */
    public function resumen(Request $request, Secuencia $secuencia)
    {
        try {
            $usuario = $request->user();

            if ($secuencia->carrera_id !== $usuario->carreraDirigida()->value('id')) {
                return response()->json(['message' => 'No diriges la carrera de esta secuencia.'], 403);
            }

            $secuencia->load(['asignatura', 'especialidad', 'carrera', 'autores', 'grupos', 'caratula', 'unidades']);

            return response()->json($secuencia);
        } catch (Throwable $e) {
            Log::error('SecuenciaController@resumen: error al obtener el resumen', [
                'secuencia_id' => $secuencia->id,
                'mensaje' => $e->getMessage(),
                'linea' => $e->getLine(),
                'archivo' => $e->getFile(),
            ]);

            return response()->json(['message' => 'No se pudo cargar el resumen.'], 500);
        }
    }

    /**
     * DELETE /api/docente/secuencias/{secuencia}
     * Solo el autor puede eliminarla, y solo mientras esté en borrador.
     */
    public function destroy(Request $request, Secuencia $secuencia)
    {
        try {
            $usuario = $request->user();
            $esAutor = $secuencia->autores()->where('users.id', $usuario->id)->exists();

            if (! $esAutor || $secuencia->estado !== 'borrador') {
                return response()->json(['message' => 'Esta secuencia ya no se puede eliminar (no eres autor o ya no está en borrador).'], 403);
            }

            // Borrado suave (soft delete): solo se marca deleted_at, no se
            // toca ningún registro relacionado (unidades, temas, etc. se
            // quedan intactos y la secuencia se puede recuperar si hace falta).
            $secuencia->delete();

            return response()->json(['message' => 'Secuencia eliminada.']);
        } catch (Throwable $e) {
            Log::error('SecuenciaController@destroy: error al eliminar la secuencia', [
                'secuencia_id' => $secuencia->id,
                'mensaje' => $e->getMessage(),
                'linea' => $e->getLine(),
                'archivo' => $e->getFile(),
            ]);

            return response()->json(['message' => 'No se pudo eliminar la secuencia.'], 500);
        }
    }

    // ── Transiciones de estado ──────────────────────────────

    /**
     * POST /api/docente/secuencias/{secuencia}/enviar-revision
     */
    public function enviarRevision(Request $request, Secuencia $secuencia)
    {
        return $this->cambiarEstado($request, $secuencia, 'borrador', 'en_revision', function () use ($secuencia) {
            $problemas = collect($this->secuenciaService->completitud($secuencia))->where('ok', false);
            if ($problemas->isNotEmpty()) {
                throw ValidationException::withMessages(['completitud' => $problemas->pluck('label')->values()->toArray()]);
            }
        });
    }

    /**
     * POST /api/docente/secuencias/{secuencia}/cancelar-envio
     */
    public function cancelarEnvio(Request $request, Secuencia $secuencia)
    {
        return $this->cambiarEstado($request, $secuencia, 'en_revision', 'borrador');
    }

    /**
     * POST /api/revisor/secuencias/{secuencia}/enviar-validacion
     */
    public function enviarValidacion(Request $request, Secuencia $secuencia)
    {
        return $this->cambiarEstado($request, $secuencia, 'en_revision', 'en_proceso_validacion');
    }

    /**
     * POST /api/revisor/secuencias/{secuencia}/rechazar
     */
    public function rechazarRevision(Request $request, Secuencia $secuencia)
    {
        return $this->cambiarEstado($request, $secuencia, 'en_revision', 'borrador');
    }

    /**
     * POST /api/director/secuencias/{secuencia}/rechazar
     */
    public function rechazar(Request $request, Secuencia $secuencia)
    {
        return $this->cambiarEstado($request, $secuencia, 'en_proceso_validacion', 'rechazada', function () use ($request, $secuencia) {
            if ($secuencia->carrera_id !== $request->user()->carreraDirigida()->value('id')) {
                abort(403, 'No diriges la carrera de esta secuencia.');
            }
        });
    }

    private function cambiarEstado(
        Request $request,
        Secuencia $secuencia,
        string $estadoEsperado,
        string $estadoNuevo,
        ?\Closure $validacionExtra = null,
    ) {
        try {
            if ($secuencia->estado !== $estadoEsperado) {
                return response()->json(['message' => "La secuencia ya no está en estado '{$estadoEsperado}'."], 422);
            }

            if ($validacionExtra) {
                $validacionExtra();
            }

            $comentario = $request->input('comentario');

            $secuencia->cambiarEstado($estadoNuevo, $request->user(), $comentario);

            if ($estadoNuevo === 'en_revision') {
                $secuencia->update(['fecha_solicitud_revision' => now()]);
            }
            if ($estadoNuevo === 'validada') {
                $secuencia->update(['fecha_validacion' => now()]);
            }

            return response()->json($secuencia->fresh(['asignatura', 'especialidad', 'carrera']));
        } catch (ValidationException $e) {
            throw $e;
        } catch (Throwable $e) {
            Log::error('SecuenciaController@cambiarEstado: error al cambiar de estado', [
                'secuencia_id' => $secuencia->id,
                'estado_nuevo' => $estadoNuevo,
                'mensaje' => $e->getMessage(),
                'linea' => $e->getLine(),
                'archivo' => $e->getFile(),
            ]);

            return response()->json(['message' => 'No se pudo cambiar el estado de la secuencia.'], 500);
        }
    }
}
