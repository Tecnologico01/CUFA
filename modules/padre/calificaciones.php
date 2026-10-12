<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../includes/db.php';

/*
|--------------------------------------------------------------------------
| CALIFICACIONES - PADRE DE FAMILIA
|--------------------------------------------------------------------------
| Este módulo es únicamente de consulta.
| El padre solo puede consultar alumnos vinculados a su usuario mediante
| la tabla padres_familia.
|--------------------------------------------------------------------------
*/


/*
|--------------------------------------------------------------------------
| 1. VALIDAR SESIÓN
|--------------------------------------------------------------------------
*/

if (
    !isset($_SESSION['user_id']) ||
    ($_SESSION['rol_slug'] ?? '') !== 'padre'
) {
    exit('Acceso no autorizado.');
}

$usuarioId = (int) $_SESSION['user_id'];
$alumnoId  = (int) ($_GET['alumno_id'] ?? 0);


/*
|--------------------------------------------------------------------------
| 2. OBTENER AUTOMÁTICAMENTE EL ALUMNO SI NO VIENE EN LA URL
|--------------------------------------------------------------------------
*/

if ($alumnoId <= 0) {

    $stmt = $pdo->prepare("
        SELECT alumno_id
        FROM padres_familia
        WHERE usuario_id = ?
        ORDER BY id ASC
        LIMIT 1
    ");

    $stmt->execute([$usuarioId]);

    $alumnoAsociado = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$alumnoAsociado) {
        ?>

        <div class="max-w-4xl mx-auto">

            <div class="bg-amber-50
                        border border-amber-200
                        text-amber-800
                        p-6
                        rounded-2xl">

                <h3 class="font-black text-lg">
                    No tienes alumnos asociados
                </h3>

                <p class="mt-2">
                    Tu cuenta de padre o tutor todavía no tiene
                    ningún alumno vinculado.
                </p>

                <a href="?modulo=padre_panel"
                   class="inline-block mt-4 px-5 py-3
                          bg-amber-100 rounded-xl font-bold">

                    Regresar

                </a>

            </div>

        </div>

        <?php
        return;
    }

    $alumnoId = (int) $alumnoAsociado['alumno_id'];
}


/*
|--------------------------------------------------------------------------
| 3. COMPROBAR QUE EL ALUMNO ESTÉ VINCULADO AL PADRE
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare("
    SELECT
        pf.id AS relacion_id,
        pf.relacion,

        a.id AS alumno_id,
        a.numero_control,
        a.grado,
        a.fecha_ingreso,

        u.id AS alumno_usuario_id,
        u.nombres,
        u.apellido_paterno,
        u.apellido_materno,
        u.email,

        c.nombre AS carrera

    FROM padres_familia pf

    INNER JOIN alumnos a
        ON a.id = pf.alumno_id

    INNER JOIN usuarios u
        ON u.id = a.usuario_id

    LEFT JOIN carreras c
        ON c.id = a.carrera_id

    WHERE pf.usuario_id = ?
      AND pf.alumno_id = ?

    LIMIT 1
");

$stmt->execute([
    $usuarioId,
    $alumnoId
]);

$alumno = $stmt->fetch(PDO::FETCH_ASSOC);


/*
|--------------------------------------------------------------------------
| 4. SI EL ALUMNO NO PERTENECE AL PADRE
|--------------------------------------------------------------------------
*/

if (!$alumno) {
    ?>

    <div class="max-w-4xl mx-auto">

        <div class="bg-red-50
                    border border-red-200
                    text-red-700
                    p-6
                    rounded-2xl">

            <h3 class="font-black text-lg">
                No tienes autorización para consultar este alumno.
            </h3>

            <p class="mt-2">
                El alumno solicitado no se encuentra asociado
                a tu cuenta de padre o tutor.
            </p>

            <div class="mt-4 bg-white/70 rounded-xl p-4 text-sm">

                <p>
                    <strong>Usuario padre:</strong>
                    <?= $usuarioId ?>
                </p>

                <p>
                    <strong>Alumno solicitado:</strong>
                    <?= $alumnoId ?>
                </p>

            </div>

            <a href="?modulo=padre_panel"
               class="inline-block mt-5 px-5 py-3
                      bg-red-100 rounded-xl font-bold">

                Regresar

            </a>

        </div>

    </div>

    <?php
    return;
}


