<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="utf-8">
    <style>
        @page {
            margin: 95px 30px 45px 30px;
        }

        header {
            position: fixed;
            top: -80px;
            left: 0;
            right: 0;
            height: 78px;
        }

        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 9px;
            color: #000;
        }

        /* ── Encabezado ── */
        .hdr-table {
            width: 100%;
            border-collapse: collapse;
        }

        .hdr-logo-left,
        .hdr-logo-right {
            width: 90px;
            vertical-align: middle;
        }

        .hdr-logo-left {
            text-align: left;
        }

        .hdr-logo-right {
            text-align: right;
        }

        .hdr-logo-left img,
        .hdr-logo-right img {
            max-width: 85px;
            max-height: 55px;
        }

        .hdr-text {
            text-align: center;
        }

        .hdr-text h1 {
            font-size: 13px;
            margin: 0;
            color: #222;
            font-weight: bold;
        }

        .hdr-text h2 {
            font-size: 9px;
            margin: 2px 0 0;
            color: #444;
            font-weight: normal;
        }

        .hdr-title {
            text-align: center;
            font-size: 10.5px;
            font-weight: bold;
            margin-top: 6px;
            text-transform: uppercase;
            color: #222;
        }

        /* ── Bloques con etiqueta ── */
        .seccion-titulo {
            background: #5a9648;
            color: #fff;
            font-weight: bold;
            font-size: 10px;
            padding: 4px 6px;
            margin: 10px 0 6px;
            text-transform: uppercase;
        }

        table.datos {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 8px;
        }

        table.datos td {
            border: 1px solid #000;
            padding: 4px 5px;
            vertical-align: top;
            font-size: 8.5px;
        }

        table.datos td.lbl {
            color: #fff;
            background: #5a9648;
            font-weight: bold;
            width: 22%;
        }

        table.datos td.val {
            width: 28%;
        }

        table.datos td.lbl-full {
            color: #fff;
            background: #5a9648;
            font-weight: bold;
            width: 22%;
        }

        /* ── Tablas de contenido (temas, evaluación, secuencia didáctica) ── */
        table.contenido {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 4px;
        }

        table.contenido th {
            background: #5a9648;
            color: #fff;
            border: 1px solid #000;
            padding: 4px 3px;
            font-size: 8.5px;
            text-align: center;
            font-weight: bold;
        }

        table.contenido td {
            border: 1px solid #000;
            padding: 4px 4px;
            font-size: 8.3px;
            vertical-align: top;
        }

        table.contenido caption,
        .subtabla-titulo {
            background: #fff;
            font-weight: bold;
            text-align: left;
            padding: 4px 6px;
            font-size: 9px;
            border: 1px solid #000;
            border-bottom: none;
        }

        .aviso-plagio {
            font-size: 7.5px;
            font-style: italic;
            color: #444;
            margin-top: 10px;
        }

        .salto-unidad {
            page-break-before: always;
        }

        .unidad-titulo {
            font-size: 10px;
            font-weight: bold;
            color: #1f7a4d;
            margin: 4px 0 8px;
        }

        .vacio {
            color: #888;
            font-style: italic;
        }
    </style>
</head>

