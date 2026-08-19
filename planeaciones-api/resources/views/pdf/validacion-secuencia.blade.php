<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="utf-8">
    <style>
        @page {
            margin: 90px 35px 50px 35px;
        }

        header {
            position: fixed;
            top: -75px;
            left: 0;
            right: 0;
            height: 70px;
        }

        footer {
            position: fixed;
            bottom: -35px;
            left: 0;
            right: 0;
            height: 30px;
            font-size: 8px;
            color: #444;
        }

        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 9.5px;
            color: #000;
        }

        /* ── Encabezado ── */
        .hdr-table {
            width: 100%;
            border-collapse: collapse;
        }

        .hdr-logo {
            width: 90px;
            text-align: left;
        }

        .hdr-logo img {
            max-width: 85px;
            max-height: 55px;
        }

        .hdr-text {
            text-align: center;
        }

        .hdr-text h1 {
            font-size: 12px;
            margin: 0;
            color: #222;
            font-weight: bold;
        }

        .hdr-text h2 {
            font-size: 8.5px;
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

        /* ── Sección Superior (Carrera, Periodo, etc.) ── */
        .top-section {
            width: 100%;
            border-collapse: collapse;
            margin: 10px 0 12px;
        }

        .top-section td {
            padding: 2px 4px;
            vertical-align: bottom;
        }

        .top-lbl {
            font-size: 8.5px;
            font-weight: bold;
            color: #000;
        }

        .top-value {
            border-bottom: 1px solid #000;
            min-height: 14px;
            font-size: 9px;
            text-align: center;
            padding-bottom: 1px;
        }

        .periodo-box {
            border: 1px solid #000;
            border-collapse: collapse;
            width: 100%;
        }

        .periodo-box td {
            border: 1px solid #000;
            padding: 2px 4px;
            font-size: 8px;
            height: 11px;
        }

        .chk-box {
            width: 12px;
            height: 11px;
            border: 1px solid #000;
            display: block;
            margin: 0 auto;
            text-align: center;
            line-height: 10px;
            font-size: 8px;
            font-weight: bold;
        }

        /* ── Tabla Principal ── */
        table.main {
            width: 100%;
            border-collapse: collapse;
        }

        table.main th {
            background: #52B683;
            /* Verde institucional exacto */
            color: #000;
            border: 1px solid #000;
            padding: 5px 3px;
            font-size: 8.5px;
            text-align: center;
            font-weight: bold;
        }

        table.main td {
            border: 1px solid #000;
            padding: 0;
            /* Controlamos el padding internamente */
            font-size: 8.5px;
            vertical-align: middle;
        }

        .col-pad {
            padding: 4px !important;
        }

        .col-asignatura {
            width: 15%;
            text-align: center;
        }

        .col-docente {
            width: 15%;
            text-align: center;
        }

        .col-cuatrigrupo {
            width: 13%;
            text-align: center;
        }

        .col-entrega {
            width: 36%;
        }

        .col-fechaval {
            width: 10%;
            text-align: center;
        }

        .col-firmaptc {
            width: 11%;
            text-align: center;
        }

        /* ── Estructura de Entrega (Sin desfasamientos) ── */
        table.entrega-nested {
            width: 100%;
            border-collapse: collapse;
            margin: -1px;
            /* Elimina la doble línea con el borde padre */
        }

        table.entrega-nested td {
            border: 1px solid #000;
            padding: 2px 3px;
            font-size: 8px;
            vertical-align: middle;
        }

        .lbl-green {
            background: #52B683;
            color: #000;
            font-weight: bold;
            width: 22%;
            text-align: left;
            padding-left: 4px !important;
        }

        .box-chk-inline {
            width: 12px;
            height: 12px;
            border: 1px solid #000;
            display: inline-block;
            vertical-align: middle;
            text-align: center;
            line-height: 11px;
            font-size: 8px;
        }

        .firma-docente-cell {
            text-align: center;
            font-size: 7.5px;
            width: 28%;
            vertical-align: middle;
            color: #222;
        }

        .firma-linea-ptc {
            border-top: 1px solid #000;
            width: 80%;
            margin: 15px auto 2px;
        }

        /* ── Sección Director de Carrera ── */
        .seccion-director {
            margin-top: 60px;
            text-align: center;
            width: 100%;
        }

        .firma-director-caja {
            text-align: center;
            width: 260px;
            margin: 0 auto;
        }

        .firma-director-linea {
            border-top: 1px solid #000;
            width: 100%;
            margin: 0 auto 4px;
        }

        .firma-director-img {
            max-height: 45px;
            max-width: 180px;
            display: block;
            margin: 0 auto 2px;
        }

        /* ── Footer ── */
        footer .foot-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 7.5px;
            color: #333;
        }

        footer .foot-left {
            text-align: left;
        }

        footer .foot-right {
            text-align: right;
        }
    </style>
</head>

