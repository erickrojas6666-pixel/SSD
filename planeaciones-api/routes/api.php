<?php

use App\Http\Controllers\Api\Asignatura\AsignaturaController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\Carreras\CarreraController;
use App\Http\Controllers\Api\Carreras\EspecialidadController;
use App\Http\Controllers\Api\Secuencias\RevisionController;
use App\Http\Controllers\Api\Secuencias\SecuenciaCaratulaController;
use App\Http\Controllers\Api\Secuencias\SecuenciaController;
use App\Http\Controllers\Api\Secuencias\PlaneacionDocumentoController;
use App\Http\Controllers\Api\Secuencias\SecuenciaFaseActividadController;
use App\Http\Controllers\Api\Secuencias\SecuenciaReferenciaController;
use App\Http\Controllers\Api\Secuencias\SecuenciaUnidadController;
use App\Http\Controllers\Api\Secuencias\SecuenciaUnidadEvaluacionController;
use App\Http\Controllers\Api\Secuencias\SecuenciaUnidadEvidenciaController;
use App\Http\Controllers\Api\Secuencias\SecuenciaUnidadTemaController;
use App\Http\Controllers\Api\Secuencias\ValidacionDocumentoController;
use App\Http\Controllers\Api\TwoFactorController;
use App\Http\Controllers\Api\Usuario\ConfirmacionCuentaController;
use App\Http\Controllers\Api\Usuario\UserController;
use App\Http\Controllers\Api\DeviceTokenController;
use Illuminate\Support\Facades\Route;

// ── Rutas públicas ──
Route::post('/login', [AuthController::class, 'login']);
Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
Route::post('/reset-password', [AuthController::class, 'resetPassword']);

// Segundo paso del login cuando el usuario tiene 2FA activo (todavía sin token)
Route::post('/2fa/verify', [TwoFactorController::class, 'verify']);
Route::post('/2fa/resend', [TwoFactorController::class, 'resend']);

// Confirmación de cuenta (enlace enviado por correo al crear el usuario)
Route::post('/confirmar-cuenta', [ConfirmacionCuentaController::class, 'confirmar']);