<body>

    {{-- ══════════════════ Helper: fusiona (rowspan) valores consecutivos iguales en una columna ══════════════════
         Se usa en las tablas de Temas, Evidencias y Actividades para que, si varias filas seguidas
         comparten el mismo texto en una columna, esa celda aparezca una sola vez ocupando varias filas
         en lugar de repetirse. Los valores vacíos/null nunca se fusionan entre sí. --}}
    @php
    if (! function_exists('uthCalcularRowspans')) {
    function uthCalcularRowspans($coleccion, string $campo): array
    {
    $items = collect($coleccion)->values();
    $resultado = [];
    $valorAnterior = null;
    $inicioGrupo = null;

    foreach ($items as $i => $item) {
    $valor = data_get($item, $campo);
    $valorVacio = $valor === null || $valor === '';

    if ($valorVacio || $valor !== $valorAnterior) {
    if ($inicioGrupo !== null) {
    $resultado[$inicioGrupo]['rowspan'] = $i - $inicioGrupo;
    }
    $inicioGrupo = $i;
    $resultado[$i] = ['mostrar' => true, 'rowspan' => 1];
    } else {
    $resultado[$i] = ['mostrar' => false, 'rowspan' => 0];
    }

    $valorAnterior = $valorVacio ? null : $valor;
    }

    if ($inicioGrupo !== null) {
    $resultado[$inicioGrupo]['rowspan'] = $items->count() - $inicioGrupo;
    }

    return $resultado;
    }
    }
    @endphp

    <header>
        <table class="hdr-table">
            <tr>
                <td class="hdr-logo-left">
                    @if($utLogoPath)
                    <img src="{{ $utLogoPath }}">
                    @endif
                </td>
                <td class="hdr-text">
                    <h1>UNIVERSIDAD TECNOLÓGICA DE HUEJOTZINGO</h1>
                    <h2>Organismo Público Descentralizado Del Estado De Puebla</h2>
                </td>
                <td class="hdr-logo-right">
                    @if($uthLogoPath)
                    <img src="{{ $uthLogoPath }}">
                    @endif
                </td>
            </tr>
        </table>
        <div class="hdr-title">Planeación Didáctica del Programa de Asignatura</div>
    </header>

    {{-- El pie de página (CÓDIGO / REVISIÓN / Página X de Y) se dibuja
         directamente sobre el PDF desde el controlador con $canvas->page_text(),
         no aquí, para que el total de páginas sea el real. --}}


    {{-- ══════════════════ A. CARÁTULA ══════════════════ --}}
    <div class="seccion-titulo">A.- Carátula</div>
    <table class="datos">
        <tr>
            <td class="lbl">Programa educativo</td>
            <td class="val">{{ $secuencia->carrera?->nombre ?? '—' }}</td>
            <td class="lbl">Docente(s)</td>
            <td class="val">{{ $secuencia->autores->pluck('nombre_completo')->join(', ') ?: '—' }}</td>
        </tr>
        <tr>
            <td class="lbl">Cuatrimestre</td>
            <td class="val">{{ $secuencia->asignatura?->cuatrimestre?->nombre ?? $secuencia->asignatura?->cuatrimestre?->numero ?? '—' }}</td>
            <td class="lbl">Periodo escolar</td>
            <td class="val">{{ $secuencia->periodo ?? '—' }}</td>
        </tr>
        <tr>
            <td class="lbl">Nombre de la asignatura</td>
            <td class="val">{{ $secuencia->asignatura?->nombre ?? '—' }}</td>
            <td class="lbl">Grupo(s)</td>
            <td class="val">{{ $secuencia->grupos->pluck('grupo')->join(', ') ?: '—' }}</td>
        </tr>
    </table>

    <table class="datos">
        <tr>
            <td class="lbl-full">Propósito de la asignatura</td>
            <td colspan="3">{{ $secuencia->caratula?->proposito_aprendizaje ?: '—' }}</td>
        </tr>
        <tr>
            <td class="lbl-full">Competencia a la que contribuye la asignatura</td>
            <td colspan="3">{{ $secuencia->caratula?->competencia ?: '—' }}</td>
        </tr>
        <tr>
            <td class="lbl">Tipo de competencia</td>
            <td class="val">{{ $secuencia->caratula?->tipo_competencia ?: '—' }}</td>
            <td class="lbl">Créditos</td>
            <td class="val">{{ $secuencia->caratula?->creditos ?: '—' }}</td>
        </tr>
        <tr>
            <td class="lbl">Modalidad</td>
            <td class="val">{{ $secuencia->caratula?->modalidad ?: '—' }}</td>
            <td class="lbl">Horas por semana</td>
            <td class="val">{{ $secuencia->caratula?->horas_semana ?: '—' }}</td>
        </tr>
    </table>

    <table class="contenido">
        <thead>
            <tr>
                <th>Horas del saber</th>
                <th>Horas del saber hacer</th>
                <th>Horas Totales</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td style="text-align:center">{{ $secuencia->caratula?->horas_saber ?? '—' }}</td>
                <td style="text-align:center">{{ $secuencia->caratula?->horas_saber_hacer ?? '—' }}</td>
                <td style="text-align:center">{{ $secuencia->caratula?->horas_totales ?? '—' }}</td>
            </tr>
        </tbody>
    </table>

    <p class="aviso-plagio">
        "En la Universidad Tecnológica de Huejotzingo queda prohibido el plagio total o parcial, definido como:
        el acto de ofrecer o presentar como propia, en su totalidad o en parte, la obra de otra persona,
        en una forma o contexto más o menos alterados"
    </p>

    {{-- ══════════════════ Por cada unidad de aprendizaje ══════════════════ --}}
    @foreach($secuencia->unidades as $unidad)
    <div class="salto-unidad"></div>

    <div class="unidad-titulo">Unidad {{ $unidad->numero }} — {{ $unidad->nombre }}</div>

    {{-- B. Información de la unidad --}}
    <div class="seccion-titulo">B.- Información de la unidad de aprendizaje</div>
    <table class="datos">
        <tr>
            <td class="lbl-full">Nombre de la unidad de aprendizaje</td>
            <td colspan="3">{{ $unidad->nombre ?: '—' }}</td>
        </tr>
        <tr>
            <td class="lbl-full">Propósito esperado</td>
            <td colspan="3">{{ $unidad->proposito_esperado ?: '—' }}</td>
        </tr>
    </table>
    <table class="contenido">
        <thead>
            <tr>
                <th>Horas del saber</th>
                <th>Horas del saber hacer</th>
                <th>Horas totales</th>
                <th>Porcentaje de la unidad</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td style="text-align:center">{{ $unidad->horas_saber ?? '—' }}</td>
                <td style="text-align:center">{{ $unidad->horas_saber_hacer ?? '—' }}</td>
                <td style="text-align:center">{{ $unidad->horas_totales ?? '—' }}</td>
                <td style="text-align:center">{{ $unidad->porcentaje_unidad !== null ? $unidad->porcentaje_unidad . '%' : '—' }}</td>
            </tr>
        </tbody>
    </table>

    {{-- Temas: si "Saber", "Saber Hacer" o "Saber Ser-convivir" se repiten en filas
         consecutivas, se fusionan en una sola celda con rowspan. --}}
    @php
    $temasArr = $unidad->temas->values();
    $rsTema = uthCalcularRowspans($temasArr, 'tema');
    $rsSaber = uthCalcularRowspans($temasArr, 'saber');
    $rsSaberHacer = uthCalcularRowspans($temasArr, 'saber_hacer');
    $rsSerConvivir = uthCalcularRowspans($temasArr, 'ser_convivir');
    @endphp
    <table class="contenido">
        <thead>
            <tr>
                <th style="width:22%">Temas</th>
                <th style="width:26%">Saber<br><span style="font-weight:normal">Dimensión conceptual</span></th>
                <th style="width:26%">Saber Hacer<br><span style="font-weight:normal">Dimensión actuacional</span></th>
                <th style="width:26%">Saber Ser-convivir<br><span style="font-weight:normal">Dimensión socioafectiva</span></th>
            </tr>
        </thead>
        <tbody>
            @forelse($temasArr as $i => $tema)
            <tr>
                @if($rsTema[$i]['mostrar'])
                <td rowspan="{{ $rsTema[$i]['rowspan'] }}">{{ $tema->tema }}</td>
                @endif
                @if($rsSaber[$i]['mostrar'])
                <td rowspan="{{ $rsSaber[$i]['rowspan'] }}">{{ $tema->saber ?: '—' }}</td>
                @endif
                @if($rsSaberHacer[$i]['mostrar'])
                <td rowspan="{{ $rsSaberHacer[$i]['rowspan'] }}">{{ $tema->saber_hacer ?: '—' }}</td>
                @endif
                @if($rsSerConvivir[$i]['mostrar'])
                <td rowspan="{{ $rsSerConvivir[$i]['rowspan'] }}">{{ $tema->ser_convivir ?: '—' }}</td>
                @endif
            </tr>
            @empty
            <tr>
                <td colspan="4" class="vacio" style="text-align:center">Sin temas registrados.</td>
            </tr>
            @endforelse
        </tbody>
    </table>

    {{-- C. Sistema de evaluación --}}
    <div class="seccion-titulo">C.- Sistema de evaluación por unidad de aprendizaje</div>
    @php
    $evidencias = $unidad->evidencias->values();
    $rsEvidencia = uthCalcularRowspans($evidencias, 'evidencia_aprendizaje');
    $rsTipoEval = uthCalcularRowspans($evidencias, 'tipo_evaluacion');
    $rsPonderacion = uthCalcularRowspans($evidencias, 'ponderacion');
    $rsInstrumento = uthCalcularRowspans($evidencias, 'instrumento_evaluacion');
    @endphp
    <table class="contenido">
        <thead>
            <tr>
                <th rowspan="2" style="width:10%">Periodo en semanas</th>
                <th rowspan="2" style="width:20%">Resultado de aprendizaje de la unidad</th>
                <th colspan="4">Proceso de evaluación</th>
            </tr>
            <tr>
                <th style="width:24%">Evidencia de aprendizaje</th>
                <th style="width:16%">Tipo de evaluación</th>
                <th style="width:12%">Ponderación %</th>
                <th style="width:18%">Instrumento de evaluación</th>
            </tr>
        </thead>
        <tbody>
            @forelse($evidencias as $i => $evidencia)
            <tr>
                @if($i === 0)
                <td rowspan="{{ $evidencias->count() }}" style="text-align:center">{{ $unidad->evaluacion?->periodo_semanas ?: '—' }}</td>
                <td rowspan="{{ $evidencias->count() }}">{{ $unidad->evaluacion?->resultado_aprendizaje ?: '—' }}</td>
                @endif
                @if($rsEvidencia[$i]['mostrar'])
                <td rowspan="{{ $rsEvidencia[$i]['rowspan'] }}">{{ $evidencia->evidencia_aprendizaje ?: '—' }}</td>
                @endif
                @if($rsTipoEval[$i]['mostrar'])
                <td rowspan="{{ $rsTipoEval[$i]['rowspan'] }}">{{ $evidencia->tipo_evaluacion ?: '—' }}</td>
                @endif
                @if($rsPonderacion[$i]['mostrar'])
                <td rowspan="{{ $rsPonderacion[$i]['rowspan'] }}" style="text-align:center">{{ $evidencia->ponderacion !== null ? $evidencia->ponderacion . '%' : '—' }}</td>
                @endif
                @if($rsInstrumento[$i]['mostrar'])
                <td rowspan="{{ $rsInstrumento[$i]['rowspan'] }}">{{ $evidencia->instrumento_evaluacion ?: '—' }}</td>
                @endif
            </tr>
            @empty
            <tr>
                <td style="text-align:center">{{ $unidad->evaluacion?->periodo_semanas ?: '—' }}</td>
                <td>{{ $unidad->evaluacion?->resultado_aprendizaje ?: '—' }}</td>
                <td colspan="4" class="vacio" style="text-align:center">Sin evidencias registradas.</td>
            </tr>
            @endforelse
        </tbody>
    </table>

    {{-- D. Secuencia didáctica --}}
    <div class="seccion-titulo">D.- Secuencia didáctica por unidad de aprendizaje</div>

    @foreach([
    'apertura' => ['label' => 'Apertura', 'fase' => $unidad->faseApertura],
    'desarrollo' => ['label' => 'Desarrollo', 'fase' => $unidad->faseDesarrollo],
    'cierre' => ['label' => 'Cierre', 'fase' => $unidad->faseCierre],
    ] as $bloque)
    <div class="subtabla-titulo">{{ $bloque['label'] }}</div>
    @php
    $actividadesArr = collect(optional($bloque['fase'])->actividades ?? [])->values();
    $rsMetodos = uthCalcularRowspans($actividadesArr, 'metodos_tecnicas');
    $rsDocente = uthCalcularRowspans($actividadesArr, 'actividades_docente');
    $rsEstudiante = uthCalcularRowspans($actividadesArr, 'actividades_estudiante');
    $rsEvidenciaAct = uthCalcularRowspans($actividadesArr, 'evidencia_aprendizaje');
    $rsMedios = uthCalcularRowspans($actividadesArr, 'medios_materiales');
    @endphp
    <table class="contenido">
        <thead>
            <tr>
                <th style="width:18%">Métodos y técnicas de<br>enseñanza-aprendizaje</th>
                <th style="width:22%">Actividades docentes</th>
                <th style="width:22%">Actividades de estudiantes</th>
                <th style="width:19%">Evidencia de aprendizaje</th>
                <th style="width:19%">Medios y Materiales<br>didácticos (Recursos)</th>
            </tr>
        </thead>
        <tbody>
            @forelse($actividadesArr as $i => $actividad)
            <tr>
                @if($rsMetodos[$i]['mostrar'])
                <td rowspan="{{ $rsMetodos[$i]['rowspan'] }}">{{ $actividad->metodos_tecnicas ?: '—' }}</td>
                @endif
                @if($rsDocente[$i]['mostrar'])
                <td rowspan="{{ $rsDocente[$i]['rowspan'] }}">{{ $actividad->actividades_docente ?: '—' }}</td>
                @endif
                @if($rsEstudiante[$i]['mostrar'])
                <td rowspan="{{ $rsEstudiante[$i]['rowspan'] }}">{{ $actividad->actividades_estudiante ?: '—' }}</td>
                @endif
                @if($rsEvidenciaAct[$i]['mostrar'])
                <td rowspan="{{ $rsEvidenciaAct[$i]['rowspan'] }}">{{ $actividad->evidencia_aprendizaje ?: '—' }}</td>
                @endif
                @if($rsMedios[$i]['mostrar'])
                <td rowspan="{{ $rsMedios[$i]['rowspan'] }}">{{ $actividad->medios_materiales ?: '—' }}</td>
                @endif
            </tr>
            @empty
            <tr>
                <td colspan="5" class="vacio" style="text-align:center">Sin actividades registradas.</td>
            </tr>
            @endforelse
        </tbody>
    </table>
    @endforeach
    @endforeach

    {{-- ══════════════════ Referencias (una sola vez, al final) ══════════════════ --}}
    <div class="seccion-titulo">Referencias bibliográficas y digitales</div>
    <table class="contenido">
        <tbody>
            @forelse($secuencia->referencias as $referencia)
            <tr>
                <td>
                    @if($referencia->autor)
                    <strong>{{ $referencia->autor }}.</strong>
                    @endif
                    @if($referencia->titulo)
                    {{ $referencia->titulo }}.
                    @endif
                    {{ $referencia->referencia }}
                </td>
            </tr>
            @empty
            <tr>
                <td class="vacio" style="text-align:center">Sin referencias registradas.</td>
            </tr>
            @endforelse
        </tbody>
    </table>

</body>

</html>