<body>

    <header>
        <table class="hdr-table">
            <tr>
                <td class="hdr-logo">
                    @if($logoPath)
                    <img src="{{ $logoPath }}">
                    @endif
                </td>
                <td class="hdr-text">
                    <h1>UNIVERSIDAD TECNOLÓGICA DE HUEJOTZINGO</h1>
                    <h2>Organismo Público Descentralizado Del Estado De Puebla</h2>
                </td>
            </tr>
        </table>
        <div class="hdr-title">Validación de la Planeación Didáctica del Programa de Asignatura</div>
    </header>

    <footer>
        <table class="foot-table">
            <tr>
                <td class="foot-left">
                    <strong>CÓDIGO:</strong> UTH-ACA-DC-F-PVSD/14 &nbsp;&nbsp;&nbsp; <strong>REVISIÓN:</strong> 1
                </td>
                <td class="foot-right">Página 1 de 1</td>
            </tr>
        </table>
    </footer>

    <!-- ═══ Datos Generales ═══ -->
    <table class="top-section">
        <tr>
            <td style="width: 8%;" class="top-lbl">Carrera</td>
            <td style="width: 52%;">
                <div class="top-value">{{ $secuencia->carrera?->nombre }}</div>
            </td>
            <td style="width: 10%; text-align: right; padding-right: 8px;" class="top-lbl">Periodo</td>
            <td style="width: 30%;">
                <table class="periodo-box">
                    <tr>
                        <td style="width: 80%;">Enero – Abril</td>
                        <td style="width: 20%;"><span class="chk-box">{{ $cuatrimestreLabel === 'Enero - Abril' ? 'X' : '' }}</span></td>
                    </tr>
                    <tr>
                        <td>Mayo – Agosto</td>
                        <td><span class="chk-box">{{ $cuatrimestreLabel === 'Mayo - Agosto' ? 'X' : '' }}</span></td>
                    </tr>
                    <tr>
                        <td>Septiembre – Diciembre</td>
                        <td><span class="chk-box">{{ $cuatrimestreLabel === 'Septiembre - Diciembre' ? 'X' : '' }}</span></td>
                    </tr>
                </table>
            </td>
        </tr>
        <tr>
            <td class="top-lbl" style="padding-top: 6px;">Nombre del PTC<br>que valida</td>
            <td style="padding-top: 6px;">
                <div class="top-value">{{ auth()->user()?->nombre_completo }}</div>
            </td>
            <td class="top-lbl" style="text-align: right; padding-right: 8px; padding-top: 6px;">Año</td>
            <td style="padding-top: 6px;">
                <div class="top-value">{{ $anioPeriodo }}</div>
            </td>
        </tr>
    </table>

    <!-- ═══ Tabla Principal ═══ -->
    <table class="main">
        <thead>
            <tr>
                <th class="col-asignatura">Asignatura</th>
                <th class="col-docente">Docente(s)</th>
                <th class="col-cuatrigrupo">Cuatrimestre y grupo(s)</th>
                <th class="col-entrega">Entrega</th>
                <th class="col-fechaval">Fecha de validación</th>
                <th class="col-firmaptc">Firma de PTC</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td class="col-asignatura col-pad">{{ $secuencia->asignatura?->nombre }}</td>
                <td class="col-docente col-pad">{{ $secuencia->autores->pluck('nombre_completo')->join(', ') }}</td>
                <td class="col-cuatrigrupo col-pad">
                    {{ $secuencia->asignatura?->cuatrimestre?->numero }}° — {{ $secuencia->grupos->pluck('grupo')->join(', ') ?: '—' }}
                </td>
                <td class="col-entrega">
                    <table class="entrega-nested">
                        <tr>
                            <td class="lbl-green">Cumplió</td>
                            <td style="width: 15%; text-align: center;">SI</td>
                            <td style="width: 10%; text-align: center;"><span class="box-chk-inline"></span></td>
                            <td style="width: 15%; text-align: center;">NO</td>
                            <td style="width: 10%; text-align: center;"><span class="box-chk-inline"></span></td>
                            <td rowspan="2" class="firma-docente-cell">
                                Firma del<br>Docente
                            </td>
                        </tr>
                        <tr>
                            <td class="lbl-green">Fecha</td>
                            <td colspan="4" style="padding-left: 6px;">
                                {{ optional($secuencia->fecha_solicitud_revision)->format('d/m/Y') ?? '—' }}
                            </td>
                        </tr>
                        <tr>
                            <td colspan="6" style="height: 22px; vertical-align: top; padding: 3px 5px;">
                                Observaciones:
                            </td>
                        </tr>
                    </table>
                </td>
                <td class="col-fechaval col-pad">{{ now()->format('d/m/Y') }}</td>
                <td class="col-firmaptc col-pad">
                    @if($firmaBase64)
                    <img src="{{ $firmaBase64 }}" style="max-width:90%;max-height:28px">
                    @else
                    <div class="firma-linea-ptc"></div>
                    @endif
                </td>
            </tr>
        </tbody>
    </table>

    <!-- ═══ Firma Director de Carrera ═══ -->
    <div class="seccion-director">
        <div class="firma-director-caja">
            @if($firmaBase64)
            <img class="firma-director-img" src="{{ $firmaBase64 }}">
            @else
            <div style="height: 35px;"></div>
            @endif
            <div class="firma-director-linea"></div>
            <div>Firma del director de carrera</div>
        </div>
    </div>

</body>

</html>