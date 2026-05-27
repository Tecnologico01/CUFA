<?php
require_once __DIR__ . '/../../includes/db.php';

$usuario_id = $_SESSION['user_id'] ?? null;

if(!$usuario_id){
    die("Sesión no válida");
}

/*
|--------------------------------------------------------------------------
| OBTENER ALUMNO
|--------------------------------------------------------------------------
*/

$stmtAlumno = $pdo->prepare("
    SELECT 
        a.id AS alumno_id
    FROM alumnos a
    WHERE a.usuario_id = ?
    LIMIT 1
");

$stmtAlumno->execute([$usuario_id]);

$alumno = $stmtAlumno->fetch(PDO::FETCH_ASSOC);

if(!$alumno){

    echo "
    <div class='bg-red-50 border border-red-200 text-red-700 p-6 rounded-3xl'>
        No existe un alumno vinculado a este usuario.
    </div>
    ";

    exit;
}

$alumno_id = $alumno['alumno_id'];

/*
|--------------------------------------------------------------------------
| OBTENER GRUPO
|--------------------------------------------------------------------------
*/

$stmtGrupo = $pdo->prepare("
    SELECT 
        ga.grupo_id,
        g.nombre AS grupo
    FROM grupos_alumnos ga

    INNER JOIN grupos g
        ON ga.grupo_id = g.id

    WHERE ga.alumno_id = ?

    ORDER BY ga.id DESC
    LIMIT 1
");

$stmtGrupo->execute([$alumno_id]);

$grupo = $stmtGrupo->fetch(PDO::FETCH_ASSOC);

if(!$grupo){

    echo "
    <div class='bg-yellow-50 border border-yellow-200 text-yellow-700 p-6 rounded-3xl'>
        No estás asignado a ningún grupo actualmente.
    </div>
    ";

    exit;
}

$grupo_id = $grupo['grupo_id'];

/*
|--------------------------------------------------------------------------
| OBTENER MATERIAS CON EXAMEN
|--------------------------------------------------------------------------
*/

$stmtMaterias = $pdo->prepare("
    SELECT DISTINCT

        m.id,
        m.nombre,

        ad.id AS asignacion_id,

        CONCAT(
            u.nombres,
            ' ',
            u.apellido_paterno,
            ' ',
            u.apellido_materno
        ) AS docente,

        COUNT(e.id) AS total_examenes

    FROM asignaciones_docentes ad

    INNER JOIN materias m
        ON ad.materia_id = m.id

    INNER JOIN docentes d
        ON ad.docente_id = d.id

    INNER JOIN usuarios u
        ON d.usuario_id = u.id

    LEFT JOIN examenes e
        ON e.asignacion_id = ad.id
        AND e.activo = 1

    WHERE ad.grupo_id = ?

    GROUP BY
        m.id,
        m.nombre,
        docente,
        ad.id

    HAVING total_examenes > 0

    ORDER BY m.nombre ASC
");

$stmtMaterias->execute([$grupo_id]);

$materias = $stmtMaterias->fetchAll(PDO::FETCH_ASSOC);

?>

<div class="max-w-7xl mx-auto">

    <!-- HEADER -->

    <div class="mb-14">

        <div class="flex flex-col lg:flex-row justify-between gap-8">

            <div>

                <span class="text-[10px] uppercase tracking-[0.4em] text-purple-500 font-black">
                    Portal Académico
                </span>

                <h1 class="text-5xl font-black tracking-tighter text-slate-900 mt-3">
                    Mis <span class="text-purple-600">Exámenes</span>
                </h1>

                <p class="text-slate-500 mt-5 max-w-2xl leading-relaxed">
                    Selecciona una asignatura para presentar
                    los exámenes configurados por tu docente.
                </p>

            </div>

            <div class="bg-white border border-slate-200 rounded-[2rem] shadow-xl px-8 py-6 min-w-[240px]">

                <p class="text-[10px] uppercase tracking-[0.3em] text-slate-400 font-black">
                    Grupo Actual
                </p>

                <h2 class="text-3xl font-black text-purple-600 mt-3">
                    <?= htmlspecialchars($grupo['grupo']) ?>
                </h2>

                <div class="mt-5 border-t border-slate-100 pt-5">

                    <p class="text-sm text-slate-500">
                        Asignaturas con Examen
                    </p>

                    <h3 class="text-4xl font-black text-slate-800 mt-1">
                        <?= count($materias) ?>
                    </h3>

                </div>

            </div>

        </div>

    </div>

    <?php if(empty($materias)): ?>

        <div class="bg-white border border-slate-200 rounded-[3rem] p-16 text-center">

            <h2 class="text-3xl font-black text-slate-700">
                No hay exámenes disponibles
            </h2>

            <p class="text-slate-500 mt-4">
                Tus docentes aún no han publicado exámenes.
            </p>

        </div>

    <?php else: ?>

        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-10">

            <?php foreach($materias as $mat): ?>

                <a href="?modulo=examen&asignacion_id=<?= $mat['asignacion_id'] ?>"
                   class="group bg-white border border-slate-200 rounded-[3rem] overflow-hidden shadow-sm hover:shadow-2xl hover:-translate-y-2 transition-all duration-500">

                    <!-- TOP -->

                    <div class="bg-gradient-to-r from-purple-500 to-fuchsia-500 p-8 text-white">

                        <div class="flex items-center justify-between">

                            <div class="w-20 h-20 rounded-[2rem] bg-white/15 flex items-center justify-center text-4xl backdrop-blur-sm">
                                📝
                            </div>

                            <div class="text-right">

                                <p class="text-[10px] uppercase tracking-[0.3em] text-white/70 font-black">
                                    Exámenes
                                </p>

                                <h3 class="text-4xl font-black mt-2">
                                    <?= $mat['total_examenes'] ?>
                                </h3>

                            </div>

                        </div>

                    </div>

                    <!-- BODY -->

                    <div class="p-8">

                        <h2 class="text-3xl font-black text-slate-800 tracking-tight leading-tight">
                            <?= htmlspecialchars($mat['nombre']) ?>
                        </h2>

                        <div class="mt-6">

                            <p class="text-[10px] uppercase tracking-[0.25em] text-slate-400 font-black mb-2">
                                Docente
                            </p>

                            <p class="text-slate-600 font-semibold leading-relaxed">
                                <?= htmlspecialchars($mat['docente']) ?>
                            </p>

                        </div>

                        <div class="mt-8 pt-6 border-t border-slate-100 flex items-center justify-between">

                            <span class="text-purple-600 font-black group-hover:translate-x-1 transition-transform">
                                Presentar examen
                            </span>

                            <div class="w-12 h-12 rounded-2xl bg-purple-100 text-purple-600 flex items-center justify-center font-black text-xl group-hover:scale-110 transition-transform">
                                →
                            </div>

                        </div>

                    </div>

                </a>

            <?php endforeach; ?>

        </div>

    <?php endif; ?>

</div>