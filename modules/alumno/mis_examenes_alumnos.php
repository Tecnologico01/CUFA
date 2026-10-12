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
        Sesión no válida. Inicia sesión nuevamente.
    </div>";
    return;
}

/*
|--------------------------------------------------------------------------
| OBTENER ALUMNO
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare("
    SELECT
        a.id AS alumno_id,
        a.numero_control,
        a.grado,
        a.carrera_id,
        u.nombres,
        u.apellido_paterno,
        u.apellido_materno
    FROM alumnos a
    INNER JOIN usuarios u
        ON u.id = a.usuario_id
    WHERE a.usuario_id = ?
    LIMIT 1
");

$stmt->execute([$usuario_id]);
$alumno = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$alumno) {
    echo "
    <div class='bg-red-50 border border-red-200 text-red-700 p-6 rounded-3xl'>
        No existe un alumno vinculado a este usuario.
    </div>";
    return;
}

$alumno_id = (int)$alumno['alumno_id'];

/*
|--------------------------------------------------------------------------
| OBTENER GRUPO ACTUAL
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare("
    SELECT
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

$stmt->execute([$alumno_id]);
$grupo = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$grupo) {
    echo "
    <div class='bg-yellow-50 border border-yellow-200 text-yellow-700 p-6 rounded-3xl'>
        No estás asignado a ningún grupo actualmente.
    </div>";
    return;
}

$grupo_id   = (int)$grupo['grupo_id'];
$periodo_id = (int)($grupo['periodo_id'] ?? 0);

/*
|--------------------------------------------------------------------------
| OBTENER MATERIAS CON EXÁMENES
|--------------------------------------------------------------------------
*/

$sql = "
    SELECT DISTINCT
        ad.id AS asignacion_id,
        m.id AS materia_id,
        m.nombre AS materia,

        CONCAT_WS(
            ' ',
            u.nombres,
            u.apellido_paterno,
            u.apellido_materno
        ) AS docente,

        COUNT(DISTINCT e.id) AS total_examenes

    FROM asignaciones_docentes ad

    INNER JOIN materias m
        ON m.id = ad.materia_id

    INNER JOIN docentes d
        ON d.id = ad.docente_id

    INNER JOIN usuarios u
        ON u.id = d.usuario_id

    INNER JOIN examenes e
        ON e.asignacion_id = ad.id

    WHERE ad.grupo_id = ?
";

$params = [$grupo_id];

if ($periodo_id > 0) {
    $sql .= " AND ad.periodo_id = ?";
    $params[] = $periodo_id;
}

$sql .= "
    GROUP BY
        ad.id,
        m.id,
        m.nombre,
        u.nombres,
        u.apellido_paterno,
        u.apellido_materno

    ORDER BY m.nombre ASC
";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);

$materias = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="max-w-7xl mx-auto">

    <!-- HEADER -->

    <div class="mb-12">

        <div class="flex flex-col lg:flex-row
                    justify-between gap-8">

            <div>

                <span class="text-[10px] uppercase
                             tracking-[0.4em]
                             text-indigo-500 font-black">
                    Portal Académico
                </span>

                <h1 class="text-5xl font-black
                           tracking-tighter
                           text-slate-900 mt-3">

                    Mis
                    <span class="text-indigo-600">
                        Exámenes
                    </span>

                </h1>

                <p class="text-slate-500 mt-5 max-w-2xl">
                    Selecciona una asignatura para consultar
                    los exámenes disponibles.
                </p>

            </div>


            <!-- GRUPO -->

            <div class="bg-white border border-slate-200
                        rounded-[2rem] shadow-xl
                        px-8 py-6 min-w-[240px]">

                <p class="text-[10px] uppercase
                          tracking-[0.3em]
                          text-slate-400 font-black">
                    Grupo actual
                </p>

                <h2 class="text-3xl font-black
                           text-indigo-600 mt-3">

                    <?= htmlspecialchars($grupo['grupo']) ?>

                </h2>

                <div class="mt-5 border-t
                            border-slate-100 pt-5">

                    <p class="text-sm text-slate-500">
                        Materias con examen
                    </p>

                    <h3 class="text-4xl font-black
                               text-slate-800 mt-1">

                        <?= count($materias) ?>

                    </h3>

                </div>

            </div>

        </div>

    </div>


    <!-- SIN EXÁMENES -->

    <?php if (!$materias): ?>

        <div class="bg-white border border-slate-200
                    rounded-[3rem] p-16 text-center">

            <div class="text-6xl mb-6">
                📝
            </div>

            <h2 class="text-3xl font-black text-slate-700">
                No hay exámenes disponibles
            </h2>

            <p class="text-slate-500 mt-4">
                Actualmente no existen exámenes publicados
                para tus asignaturas.
            </p>

        </div>

    <?php else: ?>


        <!-- MATERIAS -->

        <div class="grid grid-cols-1
                    md:grid-cols-2
                    xl:grid-cols-3
                    gap-8">

            <?php foreach ($materias as $materia): ?>

                <a
                    href="?modulo=examen&asignacion_id=<?= (int)$materia['asignacion_id'] ?>"
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

                    <!-- CABECERA -->

                    <div class="bg-gradient-to-r
                                from-violet-500
                                to-indigo-600
                                p-8 text-white">

                        <div class="flex
                                    justify-between
                                    items-center">

                            <div class="w-20 h-20
                                        rounded-[2rem]
                                        bg-white/15
                                        flex items-center
                                        justify-center
                                        text-4xl">

                                📝

                            </div>

                            <div class="text-right">

                                <p class="text-[10px]
                                          uppercase
                                          tracking-[0.3em]
                                          text-white/70
                                          font-black">

                                    Exámenes

                                </p>

                                <p class="text-4xl
                                          font-black mt-2">

                                    <?= (int)$materia['total_examenes'] ?>

                                </p>

                            </div>

                        </div>

                    </div>


                    <!-- CONTENIDO -->

                    <div class="p-8">

                        <h2 class="text-3xl
                                   font-black
                                   text-slate-800
                                   tracking-tight">

                            <?= htmlspecialchars($materia['materia']) ?>

                        </h2>


                        <div class="mt-6">

                            <p class="text-[10px]
                                      uppercase
                                      tracking-[0.25em]
                                      text-slate-400
                                      font-black">

                                Docente

                            </p>

                            <p class="text-slate-600
                                      font-semibold mt-2">

                                <?= htmlspecialchars(
                                    trim($materia['docente'])
                                ) ?>

                            </p>

                        </div>


                        <div class="mt-8
                                    pt-6
                                    border-t
                                    border-slate-100
                                    flex
                                    justify-between
                                    items-center">

                            <span class="text-indigo-600
                                         font-black">

                                Ver exámenes

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
                                        group-hover:translate-x-1
                                        transition">

                                →

                            </div>

                        </div>

                    </div>

                </a>

            <?php endforeach; ?>

        </div>

    <?php endif; ?>

</div>