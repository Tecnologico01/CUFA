<?php
require_once __DIR__ . '/../../includes/db.php';

$usuarioId = (int)($_SESSION['user_id'] ?? 0);

if ($usuarioId <= 0) {
    echo "<div class='p-6 bg-red-50 text-red-700 rounded-2xl'>
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
    echo "<div class='p-6 bg-red-50 text-red-700 rounded-2xl'>
            No existe un alumno vinculado a esta cuenta.
          </div>";
    return;
}

$alumnoId = (int)$alumno['id'];

/*
|--------------------------------------------------------------------------
| OBTENER ASISTENCIAS
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare("
    SELECT
        asi.fecha,
        asi.presente,
        asi.justificacion,
        g.nombre AS grupo

    FROM asistencias asi

    LEFT JOIN grupos g
        ON g.id = asi.grupo_id

    WHERE asi.alumno_id = ?

    ORDER BY asi.fecha DESC
");

$stmt->execute([$alumnoId]);

$asistencias = $stmt->fetchAll(PDO::FETCH_ASSOC);

$total = count($asistencias);
$presentes = 0;

foreach ($asistencias as $registro) {
    if ((int)$registro['presente'] === 1) {
        $presentes++;
    }
}

$porcentaje = $total > 0
    ? ($presentes / $total) * 100
    : 0;
?>

<div class="space-y-8">

    <div class="flex justify-between items-center flex-wrap gap-4">

        <div>
            <h2 class="text-3xl font-black text-slate-800">
                Asistencia
            </h2>

            <p class="text-slate-500">
                <?= htmlspecialchars(
                    $alumno['nombres'] . ' ' .
                    $alumno['apellido_paterno']
                ) ?>
            </p>
        </div>

        <a href="?modulo=padre_panel"
           class="px-5 py-3 bg-slate-100 rounded-xl font-bold">
            Regresar
        </a>

    </div>

    <div class="grid md:grid-cols-3 gap-5">

        <div class="bg-indigo-50 rounded-3xl p-6">
            <p class="text-indigo-500 text-xs uppercase font-bold">
                Registros
            </p>

            <p class="text-4xl font-black text-indigo-900 mt-2">
                <?= $total ?>
            </p>
        </div>

        <div class="bg-emerald-50 rounded-3xl p-6">
            <p class="text-emerald-600 text-xs uppercase font-bold">
                Asistencias
            </p>

            <p class="text-4xl font-black text-emerald-800 mt-2">
                <?= $presentes ?>
            </p>
        </div>

        <div class="bg-purple-50 rounded-3xl p-6">
            <p class="text-purple-600 text-xs uppercase font-bold">
                Porcentaje
            </p>

            <p class="text-4xl font-black text-purple-800 mt-2">
                <?= number_format($porcentaje, 1) ?>%
            </p>
        </div>

    </div>

    <div class="overflow-x-auto border border-slate-100 rounded-3xl">

        <table class="w-full">

            <thead class="bg-slate-50 text-left">
                <tr>
                    <th class="p-4">Fecha</th>
                    <th class="p-4">Grupo</th>
                    <th class="p-4">Estado</th>
                    <th class="p-4">Justificación</th>
                </tr>
            </thead>

            <tbody>

            <?php if (!$asistencias): ?>

                <tr>
                    <td colspan="4" class="p-8 text-center text-slate-500">
                        No existen registros de asistencia.
                    </td>
                </tr>

            <?php else: ?>

                <?php foreach ($asistencias as $fila): ?>

                    <tr class="border-t border-slate-100">

                        <td class="p-4">
                            <?= htmlspecialchars($fila['fecha']) ?>
                        </td>

                        <td class="p-4">
                            <?= htmlspecialchars($fila['grupo'] ?? '-') ?>
                        </td>

                        <td class="p-4">

                            <?php if ((int)$fila['presente'] === 1): ?>

                                <span class="bg-emerald-50 text-emerald-700
                                             px-3 py-1 rounded-full font-bold text-sm">
                                    Presente
                                </span>

                            <?php else: ?>

                                <span class="bg-red-50 text-red-700
                                             px-3 py-1 rounded-full font-bold text-sm">
                                    Falta
                                </span>

                            <?php endif; ?>

                        </td>

                        <td class="p-4 text-slate-500">
                            <?= htmlspecialchars($fila['justificacion'] ?: '—') ?>
                        </td>

                    </tr>

                <?php endforeach; ?>

            <?php endif; ?>

            </tbody>

        </table>

    </div>

</div>