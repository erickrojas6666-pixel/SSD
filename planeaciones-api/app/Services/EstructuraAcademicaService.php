<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Smalot\PdfParser\Parser as PdfParser;

/**
 * EstructuraAcademicaService
 *
 * Centraliza toda la lógica de negocio del módulo de estructura académica:
 *   - Manejo de archivos (secuencias didácticas)
 *   - Validación y extracción de PDFs (secuencias didácticas)
 */
class EstructuraAcademicaService
{
    // =========================================================================
    // VALIDACIÓN DE PDF — Secuencias didácticas
    // =========================================================================

    private array $elementosPaginaUno = [
        'PROGRAMA DE ASIGNATURA',
        'CLAVE',
        'Propósito de aprendizaje de la Asignatura',
        'Competencia a la que contribuye la asignatura',
        'Tipo de competencia',
        'Cuatrimestre',
        'Créditos',
        'Modalidad',
        'Horas por semana',
        'Horas Totales',
    ];

    public function validarPdfSecuencia(UploadedFile $archivo): array
    {
        try {
            $parser    = new PdfParser();
            $pdf       = $parser->parseFile($archivo->getPathname());
            $pageTexts = [];

            foreach ($pdf->getPages() as $i => $page) {
                $pageTexts[$i + 1] = $page->getText();
            }

            return $this->validarEstructuraPDF($pageTexts);
        } catch (\Exception $e) {
            return [
                'valido'   => false,
                'errores'  => ['No se pudo leer el archivo PDF: ' . $e->getMessage()],
                'detalles' => [],
            ];
        }
    }

    public function extraerDatosPdf(array $pageTexts): array
    {
        $info = [
            'datos_generales' => [
                'programa_asignatura' => null,
                'clave'               => null,
                'programa_educativo'  => null,
                'tipo_competencia'    => null,
                'cuatrimestre'        => null,
                'creditos'            => null,
                'modalidad'           => null,
                'horas_totales'       => null,
                'horas_saber'         => null,
                'horas_hacer'         => null,
                'horas_semana'        => null,
            ],
            'proposito'            => null,
            'competencia'          => null,
            'unidades_aprendizaje' => [],
            'bibliografia'         => [],
        ];

        if (isset($pageTexts[1])) {
            $texto1 = $pageTexts[1];

            if (preg_match('/PROGRAMA EDUCATIVO:\s*(.*?)(?=PROGRAMA DE ASIGNATURA)/s', $texto1, $m)) {
                $info['datos_generales']['programa_educativo'] = trim($m[1]);
            }

            if (preg_match('/PROGRAMA DE ASIGNATURA:\s*([^\n]+?)\s+CLAVE:\s*([^\n\s]+)/i', $texto1, $m)) {
                $info['datos_generales']['programa_asignatura'] = trim($m[1]);
                $info['datos_generales']['clave']               = trim($m[2]);
            }

            if (preg_match('/Propósito de aprendizaje de la\s+Asignatura[\s:]*([\s\S]*?)\s+Competencia/si', $texto1, $m)) {
                $info['proposito'] = trim($m[1]);
            }

            if (preg_match('/Competencia a la que\s+contribuye la asignatura[\s:]*([\s\S]*?)\s+Tipo de\s+competencia/si', $texto1, $m)) {
                $info['competencia'] = trim($m[1]);
            }

            if (preg_match(
                '/(Específica|Genérica|Transversal)\s+([\d.]+)\s+([\d.]+)\s+(\w+)\s+([\d.]+)\s+([\d]+)/i',
                $texto1,
                $m
            )) {
                $info['datos_generales']['tipo_competencia'] = $m[1];
                $info['datos_generales']['cuatrimestre']     = $m[2];
                $info['datos_generales']['creditos']         = $m[3];
                $info['datos_generales']['modalidad']        = $m[4];
                $info['datos_generales']['horas_semana']     = $m[5];
                $info['datos_generales']['horas_totales']    = $m[6];
            }
        }

        if (isset($pageTexts[2])) {
            if (preg_match('/Totales\s+([\d.]+)\s+([\d.]+)\s+([\d]+)/i', $pageTexts[2], $m)) {
                $info['datos_generales']['horas_saber']   = $m[1];
                $info['datos_generales']['horas_hacer']   = $m[2];
                $info['datos_generales']['horas_totales'] = $m[3];
            }
        }

        $textoCompleto = implode("\n", $pageTexts);
        $info['unidades_aprendizaje'] = $this->extraerUnidadesAprendizaje($pageTexts);
        $info['bibliografia']         = $this->extraerBibliografia($textoCompleto);

        return $info;
    }