/*
|--------------------------------------------------------------------------
| 5. OBTENER CALIFICACIONES FINALES
|--------------------------------------------------------------------------
|
| calificaciones_finales:
|
| asignacion_id
| alumno_id
| unidad
| promedio_actividades
| calificacion_examen
| calificacion_final
| observaciones
| estado
| fecha_registro
|
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare("
    SELECT
        cf.id,
        cf.asignacion_id,
        cf.alumno_id,
        cf.unidad,
        cf.promedio_actividades,
        cf.calificacion_examen,
        cf.calificacion_final,
        cf.observaciones,
        cf.estado,
        cf.fecha_registro,

        ad.materia_id,
        ad.grupo_id,
        ad.periodo_id,

        m.nombre AS materia,
        m.clave AS materia_clave,

        g.nombre AS grupo,

        p.nombre AS periodo

    FROM calificaciones_finales cf

    INNER JOIN asignaciones_docentes ad
        ON ad.id = cf.asignacion_id

    INNER JOIN materias m
        ON m.id = ad.materia_id

    LEFT JOIN grupos g
        ON g.id = ad.grupo_id

    LEFT JOIN periodos p
        ON p.id = ad.periodo_id

    WHERE cf.alumno_id = ? AND cf.estado = 'aprobado'

    ORDER BY
        m.nombre ASC,
        cf.unidad ASC
");

$stmt->execute([$alumnoId]);

$registros = $stmt->fetchAll(PDO::FETCH_ASSOC);


/*
|--------------------------------------------------------------------------
| 6. AGRUPAR CALIFICACIONES POR ASIGNACIÓN / MATERIA
|--------------------------------------------------------------------------
*/

$materias = [];

foreach ($registros as $registro) {

    $asignacionId = (int) $registro['asignacion_id'];

    if (!isset($materias[$asignacionId])) {

        $materias[$asignacionId] = [
            'asignacion_id' => $asignacionId,
            'materia_id'    => $registro['materia_id'],
            'materia'       => $registro['materia'],
            'clave'         => $registro['materia_clave'],
            'grupo'         => $registro['grupo'],
            'periodo'       => $registro['periodo'],
            'unidades'      => []
        ];
    }

    $materias[$asignacionId]['unidades'][] = $registro;
}


/*
|--------------------------------------------------------------------------
| 7. NOMBRE COMPLETO DEL ALUMNO
|--------------------------------------------------------------------------
*/

$nombreCompleto = trim(
    ($alumno['nombres'] ?? '') . ' ' .
    ($alumno['apellido_paterno'] ?? '') . ' ' .
    ($alumno['apellido_materno'] ?? '')
);

?>

