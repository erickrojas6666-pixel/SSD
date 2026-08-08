<?php

namespace App\Services;

use App\Models\Asignatura;
use App\Models\Secuencia;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class SecuenciaService
{
    public function __construct(private EstructuraAcademicaService $estructuraAcademica) {}

    /**
     * Crea una nueva secuencia. Si la asignatura no tiene plan de estudio y se
     * sube uno, se valida y se guarda en la asignatura. Después, se usa el PDF
     * (el que ya tenía la asignatura o el recién subido) para prellenar el
     * máximo de información posible: carátula, unidades, temas, evaluación y
     * referencias.
     */
    public function crear(array $data, ?UploadedFile $planAsignatura, User $autorPrincipal): Secuencia
    {
        $asignatura = Asignatura::with('cuatrimestre')->findOrFail($data['asignatura_id']);

        if ($planAsignatura) {
            $validacion = $this->estructuraAcademica->validarPdfSecuencia($planAsignatura);
            if (! $validacion['valido']) {
                throw new \App\Exceptions\PlanEstudioInvalidoException($validacion['errores'], $validacion['detalles']);
            }

            $ruta = $planAsignatura->store('planes-estudio', 'public');
            $asignatura->update(['plan_estudio_url' => Storage::disk('public')->url($ruta)]);
        }

        return DB::transaction(function () use ($data, $asignatura, $autorPrincipal) {
            $secuencia = Secuencia::create([
                'asignatura_id' => $asignatura->id,
                'especialidad_id' => $data['especialidad_id'],
                'carrera_id' => $data['carrera_id'],
                'periodo' => $data['periodo'],
                'estado' => 'borrador',
            ]);

            // Autor principal + coautores elegidos
            $autores = array_unique(array_merge([$autorPrincipal->id], $data['coautor_ids'] ?? []));
            $secuencia->autores()->attach($autores);

            foreach ($data['grupos'] ?? [] as $grupo) {
                $secuencia->grupos()->create(['grupo' => $grupo]);
            }

            $secuencia->historialEstados()->create([
                'estado_anterior' => null,
                'estado_nuevo' => 'borrador',
                'user_id' => $autorPrincipal->id,
                'comentario' => 'Secuencia creada.',
            ]);

            $this->prellenarDesdeAsignatura($secuencia, $asignatura);

            return $secuencia->fresh([
                'asignatura',
                'especialidad',
                'carrera',
                'autores',
                'grupos',
                'caratula',
                'unidades.temas',
                'unidades.evaluacion',
                'unidades.evidencias',
                'unidades.fases.actividades',
                'referencias',
            ]);
        });
    }

    /**
     * Usa el PDF del plan de estudio de la asignatura (si existe) para crear
     * automáticamente la carátula, las unidades, sus temas, el encabezado de
     * evaluación y las referencias. Si no hay PDF válido, solo deja la carátula
     * con los datos mínimos y ninguna unidad (el docente las agrega a mano).
     */
    private function prellenarDesdeAsignatura(Secuencia $secuencia, Asignatura $asignatura): void
    {
        $datosPdf = null;

        if ($asignatura->plan_estudio_url) {
            $datosPdf = $this->intentarExtraerDatos($asignatura->plan_estudio_url);
        }

        $dg = $datosPdf['datos_generales'] ?? [];

        $secuencia->caratula()->create([
            'programa_educativo' => $dg['programa_educativo'] ?? '',
            'proposito_aprendizaje' => $datosPdf['proposito'] ?? '',
            'competencia' => $datosPdf['competencia'] ?? '',
            'tipo_competencia' => $dg['tipo_competencia'] ?? '',
            'creditos' => $dg['creditos'] ?? 0,
            'modalidad' => $dg['modalidad'] ?? '',
            'horas_saber' => $dg['horas_saber'] ?? 0,
            'horas_saber_hacer' => $dg['horas_hacer'] ?? 0,
            'horas_totales' => $dg['horas_totales'] ?? 0,
            'horas_semana' => $dg['horas_semana'] ?? 0,
        ]);

        //porcentaje de la unidad se calcula las horas totales de la caratula entre las horas totales de la unidad
        foreach ($datosPdf['unidades_aprendizaje'] ?? [] as $i => $u) {
            $unidad = $secuencia->unidades()->create([
                'numero' => $i + 1,
                'nombre' => $u['nombre'] ?? "Unidad " . ($i + 1),
                'proposito_esperado' => $u['proposito'] ?? '',
                'horas_saber' => $u['tiempo_asignado']['horas_saber'] ?? 0,
                'horas_saber_hacer' => $u['tiempo_asignado']['horas_hacer'] ?? 0,
                'horas_totales' => $u['tiempo_asignado']['horas_totales'] ?? 0,
                'porcentaje_unidad' => ($dg['horas_totales'] ?? 0) > 0 ? round((($u['tiempo_asignado']['horas_totales'] ?? 0) / ($dg['horas_totales'] ?? 1)) * 100, 2) : 0,
            ]);

            foreach ($u['temas'] ?? [] as $orden => $t) {
                $unidad->temas()->create([
                    'tema' => $t['nombre'] ?? '',
                    'saber' => $t['saber'] ?? '',
                    'saber_hacer' => $t['saber_hacer'] ?? '',
                    'ser_convivir' => $t['ser_convivir'] ?? null,
                    'orden' => $orden,
                ]);
            }

            // periodo semana se obtiene de horas totales de la unidad entre las horas semanales de la caratula, redondeando hacia arriba
            $unidad->evaluacion()->create([
                'periodo_semanas' => ($dg['horas_semana'] ?? 0) > 0 ? ceil(($u['tiempo_asignado']['horas_totales'] ?? 0) / ($dg['horas_semana'] ?? 1)) : 0,
                'resultado_aprendizaje' => $u['proceso_evaluacion']['resultado_aprendizaje'] ?? '',
            ]);

            foreach (['apertura', 'desarrollo', 'cierre'] as $fase) {
                $unidad->fases()->create(['fase' => $fase]);
            }
        }

        foreach ($datosPdf['bibliografia'] ?? [] as $orden => $ref) {
            $secuencia->referencias()->create([
                'autor' => '',
                'titulo' => '',
                'referencia' => is_array($ref) ? ($ref['titulo'] ?? '') : $ref,
                'orden' => $orden,
            ]);
        }

        // Si no había PDF (ni el análisis regresó unidades), se deja al menos
        // una unidad en blanco con sus 3 fases para que el docente comience.
        if ($secuencia->unidades()->count() === 0) {
            $unidad = $secuencia->unidades()->create([
                'numero' => 1,
                'nombre' => 'Unidad 1',
                'proposito_esperado' => '',
                'horas_saber' => 0,
                'horas_saber_hacer' => 0,
                'horas_totales' => 0,
                'porcentaje_unidad' => 0,
            ]);
            $unidad->evaluacion()->create(['periodo_semanas' => 1, 'resultado_aprendizaje' => '']);
            foreach (['apertura', 'desarrollo', 'cierre'] as $fase) {
                $unidad->fases()->create(['fase' => $fase]);
            }
        }
    }

    private function intentarExtraerDatos(string $planUrl): ?array
    {
        try {
            $ruta = str_replace(Storage::disk('public')->url(''), '', $planUrl);
            $rutaCompleta = Storage::disk('public')->path($ruta);

            $parser = new \Smalot\PdfParser\Parser();
            $pdf = $parser->parseFile($rutaCompleta);
            $pageTexts = [];
            foreach ($pdf->getPages() as $i => $page) {
                $pageTexts[$i + 1] = $page->getText();
            }

            return $this->estructuraAcademica->extraerDatosPdf($pageTexts);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('SecuenciaService: no se pudo extraer datos del PDF, se crea la secuencia vacía', [
                'mensaje' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Duplica una secuencia validada como punto de partida de una nueva (en borrador),
     * copiando carátula, unidades, temas, evaluación, evidencias, fases/actividades
     * y referencias. No copia autores/grupos (se eligen de nuevo) ni el historial.
     */
    public function duplicar(Secuencia $original, User $autorPrincipal, array $data): Secuencia
    {
        $original->loadMissing([
            'caratula',
            'unidades.temas',
            'unidades.evaluacion',
            'unidades.evidencias',
            'unidades.fases.actividades',
            'referencias',
        ]);

        return DB::transaction(function () use ($original, $autorPrincipal, $data) {
            $nueva = Secuencia::create([
                'asignatura_id' => $original->asignatura_id,
                'especialidad_id' => $data['especialidad_id'] ?? $original->especialidad_id,
                'carrera_id' => $data['carrera_id'] ?? $original->carrera_id,
                'periodo' => $data['periodo'],
                'estado' => 'borrador',
            ]);

            $autores = array_unique(array_merge([$autorPrincipal->id], $data['coautor_ids'] ?? []));
            $nueva->autores()->attach($autores);

            foreach ($data['grupos'] ?? [] as $grupo) {
                $nueva->grupos()->create(['grupo' => $grupo]);
            }

            $nueva->historialEstados()->create([
                'estado_anterior' => null,
                'estado_nuevo' => 'borrador',
                'user_id' => $autorPrincipal->id,
                'comentario' => "Copiada a partir de la secuencia #{$original->id}.",
            ]);

            if ($original->caratula) {
                $nueva->caratula()->create($original->caratula->only([
                    'programa_educativo',
                    'proposito_aprendizaje',
                    'competencia',
                    'tipo_competencia',
                    'creditos',
                    'modalidad',
                    'horas_saber',
                    'horas_saber_hacer',
                    'horas_totales',
                    'horas_semana',
                ]));
            }

            foreach ($original->unidades as $unidadOriginal) {
                $unidad = $nueva->unidades()->create($unidadOriginal->only([
                    'numero',
                    'nombre',
                    'proposito_esperado',
                    'horas_saber',
                    'horas_saber_hacer',
                    'horas_totales',
                    'porcentaje_unidad',
                ]));

                foreach ($unidadOriginal->temas as $tema) {
                    $unidad->temas()->create($tema->only(['tema', 'saber', 'saber_hacer', 'ser_convivir', 'orden']));
                }

                if ($unidadOriginal->evaluacion) {
                    $evaluacion = $unidad->evaluacion()->create(
                        $unidadOriginal->evaluacion->only(['periodo_semanas', 'resultado_aprendizaje'])
                    );

                    foreach ($unidadOriginal->evidencias as $evidencia) {
                        $unidad->evidencias()->create($evidencia->only([
                            'evidencia_aprendizaje',
                            'tipo_evaluacion',
                            'ponderacion',
                            'instrumento_evaluacion',
                            'orden',
                        ]));
                    }
                }

                foreach ($unidadOriginal->fases as $faseOriginal) {
                    $fase = $unidad->fases()->create(['fase' => $faseOriginal->fase]);
                    foreach ($faseOriginal->actividades as $actividad) {
                        $fase->actividades()->create($actividad->only([
                            'numero',
                            'metodos_tecnicas',
                            'actividades_docente',
                            'actividades_estudiante',
                            'evidencia_aprendizaje',
                            'medios_materiales',
                        ]));
                    }
                }
            }

            foreach ($original->referencias as $referencia) {
                $nueva->referencias()->create($referencia->only(['autor', 'titulo', 'referencia', 'orden']));
            }

            return $nueva->fresh([
                'asignatura',
                'especialidad',
                'carrera',
                'autores',
                'grupos',
                'caratula',
                'unidades.temas',
                'unidades.evaluacion',
                'unidades.evidencias',
                'unidades.fases.actividades',
                'referencias',
            ]);
        });
    }

    /**
     * Revisa qué le falta a la secuencia antes de poder enviarse a revisión.
     * Regresa una lista de puntos con su estatus (ok/falta) y a qué sección
     * pertenecen, para que el frontend pueda mostrar el checklist y navegar.
     */
    public function completitud(Secuencia $secuencia): array
    {
        $secuencia->loadMissing(['caratula', 'grupos', 'unidades.temas', 'unidades.evaluacion', 'unidades.evidencias', 'unidades.fases.actividades', 'referencias']);

        $items = [];

        $items[] = [
            'seccion' => 'caratula',
            'label' => 'La carátula tiene propósito y competencia registrados',
            'ok' => $secuencia->caratula && trim($secuencia->caratula->proposito_aprendizaje) !== '' && trim($secuencia->caratula->competencia) !== '',
        ];

        $items[] = [
            'seccion' => 'caratula',
            'label' => 'Se asignó al menos un grupo',
            'ok' => $secuencia->grupos->isNotEmpty(),
        ];

        $items[] = [
            'seccion' => 'caratula',
            'label' => 'La secuencia tiene al menos 3 unidades de aprendizaje',
            'ok' => $secuencia->unidades->count() >= 3,
        ];

        foreach ($secuencia->unidades as $i => $unidad) {
            $num = $i + 1;
            $seccionUnidad = "unidad-{$unidad->id}";

            $items[] = [
                'seccion' => $seccionUnidad,
                'label' => "Unidad {$num}: tiene al menos un tema registrado",
                'ok' => $unidad->temas->isNotEmpty(),
            ];

            $evidencias = $unidad->evidencias;
            $sumaPonderacion = round($evidencias->sum('ponderacion'), 2);
            $tiposDistintos = $evidencias->pluck('tipo_evaluacion')->filter()->unique()->count();

            $items[] = [
                'seccion' => "{$seccionUnidad}-evaluacion",
                'label' => "Unidad {$num}: la ponderación de las evidencias suma 100%",
                'ok' => $evidencias->isNotEmpty() && $sumaPonderacion === 100.0,
            ];

            $items[] = [
                'seccion' => "{$seccionUnidad}-evaluacion",
                'label' => "Unidad {$num}: al menos dos tipos de evaluación distintos",
                'ok' => $tiposDistintos >= 2,
            ];

            foreach (['apertura', 'desarrollo', 'cierre'] as $tipoFase) {
                $fase = $unidad->fases->firstWhere('fase', $tipoFase);
                $items[] = [
                    'seccion' => "{$seccionUnidad}-secuencia",
                    'label' => "Unidad {$num}: la fase de " . ucfirst($tipoFase) . ' tiene al menos una actividad',
                    'ok' => $fase && $fase->actividades->isNotEmpty(),
                ];
            }
        }

        $items[] = [
            'seccion' => 'bibliografia',
            'label' => 'Hay al menos una referencia bibliográfica o digital',
            'ok' => $secuencia->referencias->isNotEmpty(),
        ];

        return $items;
    }
}