    // =========================================================================
    // EXTRACCIÓN DE UNIDADES DE APRENDIZAJE
    // =========================================================================

    public function extraerUnidadesAprendizaje(array $pageTexts): array
    {
        $textoCompleto = implode("\n", $pageTexts);
        $bloques       = $this->dividirEnBloquesPorUnidad($textoCompleto);

        $unidades = [];
        foreach ($bloques as $bloque) {
            $unidad = $this->parsearBloqueUnidad($bloque);
            if ($unidad !== null) {
                $unidades[] = $unidad;
            }
        }

        return $unidades;
    }

    private function dividirEnBloquesPorUnidad(string $texto): array
    {
        $patron = '/(?=Unidad\s+de\s+Aprendizaje\s+[IVXLCDM\d]+[\.\s])/i';
        $partes = preg_split($patron, $texto, -1, PREG_SPLIT_NO_EMPTY);

        return $partes ?: [];
    }

    private function parsearBloqueUnidad(string $bloque): ?array
    {
        if (!preg_match(
            '/Unidad\s+de\s+Aprendizaje\s+([IVXLCDM\d]+)[\.\s]+([^\n\r]+)/i',
            $bloque,
            $mNombre
        )) {
            return null;
        }

        $unidad = [
            'numero'             => trim($mNombre[1]),
            'nombre'             => trim($mNombre[2]),
            'proposito'          => null,
            'tiempo_asignado'    => ['horas_saber' => null, 'horas_hacer' => null, 'horas_totales' => null],
            'temas'              => [],
            'proceso_ensenanza'  => ['metodos_tecnicas' => [], 'medios_materiales' => [], 'espacio_formativo' => null],
            'proceso_evaluacion' => ['resultado_aprendizaje' => null, 'evidencia_aprendizaje' => null, 'instrumentos' => []],
        ];

        if (preg_match(
            '/Propósito\s+esperado\s+([\s\S]*?)(?=Tiempo\s+Asignado|Horas\s+del\s+Saber)/i',
            $bloque,
            $m
        )) {
            $unidad['proposito'] = $this->limpiarTexto($m[1]);
        }

        if (preg_match(
            '/Horas\s+del\s+Saber\s+([\d.]+)\s+Horas\s+del\s+Saber\s+Hacer\s+([\d.]+)\s+Horas\s+Totales\s+([\d.]+)/i',
            $bloque,
            $m
        )) {
            $unidad['tiempo_asignado']['horas_saber']   = $m[1];
            $unidad['tiempo_asignado']['horas_hacer']   = $m[2];
            $unidad['tiempo_asignado']['horas_totales'] = $m[3];
        }

        $unidad['temas']              = $this->extraerTemasDeBloque($bloque);
        $unidad['proceso_ensenanza']  = $this->extraerProcesoEnsenanza($bloque);
        $unidad['proceso_evaluacion'] = $this->extraerProcesoEvaluacion($bloque);

        return $unidad;
    }

    // =========================================================================
    // EXTRACCIÓN DE TEMAS
    // =========================================================================

