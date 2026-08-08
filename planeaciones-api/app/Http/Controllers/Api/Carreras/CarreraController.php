<?php

namespace App\Http\Controllers\Api\Carreras;

use App\Http\Controllers\Controller;
use App\Models\Carrera;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Throwable;

class CarreraController extends Controller
{
    /**
     * GET /api/admin/carreras?q=&activo=&page=
     */
    public function index(Request $request)
    {
        try {
            $carreras = Carrera::query()
                ->with('director')
                ->withCount('especialidades')
                ->when($request->filled('q'), function ($query) use ($request) {
                    $q = $request->q;
                    $query->where(fn($w) => $w->where('nombre', 'like', "%{$q}%")
                        ->orWhere('clave', 'like', "%{$q}%"));
                })
                ->when($request->filled('activo'), fn($query) => $query->where('activo', $request->boolean('activo')))
                ->orderBy('nombre')
                ->paginate(10);

            return response()->json($carreras);
        } catch (Throwable $e) {
            Log::error('CarreraController@index: error al listar carreras', [
                'mensaje' => $e->getMessage(),
                'linea' => $e->getLine(),
                'archivo' => $e->getFile(),
            ]);

            return response()->json(['message' => 'No se pudieron cargar las carreras.'], 500);
        }
    }

    /**
     * GET /api/admin/carreras/{carrera}
     * Detalle completo: carrera + especialidades + asignaturas de cada una (para la vista de árbol)
     */
    public function show(string $id)
    {
        try {
            $carrera = Carrera::with([
                'director',
                'especialidades' => fn($q) => $q->orderBy('nombre'),
                'especialidades.asignaturas' => fn($q) => $q->orderBy('nombre'),
            ])->findOrFail($id);

            return response()->json($carrera);
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => 'La carrera no existe.'], 404);
        } catch (Throwable $e) {
            Log::error('CarreraController@show: error al obtener el detalle de la carrera', [
                'carrera_id' => $id,
                'mensaje' => $e->getMessage(),
                'linea' => $e->getLine(),
                'archivo' => $e->getFile(),
            ]);

            return response()->json(['message' => 'No se pudo cargar el detalle de la carrera.'], 500);
        }
    }

    /**
     * GET /api/admin/carreras/directores-disponibles?except_carrera_id=
     */
    public function directoresDisponibles(Request $request)
    {
        try {
            $exceptId = $request->integer('except_carrera_id');

            $directores = User::whereHas('roles', fn($q) => $q->where('nombre', 'Director'))
                ->where('activo', true)
                ->whereDoesntHave('carreraDirigida', function ($q) use ($exceptId) {
                    if ($exceptId) {
                        $q->where('id', '!=', $exceptId);
                    }
                })
                ->orderBy('nombre')
                ->get(['id', 'nombre', 'apellido_paterno', 'apellido_materno']);

            return response()->json($directores);
        } catch (Throwable $e) {
            Log::error('CarreraController@directoresDisponibles: error al listar directores disponibles', [
                'mensaje' => $e->getMessage(),
                'linea' => $e->getLine(),
                'archivo' => $e->getFile(),
            ]);

            return response()->json(['message' => 'No se pudieron cargar los directores disponibles.'], 500);
        }
    }

    /**
     * POST /api/admin/carreras
     */
    public function store(Request $request)
    {
        try {
            $data = $this->validarDatos($request);

            $carrera = Carrera::create($data);

            return response()->json($carrera->load('director'), 201);
        } catch (ValidationException $e) {
            throw $e; // deja que Laravel regrese el 422 con los mensajes de validación
        } catch (Throwable $e) {
            Log::error('CarreraController@store: error al crear la carrera', [
                'datos' => $request->all(),
                'mensaje' => $e->getMessage(),
                'linea' => $e->getLine(),
                'archivo' => $e->getFile(),
            ]);

            return response()->json(['message' => 'No se pudo crear la carrera.'], 500);
        }
    }

    /**
     * PUT /api/admin/carreras/{carrera}
     */
    public function update(Request $request, Carrera $carrera)
    {
        try {
            $data = $this->validarDatos($request, $carrera->id);

            $carrera->update($data);

            return response()->json($carrera->fresh('director'));
        } catch (ValidationException $e) {
            throw $e;
        } catch (Throwable $e) {
            Log::error('CarreraController@update: error al actualizar la carrera', [
                'carrera_id' => $carrera->id,
                'datos' => $request->all(),
                'mensaje' => $e->getMessage(),
                'linea' => $e->getLine(),
                'archivo' => $e->getFile(),
            ]);

            return response()->json(['message' => 'No se pudo actualizar la carrera.'], 500);
        }
    }

    /**
     * PATCH /api/admin/carreras/{carrera}/toggle-activo
     */
    public function toggleActivo(Carrera $carrera)
    {
        try {
            $carrera->update(['activo' => ! $carrera->activo]);

            return response()->json($carrera->fresh('director'));
        } catch (Throwable $e) {
            Log::error('CarreraController@toggleActivo: error al cambiar el estado de la carrera', [
                'carrera_id' => $carrera->id,
                'mensaje' => $e->getMessage(),
                'linea' => $e->getLine(),
                'archivo' => $e->getFile(),
            ]);

            return response()->json(['message' => 'No se pudo cambiar el estado de la carrera.'], 500);
        }
    }

    private function validarDatos(Request $request, ?int $ignorarId = null): array
    {
        return $request->validate([
            'nombre' => ['required', 'string', 'max:150'],
            'clave' => ['required', 'string', 'max:20', Rule::unique('carreras', 'clave')->ignore($ignorarId)],
            'director_id' => [
                'nullable',
                'exists:users,id',
                Rule::unique('carreras', 'director_id')->ignore($ignorarId),
                function ($attribute, $value, $fail) {
                    if ($value && ! User::find($value)?->tieneRol('Director')) {
                        $fail('El usuario seleccionado no tiene el rol de Director.');
                    }
                },
            ],
        ]);
    }
}
