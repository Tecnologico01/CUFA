<?php

require_once __DIR__ . '/../../includes/db.php';

/*
|--------------------------------------------------------------------------
| OBTENER ALUMNOS
|--------------------------------------------------------------------------
*/

try {

    $sql = "
        SELECT
            a.id AS alumno_id,
            a.usuario_id,
            a.numero_control,
            a.grado,
            a.fecha_ingreso,
            a.carrera_id,

            u.nombres,
            u.apellido_paterno,
            u.apellido_materno,
            u.email,
            u.activo,

            c.nombre AS carrera

        FROM alumnos a

        INNER JOIN usuarios u
            ON u.id = a.usuario_id

        LEFT JOIN carreras c
            ON c.id = a.carrera_id

        ORDER BY
            u.apellido_paterno ASC,
            u.apellido_materno ASC,
            u.nombres ASC
    ";

    $stmt = $pdo->query($sql);
    $alumnos = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {

    $alumnos = [];
    $error = $e->getMessage();
}

?>

<div class="space-y-8">

    <!-- ENCABEZADO -->

    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">

        <div>
            <h2 class="text-3xl font-black text-indigo-900">
                Alumnos
            </h2>

            <p class="text-slate-500 mt-2">
                Consulta los alumnos registrados en el sistema.
            </p>
        </div>

        <a
            href="?modulo=gestion_personas"
            class="inline-flex items-center justify-center
                   px-5 py-3 bg-slate-100
                   hover:bg-slate-200
                   text-slate-700 font-bold
                   rounded-xl transition"
        >
            ← Regresar
        </a>

    </div>


    <?php if (isset($error)): ?>

        <div class="bg-red-50 border border-red-200
                    text-red-700 rounded-2xl p-6">

            <h3 class="font-bold text-lg">
                Error al consultar alumnos
            </h3>

            <p class="mt-2 text-sm">
                <?= htmlspecialchars($error) ?>
            </p>

        </div>

    <?php else: ?>


        <!-- TOTAL -->

        <div class="grid grid-cols-1 md:grid-cols-3 gap-5">

            <div class="bg-gradient-to-br
                        from-indigo-50 to-purple-50
                        border border-indigo-100
                        rounded-3xl p-6">

                <p class="text-xs uppercase tracking-wider
                          font-bold text-indigo-500">
                    Total de alumnos
                </p>

                <p class="text-4xl font-black text-indigo-900 mt-2">
                    <?= count($alumnos) ?>
                </p>

            </div>

        </div>


        <!-- TABLA -->

        <div class="bg-white border border-slate-100
                    shadow-sm rounded-3xl overflow-hidden">

            <div class="overflow-x-auto">

                <table class="w-full">

                    <thead class="bg-slate-50">

                        <tr class="text-left text-sm text-slate-500">

                            <th class="px-6 py-4">
                                No. Control
                            </th>

                            <th class="px-6 py-4">
                                Alumno
                            </th>

                            <th class="px-6 py-4">
                                Carrera
                            </th>

                            <th class="px-6 py-4">
                                Grado
                            </th>

                            <th class="px-6 py-4">
                                Correo
                            </th>

                            <th class="px-6 py-4">
                                Estado
                            </th>

                            <th class="px-6 py-4 text-center">
                                Acciones
                            </th>

                        </tr>

                    </thead>


                    <tbody>

                    <?php if (!$alumnos): ?>

                        <tr>

                            <td
                                colspan="7"
                                class="px-6 py-12 text-center text-slate-500"
                            >
                                No hay alumnos registrados.
                            </td>

                        </tr>

                    <?php else: ?>


                        <?php foreach ($alumnos as $alumno): ?>

                            <tr class="border-t border-slate-100
                                       hover:bg-slate-50/50 transition">

                                <!-- CONTROL -->

                                <td class="px-6 py-5">

                                    <span class="font-bold text-indigo-600">

                                        <?= htmlspecialchars(
                                            $alumno['numero_control'] ?? 'Sin control'
                                        ) ?>

                                    </span>

                                </td>


                                <!-- NOMBRE -->

                                <td class="px-6 py-5">

                                    <div class="flex items-center gap-3">

                                        <div class="w-10 h-10 rounded-xl
                                                    bg-indigo-100
                                                    text-indigo-700
                                                    flex items-center justify-center
                                                    font-black">

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

                                        <div>

                                            <p class="font-bold text-slate-800">

                                                <?= htmlspecialchars(
                                                    trim(
                                                        ($alumno['nombres'] ?? '') . ' ' .
                                                        ($alumno['apellido_paterno'] ?? '') . ' ' .
                                                        ($alumno['apellido_materno'] ?? '')
                                                    )
                                                ) ?>

                                            </p>

                                        </div>

                                    </div>

                                </td>


                                <!-- CARRERA -->

                                <td class="px-6 py-5 text-slate-600">

                                    <?= htmlspecialchars(
                                        $alumno['carrera'] ?? 'Sin asignar'
                                    ) ?>

                                </td>


                                <!-- GRADO -->

                                <td class="px-6 py-5">

                                    <span class="bg-purple-50
                                                 text-purple-700
                                                 px-3 py-1
                                                 rounded-full
                                                 text-sm font-bold">

                                        <?= htmlspecialchars(
                                            $alumno['grado'] ?? '—'
                                        ) ?>

                                    </span>

                                </td>


                                <!-- EMAIL -->

                                <td class="px-6 py-5 text-slate-500">

                                    <?= htmlspecialchars(
                                        $alumno['email'] ?? 'Sin correo'
                                    ) ?>

                                </td>


                                <!-- ESTADO -->

                                <td class="px-6 py-5">

                                    <?php if ((int)($alumno['activo'] ?? 0) === 1): ?>

                                        <span class="bg-emerald-50
                                                     text-emerald-700
                                                     px-3 py-1
                                                     rounded-full
                                                     text-sm font-bold">

                                            Activo

                                        </span>

                                    <?php else: ?>

                                        <span class="bg-red-50
                                                     text-red-700
                                                     px-3 py-1
                                                     rounded-full
                                                     text-sm font-bold">

                                            Inactivo

                                        </span>

                                    <?php endif; ?>

                                </td>


                                <!-- ACCIONES -->

                                <td class="px-6 py-5 text-center">

                                    <a
                                        href="?modulo=detalle_alumno&id=<?= (int)$alumno['alumno_id'] ?>"
                                        class="inline-flex items-center
                                               px-4 py-2
                                               bg-indigo-600
                                               hover:bg-indigo-700
                                               text-white
                                               rounded-xl
                                               font-bold text-sm
                                               transition"
                                    >
                                        Ver detalle
                                    </a>

                                </td>

                            </tr>

                        <?php endforeach; ?>

                    <?php endif; ?>

                    </tbody>

                </table>

            </div>

        </div>

    <?php endif; ?>

</div>