    private function extraerTemasDeBloque(string $bloque): array
    {
        $patronCabecera = '/Temas\s+Saber\s+Dimensi[oóón]+\s+Conceptual\s+Saber\s+Hacer\s+Dimensi[oóón]+\s+Actuacional\s+Ser\s+y\s+Convivir\s+Dimensi[oóón]+\s+Socioafectiva/iu';

        if (!preg_match($patronCabecera, $bloque, $mCab, PREG_OFFSET_CAPTURE)) {
            Log::info('Cabecera de tabla no encontrada en bloque de unidad:' . PHP_EOL . substr($bloque, 0, 500));
            return [];
        }

        $inicioCuerpo = $mCab[0][1] + strlen($mCab[0][0]);

        $patronFin = '/Proceso\s+Ense[nñ]anza|Métodos\s+y\s+técnicas\s+de\s+enseñanza|Medios\s+y\s+materiales\s+didácticos/i';
        $cuerpo = preg_match($patronFin, $bloque, $mFin, PREG_OFFSET_CAPTURE, $inicioCuerpo)
            ? substr($bloque, $inicioCuerpo, $mFin[0][1] - $inicioCuerpo)
            : substr($bloque, $inicioCuerpo);

        $cuerpo = preg_replace($patronCabecera, ' ', $cuerpo);
        $cuerpo = preg_replace(
            '/^(Temas|Saber|Dimensi[oó]n\s+Conceptual|Saber\s+Hacer|Dimensi[oó]n\s+Actuacional|Ser\s+y\s+Convivir|Dimensi[oó]n\s+Socioafectiva)\s*$/im',
            '',
            $cuerpo
        );

        $verbosActuacionales = [
            'Diseñar', 'Disenar', 'Seleccionar', 'Estructurar', 'Determinar', 'Implementar', 'Validar',
            'Codificar', 'Depurar', 'Documentar', 'Proponer', 'Utilizar', 'Ejecutar', 'Asegurar',
            'Aplicar', 'Construir', 'Elaborar', 'Generar', 'Crear', 'Resolver',
        ];

        $verbosConceptuales = [
            'Explicar', 'Describir', 'Identificar', 'Diferenciar', 'Definir', 'Distinguir', 'Reconocer',
            'Enumerar', 'Clasificar', 'Comparar', 'Mencionar', 'Señalar', 'Indicar', 'Interpretar',
            'Relacionar', 'Comprender', 'Conocer', 'Entender',
        ];

        $verbosSerConvivir = [
            'Cultivar', 'Incentivar', 'Valorar', 'Fomentar', 'Promover', 'Reconocer', 'Asumir',
            'Demostrar', 'Mostrar', 'Practicar', 'Reflexionar', 'Colaborar', 'Respetar', 'Actuar',
        ];

        $patronSH = $this->construirPatronVerbosInicio($verbosActuacionales);
        $patronSC = $this->construirPatronVerbosInicio($verbosSerConvivir);

        $posSH = null;
        if (preg_match($patronSH, $cuerpo, $mSH, PREG_OFFSET_CAPTURE)) {
            $posSH = $mSH[0][1];
            if (isset($cuerpo[$posSH]) && $cuerpo[$posSH] === "\n") {
                $posSH++;
            }
        }

        if ($posSH === null) {
            return $this->ensamblarTemas(
                $this->parsearColumnasTemasSaber($cuerpo, $verbosActuacionales),
                '',
                '',
                $verbosActuacionales
            );
        }

        $posSC = null;
        if (preg_match($patronSC, $cuerpo, $mSC, PREG_OFFSET_CAPTURE, $posSH)) {
            $posSC = $mSC[0][1];
            if (isset($cuerpo[$posSC]) && $cuerpo[$posSC] === "\n") {
                $posSC++;
            }
        }

        $zonaTema1Saber = substr($cuerpo, 0, $posSH);

        if ($posSC === null) {
            $zonaSH      = substr($cuerpo, $posSH);
            $zonaPostSC  = '';
        } else {
            $zonaSH     = substr($cuerpo, $posSH, $posSC - $posSH);
            $zonaPostSC = substr($cuerpo, $posSC);
        }

        [$zonaSerConvivir, $zonaTemasResto] = $this->separarSerConvivirYTemas(
            $zonaPostSC,
            $verbosConceptuales
        );

        $zonaTemasSaber = $zonaTema1Saber . "\n" . $zonaTemasResto;
        $pares          = $this->parsearColumnasTemasSaber($zonaTemasSaber, $verbosActuacionales);

        $saberHacerGlobal  = $this->limpiarTexto($this->filtrarMetadatosPdf($zonaSH));
        $serConvivirGlobal = $this->limpiarTexto($this->filtrarMetadatosPdf($zonaSerConvivir));

        return $this->ensamblarTemas($pares, $saberHacerGlobal, $serConvivirGlobal, $verbosActuacionales);
    }

    private function ensamblarTemas(
        array $pares,
        string $saberHacerGlobal,
        string $serConvivirGlobal,
        array $verbosActuacionales
    ): array {
        $temas = [];

        foreach ($pares as $par) {
            $esEspurio = empty($par['saber'])
                && $this->iniciaConAlgunTermino($par['nombre'], $verbosActuacionales);

            if ($esEspurio) {
                continue;
            }

            $temas[] = [
                'nombre'       => $par['nombre'],
                'saber'        => $par['saber'] ?: null,
                'saber_hacer'  => $saberHacerGlobal ?: null,
                'ser_convivir' => $serConvivirGlobal ?: null,
            ];
        }

        return $temas;
    }

