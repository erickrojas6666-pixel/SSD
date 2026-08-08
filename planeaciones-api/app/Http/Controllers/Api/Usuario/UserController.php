<?php

namespace App\Http\Controllers\Api\Usuario;

use App\Http\Controllers\Controller;
use App\Models\Asignatura;
use App\Models\Carrera;
use App\Models\ConfirmacionCuenta;
use App\Models\Role;
use App\Models\User;
use App\Notifications\NuevaCuentaNotification;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Throwable;

class UserController extends Controller
{
    /**
     * GET /api/admin/usuarios?q=&rol=&activo=&page=
     */
    public function index(Request $request)
    {
        try {
            $usuarios = User::query()
                ->with(['roles', 'carreraDirigida', 'asignaturas'])
                ->when($request->filled('q'), function ($query) use ($request) {
                    $q = $request->q;
                    $query->where(fn ($w) => $w->where('nombre', 'like', "%{$q}%")
                        ->orWhere('apellido_paterno', 'like', "%{$q}%")
                        ->orWhere('apellido_materno', 'like', "%{$q}%")
                        ->orWhere('email', 'like', "%{$q}%"));
                })
                ->when($request->filled('rol'), function ($query) use ($request) {
                    $query->whereHas('roles', fn ($w) => $w->where('nombre', $request->rol));
                })
                ->when($request->filled('activo'), fn ($query) => $query->where('activo', $request->boolean('activo')))
                ->orderBy('nombre')
                ->paginate(10);

            return response()->json($usuarios);
        } catch (Throwable $e) {
            Log::error('UserController@index: error al listar usuarios', [
                'mensaje' => $e->getMessage(), 'linea' => $e->getLine(), 'archivo' => $e->getFile(),
            ]);

            return response()->json(['message' => 'No se pudieron cargar los usuarios.'], 500);
        }
    }

    /**
     * GET /api/admin/usuarios/catalogos
     */
    public function catalogos()
    {
        try {
            return response()->json([
                'roles' => Role::orderBy('nombre')->get(['id', 'nombre']),
                'asignaturas' => Asignatura::with('cuatrimestre')
                    ->where('activo', true)
                    ->orderBy('nombre')
                    ->get(['id', 'nombre', 'clave', 'cuatrimestre_id']),
                'carreras' => Carrera::where('activo', true)->orderBy('nombre')->get(['id', 'nombre', 'clave', 'director_id']),
            ]);
        } catch (Throwable $e) {
            Log::error('UserController@catalogos: error al cargar catálogos', [
                'mensaje' => $e->getMessage(), 'linea' => $e->getLine(), 'archivo' => $e->getFile(),
            ]);

            return response()->json(['message' => 'No se pudieron cargar los catálogos.'], 500);
        }
    }

