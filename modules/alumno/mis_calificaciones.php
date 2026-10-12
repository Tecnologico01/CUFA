<?php
require_once __DIR__ . '/../../includes/db.php';

$usuarioId = (int)($_SESSION['user_id'] ?? 0);

if ($usuarioId <= 0) {
    echo "
        <div class='max-w-4xl mx-auto mt-10 p-6 bg-red-50
                    text-red-700 rounded-3xl font-bold'>
            Sesión no válida.
        </div>
    ";
    return;
}

/* =========================================================
   OBTENER ALUMNO DE LA SESIÓN
========================================================= */

$stmt = $pdo->prepare("
    SELECT
        a.id AS alumno_id,
        a.numero_control,
        a.grado,

        u.nombres,
        u.apellido_paterno,
        u.apellido_materno

    FROM alumnos a

    INNER JOIN usuarios u
        ON u.id = a.usuario_id

    WHERE a.usuario_id = ?

    LIMIT 1
");

$stmt->execute([$usuarioId]);
$alumno = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$alumno) {
    echo "
        <div class='max-w-4xl mx-auto mt-10 p-6 bg-red-50
                    text-red-700 rounded-3xl font-bold'>
            El usuario actual no está vinculado a un alumno.
        </div>
    ";
    return;
}

$alumnoId = (int)$alumno['alumno_id'];


/* =========================================================
   OBTENER ASIGNATURAS DEL ALUMNO
========================================================= */