    private function separarSerConvivirYTemas(string $texto, array $verbosConceptuales): array
    {
        $lineas = array_values(array_filter(
            preg_split('/\r?\n/', $texto),
            function (string $l): bool {
                $l = trim($l);
                if (strlen($l) < 2) return false;
                if (preg_match('/^(ELABOR[OÓ]|REVIS[OÓ]|APROB[OÓ]|VIGENTE|F-DA-|DGUTyP|SEPTIEMBRE)/i', $l)) return false;
                return true;
            }
        ));

        $scLines    = [];
        $temasLines = [];
        $foundTemas = false;

        for ($i = 0; $i < count($lineas); $i++) {
            if ($foundTemas) {
                $temasLines[] = $lineas[$i];
                continue;
            }

            $linea = trim($lineas[$i]);

            if (preg_match('/^Proceso\s+Ense[nñ]anza|^Proceso\s+de\s+Evaluaci[oó]n/iu', $linea)) {
                continue;
            }
            if (preg_match('/^[a-záéíóúüñ]/u', $linea)) {
                $scLines[] = $lineas[$i];
                continue;
            }

            $esTema = false;
            for ($j = $i + 1; $j < min($i + 6, count($lineas)); $j++) {
                $sig = trim($lineas[$j]);
                if ($this->iniciaConAlgunTermino($sig, $verbosConceptuales)) {
                    $esTema = true;
                    break;
                }
            }

            if ($esTema) {
                $foundTemas   = true;
                $temasLines[] = $lineas[$i];
            } else {
                $scLines[] = $lineas[$i];
            }
        }

        return [
            implode("\n", $scLines),
            implode("\n", $temasLines),
        ];
    }

    private function construirPatronVerbosInicio(array $verbos): string
    {
        $alternativas = implode('|', array_map('preg_quote', $verbos));
        return '/(?:^|\n)[ \t]*(' . $alternativas . ')[ \p{L}]/u';
    }

    private function parsearColumnasTemasSaber(string $texto, array $verbosActuacionales = []): array
    {
        $verbosConceptuales = [
            'Explicar', 'Describir', 'Identificar', 'Diferenciar', 'Definir', 'Distinguir', 'Reconocer',
            'Enumerar', 'Clasificar', 'Comparar', 'Mencionar', 'Señalar', 'Indicar', 'Interpretar',
            'Relacionar', 'Comprender', 'Conocer', 'Entender',
        ];

        $lineas = preg_split('/\r?\n/', $texto);
        $lineas = array_values(array_filter(array_map('trim', $lineas), function (string $l): bool {
            if (strlen($l) < 3) return false;
            if (preg_match('/^(ELABOR[OÓ]|REVIS[OÓ]|APROB[OÓ]|VIGENTE|F-DA-|DGUTyP|SEPTIEMBRE)/i', $l)) return false;
            if (preg_match('/^Proceso\s+Ense[nñ]anza|^Proceso\s+de\s+Evaluaci[oó]n/iu', $l)) return false;
            return true;
        }));

        $iniciaComoTema = fn(string $linea): bool => (bool) preg_match('/^\p{Lu}/u', $linea);

        $nombreTerminaIncompleto = fn(?string $nombre): bool =>
        $nombre !== null && (bool) preg_match('/[-–]\s*$/u', $nombre);

        $patronVerbos = '/(?:' . implode('|', array_map('preg_quote', array_merge($verbosConceptuales, $verbosActuacionales))) . ')/u';

        $lineasUnidas = [];
        $buffer = '';
        foreach ($lineas as $linea) {
            if ($buffer === '') {
                $buffer = $linea;
            } elseif (!$iniciaComoTema($linea) || $nombreTerminaIncompleto($buffer)) {
                $buffer .= ' ' . $linea;
            } else {
                $lineasUnidas[] = $buffer;
                $buffer = $linea;
            }
        }

        if ($buffer !== '') $lineasUnidas[] = $buffer;

        $lineasExpandidas = [];
        foreach ($lineasUnidas as $linea) {
            if (preg_match($patronVerbos, $linea, $mV, PREG_OFFSET_CAPTURE) && $mV[0][1] > 0) {
                $antes = trim(substr($linea, 0, $mV[0][1]));
                $desde = trim(substr($linea, $mV[0][1]));
                if (strlen($antes) > 2) $lineasExpandidas[] = $antes;
                $lineasExpandidas[] = $desde;
            } else {
                $lineasExpandidas[] = $linea;
            }
        }
        $lineas = $lineasExpandidas;

        $pares   = [];
        $nombre  = null;
        $saber   = '';
        $enSaber = false;

        for ($i = 0; $i < count($lineas); $i++) {
            $linea = $lineas[$i];

            $esConceptual  = $this->iniciaConAlgunTermino($linea, $verbosConceptuales);
            $esActuacional = !empty($verbosActuacionales)
                && $this->iniciaConAlgunTermino($linea, $verbosActuacionales);

            if ($esActuacional) break;

            if ($esConceptual) {
                $saber   .= ' ' . $linea;
                $enSaber  = true;
            } elseif ($enSaber) {
                if (!$iniciaComoTema($linea)) {
                    $saber .= ' ' . $linea;
                } else {
                    $siguienteEsConceptual = false;
                    for ($j = $i + 1; $j < min($i + 3, count($lineas)); $j++) {
                        if ($this->iniciaConAlgunTermino($lineas[$j], $verbosConceptuales)) {
                            $siguienteEsConceptual = true;
                            break;
                        }
                        if ($iniciaComoTema($lineas[$j])) {
                            break;
                        }
                    }

                    if (!$siguienteEsConceptual) {
                        $saber .= ' ' . $linea;
                    } else {
                        if ($nombre !== null) {
                            $pares[] = [
                                'nombre' => $this->limpiarTexto($nombre),
                                'saber'  => $this->limpiarTexto($saber),
                            ];
                        }
                        $nombre  = $linea;
                        $saber   = '';
                        $enSaber = false;
                    }
                }
            } else {
                if ($nombre !== null && !$iniciaComoTema($linea)) {
                    $nombre .= ' ' . $linea;
                } elseif ($nombre !== null) {
                    $pares[] = [
                        'nombre' => $this->limpiarTexto($nombre),
                        'saber'  => $this->limpiarTexto($saber),
                    ];
                    $nombre = $linea;
                    $saber  = '';
                } else {
                    $nombre = $linea;
                }
            }
        }
        if ($nombre !== null) {
            $pares[] = [
                'nombre' => $this->limpiarTexto($nombre),
                'saber'  => $this->limpiarTexto($saber),
            ];
        }

        return array_values(array_filter($pares, fn($p) => strlen($p['nombre']) > 2));
    }

