<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Auth\Notifications\ResetPassword;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Relation::morphMap([
            'secuencia_unidad'         => \App\Models\SecuenciaUnidad::class,
            'secuencia_unidad_tema'    => \App\Models\SecuenciaUnidadTema::class,
            'secuencia_unidad_evaluacion' => \App\Models\SecuenciaUnidadEvaluacion::class,
            'secuencia_unidad_evidencia'  => \App\Models\SecuenciaUnidadEvidencia::class,
            'secuencia_unidad_fase'    => \App\Models\SecuenciaUnidadFase::class,
            'secuencia_fase_actividad' => \App\Models\SecuenciaFaseActividad::class,
            'secuencia_referencia'     => \App\Models\SecuenciaReferencia::class,
        ]);

        ResetPassword::createUrlUsing(function ($user, string $token) {
            $frontendUrl = rtrim(config('app.frontend_url'), '/');

            return "{$frontendUrl}/restablecer-password?token={$token}&email=" . urlencode($user->email);
        });
    }
}
