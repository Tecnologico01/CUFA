<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../includes/db.php';

if (
    !isset($_SESSION['user_id']) ||
    ($_SESSION['rol_slug'] ?? '') !== 'padre'
) {
    exit('Acceso no autorizado.');
}

$usuarioId = (int) $_SESSION['user_id'];

$stmt = $pdo->prepare("
    SELECT
        pf.id AS padre_id,
        pf.relacion,

        a.id AS alumno_id,
        a.numero_control,
        a.grado,
        a.fecha_ingreso,

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
");

$stmt->execute([$usuarioId]);
$alumnos = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="space-y-8">

    <div>
        <h2 class="text-3xl font-black text-indigo-900">
            Seguimiento académico
        </h2>

        <p class="text-slate-500 mt-2">
            Consulta la información académica de tus alumnos asociados.
        </p>
    </div>

    <?php if (!$alumnos): ?>

        <div class="bg-amber-50 border border-amber-200 rounded-3xl p-8">
            <h3 class="font-bold text-amber-800 text-lg">
                No hay alumnos asociados
            </h3>

            <p class="text-amber-700 mt-2">
                Tu cuenta todavía no tiene un alumno vinculado.
                Contacta a Control Escolar.
            </p>
        </div>

    <?php else: ?>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

            <?php foreach ($alumnos as $alumno): ?>

                <div class="bg-gradient-to-br from-indigo-50 to-purple-50
                            border border-indigo-100 rounded-3xl p-7">

                    <div class="flex justify-between items-start gap-4">

                        <div>
                            <p class="text-xs font-bold uppercase tracking-widest text-indigo-500">
                                <?= htmlspecialchars($alumno['relacion'] ?: 'Tutor') ?>
                            </p>

                            <h3 class="text-2xl font-black text-indigo-950 mt-2">
                                <?= htmlspecialchars(
                                    trim(
                                        $alumno['nombres'] . ' ' .
                                        $alumno['apellido_paterno'] . ' ' .
                                        $alumno['apellido_materno']
                                    )
                                ) ?>
                            </h3>

                            <p class="text-slate-500 mt-1">
                                No. Control:
                                <strong>
                                    <?= htmlspecialchars($alumno['numero_control']) ?>
                                </strong>
                            </p>
                        </div>

                        <div class="w-14 h-14 rounded-2xl bg-white shadow
                                    flex items-center justify-center
                                    font-black text-indigo-600 text-xl">

                            <?= strtoupper(
                                substr($alumno['nombres'], 0, 1)
                            ) ?>

                        </div>

                    </div>

                    <div class="grid grid-cols-2 gap-4 mt-7">

                        <div class="bg-white rounded-2xl p-4">
                            <p class="text-xs uppercase font-bold text-slate-400">
                                Carrera
                            </p>

                            <p class="font-bold text-slate-700 mt-1">
                                <?= htmlspecialchars($alumno['carrera'] ?? 'Sin asignar') ?>
                            </p>
                        </div>

                        <div class="bg-white rounded-2xl p-4">
                            <p class="text-xs uppercase font-bold text-slate-400">
                                Grado
                            </p>

                            <p class="font-bold text-slate-700 mt-1">
                                <?= (int) $alumno['grado'] ?>
                            </p>
                        </div>

                    </div>

                    <div class="grid grid-cols-2 gap-3 mt-6">

                        <a
                            href="?modulo=padre_calificaciones&alumno_id=<?= (int)$alumno['alumno_id'] ?>"
                            class="text-center bg-indigo-600 hover:bg-indigo-700
                                   text-white font-bold rounded-xl px-4 py-3 transition"
                        >
                            Calificaciones
                        </a>

                        <a
                            href="?modulo=padre_asistencia&alumno_id=<?= (int)$alumno['alumno_id'] ?>"
                            class="text-center bg-white hover:bg-indigo-50
                                   text-indigo-700 font-bold rounded-xl px-4 py-3
                                   border border-indigo-200 transition"
                        >
                            Asistencia
                        </a>

                        <a
                            href="?modulo=padre_actividades&alumno_id=<?= (int)$alumno['alumno_id'] ?>"
                            class="text-center bg-white hover:bg-purple-50
                                   text-purple-700 font-bold rounded-xl px-4 py-3
                                   border border-purple-200 transition"
                        >
                            Actividades
                        </a>

                        <a
                            href="?modulo=padre_alumno&alumno_id=<?= (int)$alumno['alumno_id'] ?>"
                            class="text-center bg-white hover:bg-slate-50
                                   text-slate-700 font-bold rounded-xl px-4 py-3
                                   border border-slate-200 transition"
                        >
                            Información
                        </a>

                    </div>

                </div>

            <?php endforeach; ?>

        </div>

    <?php endif; ?>

</div>