// ── Rutas protegidas (requieren token de Sanctum) ──
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);

    Route::post('/dispositivo/fcm-token', [DeviceTokenController::class, 'store']);
    Route::delete('/dispositivo/fcm-token', [DeviceTokenController::class, 'destroy']);

    // Configuración de 2FA por el propio usuario
    Route::post('/2fa/enable', [TwoFactorController::class, 'enable']);
    Route::post('/2fa/confirm', [TwoFactorController::class, 'confirm']);
    Route::post('/2fa/disable', [TwoFactorController::class, 'disable']);

    // ── Módulo de administración (solo rol Administrador) ──
    Route::middleware('role:Administrador')->prefix('admin')->group(function () {
        Route::get('/carreras/directores-disponibles', [CarreraController::class, 'directoresDisponibles']);
        Route::apiResource('carreras', CarreraController::class)->except(['destroy']);
        Route::patch('/carreras/{carrera}/toggle-activo', [CarreraController::class, 'toggleActivo']);

        Route::get('/especialidades/carreras-disponibles', [EspecialidadController::class, 'carrerasDisponibles']);
        Route::apiResource('especialidades', EspecialidadController::class)->except(['destroy']);
        Route::patch('/especialidades/{especialidad}/toggle-activo', [EspecialidadController::class, 'toggleActivo']);

        Route::get('/asignaturas/catalogos', [AsignaturaController::class, 'catalogos']);
        Route::post('/asignaturas/masivo/verificar', [AsignaturaController::class, 'verificarDuplicadoMasivo']);
        Route::post('/asignaturas/masivo', [AsignaturaController::class, 'storeMasivo']);
        Route::patch('/asignaturas/{asignatura}/vincular-especialidades', [AsignaturaController::class, 'vincularEspecialidades']);
        Route::apiResource('asignaturas', AsignaturaController::class)->except(['destroy']);
        Route::patch('/asignaturas/{asignatura}/toggle-activo', [AsignaturaController::class, 'toggleActivo']);

        Route::get('/usuarios/catalogos', [UserController::class, 'catalogos']);
        Route::post('/usuarios/{usuario}/reenviar-credenciales', [UserController::class, 'reenviarCredenciales']);
        Route::apiResource('usuarios', UserController::class)->except(['destroy']);
        Route::patch('/usuarios/{usuario}/toggle-activo', [UserController::class, 'toggleActivo']);
    });

    // ── Secuencias didácticas ──────────────────────────────

    // Compartidas (la autorización fina vive dentro del controlador)
    Route::get('/secuencias/catalogos', [SecuenciaController::class, 'catalogos']);
    Route::get('/secuencias/{secuencia}', [SecuenciaController::class, 'show']);
    Route::get('/secuencias/{secuencia}/completitud', [SecuenciaController::class, 'completitud']);
    Route::get('/secuencias/{secuencia}/documento-planeacion', [PlaneacionDocumentoController::class, 'descargar']);

    // Docente
    Route::middleware('role:Docente')->prefix('docente')->group(function () {
        Route::get('/secuencias', [SecuenciaController::class, 'misSecuencias']);
        Route::post('/secuencias', [SecuenciaController::class, 'store']);
        Route::delete('/secuencias/{secuencia}', [SecuenciaController::class, 'destroy']);
        Route::post('/secuencias/{secuencia}/duplicar', [SecuenciaController::class, 'duplicar']);
        Route::post('/secuencias/{secuencia}/enviar-revision', [SecuenciaController::class, 'enviarRevision']);
        Route::post('/secuencias/{secuencia}/cancelar-envio', [SecuenciaController::class, 'cancelarEnvio']);

        Route::patch('/secuencias/{secuencia}/caratula', [SecuenciaCaratulaController::class, 'update']);
        Route::patch('/secuencias/{secuencia}/grupos-autores', [SecuenciaCaratulaController::class, 'actualizarGruposAutores']);

        Route::patch('/unidades/{unidad}', [SecuenciaUnidadController::class, 'update']);

        Route::post('/unidades/{unidad}/temas', [SecuenciaUnidadTemaController::class, 'store']);
        Route::patch('/temas/{tema}', [SecuenciaUnidadTemaController::class, 'update']);
        Route::delete('/temas/{tema}', [SecuenciaUnidadTemaController::class, 'destroy']);

        Route::patch('/unidades/{unidad}/evaluacion', [SecuenciaUnidadEvaluacionController::class, 'updateOrCreate']);
        Route::patch('/evaluaciones/{evaluacion}', [SecuenciaUnidadEvaluacionController::class, 'update']);

        Route::post('/unidades/{unidad}/evidencias', [SecuenciaUnidadEvidenciaController::class, 'store']);
        Route::patch('/evidencias/{evidencia}', [SecuenciaUnidadEvidenciaController::class, 'update']);
        Route::delete('/evidencias/{evidencia}', [SecuenciaUnidadEvidenciaController::class, 'destroy']);

        Route::post('/unidades/{unidad}/fases/{tipo}/actividades', [SecuenciaFaseActividadController::class, 'store']);
        Route::patch('/fase-actividades/{actividad}', [SecuenciaFaseActividadController::class, 'update']);
        Route::delete('/fase-actividades/{actividad}', [SecuenciaFaseActividadController::class, 'destroy']);

        Route::post('/secuencias/{secuencia}/referencias', [SecuenciaReferenciaController::class, 'store']);
        Route::patch('/referencias/{referencia}', [SecuenciaReferenciaController::class, 'update']);
        Route::delete('/referencias/{referencia}', [SecuenciaReferenciaController::class, 'destroy']);
    });

    // Revisor
    Route::middleware('role:Revisor')->prefix('revisor')->group(function () {
        Route::get('/secuencias', [SecuenciaController::class, 'colaRevisor']);
        Route::post('/secuencias/{secuencia}/enviar-validacion', [SecuenciaController::class, 'enviarValidacion']);
        Route::post('/secuencias/{secuencia}/rechazar', [SecuenciaController::class, 'rechazarRevision']);
        Route::patch('/validacion/{tipo}/{id}', [RevisionController::class, 'actualizar']);
    });

    // Director
    Route::middleware('role:Director')->prefix('director')->group(function () {
        Route::get('/secuencias', [SecuenciaController::class, 'colaDirector']);
        Route::get('/secuencias/{secuencia}/resumen', [SecuenciaController::class, 'resumen']);
        Route::get('/secuencias/{secuencia}/formato-validacion', [ValidacionDocumentoController::class, 'descargar']);
        Route::post('/secuencias/{secuencia}/validar', [ValidacionDocumentoController::class, 'subir']);
        Route::post('/secuencias/{secuencia}/rechazar', [SecuenciaController::class, 'rechazar']);
    });
});