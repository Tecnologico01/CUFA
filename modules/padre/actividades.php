<?php
require_once __DIR__ . '/../../includes/db.php';

$usuarioId = (int)($_SESSION['user_id'] ?? 0);

if ($usuarioId <= 0) {
    echo "<div class='bg-red-50 text-red-700 p-6 rounded-2xl'>
            Sesión no válida.
          </div>";
    return;
}

/*
|--------------------------------------------------------------------------
| OBTENER ALUMNO VINCULADO
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare("
    SELECT
        a.id,
        u.nombres,
        u.apellido_paterno,
        u.apellido_materno

    FROM padres_familia pf

    INNER JOIN alumnos a
        ON a.id = pf.alumno_id

    INNER JOIN usuarios u
        ON u.id = a.usuario_id

    WHERE pf.usuario_id = ?

    LIMIT 1
");

$stmt->execute([$usuarioId]);

$alumno = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$alumno) {
    echo "<div class='bg-red-50 text-red-700 p-6 rounded-2xl'>
            No existe un alumno vinculado a esta cuenta.
          </div>";
    return;
}

$alumnoId = (int)$alumno['id'];

/*
|--------------------------------------------------------------------------
| OBTENER ACTIVIDADES
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare("
    SELECT DISTINCT
        act.id,
        act.nombre,
        act.descripcion,
        act.fecha_apertura,
        act.fecha_cierre,
        act.valor,
        act.parcial,
        act.activo,

        m.nombre AS materia,
        m.clave,

        e.estado AS estado_entrega,
        e.calificacion

    FROM grupos_alumnos ga

    INNER JOIN asignaciones_docentes ad
        ON ad.grupo_id = ga.grupo_id

    INNER JOIN materias m
        ON m.id = ad.materia_id

    INNER JOIN actividades act
        ON act.asignacion_id = ad.id

    LEFT JOIN entregas e
        ON e.actividad_id = act.id
       AND e.alumno_id = ga.alumno_id

    WHERE ga.alumno_id = ?

    ORDER BY act.fecha_cierre DESC, m.nombre
");

$stmt->execute([$alumnoId]);

$actividades = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="space-y-7">

    <div class="flex justify-between items-center">

        <div>
            <h2 class="text-3xl font-black text-slate-800">
                Actividades
            </h2>

            <p class="text-slate-500">
                Seguimiento de tareas y actividades académicas de
                <?= htmlspecialchars(
                    $alumno['nombres'] . ' ' .
                    $alumno['apellido_paterno']
                ) ?>.
            </p>
        </div>

        <a href="?modulo=padre_panel"
           class="px-5 py-3 bg-slate-100 rounded-xl font-bold">
            Regresar
        </a>

    </div>

    <?php if (!$actividades): ?>

        <div class="p-8 bg-slate-50 rounded-3xl text-center text-slate-500">
            No existen actividades registradas.
        </div>

    <?php else: ?>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">

            <?php foreach ($actividades as $actividad): ?>

                <div class="border border-slate-100 rounded-3xl p-6 bg-white shadow-sm">

                    <div class="flex justify-between gap-4">

                        <div>

                            <p class="text-xs uppercase tracking-wider font-bold text-indigo-500">
                                <?= htmlspecialchars($actividad['materia']) ?>
                            </p>

                            <h3 class="text-xl font-black text-slate-800 mt-1">
                                <?= htmlspecialchars($actividad['nombre']) ?>
                            </h3>

                        </div>

                        <span class="text-sm font-bold text-slate-400">
                            <?= number_format((float)$actividad['valor'], 2) ?> pts
                        </span>

                    </div>

                    <p class="text-slate-500 text-sm mt-4">
                        <?= nl2br(htmlspecialchars($actividad['descripcion'] ?? '')) ?>
                    </p>

                    <div class="grid grid-cols-2 gap-3 mt-5">

                        <div class="bg-slate-50 rounded-xl p-3">

                            <p class="text-xs text-slate-400">
                                Fecha límite
                            </p>

                            <p class="font-bold text-slate-700">
                                <?= htmlspecialchars($actividad['fecha_cierre'] ?? '—') ?>
                            </p>

                        </div>

                        <div class="bg-slate-50 rounded-xl p-3">

                            <p class="text-xs text-slate-400">
                                Entrega
                            </p>

                            <p class="font-bold text-slate-700">
                                <?= htmlspecialchars(
                                    ucfirst($actividad['estado_entrega'] ?? 'Pendiente')
                                ) ?>
                            </p>

                        </div>

                    </div>

                    <?php if ($actividad['calificacion'] !== null): ?>

                        <div class="mt-4 bg-emerald-50 text-emerald-700
                                    rounded-xl p-3 font-bold">

                            Calificación:
                            <?= number_format(
                                (float)$actividad['calificacion'],
                                2
                            ) ?>

                        </div>

                    <?php endif; ?>

                </div>

            <?php endforeach; ?>

        </div>

    <?php endif; ?>

</div>