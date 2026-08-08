<?php

namespace App\Support;

/**
 * Catálogo fijo de estrategias didácticas por fase (punto 35 del instructivo).
 * Debe coincidir exactamente con src/config/estrategias.js del frontend.
 */
class EstrategiasCatalogo
{
    public static function porFase(string $fase): array
    {
        return match ($fase) {
            'apertura' => self::apertura(),
            'desarrollo' => self::desarrollo(),
            'cierre' => self::cierre(),
            default => [],
        };
    }

    private static function apertura(): array
    {
        return [
            'Preguntas: generadoras, guía, exploratorias, literales o intercaladas',
            'SQA (¿Qué sé?, ¿Qué quiero saber?, ¿Qué aprendí?)',
            'Identificación de expectativas',
            'Lluvia de ideas',
            'Análisis de artículos, documentos, anécdotas, noticias, hechos históricos o material audiovisual',
            'Dinámicas de presentación, activación, rompehielos, integración, etc.',
            'Analogía',
            'Clase magistral / Técnica expositiva',
            'Mapa mental o conceptual',
            'Diagramas (Causa-efecto, de flujo, de árbol, radial, jerárquico, de Venn)',
            'Tabla relacional',
            'Esquema',
            'Red semántica',
            'Cuadro sinóptico o comparativo',
            'Línea de tiempo',
            'Organigrama',
            'Constelación de palabras',
            'Árbol de problemas',
            'Secuencia de hechos',
            'Análisis de evidencias (gráficos, esquemas, diagramas, cuadros de doble entrada, etc.)',
            'Matriz de clasificación y/o de inducción',
            'Asistencia a conferencia',
            'Entrevista',
            'Visita a empresa o sitios de interés',
            'Lectura de documentos',
            'Lectura comentada',
            'Investigación',
            'Webquest (investigación por medios electrónicos)',
            'Presentación multimedia',
            'Cuestionario',
            'Asamblea',
            'Participación en congresos, coloquios, foros, simposios, seminarios, panel de mesa redonda',
            'Diálogo Philips 6 6',
            'Resumen',
            'Subrayado',
            'Cartografía conceptual',
            'Elaboración de carteles',
            'Demostración',
            'Tutoría de pares',
            'Murmullos o diálogos simultáneos',
            'Ejercicios escritos',
            'Ejercicios para ciencias exactas (problemas de matemáticas, física, química, etc.)',
            'Organizadores de información',
            'Clasificación de conceptos',
            'Análisis de semejanzas y diferencias',
            'Análisis de ventajas y desventajas',
            'Proyectos de investigación',
            'Grupos focales',
            'Debate',
            'Correlación',
            'Ensayo',
            'QQQ (¿Qué veo?, ¿Qué no veo?, ¿Qué infiero?)',
            'Síntesis',
            'V Heurística',
            'Práctica guiada',
            'Práctica semiguiada y/o modelada (demostración)',
        ];
    }

    private static function desarrollo(): array
    {
        return [
            'Dramatización', 'Estudio de casos (EC)', 'Debate', 'Foro', 'Panel', 'Simposio',
            'Seminario', 'Mesa redonda', 'Coloquio', 'Ensayo', 'Taller', 'Tutoría de pares',
            'Aprendizaje cooperativo', 'Aprendizaje basado en problemas (ABP)', 'Aprendizaje por proyectos',
            'Simulación (AFP)', 'Juego de roles', 'Aprendizaje situado (trabajo de campo)',
            'Prácticas de laboratorio', 'Grupos focales', 'Estancias y estadías',
        ];
    }

    private static function cierre(): array
    {
        return [
            'Cuestionario para reflexionar sobre lo aprendido',
            'SQA (¿Qué sé?, ¿Qué quiero saber?, ¿Qué aprendí?)',
            'Presentación multimedia', 'Presentación de resultados de ABP, APP o EC',
            'Mapa mental o conceptual', 'Diagrama causa-efecto', 'Tabla relacional', 'Esquema',
            'Red semántica', 'Cuadro sinóptico', 'Cuadro comparativo', 'Ensayo', 'Video testimonial',
            'Análisis de artículos, anécdotas, hechos históricos, o material audiovisual',
            'Debate', 'Foro', 'Simposio', 'Seminario', 'Coloquio', 'Panel', 'Mesa redonda',
            'Presentación y análisis de reporte de prácticas', 'Seguimiento por pares',
        ];
    }
}
