<?php

namespace App\Http\Controllers\Api\Carreras;

use App\Http\Controllers\Controller;
use App\Models\Carrera;
use App\Models\Especialidad;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Throwable;

class EspecialidadController extends Controller
{
    /**
     * GET /api/admin/especialidades?q=&carrera_id=&activo=&page=
     */
    public function index(Request $request)
    {
        try {
            $especialidades = Especialidad::query()
                ->with('carrera')
                ->when($request->filled('q'), function ($query) use ($request) {
                    $q = $request->q;
                    $query->where(fn($w) => $w->where('nombre', 'like', "%{$q}%")
                        ->orWhere('clave', 'like', "%{$q}%"));
                })
                ->when($request->filled('carrera_id'), fn($query) => $query->where('carrera_id', $request->carrera_id))
                ->when($request->filled('activo'), fn($query) => $query->where('activo', $request->boolean('activo')))
                ->orderBy('nombre')
                ->paginate(10);

            return response()->json($especialidades);
        } catch (Throwable $e) {
            Log::error('EspecialidadController@index: error al listar especialidades', [
                'mensaje' => $e->getMessage(),
                'linea' => $e->getLine(),
                'archivo' => $e->getFile(),
            ]);

            return response()->json(['message' => 'No se pudieron cargar las especialidades.'], 500);
        }
    }

    /**
     * GET /api/admin/especialidades/{especialidad}
     */
    public function show(string $id)
    {
        try {
            $especialidad = Especialidad::with(['carrera', 'asignaturas'])->findOrFail($id);

            return response()->json($especialidad);
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => 'La especialidad no existe.'], 404);
        } catch (Throwable $e) {
            Log::error('EspecialidadController@show: error al obtener el detalle de la especialidad', [
                'especialidad_id' => $id,
                'mensaje' => $e->getMessage(),
                'linea' => $e->getLine(),
                'archivo' => $e->getFile(),
            ]);

            return response()->json(['message' => 'No se pudo cargar el detalle de la especialidad.'], 500);
        }
    }

    /**
     * GET /api/admin/especialidades/carreras-disponibles
     */
    public function carrerasDisponibles()
    {
        try {
            return response()->json(
                Carrera::where('activo', true)->orderBy('nombre')->get(['id', 'nombre'])
            );
        } catch (Throwable $e) {
            Log::error('EspecialidadController@carrerasDisponibles: error al listar carreras disponibles', [
                'mensaje' => $e->getMessage(),
                'linea' => $e->getLine(),
                'archivo' => $e->getFile(),
            ]);

            return response()->json(['message' => 'No se pudieron cargar las carreras disponibles.'], 500);
        }
    }

    /**
     * POST /api/admin/especialidades
     */
    public function store(Request $request)
    {
        try {
            $data = $this->validarDatos($request);

            $especialidad = Especialidad::create($data);

            return response()->json($especialidad->load('carrera'), 201);
        } catch (ValidationException $e) {
            throw $e;
        } catch (Throwable $e) {
            Log::error('EspecialidadController@store: error al crear la especialidad', [
                'datos' => $request->all(),
                'mensaje' => $e->getMessage(),
                'linea' => $e->getLine(),
                'archivo' => $e->getFile(),
            ]);

            return response()->json(['message' => 'No se pudo crear la especialidad.'], 500);
        }
    }

    /**
     * PUT /api/admin/especialidades/{especialidad}
     */
    public function update(Request $request, Especialidad $especialidad)
    {
        try {
            $data = $this->validarDatos($request, $especialidad->id);

            $especialidad->update($data);

            return response()->json($especialidad->fresh('carrera'));
        } catch (ValidationException $e) {
            throw $e;
        } catch (Throwable $e) {
            Log::error('EspecialidadController@update: error al actualizar la especialidad', [
                'especialidad_id' => $especialidad->id,
                'datos' => $request->all(),
                'mensaje' => $e->getMessage(),
                'linea' => $e->getLine(),
                'archivo' => $e->getFile(),
            ]);

            return response()->json(['message' => 'No se pudo actualizar la especialidad.'], 500);
        }
    }

    /**
     * PATCH /api/admin/especialidades/{especialidad}/toggle-activo
     */
    public function toggleActivo(Especialidad $especialidad)
    {
        try {
            $especialidad->update(['activo' => ! $especialidad->activo]);

            return response()->json($especialidad->fresh('carrera'));
        } catch (Throwable $e) {
            Log::error('EspecialidadController@toggleActivo: error al cambiar el estado de la especialidad', [
                'especialidad_id' => $especialidad->id,
                'mensaje' => $e->getMessage(),
                'linea' => $e->getLine(),
                'archivo' => $e->getFile(),
            ]);

            return response()->json(['message' => 'No se pudo cambiar el estado de la especialidad.'], 500);
        }
    }

    private function validarDatos(Request $request, ?int $ignorarId = null): array
    {
        return $request->validate([
            'nombre' => ['required', 'string', 'max:150'],
            'clave' => ['required', 'string', 'max:20', Rule::unique('especialidades', 'clave')->ignore($ignorarId)],
            'carrera_id' => ['required', 'exists:carreras,id'],
        ]);
    }
}