<div class="max-w-7xl mx-auto space-y-8">


    <!-- ==========================================================
         ENCABEZADO
    =========================================================== -->

    <div class="flex flex-col md:flex-row
                md:items-center md:justify-between gap-5">

        <div>

            <span class="inline-flex items-center
                         bg-indigo-100
                         text-indigo-700
                         px-4 py-2
                         rounded-full
                         text-xs
                         font-black
                         uppercase
                         tracking-wider">

                Seguimiento académico

            </span>

            <h2 class="text-3xl md:text-4xl
                       font-black
                       text-slate-800
                       mt-4">

                Calificaciones

            </h2>

            <p class="text-slate-500 mt-2">

                Consulta las calificaciones registradas de

                <strong class="text-slate-700">

                    <?= htmlspecialchars($nombreCompleto) ?>

                </strong>

            </p>

        </div>


        <a href="?modulo=padre_panel"
           class="inline-flex items-center justify-center
                  px-5 py-3
                  bg-slate-100
                  hover:bg-slate-200
                  text-slate-700
                  rounded-xl
                  font-bold
                  transition">

            ← Regresar

        </a>

    </div>



    <!-- ==========================================================
         INFORMACIÓN GENERAL DEL ALUMNO
    =========================================================== -->

    <div class="bg-gradient-to-br
                from-indigo-50
                to-purple-50
                border
                border-indigo-100
                rounded-3xl
                p-6 md:p-8">

        <div class="flex flex-col md:flex-row
                    md:items-center
                    md:justify-between
                    gap-6">


            <!-- NOMBRE -->

            <div>

                <p class="text-xs
                          uppercase
                          tracking-widest
                          font-black
                          text-indigo-500">

                    <?= htmlspecialchars(
                        $alumno['relacion'] ?: 'Alumno asociado'
                    ) ?>

                </p>


                <h3 class="text-2xl
                           md:text-3xl
                           font-black
                           text-indigo-950
                           mt-2">

                    <?= htmlspecialchars($nombreCompleto) ?>

                </h3>


                <p class="text-indigo-500 mt-2">

                    No. Control:

                    <strong>

                        <?= htmlspecialchars(
                            $alumno['numero_control']
                            ?: 'No registrado'
                        ) ?>

                    </strong>

                </p>

            </div>


            <!-- INICIAL -->

            <div class="w-16 h-16
                        rounded-2xl
                        bg-white
                        shadow-sm
                        flex
                        items-center
                        justify-center
                        text-2xl
                        font-black
                        text-indigo-600">

                <?= htmlspecialchars(
                    strtoupper(
                        substr(
                            $alumno['nombres'] ?? 'A',
                            0,
                            1
                        )
                    )
                ) ?>

            </div>

        </div>



        <!-- DATOS -->

        <div class="grid
                    grid-cols-1
                    sm:grid-cols-2
                    lg:grid-cols-4
                    gap-4
                    mt-8">


            <!-- CARRERA -->

            <div class="bg-white
                        rounded-2xl
                        p-5">

                <p class="text-xs
                          uppercase
                          tracking-wider
                          font-bold
                          text-slate-400">

                    Carrera

                </p>

                <p class="font-black
                          text-slate-700
                          mt-2">

                    <?= htmlspecialchars(
                        $alumno['carrera']
                        ?: 'Sin asignar'
                    ) ?>

                </p>

            </div>



            <!-- GRADO -->

            <div class="bg-white
                        rounded-2xl
                        p-5">

                <p class="text-xs
                          uppercase
                          tracking-wider
                          font-bold
                          text-slate-400">

                    Grado

                </p>

                <p class="font-black
                          text-slate-700
                          mt-2">

                    <?= htmlspecialchars(
                        $alumno['grado']
                        ?: 'No registrado'
                    ) ?>

                </p>

            </div>



            <!-- FECHA INGRESO -->

            <div class="bg-white
                        rounded-2xl
                        p-5">

                <p class="text-xs
                          uppercase
                          tracking-wider
                          font-bold
                          text-slate-400">

                    Fecha de ingreso

                </p>

                <p class="font-black
                          text-slate-700
                          mt-2">

                    <?= htmlspecialchars(
                        $alumno['fecha_ingreso']
                        ?: 'No registrada'
                    ) ?>

                </p>

            </div>



            <!-- RELACIÓN -->

            <div class="bg-white
                        rounded-2xl
                        p-5">

                <p class="text-xs
                          uppercase
                          tracking-wider
                          font-bold
                          text-slate-400">

                    Relación

                </p>

                <p class="font-black
                          text-slate-700
                          mt-2">

                    <?= htmlspecialchars(
                        $alumno['relacion']
                        ?: 'Tutor'
                    ) ?>

                </p>

            </div>

        </div>

    </div>



    <!-- ==========================================================
         SI NO EXISTEN CALIFICACIONES
    =========================================================== -->

    <?php if (empty($materias)): ?>

        <div class="bg-white
                    border
                    border-slate-100
                    rounded-3xl
                    p-10
                    md:p-14
                    text-center
                    shadow-sm">

            <div class="w-16 h-16
                        mx-auto
                        bg-slate-100
                        rounded-2xl
                        flex
                        items-center
                        justify-center
                        text-3xl">

                📚

            </div>

            <h3 class="text-xl
                       font-black
                       text-slate-700
                       mt-5">

                Sin calificaciones registradas

            </h3>

            <p class="text-slate-400 mt-2">

                Todavía no existen calificaciones
                registradas para este alumno.

            </p>

        </div>

    <?php endif; ?>



    <!-- ==========================================================
         RECORRER MATERIAS
    =========================================================== -->

    <?php foreach ($materias as $materia): ?>


        <?php

        /*
        |--------------------------------------------------------------------------
        | CALCULAR PROMEDIO DE LA MATERIA
        |--------------------------------------------------------------------------
        */

        $sumaCalificaciones = 0;
        $cantidadCalificaciones = 0;

        foreach ($materia['unidades'] as $unidad) {

            if ($unidad['calificacion_final'] !== null) {

                $sumaCalificaciones +=
                    (float) $unidad['calificacion_final'];

                $cantidadCalificaciones++;
            }
        }

        $promedioMateria =
            $cantidadCalificaciones > 0
                ? $sumaCalificaciones / $cantidadCalificaciones
                : 0;

        ?>


        <div class="bg-white
                    border
                    border-slate-100
                    rounded-3xl
                    shadow-sm
                    overflow-hidden">


            <!-- ==================================================
                 ENCABEZADO DE LA MATERIA
            =================================================== -->

            <div class="p-6 md:p-7
                        border-b
                        border-slate-100
                        flex
                        flex-col
                        md:flex-row
                        md:items-center
                        md:justify-between
                        gap-5">


                <div>

                    <p class="text-xs
                              font-black
                              uppercase
                              tracking-widest
                              text-indigo-500">

                        <?= htmlspecialchars(
                            $materia['clave']
                            ?: 'Materia'
                        ) ?>

                    </p>


                    <h3 class="text-xl
                               md:text-2xl
                               font-black
                               text-slate-800
                               mt-1">

                        <?= htmlspecialchars(
                            $materia['materia']
                        ) ?>

                    </h3>


                    <div class="flex flex-wrap
                                gap-x-5 gap-y-2
                                mt-3
                                text-sm
                                text-slate-400">


                        <span>

                            Grupo:

                            <strong class="text-slate-600">

                                <?= htmlspecialchars(
                                    $materia['grupo']
                                    ?: 'Sin grupo'
                                ) ?>

                            </strong>

                        </span>


                        <span>

                            Periodo:

                            <strong class="text-slate-600">

                                <?= htmlspecialchars(
                                    $materia['periodo']
                                    ?: 'Sin periodo'
                                ) ?>

                            </strong>

                        </span>

                    </div>

                </div>



                <!-- PROMEDIO -->

                <div class="min-w-[140px]
                            text-center
                            bg-indigo-50
                            px-6
                            py-4
                            rounded-2xl">

                    <p class="text-xs
                              uppercase
                              font-black
                              tracking-wider
                              text-indigo-400">

                        Promedio

                    </p>


                    <p class="text-3xl
                              font-black
                              mt-1
                              <?=
                                $promedioMateria >= 70
                                    ? 'text-emerald-600'
                                    : 'text-red-500'
                              ?>">

                        <?= number_format(
                            $promedioMateria,
                            1
                        ) ?>

                    </p>

                </div>

            </div>



            <!-- ==================================================
                 TABLA DE CALIFICACIONES
            =================================================== -->

            <div class="overflow-x-auto">

                <table class="w-full min-w-[750px]">

                    <thead class="bg-slate-50">

                        <tr class="text-left
                                   text-xs
                                   uppercase
                                   tracking-wider
                                   text-slate-400">

                            <th class="p-4">
                                Unidad
                            </th>

                            <th class="p-4 text-center">
                                Actividades
                            </th>

                            <th class="p-4 text-center">
                                Examen
                            </th>

                            <th class="p-4 text-center">
                                Calificación final
                            </th>

                            <th class="p-4 text-center">
                                Estado
                            </th>

                            <th class="p-4">
                                Observaciones
                            </th>

                        </tr>

                    </thead>


                    <tbody>


                        <?php foreach (
                            $materia['unidades']
                            as $unidad
                        ): ?>


                            <?php

                            $promedioActividades =
                                (float) $unidad['promedio_actividades'];

                            $calificacionExamen =
                                (float) $unidad['calificacion_examen'];

                            $calificacionFinal =
                                (float) $unidad['calificacion_final'];

                            $estado =
                                $unidad['estado']
                                ?? 'borrador';

                            ?>


                            <tr class="border-t
                                       border-slate-100
                                       hover:bg-slate-50/50
                                       transition">


                                <!-- UNIDAD -->

                                <td class="p-4">

                                    <span class="inline-flex
                                                 items-center
                                                 justify-center
                                                 bg-indigo-50
                                                 text-indigo-700
                                                 px-3
                                                 py-2
                                                 rounded-xl
                                                 font-black">

                                        Unidad
                                        <?= (int) $unidad['unidad'] ?>

                                    </span>

                                </td>



                                <!-- ACTIVIDADES -->

                                <td class="p-4
                                           text-center
                                           font-bold
                                           text-slate-600">

                                    <?= number_format(
                                        $promedioActividades,
                                        1
                                    ) ?>

                                </td>



                                <!-- EXAMEN -->

                                <td class="p-4
                                           text-center
                                           font-bold
                                           text-slate-600">

                                    <?= number_format(
                                        $calificacionExamen,
                                        1
                                    ) ?>

                                </td>



                                <!-- FINAL -->

                                <td class="p-4 text-center">

                                    <span class="text-xl
                                                 font-black
                                                 <?=
                                                    $calificacionFinal >= 70
                                                        ? 'text-emerald-600'
                                                        : 'text-red-500'
                                                 ?>">

                                        <?= number_format(
                                            $calificacionFinal,
                                            1
                                        ) ?>

                                    </span>

                                </td>



                                <!-- ESTADO -->

                                <td class="p-4 text-center">


                                    <?php if (
                                        $estado === 'aprobado'
                                    ): ?>


                                        <span class="inline-flex
                                                     bg-emerald-50
                                                     text-emerald-700
                                                     px-3 py-1
                                                     rounded-full
                                                     text-xs
                                                     font-bold">

                                            Aprobado

                                        </span>


                                    <?php elseif (
                                        $estado === 'cerrado'
                                    ): ?>


                                        <span class="inline-flex
                                                     bg-blue-50
                                                     text-blue-700
                                                     px-3 py-1
                                                     rounded-full
                                                     text-xs
                                                     font-bold">

                                            Cerrado

                                        </span>


                                    <?php else: ?>


                                        <span class="inline-flex
                                                     bg-amber-50
                                                     text-amber-700
                                                     px-3 py-1
                                                     rounded-full
                                                     text-xs
                                                     font-bold">

                                            Borrador

                                        </span>


                                    <?php endif; ?>


                                </td>



                                <!-- OBSERVACIONES -->

                                <td class="p-4
                                           text-sm
                                           text-slate-500">

                                    <?= htmlspecialchars(
                                        $unidad['observaciones']
                                        ?: 'Sin observaciones'
                                    ) ?>

                                </td>


                            </tr>


                        <?php endforeach; ?>


                    </tbody>

                </table>

            </div>

        </div>


    <?php endforeach; ?>


</div>