    private function filtrarMetadatosPdf(string $texto): string
    {
        $lineas = preg_split('/\r?\n/', $texto);
        $lineas = array_filter($lineas, function (string $l): bool {
            $l = trim($l);
            if (strlen($l) < 3) return false;
            if (preg_match('/^(ELABOR[OÓ]|REVIS[OÓ]|APROB[OÓ]|VIGENTE|F-DA-|DGUTyP|SEPTIEMBRE)/i', $l)) return false;
            if (preg_match('/^(Temas|Saber|Dimensi[oó]n\s+Conceptual|Saber\s+Hacer|Dimensi[oó]n\s+Actuacional|Ser\s+y\s+Convivir|Dimensi[oó]n\s+Socioafectiva)\s*$/i', $l)) return false;
            return true;
        });
        return implode(' ', $lineas);
    }

    // =========================================================================
    // PROCESO ENSEÑANZA-APRENDIZAJE Y EVALUACIÓN
    // =========================================================================

    private function extraerProcesoEnsenanza(string $bloque): array
    {
        $resultado = [
            'metodos_tecnicas'  => [],
            'medios_materiales' => [],
            'espacio_formativo' => null,
        ];

        if (!preg_match(
            '/Proceso\s+Ense[ñn]anza[–\-]?Aprendizaje([\s\S]*?)(?=Proceso\s+de\s+Evaluaci[oó]n|$)/iu',
            $bloque,
            $m
        )) {
            return $resultado;
        }

        $seccion = $m[1];

        if (preg_match('/(Aula|Laboratorio\s*\/\s*Taller|Empresa)\s+X/iu', $seccion, $mE)) {
            $resultado['espacio_formativo'] = trim(str_ireplace('X', '', $mE[0]));
        }

        $contenido = $seccion;
        $contenido = preg_replace('/M[eé]todos\s+y\s+t[eé]cnicas\s+de\s+ense[ñn]anza/iu',   '', $contenido);
        $contenido = preg_replace('/Medios\s+y\s+materiales\s+did[aá]cticos/iu',              '', $contenido);
        $contenido = preg_replace('/Espacio\s+Formativo/iu',                                   '', $contenido);
        $contenido = preg_replace('/(Aula|Laboratorio\s*\/\s*Taller|Empresa)\s*X?/iu',        '', $contenido);

        $contenido = preg_replace('/\s+/u', ' ', trim($contenido));

        $raw = preg_split('/(?<=[a-z\xE1\xE9\xED\xF3\xFA\xF1\xE0\xE8.,;])\s+(?=[A-Z\xC1\xC9\xCD\xD3\xDA\xD1])/u', $contenido);
        $items = array_values(array_filter(
            array_map('trim', $raw),
            static fn($i) => mb_strlen($i) > 1
        ));

        $mediosKeywords = [
            'Proyector', 'Pizarr', 'Equipo', 'Computadora', 'C.mputo', 'Bibliograf', 'Internet',
            'Software', 'Ca[ñn][oó]n', 'Material', 'Plataforma', 'Aplicaci', 'Herramienta',
            'Buscador', 'Acceso', 'Dispositivo', 'Tablet', 'Celular', 'Video', 'Multimedia',
            'Libro', 'Revista', 'C.digo', 'Diaposit',
        ];

        $splitIdx = count($items);
        foreach ($items as $idx => $item) {
            foreach ($mediosKeywords as $kw) {
                if (preg_match('/' . $kw . '/iu', $item)) {
                    $splitIdx = $idx;
                    break 2;
                }
            }
        }

        $resultado['metodos_tecnicas']  = array_values(array_slice($items, 0, $splitIdx));
        $resultado['medios_materiales'] = array_values(array_slice($items, $splitIdx));

        return $resultado;
    }

