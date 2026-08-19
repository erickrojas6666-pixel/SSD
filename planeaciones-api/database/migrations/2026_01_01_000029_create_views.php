<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function down(): void
    {
        DB::statement('DROP VIEW IF EXISTS vw_actividad_detalle');
        DB::statement('DROP VIEW IF EXISTS vw_fase_detalle');
        DB::statement('DROP VIEW IF EXISTS vw_evidencia_detalle');
        DB::statement('DROP VIEW IF EXISTS vw_tema_detalle');
        DB::statement('DROP VIEW IF EXISTS vw_unidad_detalle');
        DB::statement('DROP VIEW IF EXISTS vw_secuencia_resumen');
    }

    public function up(): void
    {
        // -----------------------------------------------------------------
        // vw_secuencia_resumen: para listados (búsqueda por carrera, periodo,
        // asignatura, docente, estatus) sin joins manuales en el controlador
        // -----------------------------------------------------------------
        DB::statement("
            CREATE VIEW vw_secuencia_resumen AS
            SELECT
                s.id,
                s.periodo,
                s.estado,
                s.carrera_id,
                c.nombre AS carrera,
                s.especialidad_id,
                e.nombre AS especialidad,
                s.asignatura_id,
                a.nombre AS asignatura,
                a.cuatrimestre_id,
                cu.numero AS cuatrimestre_numero,
                GROUP_CONCAT(DISTINCT CONCAT(u.nombre, ' ', u.apellido_paterno) SEPARATOR ', ') AS docentes,
                s.fecha_solicitud_revision,
                s.fecha_validacion,
                s.created_at
            FROM secuencias s
            JOIN carreras c ON c.id = s.carrera_id
            JOIN especialidades e ON e.id = s.especialidad_id
            JOIN asignaturas a ON a.id = s.asignatura_id
            JOIN cuatrimestres cu ON cu.id = a.cuatrimestre_id
            LEFT JOIN secuencia_user su ON su.secuencia_id = s.id
            LEFT JOIN users u ON u.id = su.user_id
            GROUP BY s.id
        ");

        // -----------------------------------------------------------------
        // vw_unidad_detalle: Sección B resuelta (unidad + encabezado de
        // evaluación de la sección C + estatus de revisión de la unidad)
        // -----------------------------------------------------------------
        DB::statement("
            CREATE VIEW vw_unidad_detalle AS
            SELECT
                un.id,
                un.secuencia_id,
                un.numero,
                un.nombre,
                un.proposito_esperado,
                un.horas_saber,
                un.horas_saber_hacer,
                un.horas_totales,
                un.porcentaje_unidad,
                ev.periodo_semanas,
                ev.resultado_aprendizaje,
                r.aprobado AS revisor_aprobado,
                r.comentario AS revisor_comentario,
                r.revisor_id,
                r.fecha_revision
            FROM secuencia_unidades un
            LEFT JOIN secuencia_unidad_evaluaciones ev ON ev.unidad_id = un.id
            LEFT JOIN revisiones r
                ON r.revisable_type = 'secuencia_unidad' AND r.revisable_id = un.id
        ");

        // -----------------------------------------------------------------
        // vw_tema_detalle: Sección B, temas por unidad (Saber/Saber Hacer/
        // Ser y Convivir) con su estatus de revisión
        // -----------------------------------------------------------------
        DB::statement("
            CREATE VIEW vw_tema_detalle AS
            SELECT
                t.id,
                t.unidad_id,
                t.tema,
                t.saber,
                t.saber_hacer,
                t.ser_convivir,
                t.orden,
                r.aprobado AS revisor_aprobado,
                r.comentario AS revisor_comentario,
                r.revisor_id,
                r.fecha_revision
            FROM secuencia_unidad_temas t
            LEFT JOIN revisiones r
                ON r.revisable_type = 'secuencia_unidad_tema' AND r.revisable_id = t.id
        ");

        // -----------------------------------------------------------------
        // vw_evidencia_detalle: Sección C, evidencias con sus tipos de
        // evaluación combinados (auto/co/hetero) y estatus de revisión
        // -----------------------------------------------------------------
        DB::statement("
            CREATE VIEW vw_evidencia_detalle AS
            SELECT
                ev.id,
                ev.unidad_id,
                ev.evidencia_aprendizaje,
                ev.ponderacion,
                ev.instrumento_evaluacion,
                ev.orden,
                GROUP_CONCAT(DISTINCT te.nombre SEPARATOR ', ') AS tipos_evaluacion,
                r.aprobado AS revisor_aprobado,
                r.comentario AS revisor_comentario,
                r.revisor_id,
                r.fecha_revision
            FROM secuencia_unidad_evidencias ev
            LEFT JOIN evidencia_tipo_evaluacion ete ON ete.evidencia_id = ev.id
            LEFT JOIN tipos_evaluacion te ON te.id = ete.tipo_evaluacion_id
            LEFT JOIN revisiones r
                ON r.revisable_type = 'secuencia_unidad_evidencia' AND r.revisable_id = ev.id
            GROUP BY ev.id
        ");

        // -----------------------------------------------------------------
        // vw_fase_detalle: Sección D, encabezado de cada fase (apertura/
        // desarrollo/cierre) con su estatus de revisión y conteo de actividades
        // -----------------------------------------------------------------
        DB::statement("
            CREATE VIEW vw_fase_detalle AS
            SELECT
                f.id,
                f.unidad_id,
                f.fase,
                COUNT(act.id) AS total_actividades,
                r.aprobado AS revisor_aprobado,
                r.comentario AS revisor_comentario,
                r.revisor_id,
                r.fecha_revision
            FROM secuencia_unidad_fases f
            LEFT JOIN secuencia_fase_actividades act ON act.fase_id = f.id
            LEFT JOIN revisiones r
                ON r.revisable_type = 'secuencia_unidad_fase' AND r.revisable_id = f.id
            GROUP BY f.id
        ");

        // -----------------------------------------------------------------
        // vw_actividad_detalle: Sección D, actividades numeradas de cada
        // fase, con el nombre de la fase y su estatus de revisión
        // -----------------------------------------------------------------
        DB::statement("
            CREATE VIEW vw_actividad_detalle AS
            SELECT
                act.id,
                act.fase_id,
                f.unidad_id,
                f.fase,
                act.numero,
                act.metodos_tecnicas,
                act.actividades_docente,
                act.actividades_estudiante,
                act.evidencia_aprendizaje,
                act.medios_materiales,
                r.aprobado AS revisor_aprobado,
                r.comentario AS revisor_comentario,
                r.revisor_id,
                r.fecha_revision
            FROM secuencia_fase_actividades act
            JOIN secuencia_unidad_fases f ON f.id = act.fase_id
            LEFT JOIN revisiones r
                ON r.revisable_type = 'secuencia_fase_actividad' AND r.revisable_id = act.id
        ");
    }
};
