<?php

namespace App\Services;

use Carbon\Carbon;

class PeriodoAcademicoService
{
    private const CUATRIMESTRES = ['Enero - Abril', 'Mayo - Agosto', 'Septiembre - Diciembre'];

    /**
     * Réplica exacta del cálculo que usa el frontend (NuevaSecuenciaModal.vue)
     * para construir el string de periodo: "Mayo - Agosto 2026", etc.
     */
    public function actual(?Carbon $fecha = null): string
    {
        $fecha = $fecha ?? now();
        $indice = intdiv($fecha->month - 1, 4); // meses 1-4 → 0, 5-8 → 1, 9-12 → 2

        return self::CUATRIMESTRES[$indice] . ' ' . $fecha->year;
    }
}