    private function extraerProcesoEvaluacion(string $bloque): array
    {
        $resultado = [
            'resultado_aprendizaje' => null,
            'evidencia_aprendizaje' => null,
            'instrumentos'          => [],
        ];

        if (!preg_match(
            '/Proceso\s+de\s+Evaluaci[oó]n([\s\S]*?)(?=Unidad\s+de\s+Aprendizaje\s+[IVXLCDM\d]|Perfil\s+id[oó]neo|$)/iu',
            $bloque,
            $m
        )) {
            return $resultado;
        }

        $seccion = $m[1];

        $seccion = preg_replace('/\bResultado\s+de\s+Aprendizaje\b/iu',      '§RA§', $seccion);
        $seccion = preg_replace('/\bEvidencia\s+de\s+Aprendizaje\b/iu',       '§EA§', $seccion);
        $seccion = preg_replace('/\bInstrumentos\s+de\s+evaluaci[oó]n\b/iu', '§IE§', $seccion);

        if (!preg_match('/§RA§([\s\S]*?)§EA§([\s\S]*?)§IE§([\s\S]*)$/u', $seccion, $parts)) {
            return $resultado;
        }

        $raRaw = preg_replace('/\s+/u', ' ', trim($parts[1]));
        $eaRaw = preg_replace('/\s+/u', ' ', trim($parts[2]));
        $ieRaw = preg_replace('/\s+/u', ' ', trim($parts[3]));

        if (mb_strlen($raRaw) > 10 && mb_strlen($eaRaw) > 10) {
            $resultado['resultado_aprendizaje'] = $this->limpiarTexto($raRaw);
            $resultado['evidencia_aprendizaje'] = $this->limpiarTexto($eaRaw);
            $resultado['instrumentos']          = $this->partirItemsEvaluacion($ieRaw);
            return $resultado;
        }

        $partes = preg_split('/\.\s+(?=[A-Z\xC1\xC9\xCD\xD3\xDA\xD1])/u', $ieRaw);
        foreach ($partes as $k => $v) {
            if ($k < count($partes) - 1) {
                $partes[$k] = $v . '.';
            }
        }

        if (count($partes) >= 3) {
            $instrIdx = count($partes) - 1;
            while ($instrIdx > 1 && mb_strlen($partes[$instrIdx]) < 80) {
                $instrIdx--;
            }
            $instrIdx++;

            $resultado['resultado_aprendizaje'] = $this->limpiarTexto(
                implode(' ', array_slice($partes, 0, 1))
            );
            $resultado['evidencia_aprendizaje'] = $this->limpiarTexto(
                implode(' ', array_slice($partes, 1, $instrIdx - 1))
            );
            $resultado['instrumentos'] = $this->partirItemsEvaluacion(
                implode(' ', array_slice($partes, $instrIdx))
            );
        } elseif (count($partes) === 2) {
            $resultado['resultado_aprendizaje'] = $this->limpiarTexto($partes[0]);
            $resultado['evidencia_aprendizaje'] = $this->limpiarTexto($partes[1]);
        } else {
            $resultado['resultado_aprendizaje'] = $this->limpiarTexto($ieRaw);
        }

        return $resultado;
    }

    private function partirItemsEvaluacion(string $texto): array
    {
        $texto = preg_replace('/\s+/u', ' ', trim($texto));
        $raw = preg_split('/(?<=[a-z\xE1\xE9\xED\xF3\xFA\xF1.,;])\s+(?=[A-Z\xC1\xC9\xCD\xD3\xDA\xD1])/u', $texto);
        return array_values(array_filter(
            array_map('trim', $raw),
            static fn($i) => mb_strlen($i) > 1
        ));
    }

