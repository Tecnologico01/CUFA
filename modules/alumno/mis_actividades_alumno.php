<?php

require_once __DIR__ . '/../../includes/db.php';

/*
|--------------------------------------------------------------------------
| VALIDAR SESIÓN
|--------------------------------------------------------------------------
*/

$usuario_id = (int)($_SESSION['user_id'] ?? 0);

if ($usuario_id <= 0) {
    echo "
    <div class='bg-red-50 border border-red-200 text-red-700 p-6 rounded-3xl'>
        <strong>Sesión no válida.</strong><br>
        Inicia sesión nuevamente.
    </div>
    ";
    return;
}

/*
|--------------------------------------------------------------------------
| OBTENER ALUMNO
|--------------------------------------------------------------------------
*/

$stmtAlumno = $pdo->prepare("
    SELECT
        a.id AS alumno_id,
        a.numero_control,
        a.carrera_id,
        a.grado
    FROM alumnos a
    WHERE a.usuario_id = ?
    LIMIT 1
");

$stmtAlumno->execute([$usuario_id]);

$alumno = $stmtAlumno->fetch(PDO::FETCH_ASSOC);

if (!$alumno) {
    echo "
    <div class='bg-red-50 border border-red-200 text-red-700 p-6 rounded-3xl'>
        <strong>No existe un alumno vinculado a este usuario.</strong>
    </div>
    ";
    return;
}

$alumno_id = (int)$alumno['alumno_id'];

/*
|--------------------------------------------------------------------------
| OBTENER GRUPO ACTUAL DEL ALUMNO
|--------------------------------------------------------------------------
|
| Se obtiene la relación más reciente del alumno con un grupo.
|
*/

$stmtGrupo = $pdo->prepare("
    SELECT
        ga.id AS grupo_alumno_id,
        ga.grupo_id,
        ga.periodo_id,
        g.nombre AS grupo
    FROM grupos_alumnos ga

    INNER JOIN grupos g
        ON g.id = ga.grupo_id

    WHERE ga.alumno_id = ?

    ORDER BY ga.id DESC
    LIMIT 1
");

$stmtGrupo->execute([$alumno_id]);

$grupo = $stmtGrupo->fetch(PDO::FETCH_ASSOC);

if (!$grupo) {
    echo "
    <div class='bg-yellow-50 border border-yellow-200 text-yellow-700 p-6 rounded-3xl'>
        <strong>No estás asignado a ningún grupo actualmente.</strong>
        <p class='mt-2'>
            Contacta a Control Escolar para verificar tu asignación.
        </p>
    </div>
    ";
    return;
}

$grupo_id = (int)$grupo['grupo_id'];
$periodo_id = isset($grupo['periodo_id'])
    ? (int)$grupo['periodo_id']
    : 0;

/*
|--------------------------------------------------------------------------
| OBTENER MATERIAS DEL ALUMNO
|--------------------------------------------------------------------------
|
| IMPORTANTE:
|
| No basta con buscar materias por grupo.
| También se utiliza el periodo de grupos_alumnos cuando está disponible,
| para evitar mostrar asignaciones antiguas del mismo grupo.
|
*/

$sqlMaterias = "
    SELECT
        ad.id AS asignacion_id,

        m.id AS materia_id,
        m.nombre,

        CONCAT_WS(
            ' ',
            u.nombres,
            u.apellido_paterno,
            u.apellido_materno
        ) AS docente,

        COUNT(DISTINCT a.id) AS total_actividades

    FROM asignaciones_docentes ad

    INNER JOIN materias m
        ON m.id = ad.materia_id

    INNER JOIN docentes d
        ON d.id = ad.docente_id

    INNER JOIN usuarios u
        ON u.id = d.usuario_id

    LEFT JOIN actividades a
        ON a.asignacion_id = ad.id
        AND a.activo = 1

    LEFT JOIN temas_materia tm
        ON tm.id = a.tema_id

    WHERE ad.grupo_id = ?
";

$parametros = [$grupo_id];

/*
|--------------------------------------------------------------------------
| FILTRAR POR PERIODO
|--------------------------------------------------------------------------
*/

if ($periodo_id > 0) {
    $sqlMaterias .= "
        AND ad.periodo_id = ?
    ";

    $parametros[] = $periodo_id;
}

/*
|--------------------------------------------------------------------------
| FILTRAR ACTIVIDADES VISIBLES
|--------------------------------------------------------------------------
|
| Una materia puede aparecer aunque todavía no tenga actividades.
| Por eso permitimos tm.estado IS NULL.
|
*/

$sqlMaterias .= "

    AND (
        tm.estado = 'aprobado'
        OR tm.estado = 'enviado'
        OR tm.estado IS NULL
    )

    GROUP BY
        ad.id,
        m.id,
        m.nombre,
        u.nombres,
        u.apellido_paterno,
        u.apellido_materno

    ORDER BY m.nombre ASC
";

$stmtMaterias = $pdo->prepare($sqlMaterias);
$stmtMaterias->execute($parametros);

$materias = $stmtMaterias->fetchAll(PDO::FETCH_ASSOC);

?>

<div class="max-w-7xl mx-auto">

    <!-- =========================================================
         HEADER
    ========================================================== -->

    <div class="mb-14">

        <div class="flex flex-col lg:flex-row justify-between gap-8">

            <div>

                <span class="text-[10px] uppercase tracking-[0.4em]
                             text-indigo-500 font-black">
                    Portal Académico
                </span>

                <h1 class="text-5xl font-black tracking-tighter
                           text-slate-900 mt-3">

                    Mis
                    <span class="text-indigo-600">
                        Asignaturas
                    </span>

                </h1>

                <p class="text-slate-500 mt-5 max-w-2xl leading-relaxed">

                    Selecciona una asignatura para visualizar las
                    actividades, materiales y entregas correspondientes.

                </p>

            </div>


            <!-- INFORMACIÓN DEL GRUPO -->

            <div class="bg-white border border-slate-200
                        rounded-[2rem] shadow-xl
                        px-8 py-6 min-w-[240px]">

                <p class="text-[10px] uppercase tracking-[0.3em]
                          text-slate-400 font-black">
                    Grupo Actual
                </p>

                <h2 class="text-3xl font-black
                           text-indigo-600 mt-3">

                    <?= htmlspecialchars(
                        $grupo['grupo'],
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>

                </h2>

                <div class="mt-5 border-t
                            border-slate-100 pt-5">

                    <p class="text-sm text-slate-500">
                        Asignaturas Disponibles
                    </p>

                    <h3 class="text-4xl font-black
                               text-slate-800 mt-1">

                        <?= count($materias) ?>

                    </h3>

                </div>

            </div>

        </div>

    </div>


    <!-- =========================================================
         SIN MATERIAS
    ========================================================== -->

    <?php if (empty($materias)): ?>

        <div class="bg-white border border-slate-200
                    rounded-[3rem] p-16 text-center">

            <div class="w-20 h-20 mx-auto
                        rounded-[2rem]
                        bg-indigo-50
                        flex items-center justify-center
                        text-4xl mb-6">

                📚

            </div>

            <h2 class="text-3xl font-black text-slate-700">

                No hay asignaturas disponibles

            </h2>

            <p class="text-slate-500 mt-4">

                Actualmente no tienes materias asignadas
                para este grupo y periodo.

            </p>

        </div>


    <?php else: ?>


        <!-- =====================================================
             LISTADO DE MATERIAS
        ====================================================== -->

        <div class="grid grid-cols-1
                    md:grid-cols-2
                    xl:grid-cols-3
                    gap-10">


            <?php foreach ($materias as $mat): ?>


                <a
                    href="?modulo=tareas&materia_id=<?= (int)$mat['materia_id'] ?>"
                    class="group bg-white
                           border border-slate-200
                           rounded-[3rem]
                           overflow-hidden
                           shadow-sm
                           hover:shadow-2xl
                           hover:-translate-y-2
                           transition-all
                           duration-500"
                >


                    <!-- =================================================
                         ENCABEZADO TARJETA
                    ================================================== -->

                    <div class="bg-gradient-to-r
                                from-indigo-500
                                to-violet-500
                                p-8 text-white">


                        <div class="flex items-center justify-between">


                            <div class="w-20 h-20
                                        rounded-[2rem]
                                        bg-white/15
                                        flex items-center
                                        justify-center
                                        text-4xl
                                        backdrop-blur-sm">

                                📘

                            </div>


                            <div class="text-right">

                                <p class="text-[10px]
                                          uppercase
                                          tracking-[0.3em]
                                          text-white/70
                                          font-black">

                                    Actividades

                                </p>


                                <h3 class="text-4xl
                                           font-black
                                           mt-2">

                                    <?= (int)$mat['total_actividades'] ?>

                                </h3>

                            </div>

                        </div>

                    </div>


                    <!-- =================================================
                         INFORMACIÓN
                    ================================================== -->

                    <div class="p-8">


                        <h2 class="text-3xl
                                   font-black
                                   text-slate-800
                                   tracking-tight
                                   leading-tight">

                            <?= htmlspecialchars(
                                $mat['nombre'],
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>

                        </h2>


                        <div class="mt-6">

                            <p class="text-[10px]
                                      uppercase
                                      tracking-[0.25em]
                                      text-slate-400
                                      font-black
                                      mb-2">

                                Docente

                            </p>


                            <p class="text-slate-600
                                      font-semibold
                                      leading-relaxed">

                                <?= htmlspecialchars(
                                    trim($mat['docente']),
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>

                            </p>

                        </div>


                        <!-- =================================================
                             BOTÓN
                        ================================================== -->

                        <div class="mt-8
                                    pt-6
                                    border-t
                                    border-slate-100
                                    flex
                                    items-center
                                    justify-between">


                            <span class="text-indigo-600
                                         font-black
                                         group-hover:translate-x-1
                                         transition-transform">

                                Ver actividades

                            </span>


                            <div class="w-12 h-12
                                        rounded-2xl
                                        bg-indigo-100
                                        text-indigo-600
                                        flex
                                        items-center
                                        justify-center
                                        font-black
                                        text-xl
                                        group-hover:scale-110
                                        transition-transform">

                                →

                            </div>

                        </div>

                    </div>

                </a>


            <?php endforeach; ?>


        </div>


    <?php endif; ?>


</div>