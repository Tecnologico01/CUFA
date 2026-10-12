<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../includes/db.php';

/*
|--------------------------------------------------------------------------
| OBTENER ID DEL GRUPO
|--------------------------------------------------------------------------
*/

$grupoId = isset($_GET['id']) ? (int) $_GET['id'] : 0;

if ($grupoId <= 0) {
    echo "
        <div class='max-w-3xl mx-auto mt-10'>
            <div class='bg-red-50 border border-red-200 text-red-700
                        rounded-3xl p-7'>
                <h3 class='text-xl font-black'>
                    Grupo no válido
                </h3>

                <p class='mt-2'>
                    No se recibió un grupo válido para consultar.
                </p>

                <a href='?modulo=lista_grupos'
                   class='inline-block mt-5 px-5 py-3 bg-red-100
                          rounded-xl font-bold'>
                    Regresar
                </a>
            </div>
        </div>
    ";
    return;
}

/*
|--------------------------------------------------------------------------
| INFORMACIÓN DEL GRUPO
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare("
    SELECT
        g.id,
        g.nombre,
        g.carrera_id,
        g.periodo_id,

        c.nombre AS carrera,

        p.nombre AS periodo

    FROM grupos g

    LEFT JOIN carreras c
        ON c.id = g.carrera_id

    LEFT JOIN periodos p
        ON p.id = g.periodo_id

    WHERE g.id = ?

    LIMIT 1
");

$stmt->execute([$grupoId]);
$grupo = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$grupo) {
    echo "
        <div class='max-w-3xl mx-auto mt-10'>
            <div class='bg-red-50 border border-red-200 text-red-700
                        rounded-3xl p-7'>

                <h3 class='text-xl font-black'>
                    Grupo no encontrado
                </h3>

                <p class='mt-2'>
                    El grupo solicitado no existe.
                </p>

                <a href='?modulo=lista_grupos'
                   class='inline-block mt-5 px-5 py-3 bg-red-100
                          rounded-xl font-bold'>
                    Regresar
                </a>

            </div>
        </div>
    ";

    return;
}

/*
|--------------------------------------------------------------------------
| ALUMNOS DEL GRUPO
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare("
    SELECT DISTINCT
        a.id,
        a.numero_control,
        a.grado,
        a.fecha_ingreso,

        u.nombres,
        u.apellido_paterno,
        u.apellido_materno,
        u.email

    FROM grupos_alumnos ga

    INNER JOIN alumnos a
        ON a.id = ga.alumno_id

    INNER JOIN usuarios u
        ON u.id = a.usuario_id

    WHERE ga.grupo_id = ?

    ORDER BY
        u.apellido_paterno ASC,
        u.apellido_materno ASC,
        u.nombres ASC
");

$stmt->execute([$grupoId]);
$alumnos = $stmt->fetchAll(PDO::FETCH_ASSOC);

/*
|--------------------------------------------------------------------------
| ASIGNACIONES DOCENTES DEL GRUPO
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare("
    SELECT DISTINCT
        ad.id,

        m.nombre AS materia,

        u.nombres,
        u.apellido_paterno,
        u.apellido_materno

    FROM asignaciones_docentes ad

    INNER JOIN materias m
        ON m.id = ad.materia_id

    INNER JOIN docentes d
        ON d.id = ad.docente_id

    INNER JOIN usuarios u
        ON u.id = d.usuario_id

    WHERE ad.grupo_id = ?

    ORDER BY m.nombre ASC
");

$stmt->execute([$grupoId]);
$asignaciones = $stmt->fetchAll(PDO::FETCH_ASSOC);

$totalAlumnos = count($alumnos);
$totalMaterias = count($asignaciones);
?>

<div class="space-y-8">

    <!-- ENCABEZADO -->

    <div class="flex flex-col md:flex-row
                md:items-center md:justify-between gap-4">

        <div>

            <p class="text-sm font-bold text-indigo-500 uppercase tracking-widest">
                Información académica
            </p>

            <h2 class="text-3xl font-black text-slate-800 mt-1">
                <?= htmlspecialchars($grupo['nombre']) ?>
            </h2>

            <p class="text-slate-500 mt-2">
                Consulta la información general, alumnos y docentes del grupo.
            </p>

        </div>

        <a
            href="?modulo=lista_grupos"
            class="px-5 py-3 bg-slate-100 hover:bg-slate-200
                   text-slate-700 rounded-xl font-bold transition"
        >
            ← Regresar
        </a>

    </div>


    <!-- INFORMACIÓN GENERAL -->

    <div class="grid grid-cols-1 md:grid-cols-4 gap-5">

        <div class="bg-indigo-50 rounded-3xl p-6">

            <p class="text-xs uppercase tracking-wider
                      font-bold text-indigo-500">
                Grupo
            </p>

            <p class="text-2xl font-black text-indigo-900 mt-2">
                <?= htmlspecialchars($grupo['nombre']) ?>
            </p>

        </div>


        <div class="bg-purple-50 rounded-3xl p-6">

            <p class="text-xs uppercase tracking-wider
                      font-bold text-purple-500">
                Carrera
            </p>

            <p class="font-black text-purple-900 mt-2">
                <?= htmlspecialchars($grupo['carrera'] ?? 'Sin asignar') ?>
            </p>

        </div>


        <div class="bg-emerald-50 rounded-3xl p-6">

            <p class="text-xs uppercase tracking-wider
                      font-bold text-emerald-600">
                Alumnos
            </p>

            <p class="text-3xl font-black text-emerald-800 mt-2">
                <?= $totalAlumnos ?>
            </p>

        </div>


        <div class="bg-amber-50 rounded-3xl p-6">

            <p class="text-xs uppercase tracking-wider
                      font-bold text-amber-600">
                Materias
            </p>

            <p class="text-3xl font-black text-amber-800 mt-2">
                <?= $totalMaterias ?>
            </p>

        </div>

    </div>


    <!-- PERIODO -->

    <div class="bg-white border border-slate-100
                rounded-3xl p-6 shadow-sm">

        <p class="text-xs uppercase tracking-wider
                  font-bold text-slate-400">
            Periodo
        </p>

        <p class="text-lg font-black text-slate-800 mt-1">
            <?= htmlspecialchars($grupo['periodo'] ?? 'Sin periodo asignado') ?>
        </p>

    </div>


    <!-- DOCENTES / MATERIAS -->

    <div>

        <div class="mb-5">

            <h3 class="text-2xl font-black text-slate-800">
                Materias y docentes
            </h3>

            <p class="text-slate-500">
                Asignaciones académicas correspondientes a este grupo.
            </p>

        </div>


        <?php if (!$asignaciones): ?>

            <div class="bg-amber-50 border border-amber-100
                        rounded-3xl p-7 text-amber-700">

                No existen docentes o materias asignadas a este grupo.

            </div>

        <?php else: ?>

            <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-5">

                <?php foreach ($asignaciones as $asignacion): ?>

                    <div class="bg-white border border-slate-100
                                rounded-3xl p-6 shadow-sm">

                        <p class="text-xs font-bold uppercase
                                  tracking-widest text-indigo-500">
                            Materia
                        </p>

                        <h4 class="font-black text-lg text-slate-800 mt-2">
                            <?= htmlspecialchars($asignacion['materia']) ?>
                        </h4>

                        <div class="mt-5 pt-4 border-t border-slate-100">

                            <p class="text-xs uppercase font-bold text-slate-400">
                                Docente
                            </p>

                            <p class="font-bold text-slate-700 mt-1">

                                <?= htmlspecialchars(
                                    trim(
                                        $asignacion['nombres'] . ' ' .
                                        $asignacion['apellido_paterno'] . ' ' .
                                        $asignacion['apellido_materno']
                                    )
                                ) ?>

                            </p>

                        </div>

                    </div>

                <?php endforeach; ?>

            </div>

        <?php endif; ?>

    </div>


    <!-- ALUMNOS -->

    <div>

        <div class="flex justify-between items-end mb-5">

            <div>

                <h3 class="text-2xl font-black text-slate-800">
                    Alumnos
                </h3>

                <p class="text-slate-500">
                    Alumnos actualmente asociados al grupo.
                </p>

            </div>

            <span class="px-4 py-2 bg-indigo-50
                         text-indigo-700 rounded-xl font-bold">

                <?= $totalAlumnos ?> alumno<?= $totalAlumnos !== 1 ? 's' : '' ?>

            </span>

        </div>


        <div class="bg-white border border-slate-100
                    rounded-3xl overflow-hidden shadow-sm">

            <div class="overflow-x-auto">

                <table class="w-full">

                    <thead class="bg-slate-50">

                        <tr class="text-left">

                            <th class="p-4 text-xs uppercase
                                       tracking-wider text-slate-500">
                                No. Control
                            </th>

                            <th class="p-4 text-xs uppercase
                                       tracking-wider text-slate-500">
                                Alumno
                            </th>

                            <th class="p-4 text-xs uppercase
                                       tracking-wider text-slate-500">
                                Grado
                            </th>

                            <th class="p-4 text-xs uppercase
                                       tracking-wider text-slate-500">
                                Correo
                            </th>

                            <th class="p-4 text-xs uppercase
                                       tracking-wider text-slate-500">
                                Ingreso
                            </th>

                        </tr>

                    </thead>


                    <tbody>

                    <?php if (!$alumnos): ?>

                        <tr>

                            <td colspan="5"
                                class="p-10 text-center text-slate-500">

                                No hay alumnos asignados a este grupo.

                            </td>

                        </tr>

                    <?php else: ?>

                        <?php foreach ($alumnos as $alumno): ?>

                            <tr class="border-t border-slate-100
                                       hover:bg-slate-50 transition">

                                <td class="p-4">

                                    <span class="font-bold text-indigo-600">

                                        <?= htmlspecialchars(
                                            $alumno['numero_control']
                                        ) ?>

                                    </span>

                                </td>


                                <td class="p-4">

                                    <p class="font-bold text-slate-800">

                                        <?= htmlspecialchars(
                                            trim(
                                                $alumno['nombres'] . ' ' .
                                                $alumno['apellido_paterno'] . ' ' .
                                                $alumno['apellido_materno']
                                            )
                                        ) ?>

                                    </p>

                                </td>


                                <td class="p-4 text-slate-600">

                                    <?= htmlspecialchars(
                                        $alumno['grado'] ?? '—'
                                    ) ?>

                                </td>


                                <td class="p-4 text-slate-600">

                                    <?= htmlspecialchars(
                                        $alumno['email'] ?? '—'
                                    ) ?>

                                </td>


                                <td class="p-4 text-slate-600">

                                    <?= htmlspecialchars(
                                        $alumno['fecha_ingreso'] ?? '—'
                                    ) ?>

                                </td>

                            </tr>

                        <?php endforeach; ?>

                    <?php endif; ?>

                    </tbody>

                </table>

            </div>

        </div>

    </div>

</div>