    // =========================================================================
    // BIBLIOGRAFÍA ESTRUCTURADA
    // =========================================================================

    public function extraerBibliografiaEstructurada(string $texto): array
    {
        $referencias = [];

        if (!preg_match(
            '/Referencias?\s+bibliográficas?\s+([\s\S]*?)(?=Referencias?\s+digitales?|$)/i',
            $texto,
            $m
        )) {
            return $referencias;
        }

        $lineas = preg_split('/\r?\n/', $m[1]);
        $lineas = array_filter(array_map('trim', $lineas));
        $buffer = '';

        foreach ($lineas as $linea) {
            if (preg_match('/^(Autor|Año|Título|Lugar|Editorial|ISBN)\b/i', $linea)) continue;
            $buffer .= ' ' . $linea;
            if (preg_match('/\b(97[89][\d\-]{10,17})\b/', $buffer, $mIsbn)) {
                $ref = $this->parsearFilaBibliografia(trim($buffer), $mIsbn[1]);
                if ($ref !== null) $referencias[] = $ref;
                $buffer = '';
            }
        }

        if (trim($buffer) !== '') {
            $ref = $this->parsearFilaBibliografia(trim($buffer), null);
            if ($ref !== null) $referencias[] = $ref;
        }

        return $referencias;
    }

    private function parsearFilaBibliografia(string $fila, ?string $isbn): ?array
    {
        if (strlen($fila) < 20) return null;

        $ref = ['autor' => null, 'anio' => null, 'titulo' => null, 'lugar_publicacion' => null, 'editorial' => null, 'isbn' => $isbn];

        if (preg_match('/\b(19\d{2}|20\d{2})\b/', $fila, $mAnio)) {
            $ref['anio']  = $mAnio[1];
            $posAnio      = strpos($fila, $mAnio[1]);
            $ref['autor'] = $this->limpiarTexto(substr($fila, 0, $posAnio));
            $resto        = substr($fila, $posAnio + strlen($mAnio[1]));
        } else {
            $resto = $fila;
        }

        if ($isbn !== null) $resto = str_replace($isbn, '', $resto);

        $partesPais = preg_split(
            '/\s+(Estados\s+Unidos|Colombia|España|México|Argentina|Chile|Perú)\s+/i',
            trim($resto),
            2,
            PREG_SPLIT_DELIM_CAPTURE
        );

        if (count($partesPais) >= 3) {
            $ref['titulo']            = $this->limpiarTexto($partesPais[0]);
            $ref['lugar_publicacion'] = trim($partesPais[1]);
            $ref['editorial']         = $this->limpiarTexto($partesPais[2]);
        } else {
            $partes = array_values(array_filter(array_map('trim', explode('  ', preg_replace('/\s{2,}/', '  ', trim($resto))))));
            $ref['titulo']            = $partes[0] ?? null;
            $ref['lugar_publicacion'] = $partes[1] ?? null;
            $ref['editorial']         = $partes[2] ?? null;
        }

        return $ref;
    }

    // =========================================================================
    // VALIDACIÓN DE ESTRUCTURA
    // =========================================================================

