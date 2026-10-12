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
| OBTENER ALUMNO VINCULADO AL PADRE
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare("
    SELECT
        a.id AS alumno_id,
        a.numero_control,
        a.grado,
        a.fecha_ingreso,

        u.nombres,
        u.apellido_paterno,
        u.apellido_materno,
        u.email,

        c.nombre AS carrera,

        pf.relacion

    FROM padres_familia pf

    INNER JOIN alumnos a
        ON a.id = pf.alumno_id

    INNER JOIN usuarios u
        ON u.id = a.usuario_id

    LEFT JOIN carreras c
        ON c.id = a.carrera_id

    WHERE pf.usuario_id = ?

    LIMIT 1
");

$stmt->execute([$usuarioId]);

$alumno = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$alumno) {
    echo "<div class='bg-red-50 text-red-700 p-6 rounded-2xl'>
            No existe un alumno vinculado a esta cuenta de padre de familia.
          </div>";
    return;
}

$alumnoId = (int)$alumno['alumno_id'];
?>

<div class="max-w-4xl mx-auto">

    <div class="flex justify-between items-center mb-8">

        <div>
            <h2 class="text-3xl font-black text-slate-800">
                Información del alumno
            </h2>

            <p class="text-slate-500">
                Datos académicos generales
            </p>
        </div>

        <a href="?modulo=padre_panel"
           class="px-5 py-3 bg-slate-100 rounded-xl font-bold">
            Regresar
        </a>

    </div>

    <div class="bg-gradient-to-br from-indigo-50 to-purple-50
                rounded-3xl p-8 border border-indigo-100">

        <h3 class="text-2xl font-black text-indigo-900">

            <?= htmlspecialchars(
                $alumno['nombres'] . ' ' .
                $alumno['apellido_paterno'] . ' ' .
                $alumno['apellido_materno']
            ) ?>

        </h3>

        <p class="text-indigo-500 font-bold mt-1">
            <?= htmlspecialchars($alumno['relacion'] ?: 'Alumno asociado') ?>
        </p>

        <div class="grid md:grid-cols-2 gap-5 mt-8">

            <?php
            $datos = [
                'Número de control' => $alumno['numero_control'],
                'Carrera' => $alumno['carrera'],
                'Grado' => $alumno['grado'],
                'Fecha de ingreso' => $alumno['fecha_ingreso'],
                'Correo' => $alumno['email']
            ];
            ?>

            <?php foreach ($datos as $titulo => $valor): ?>

                <div class="bg-white rounded-2xl p-5">

                    <p class="text-xs uppercase tracking-wider
                              font-bold text-slate-400">
                        <?= htmlspecialchars($titulo) ?>
                    </p>

                    <p class="font-bold text-slate-800 mt-1">
                        <?= htmlspecialchars($valor ?: 'No registrado') ?>
                    </p>

                </div>

            <?php endforeach; ?>

        </div>

    </div>

</div>