    /**
     * GET /api/admin/usuarios/{usuario}
     */
    public function show(string $id)
    {
        try {
            $usuario = User::with(['roles', 'carreraDirigida', 'asignaturas'])->findOrFail($id);

            return response()->json($usuario);
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => 'El usuario no existe.'], 404);
        } catch (Throwable $e) {
            Log::error('UserController@show: error al obtener el usuario', [
                'usuario_id' => $id, 'mensaje' => $e->getMessage(), 'linea' => $e->getLine(), 'archivo' => $e->getFile(),
            ]);

            return response()->json(['message' => 'No se pudo cargar el usuario.'], 500);
        }
    }

    /**
     * POST /api/admin/usuarios
     */
    public function store(Request $request)
    {
        try {
            $data = $this->validarDatos($request);
            $nombresRoles = Role::whereIn('id', $data['rol_ids'])->pluck('nombre');

            $this->validarReglasDeRol($nombresRoles, $data, null);

            $passwordTemporal = Str::password(12);

            $usuario = DB::transaction(function () use ($data, $nombresRoles, $passwordTemporal) {
                $nuevo = User::create([
                    'nombre' => $data['nombre'],
                    'apellido_paterno' => $data['apellido_paterno'],
                    'apellido_materno' => $data['apellido_materno'] ?? null,
                    'email' => $data['email'],
                    'password' => Hash::make($passwordTemporal),
                ]);

                $nuevo->roles()->sync($data['rol_ids']);

                if ($nombresRoles->contains('Docente')) {
                    $nuevo->asignaturas()->sync($data['asignatura_ids'] ?? []);
                }

                if ($nombresRoles->contains('Director') && ! empty($data['carrera_id'])) {
                    Carrera::whereKey($data['carrera_id'])->update(['director_id' => $nuevo->id]);
                }

                return $nuevo;
            });

            $this->enviarCredenciales($usuario, $passwordTemporal);

            return response()->json(
                $usuario->fresh(['roles', 'carreraDirigida', 'asignaturas']),
                201
            );
        } catch (ValidationException $e) {
            throw $e;
        } catch (Throwable $e) {
            Log::error('UserController@store: error al crear el usuario', [
                'datos' => $request->except('password'),
                'mensaje' => $e->getMessage(), 'linea' => $e->getLine(), 'archivo' => $e->getFile(),
            ]);

            return response()->json(['message' => 'No se pudo crear el usuario.'], 500);
        }
    }

    /**
     * PUT /api/admin/usuarios/{usuario}
     */
    public function update(Request $request, User $usuario)
    {
        try {
            $data = $this->validarDatos($request, $usuario->id);
            $nombresRoles = Role::whereIn('id', $data['rol_ids'])->pluck('nombre');

            $this->validarReglasDeRol($nombresRoles, $data, $usuario);

            DB::transaction(function () use ($usuario, $data, $nombresRoles) {
                $usuario->update([
                    'nombre' => $data['nombre'],
                    'apellido_paterno' => $data['apellido_paterno'],
                    'apellido_materno' => $data['apellido_materno'] ?? null,
                    'email' => $data['email'],
                ]);

                $usuario->roles()->sync($data['rol_ids']);

                // Docente: sincroniza materias (si perdió el rol, se queda sin ninguna)
                $usuario->asignaturas()->sync(
                    $nombresRoles->contains('Docente') ? ($data['asignatura_ids'] ?? []) : []
                );

                // Director: libera la carrera anterior si ya no aplica o cambió
                $carreraActual = $usuario->carreraDirigida()->first();
                $nuevaCarreraId = $nombresRoles->contains('Director') ? ($data['carrera_id'] ?? null) : null;

                if ($carreraActual && $carreraActual->id !== $nuevaCarreraId) {
                    Carrera::whereKey($carreraActual->id)->update(['director_id' => null]);
                }

                if ($nuevaCarreraId) {
                    Carrera::whereKey($nuevaCarreraId)->update(['director_id' => $usuario->id]);
                }
            });

            return response()->json($usuario->fresh(['roles', 'carreraDirigida', 'asignaturas']));
        } catch (ValidationException $e) {
            throw $e;
        } catch (Throwable $e) {
            Log::error('UserController@update: error al actualizar el usuario', [
                'usuario_id' => $usuario->id,
                'datos' => $request->except('password'),
                'mensaje' => $e->getMessage(), 'linea' => $e->getLine(), 'archivo' => $e->getFile(),
            ]);

            return response()->json(['message' => 'No se pudo actualizar el usuario.'], 500);
        }
    }

    /**
     * PATCH /api/admin/usuarios/{usuario}/toggle-activo
     */
    public function toggleActivo(User $usuario)
    {
        try {
            $usuario->update(['activo' => ! $usuario->activo]);

            return response()->json($usuario->fresh(['roles', 'carreraDirigida', 'asignaturas']));
        } catch (Throwable $e) {
            Log::error('UserController@toggleActivo: error al cambiar el estado del usuario', [
                'usuario_id' => $usuario->id,
                'mensaje' => $e->getMessage(), 'linea' => $e->getLine(), 'archivo' => $e->getFile(),
            ]);

            return response()->json(['message' => 'No se pudo cambiar el estado del usuario.'], 500);
        }
    }

    /**
     * POST /api/admin/usuarios/{usuario}/reenviar-credenciales
     * Genera una nueva contraseña temporal y la reenvía por correo
     * (útil si el usuario perdió el correo original o olvidó su contraseña).
     */
    public function reenviarCredenciales(User $usuario)
    {
        try {
            $passwordTemporal = Str::password(12);

            $usuario->update(['password' => Hash::make($passwordTemporal)]);
            $usuario->tokens()->delete(); // cierra sesiones activas por seguridad

            $this->enviarCredenciales($usuario, $passwordTemporal);

            return response()->json(['message' => 'Se generó una nueva contraseña y se envió por correo.']);
        } catch (Throwable $e) {
            Log::error('UserController@reenviarCredenciales: error al reenviar credenciales', [
                'usuario_id' => $usuario->id,
                'mensaje' => $e->getMessage(), 'linea' => $e->getLine(), 'archivo' => $e->getFile(),
            ]);

            return response()->json(['message' => 'No se pudieron reenviar las credenciales.'], 500);
        }
    }

    // ── helpers ──────────────────────────────────────────────

    private function validarDatos(Request $request, ?int $ignorarId = null): array
    {
        return $request->validate([
            'nombre' => ['required', 'string', 'max:100'],
            'apellido_paterno' => ['required', 'string', 'max:100'],
            'apellido_materno' => ['nullable', 'string', 'max:100'],
            'email' => ['required', 'email', Rule::unique('users', 'email')->ignore($ignorarId)],
            'rol_ids' => ['required', 'array', 'min:1'],
            'rol_ids.*' => ['exists:roles,id'],
            'asignatura_ids' => ['nullable', 'array'],
            'asignatura_ids.*' => ['exists:asignaturas,id'],
            'carrera_id' => ['nullable', 'exists:carreras,id'],
        ]);
    }

    /**
     * Reglas de negocio que dependen de los roles seleccionados:
     * - Director solo puede dirigir una carrera, y una carrera solo tiene un director.
     */
    private function validarReglasDeRol($nombresRoles, array $data, ?User $usuarioActual): void
    {
        if (! $nombresRoles->contains('Director') || empty($data['carrera_id'])) {
            return;
        }

        $carrera = Carrera::find($data['carrera_id']);

        $yaTieneOtroDirector = $carrera
            && $carrera->director_id
            && $carrera->director_id !== ($usuarioActual?->id);

        if ($yaTieneOtroDirector) {
            throw ValidationException::withMessages([
                'carrera_id' => ['Esa carrera ya tiene un director asignado.'],
            ]);
        }
    }

    private function enviarCredenciales(User $usuario, string $passwordTemporal): void
    {
        $confirmacion = ConfirmacionCuenta::updateOrCreate(
            ['user_id' => $usuario->id],
            ['token' => Str::random(64), 'expires_at' => now()->addDays(7)]
        );

        $link = $usuario->email_verified_at
            ? null
            : rtrim(config('app.frontend_url'), '/') . '/confirmar-cuenta?token=' . $confirmacion->token;

        $usuario->notify(new NuevaCuentaNotification($passwordTemporal, $link));
    }
}