    private function validarEstructuraPDF(array $pageTexts): array
    {
        $errores  = [];
        $detalles = [];

        if (!isset($pageTexts[1])) {
            return ['valido' => false, 'errores' => ['No se pudo leer la primera página.'], 'detalles' => []];
        }

        $faltantes = array_filter(
            $this->elementosPaginaUno,
            fn($el) => !$this->buscarTextoFlexible($pageTexts[1], $el)
        );

        if (!empty($faltantes)) {
            $errores[]                      = 'Página 1: faltan elementos obligatorios';
            $detalles['pagina_1_faltantes'] = array_values($faltantes);
        } else {
            $detalles['pagina_1'] = 'Válida';
        }

        $textoCompleto    = implode("\n", $pageTexts);
        $textoNormalizado = $this->normalizarTexto($textoCompleto);

        preg_match_all('/Unidad\s+de\s+Aprendizaje/i', $textoNormalizado, $matchesUnidades);
        $unidades = count($matchesUnidades[0]);

        if ($unidades < 3) {
            $errores[] = "Se requieren al menos 3 unidades de aprendizaje (encontradas: {$unidades})";
        } else {
            $detalles['unidades_aprendizaje'] = "{$unidades} unidades encontradas";
        }

        $tieneTemas = $this->buscarTextoFlexible($textoCompleto, 'Temas')
            && $this->buscarTextoFlexible($textoCompleto, 'Saber')
            && $this->buscarTextoFlexible($textoCompleto, 'Saber Hacer');

        $tieneTemas
            ? $detalles['temas'] = 'Encontrados'
            : $errores[] = 'No se encontraron temas con dimensiones (Saber, Saber Hacer)';

        $tieneEnsenanza = $this->buscarTextoFlexible($textoCompleto, 'Proceso Enseñanza-Aprendizaje')
            || ($this->buscarTextoFlexible($textoCompleto, 'Métodos y técnicas de enseñanza')
                && $this->buscarTextoFlexible($textoCompleto, 'Medios y materiales didácticos'));

        $tieneEnsenanza
            ? $detalles['proceso_ensenanza'] = 'Encontrado'
            : $errores[] = 'Falta sección: Proceso Enseñanza-Aprendizaje';

        $tieneEvaluacion = $this->buscarTextoFlexible($textoCompleto, 'Proceso de Evaluación')
            || ($this->buscarTextoFlexible($textoCompleto, 'Resultado de Aprendizaje')
                && $this->buscarTextoFlexible($textoCompleto, 'Evidencia de Aprendizaje'));

        $tieneEvaluacion
            ? $detalles['proceso_evaluacion'] = 'Encontrado'
            : $errores[] = 'Falta sección: Proceso de Evaluación';

        $tieneBibliografia = $this->buscarTextoFlexible($textoCompleto, 'Bibliografía')
            || $this->buscarTextoFlexible($textoCompleto, 'Referencias')
            || $this->buscarTextoFlexible($textoCompleto, 'Referencias Bibliográficas');

        $tieneBibliografia
            ? $detalles['bibliografia'] = 'Encontrada'
            : $errores[] = 'Falta sección: Bibliografía';

        return ['valido' => empty($errores), 'errores' => $errores, 'detalles' => $detalles];
    }

    // =========================================================================
    // HELPERS
    // =========================================================================

    public function normalizarTexto(string $texto): string
    {
        $texto = preg_replace('/[\x00-\x1F\x80-\xFF]/', ' ', $texto);
        $texto = preg_replace('/-\s*\n\s*/', '', $texto);
        $texto = preg_replace('/\r\n|\r|\n/', ' ', $texto);
        $texto = preg_replace('/\s+/', ' ', $texto);
        return trim($texto);
    }

    private function limpiarTexto(string $texto): string
    {
        $texto = preg_replace('/\r\n|\r|\n/', ' ', $texto);
        $texto = preg_replace('/\s{2,}/', ' ', $texto);
        return trim($texto);
    }

    private function buscarTextoFlexible(string $haystack, string $needle): bool
    {
        return stripos(
            $this->normalizarTexto($haystack),
            $this->normalizarTexto($needle)
        ) !== false;
    }

    private function iniciaConAlgunTermino(string $linea, array $terminos): bool
    {
        foreach ($terminos as $termino) {
            if (stripos(trim($linea), $termino) === 0) {
                return true;
            }
        }
        return false;
    }

    private function extraerItems(string $texto): array
    {
        $texto  = $this->limpiarTexto($texto);
        $lineas = preg_split('/[\n;]/', $texto);
        $items  = [];
        foreach ($lineas as $linea) {
            $linea = trim($linea, " \t\n\r\0\x0B-•·");
            if (strlen($linea) > 2) $items[] = $linea;
        }
        return array_values(array_unique($items));
    }

    private function extraerBibliografia(string $texto): array
    {
        $t    = $this->normalizarTexto($texto);
        $refs = [];
        if (preg_match('/bibliografía\s*[:\-]?\s*(.*)/i', $t, $m)) {
            $lineas = preg_split('/\.\s+/', $m[1]);
            foreach ($lineas as $l) {
                $l = trim($l);
                if (strlen($l) > 15) {
                    $refs[] = str_ends_with($l, '.') ? $l : $l . '.';
                }
            }
        }
        return array_slice($refs, 0, 10);
    }

    // =========================================================================
    // DATOS AUXILIARES
    // =========================================================================

    public function meses(): array
    {
        return [
            1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril', 5 => 'Mayo', 6 => 'Junio',
            7 => 'Julio', 8 => 'Agosto', 9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre',
        ];
    }

    public function aniosDisponibles(): array
    {
        return range(now()->year - 1, now()->year + 5);
    }
}
