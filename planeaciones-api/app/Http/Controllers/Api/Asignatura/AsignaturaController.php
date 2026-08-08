<?php

namespace App\Http\Controllers\Api\Asignatura;

use App\Http\Controllers\Controller;
use App\Models\Asignatura;
use App\Models\Cuatrimestre;
use App\Models\Especialidad;
use App\Services\EstructuraAcademicaService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Throwable;

class AsignaturaController extends Controller
{
    public function __construct(private EstructuraAcademicaService $estructuraAcademica)
    {
    }

    /**
     * GET /api/admin/asignaturas?q=&cuatrimestre_id=&especialidad_id=&activo=&page=
     */
    public function index(Request $request)
    {
        try {
            $asignaturas = Asignatura::query()
                ->with(['cuatrimestre', 'especialidades'])
                ->when($request->filled('q'), function ($query) use ($request) {
                    $q = $request->q;
                    $query->where(fn ($w) => $w->where('nombre', 'like', "%{$q}%")
                        ->orWhere('clave', 'like', "%{$q}%"));
                })
                ->when($request->filled('cuatrimestre_id'), fn ($q) => $q->where('cuatrimestre_id', $request->cuatrimestre_id))
                ->when($request->filled('especialidad_id'), function ($q) use ($request) {
                    $q->whereHas('especialidades', fn ($w) => $w->where('especialidades.id', $request->especialidad_id));
                })
                ->when($request->filled('activo'), fn ($q) => $q->where('activo', $request->boolean('activo')))
                ->orderBy('nombre')
                ->paginate(10);

            return response()->json($asignaturas);
        } catch (Throwable $e) {
            Log::error('AsignaturaController@index: error al listar asignaturas', [
                'mensaje' => $e->getMessage(), 'linea' => $e->getLine(), 'archivo' => $e->getFile(),
            ]);

            return response()->json(['message' => 'No se pudieron cargar las asignaturas.'], 500);
        }
    }

    /**
     * GET /api/admin/asignaturas/catalogos
     * Cuatrimestres y especialidades activas para los formularios (una sola petición)
     */
    public function catalogos()
    {
        try {
            return response()->json([
                'cuatrimestres' => Cuatrimestre::where('activo', true)->orderBy('numero')->get(['id', 'numero', 'nombre']),
                'especialidades' => Especialidad::with('carrera')
                    ->where('activo', true)
                    ->orderBy('nombre')
                    ->get(['id', 'nombre', 'clave', 'carrera_id']),
            ]);
        } catch (Throwable $e) {
            Log::error('AsignaturaController@catalogos: error al cargar catálogos', [
                'mensaje' => $e->getMessage(), 'linea' => $e->getLine(), 'archivo' => $e->getFile(),
            ]);

            return response()->json(['message' => 'No se pudieron cargar los catálogos.'], 500);
        }
    }