$stmt = $pdo->prepare("
    SELECT DISTINCT

        ad.id AS asignacion_id,

        m.nombre AS materia,
        m.clave,

        g.nombre AS grupo,

        p.nombre AS periodo,

        CONCAT(
            u.nombres, ' ',
            COALESCE(u.apellido_paterno, ''), ' ',
            COALESCE(u.apellido_materno, '')
        ) AS docente

    FROM grupos_alumnos ga

    INNER JOIN asignaciones_docentes ad
        ON ad.grupo_id = ga.grupo_id
        AND ad.periodo_id = ga.periodo_id

    INNER JOIN materias m
        ON m.id = ad.materia_id

    INNER JOIN grupos g
        ON g.id = ad.grupo_id

    INNER JOIN periodos p
        ON p.id = ad.periodo_id

    LEFT JOIN docentes d
        ON d.id = ad.docente_id

    LEFT JOIN usuarios u
        ON u.id = d.usuario_id

    WHERE ga.alumno_id = ?

    ORDER BY m.nombre ASC
");

$stmt->execute([$alumnoId]);
$materias = $stmt->fetchAll(PDO::FETCH_ASSOC);


/* =========================================================
   OBTENER CALIFICACIONES
========================================================= */

$stmt = $pdo->prepare("
    SELECT

        cf.id,
        cf.asignacion_id,
        cf.unidad,

        cf.promedio_actividades,
        cf.calificacion_examen,
        cf.calificacion_final,

        cf.observaciones,
        cf.estado,

        m.nombre AS materia

    FROM calificaciones_finales cf

    INNER JOIN asignaciones_docentes ad
        ON ad.id = cf.asignacion_id

    INNER JOIN materias m
        ON m.id = ad.materia_id

    WHERE cf.alumno_id = ? AND cf.estado = 'aprobado'

    ORDER BY
        m.nombre ASC,
        cf.unidad ASC
");

$stmt->execute([$alumnoId]);
$registros = $stmt->fetchAll(PDO::FETCH_ASSOC);


/* =========================================================
   AGRUPAR CALIFICACIONES POR ASIGNACIÓN
========================================================= */

$calificaciones = [];

foreach ($registros as $registro) {

    $asignacionId = (int)$registro['asignacion_id'];

    if (!isset($calificaciones[$asignacionId])) {
        $calificaciones[$asignacionId] = [];
    }

    $calificaciones[$asignacionId][] = $registro;
}


/* =========================================================
   FUNCIÓN COLOR CALIFICACIÓN
========================================================= */

function colorCalificacion($calificacion)
{
    $calificacion = (float)$calificacion;

    if ($calificacion >= 70) {
        return 'text-emerald-600';
    }

    return 'text-red-600';
}
?>


<div class="max-w-7xl mx-auto px-6 py-10">

    <!-- =====================================================
         ENCABEZADO
    ====================================================== -->

    <div class="mb-10">

        <p class="text-xs font-black uppercase tracking-[0.30em]
                  text-indigo-500">
            Portal académico
        </p>

        <h1 class="text-4xl md:text-5xl font-black
                   text-slate-900 mt-3">

            Mis
            <span class="text-indigo-600">
                Calificaciones
            </span>

        </h1>

        <p class="text-slate-500 mt-4 text-lg">

            Consulta tus resultados académicos por asignatura
            y unidad.

        </p>

    </div>


    <!-- =====================================================
         INFORMACIÓN DEL ALUMNO
    ====================================================== -->

    <div class="bg-gradient-to-r from-indigo-600 to-purple-600
                rounded-3xl p-7 text-white mb-10 shadow-lg">

        <div class="flex flex-col md:flex-row
                    md:items-center md:justify-between gap-5">

            <div>

                <p class="text-indigo-100 text-xs
                          uppercase tracking-widest font-bold">

                    Alumno

                </p>

                <h2 class="text-2xl font-black mt-2">

                    <?= htmlspecialchars(
                        trim(
                            $alumno['nombres'] . ' ' .
                            $alumno['apellido_paterno'] . ' ' .
                            $alumno['apellido_materno']
                        )
                    ) ?>

                </h2>

                <p class="text-indigo-100 mt-2">

                    No. Control:
                    <strong>
                        <?= htmlspecialchars($alumno['numero_control']) ?>
                    </strong>

                </p>

            </div>

            <div class="bg-white/10 backdrop-blur-sm
                        px-6 py-4 rounded-2xl">

                <p class="text-xs uppercase tracking-widest
                          text-indigo-100 font-bold">

                    Asignaturas

                </p>

                <p class="text-3xl font-black mt-1">

                    <?= count($materias) ?>

                </p>

            </div>

        </div>

    </div>


    <!-- =====================================================
         SIN ASIGNATURAS
    ====================================================== -->

    <?php if (!$materias): ?>

        <div class="bg-amber-50 border border-amber-200
                    rounded-3xl p-8">

            <h2 class="font-black text-amber-800 text-xl">

                Sin asignaturas

            </h2>

            <p class="text-amber-700 mt-2">

                Actualmente no tienes asignaturas asociadas
                a tu grupo.

            </p>

        </div>

    <?php else: ?>


        <!-- =================================================
             ASIGNATURAS
        ================================================== -->

        <div class="space-y-8">

            <?php foreach ($materias as $materia): ?>

                <?php

                $asignacionId = (int)$materia['asignacion_id'];

                $calificacionesMateria =
                    $calificaciones[$asignacionId] ?? [];

                ?>


                <div class="bg-white border border-slate-100
                            rounded-3xl shadow-sm overflow-hidden">


                    <!-- =====================================
                         CABECERA ASIGNATURA
                    ====================================== -->

                    <div class="bg-slate-50 p-6
                                border-b border-slate-100">

                        <div class="flex flex-col md:flex-row
                                    md:items-center
                                    md:justify-between gap-4">

                            <div>

                                <p class="text-xs font-black
                                          uppercase tracking-widest
                                          text-indigo-500">

                                    <?= htmlspecialchars(
                                        $materia['clave'] ?? ''
                                    ) ?>

                                </p>

                                <h2 class="text-2xl font-black
                                           text-slate-800 mt-1">

                                    <?= htmlspecialchars(
                                        $materia['materia']
                                    ) ?>

                                </h2>

                                <p class="text-slate-500 mt-2">

                                    Docente:
                                    <strong>
                                        <?= htmlspecialchars(
                                            trim($materia['docente'])
                                            ?: 'Sin docente'
                                        ) ?>
                                    </strong>

                                </p>

                            </div>


                            <div class="flex gap-3 flex-wrap">

                                <span class="bg-indigo-100
                                             text-indigo-700
                                             px-4 py-2
                                             rounded-full
                                             text-xs font-black">

                                    Grupo
                                    <?= htmlspecialchars(
                                        $materia['grupo']
                                    ) ?>

                                </span>


                                <span class="bg-purple-100
                                             text-purple-700
                                             px-4 py-2
                                             rounded-full
                                             text-xs font-black">

                                    <?= htmlspecialchars(
                                        $materia['periodo']
                                    ) ?>

                                </span>

                            </div>

                        </div>

                    </div>


                    <!-- =====================================
                         SIN CALIFICACIONES
                    ====================================== -->

                    <?php if (!$calificacionesMateria): ?>

                        <div class="p-8 text-center">

                            <div class="text-4xl mb-3">
                                📋
                            </div>

                            <h3 class="font-black text-slate-700">

                                Sin calificaciones registradas

                            </h3>

                            <p class="text-slate-400 mt-2">

                                El docente todavía no ha registrado
                                calificaciones para esta asignatura.

                            </p>

                        </div>


                    <?php else: ?>


                        <!-- =================================
                             TABLA
                        ================================== -->

                        <div class="overflow-x-auto">

                            <table class="w-full">

                                <thead>

                                    <tr class="text-left
                                               bg-white
                                               text-slate-400
                                               text-xs uppercase
                                               tracking-widest">

                                        <th class="p-5">
                                            Unidad
                                        </th>

                                        <th class="p-5 text-center">
                                            Actividades
                                        </th>

                                        <th class="p-5 text-center">
                                            Examen
                                        </th>

                                        <th class="p-5 text-center">
                                            Final
                                        </th>

                                        <th class="p-5 text-center">
                                            Estado
                                        </th>

                                    </tr>

                                </thead>


                                <tbody>

                                    <?php foreach (
                                        $calificacionesMateria
                                        as $calificacion
                                    ): ?>

                                        <tr class="border-t
                                                   border-slate-100
                                                   hover:bg-slate-50">

                                            <td class="p-5">

                                                <span class="
                                                    bg-indigo-50
                                                    text-indigo-700
                                                    px-4 py-2
                                                    rounded-xl
                                                    font-black">

                                                    Unidad
                                                    <?= (int)$calificacion['unidad'] ?>

                                                </span>

                                            </td>


                                            <td class="p-5
                                                       text-center
                                                       font-bold
                                                       text-slate-700">

                                                <?= number_format(
                                                    (float)$calificacion['promedio_actividades'],
                                                    1
                                                ) ?>

                                            </td>


                                            <td class="p-5
                                                       text-center
                                                       font-bold
                                                       text-slate-700">

                                                <?= number_format(
                                                    (float)$calificacion['calificacion_examen'],
                                                    1
                                                ) ?>

                                            </td>


                                            <td class="p-5 text-center">

                                                <span class="
                                                    text-2xl
                                                    font-black
                                                    <?= colorCalificacion(
                                                        $calificacion['calificacion_final']
                                                    ) ?>
                                                ">

                                                    <?= number_format(
                                                        (float)$calificacion['calificacion_final'],
                                                        1
                                                    ) ?>

                                                </span>

                                            </td>


                                            <td class="p-5 text-center">

                                                <?php

                                                $estado =
                                                    $calificacion['estado']
                                                    ?? 'borrador';

                                                $colorEstado =
                                                    'bg-slate-100 text-slate-600';

                                                if ($estado === 'aprobado') {
                                                    $colorEstado =
                                                        'bg-emerald-100 text-emerald-700';
                                                }

                                                if ($estado === 'cerrado') {
                                                    $colorEstado =
                                                        'bg-blue-100 text-blue-700';
                                                }

                                                if ($estado === 'devuelto') {
                                                    $colorEstado =
                                                        'bg-amber-100 text-amber-700';
                                                }

                                                ?>

                                                <span class="
                                                    <?= $colorEstado ?>
                                                    px-3 py-2
                                                    rounded-full
                                                    text-xs
                                                    font-black
                                                    uppercase">

                                                    <?= htmlspecialchars(
                                                        $estado
                                                    ) ?>

                                                </span>

                                            </td>

                                        </tr>


                                        <?php if (
                                            !empty(
                                                $calificacion['observaciones']
                                            )
                                        ): ?>

                                            <tr>

                                                <td colspan="5"
                                                    class="px-5 pb-5">

                                                    <div class="
                                                        bg-amber-50
                                                        text-amber-700
                                                        rounded-xl
                                                        p-4
                                                        text-sm">

                                                        <strong>
                                                            Observación:
                                                        </strong>

                                                        <?= htmlspecialchars(
                                                            $calificacion['observaciones']
                                                        ) ?>

                                                    </div>

                                                </td>

                                            </tr>

                                        <?php endif; ?>


                                    <?php endforeach; ?>

                                </tbody>

                            </table>

                        </div>

                    <?php endif; ?>

                </div>

            <?php endforeach; ?>

        </div>

    <?php endif; ?>


    <!-- =====================================================
         REGRESAR
    ====================================================== -->

    <div class="mt-10">

        <a href="?modulo=alumno_panel"
           class="inline-flex items-center gap-2
                  bg-slate-100 hover:bg-slate-200
                  text-slate-700 font-bold
                  px-6 py-3 rounded-xl transition">

            ← Regresar al Portal Estudiantil

        </a>

    </div>

</div>