    /**
     * GET /api/admin/asignaturas/{asignatura}
     */
    public function show(string $id)
    {
        try {
            $asignatura = Asignatura::with(['cuatrimestre', 'especialidades.carrera'])->findOrFail($id);

            return response()->json($asignatura);
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => 'La asignatura no existe.'], 404);
        } catch (Throwable $e) {
            Log::error('AsignaturaController@show: error al obtener la asignatura', [
                'asignatura_id' => $id, 'mensaje' => $e->getMessage(), 'linea' => $e->getLine(), 'archivo' => $e->getFile(),
            ]);

            return response()->json(['message' => 'No se pudo cargar la asignatura.'], 500);
        }
    }

    /**
     * POST /api/admin/asignaturas  (multipart/form-data)
     * body: nombre, cuatrimestre_id, especialidad_ids[], plan_estudio? (file), forzar? (bool)
     */
    public function store(Request $request)
    {
        try {
            $data = $request->validate([
                'nombre' => ['required', 'string', 'max:150'],
                'cuatrimestre_id' => ['required', 'exists:cuatrimestres,id'],
                'especialidad_ids' => ['required', 'array', 'min:1'],
                'especialidad_ids.*' => ['exists:especialidades,id'],
                'plan_estudio' => ['nullable', 'file', 'mimes:pdf', 'max:10240'],
                'forzar' => ['nullable', 'boolean'],
            ]);

            $normalizado = Asignatura::normalizar($data['nombre']);
            $existente = Asignatura::with(['cuatrimestre', 'especialidades'])
                ->where('nombre_normalizado', $normalizado)
                ->first();

            if ($existente && ! $request->boolean('forzar')) {
                return response()->json([
                    'duplicado' => true,
                    'asignatura_existente' => $existente,
                ], 409);
            }

            $planEstudioUrl = null;
            if ($request->hasFile('plan_estudio')) {
                $resultado = $this->validarYGuardarPlanEstudio($request->file('plan_estudio'));
                if ($resultado['error']) {
                    return response()->json($resultado['respuesta'], 422);
                }
                $planEstudioUrl = $resultado['url'];
            }

            $asignatura = DB::transaction(function () use ($data, $planEstudioUrl) {
                $nueva = Asignatura::create([
                    'nombre' => $data['nombre'],
                    'cuatrimestre_id' => $data['cuatrimestre_id'],
                    'plan_estudio_url' => $planEstudioUrl,
                    'clave' => $this->generarClave($data['nombre']),
                ]);

                $nueva->especialidades()->attach($data['especialidad_ids']);

                return $nueva;
            });

            return response()->json($asignatura->load(['cuatrimestre', 'especialidades']), 201);
        } catch (ValidationException $e) {
            throw $e;
        } catch (Throwable $e) {
            Log::error('AsignaturaController@store: error al crear la asignatura', [
                'datos' => $request->except('plan_estudio'),
                'mensaje' => $e->getMessage(), 'linea' => $e->getLine(), 'archivo' => $e->getFile(),
            ]);

            return response()->json(['message' => 'No se pudo crear la asignatura.'], 500);
        }
    }

    /**
     * PUT /api/admin/asignaturas/{asignatura}  (enviar como POST + _method=PUT si lleva archivo)
     */
    public function update(Request $request, Asignatura $asignatura)
    {
        try {
            $data = $request->validate([
                'nombre' => ['required', 'string', 'max:150'],
                'cuatrimestre_id' => ['required', 'exists:cuatrimestres,id'],
                'especialidad_ids' => ['required', 'array', 'min:1'],
                'especialidad_ids.*' => ['exists:especialidades,id'],
                'plan_estudio' => ['nullable', 'file', 'mimes:pdf', 'max:10240'],
                'forzar' => ['nullable', 'boolean'],
            ]);

            $normalizado = Asignatura::normalizar($data['nombre']);
            $existente = Asignatura::where('nombre_normalizado', $normalizado)
                ->where('id', '!=', $asignatura->id)
                ->with(['cuatrimestre', 'especialidades'])
                ->first();

            if ($existente && ! $request->boolean('forzar')) {
                return response()->json([
                    'duplicado' => true,
                    'asignatura_existente' => $existente,
                ], 409);
            }

            $planEstudioUrl = $asignatura->plan_estudio_url;
            if ($request->hasFile('plan_estudio')) {
                $resultado = $this->validarYGuardarPlanEstudio($request->file('plan_estudio'));
                if ($resultado['error']) {
                    return response()->json($resultado['respuesta'], 422);
                }

                $this->eliminarArchivoAnterior($asignatura->plan_estudio_url);
                $planEstudioUrl = $resultado['url'];
            }

            DB::transaction(function () use ($asignatura, $data, $planEstudioUrl) {
                $asignatura->update([
                    'nombre' => $data['nombre'],
                    'cuatrimestre_id' => $data['cuatrimestre_id'],
                    'plan_estudio_url' => $planEstudioUrl,
                ]);

                $asignatura->especialidades()->sync($data['especialidad_ids']);
            });

            return response()->json($asignatura->fresh(['cuatrimestre', 'especialidades']));
        } catch (ValidationException $e) {
            throw $e;
        } catch (Throwable $e) {
            Log::error('AsignaturaController@update: error al actualizar la asignatura', [
                'asignatura_id' => $asignatura->id,
                'datos' => $request->except('plan_estudio'),
                'mensaje' => $e->getMessage(), 'linea' => $e->getLine(), 'archivo' => $e->getFile(),
            ]);

            return response()->json(['message' => 'No se pudo actualizar la asignatura.'], 500);
        }
    }

    /**
     * PATCH /api/admin/asignaturas/{asignatura}/vincular-especialidades
     * Se usa cuando el usuario elige "vincular a la existente" en vez de crear un duplicado
     */
    public function vincularEspecialidades(Request $request, Asignatura $asignatura)
    {
        try {
            $data = $request->validate([
                'especialidad_ids' => ['required', 'array', 'min:1'],
                'especialidad_ids.*' => ['exists:especialidades,id'],
            ]);

            $asignatura->especialidades()->syncWithoutDetaching($data['especialidad_ids']);

            return response()->json($asignatura->fresh(['cuatrimestre', 'especialidades']));
        } catch (ValidationException $e) {
            throw $e;
        } catch (Throwable $e) {
            Log::error('AsignaturaController@vincularEspecialidades: error al vincular especialidades', [
                'asignatura_id' => $asignatura->id,
                'mensaje' => $e->getMessage(), 'linea' => $e->getLine(), 'archivo' => $e->getFile(),
            ]);

            return response()->json(['message' => 'No se pudo vincular la asignatura.'], 500);
        }
    }

    /**
     * PATCH /api/admin/asignaturas/{asignatura}/toggle-activo
     */
    public function toggleActivo(Asignatura $asignatura)
    {
        try {
            $asignatura->update(['activo' => ! $asignatura->activo]);

            return response()->json($asignatura->fresh(['cuatrimestre', 'especialidades']));
        } catch (Throwable $e) {
            Log::error('AsignaturaController@toggleActivo: error al cambiar el estado', [
                'asignatura_id' => $asignatura->id,
                'mensaje' => $e->getMessage(), 'linea' => $e->getLine(), 'archivo' => $e->getFile(),
            ]);

            return response()->json(['message' => 'No se pudo cambiar el estado de la asignatura.'], 500);
        }
    }

    /**
     * POST /api/admin/asignaturas/masivo/verificar
     * body: { nombres: string[] }
     * Revisa cada nombre contra la base y regresa si ya existe una coincidencia
     */
    public function verificarDuplicadoMasivo(Request $request)
    {
        try {
            $data = $request->validate([
                'nombres' => ['required', 'array', 'min:1'],
                'nombres.*' => ['string'],
            ]);

            $resultado = collect($data['nombres'])
                ->map(function (string $nombre) {
                    $normalizado = Asignatura::normalizar($nombre);
                    $existente = Asignatura::with(['cuatrimestre'])
                        ->where('nombre_normalizado', $normalizado)
                        ->first();

                    return [
                        'nombre' => $nombre,
                        'existe' => (bool) $existente,
                        'asignatura_existente' => $existente,
                    ];
                })
                ->values();

            return response()->json($resultado);
        } catch (ValidationException $e) {
            throw $e;
        } catch (Throwable $e) {
            Log::error('AsignaturaController@verificarDuplicadoMasivo: error al verificar duplicados', [
                'mensaje' => $e->getMessage(), 'linea' => $e->getLine(), 'archivo' => $e->getFile(),
            ]);

            return response()->json(['message' => 'No se pudo verificar la lista de asignaturas.'], 500);
        }
    }

    /**
     * POST /api/admin/asignaturas/masivo
     * body: { cuatrimestre_id, especialidad_ids: [], items: [{ nombre, vincular_a_id: null|int }] }
     */
    public function storeMasivo(Request $request)
    {
        try {
            $data = $request->validate([
                'cuatrimestre_id' => ['required', 'exists:cuatrimestres,id'],
                'especialidad_ids' => ['required', 'array', 'min:1'],
                'especialidad_ids.*' => ['exists:especialidades,id'],
                'items' => ['required', 'array', 'min:1'],
                'items.*.nombre' => ['required', 'string', 'max:150'],
                'items.*.vincular_a_id' => ['nullable', 'exists:asignaturas,id'],
            ]);

            $creadas = 0;
            $vinculadas = 0;

            DB::transaction(function () use ($data, &$creadas, &$vinculadas) {
                foreach ($data['items'] as $item) {
                    if (! empty($item['vincular_a_id'])) {
                        $existente = Asignatura::findOrFail($item['vincular_a_id']);
                        $existente->especialidades()->syncWithoutDetaching($data['especialidad_ids']);
                        $vinculadas++;
                        continue;
                    }

                    $nueva = Asignatura::create([
                        'nombre' => $item['nombre'],
                        'cuatrimestre_id' => $data['cuatrimestre_id'],
                        'clave' => $this->generarClave($item['nombre']),
                    ]);

                    $nueva->especialidades()->attach($data['especialidad_ids']);
                    $creadas++;
                }
            });

            return response()->json([
                'message' => "Se crearon {$creadas} asignaturas nuevas y se vincularon {$vinculadas} a especialidades existentes.",
                'creadas' => $creadas,
                'vinculadas' => $vinculadas,
            ], 201);
        } catch (ValidationException $e) {
            throw $e;
        } catch (Throwable $e) {
            Log::error('AsignaturaController@storeMasivo: error en la carga masiva', [
                'datos' => $request->all(),
                'mensaje' => $e->getMessage(), 'linea' => $e->getLine(), 'archivo' => $e->getFile(),
            ]);

            return response()->json(['message' => 'No se pudo completar la carga masiva.'], 500);
        }
    }

    // ── helpers ──────────────────────────────────────────────

    private function validarYGuardarPlanEstudio($archivo): array
    {
        $validacion = $this->estructuraAcademica->validarPdfSecuencia($archivo);

        if (! $validacion['valido']) {
            return [
                'error' => true,
                'respuesta' => [
                    'message' => 'El PDF del plan de estudio no tiene el formato esperado.',
                    'errores' => $validacion['errores'],
                    'detalles' => $validacion['detalles'],
                ],
            ];
        }

        $ruta = $archivo->store('planes-estudio', 'public');

        return ['error' => false, 'url' => Storage::disk('public')->url($ruta)];
    }

    private function eliminarArchivoAnterior(?string $url): void
    {
        if (! $url) {
            return;
        }

        $ruta = str_replace(Storage::disk('public')->url(''), '', $url);

        if (Storage::disk('public')->exists($ruta)) {
            Storage::disk('public')->delete($ruta);
        }
    }

    /**
     * Genera una clave única a partir de las iniciales del nombre + un consecutivo.
     * Ej: "Fundamentos de Programación" -> "FP-001"
     */
    private function generarClave(string $nombre): string
    {
        $stopwords = ['DE', 'Y', 'EN', 'A', 'LA', 'EL', 'LOS', 'LAS', 'PARA', 'CON', 'SU', 'SUS', 'UN', 'UNA', 'O', 'DEL'];

        $palabras = array_filter(
            explode(' ', Asignatura::normalizar($nombre)),
            fn ($p) => $p !== '' && ! in_array($p, $stopwords, true)
        );

        $iniciales = '';
        foreach (array_values($palabras) as $palabra) {
            $iniciales .= mb_substr($palabra, 0, 1);
        }

        $iniciales = $iniciales === '' ? 'AS' : mb_substr($iniciales, 0, 6);

        $numero = 1;
        do {
            $clave = $iniciales . '-' . str_pad((string) $numero, 3, '0', STR_PAD_LEFT);
            $existe = Asignatura::withTrashed()->where('clave', $clave)->exists();
            $numero++;
        } while ($existe);

        return $clave;
    }
}
