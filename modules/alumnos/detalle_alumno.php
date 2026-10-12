<?php

require_once __DIR__ . '/../../includes/db.php';

$alumnoId = isset($_GET['id'])
    ? (int) $_GET['id']
    : 0;


/*
|--------------------------------------------------------------------------
| VALIDAR ID
|--------------------------------------------------------------------------
*/

if ($alumnoId <= 0) {

    echo "
        <div class='bg-red-50 border border-red-200
                    text-red-700 rounded-2xl p-6'>

            <h2 class='font-bold text-xl'>
                Alumno no válido
            </h2>

            <p class='mt-2'>
                No se recibió un alumno válido.
            </p>

            <a
                href='?modulo=alumnos'
                class='inline-block mt-5
                       px-5 py-3
                       bg-red-100
                       rounded-xl font-bold'
            >
                Regresar
            </a>

        </div>
    ";

    return;
}


/*
|--------------------------------------------------------------------------
| CONSULTAR ALUMNO
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare("
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

    WHERE a.id = ?

    LIMIT 1
");

$stmt->execute([$alumnoId]);

$alumno = $stmt->fetch(PDO::FETCH_ASSOC);


/*
|--------------------------------------------------------------------------
| ALUMNO NO ENCONTRADO
|--------------------------------------------------------------------------
*/

if (!$alumno) {

    echo "
        <div class='bg-red-50 border border-red-200
                    text-red-700 rounded-2xl p-6'>

            <h2 class='font-bold text-xl'>
                Alumno no encontrado
            </h2>

            <p class='mt-2'>
                El alumno solicitado no existe.
            </p>

            <a
                href='?modulo=alumnos'
                class='inline-block mt-5
                       px-5 py-3
                       bg-red-100
                       rounded-xl font-bold'
            >
                Regresar
            </a>

        </div>
    ";

    return;
}


/*
|--------------------------------------------------------------------------
| CONSULTAR GRUPOS
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare("
    SELECT
        g.id,
        g.nombre

    FROM grupos_alumnos ga

    INNER JOIN grupos g
        ON g.id = ga.grupo_id

    WHERE ga.alumno_id = ?

    ORDER BY g.nombre
");

$stmt->execute([$alumnoId]);

$grupos = $stmt->fetchAll(PDO::FETCH_ASSOC);


/*
|--------------------------------------------------------------------------
| PADRES / TUTORES
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare("
    SELECT
        pf.relacion,

        u.nombres,
        u.apellido_paterno,
        u.apellido_materno,
        u.email

    FROM padres_familia pf

    INNER JOIN usuarios u
        ON u.id = pf.usuario_id

    WHERE pf.alumno_id = ?

    ORDER BY u.apellido_paterno, u.nombres
");

$stmt->execute([$alumnoId]);

$padres = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>

<div class="space-y-8">


    <!-- ENCABEZADO -->

    <div class="flex flex-col md:flex-row
                md:items-center md:justify-between gap-4">

        <div>

            <p class="text-xs uppercase tracking-widest
                      font-bold text-indigo-500">
                Expediente del alumno
            </p>

            <h2 class="text-3xl font-black text-indigo-900 mt-1">

                <?= htmlspecialchars(
                    trim(
                        ($alumno['nombres'] ?? '') . ' ' .
                        ($alumno['apellido_paterno'] ?? '') . ' ' .
                        ($alumno['apellido_materno'] ?? '')
                    )
                ) ?>

            </h2>

            <p class="text-slate-500 mt-2">

                Número de control:

                <strong>
                    <?= htmlspecialchars(
                        $alumno['numero_control'] ?? 'No registrado'
                    ) ?>
                </strong>

            </p>

        </div>


        <a
            href="?modulo=alumnos"
            class="px-5 py-3
                   bg-slate-100
                   hover:bg-slate-200
                   text-slate-700
                   rounded-xl
                   font-bold transition"
        >
            ← Regresar
        </a>

    </div>


    <!-- INFORMACIÓN GENERAL -->

    <div class="bg-gradient-to-br
                from-indigo-50 to-purple-50
                border border-indigo-100
                rounded-3xl p-8">

        <h3 class="text-xl font-black text-indigo-900 mb-6">
            Información académica
        </h3>


        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">


            <div class="bg-white rounded-2xl p-5">

                <p class="text-xs uppercase
                          tracking-wider font-bold
                          text-slate-400">
                    Número de control
                </p>

                <p class="font-bold text-slate-800 mt-2">

                    <?= htmlspecialchars(
                        $alumno['numero_control'] ?? 'No registrado'
                    ) ?>

                </p>

            </div>


            <div class="bg-white rounded-2xl p-5">

                <p class="text-xs uppercase
                          tracking-wider font-bold
                          text-slate-400">
                    Carrera
                </p>

                <p class="font-bold text-slate-800 mt-2">

                    <?= htmlspecialchars(
                        $alumno['carrera'] ?? 'Sin asignar'
                    ) ?>

                </p>

            </div>


            <div class="bg-white rounded-2xl p-5">

                <p class="text-xs uppercase
                          tracking-wider font-bold
                          text-slate-400">
                    Grado
                </p>

                <p class="font-bold text-slate-800 mt-2">

                    <?= htmlspecialchars(
                        $alumno['grado'] ?? 'No registrado'
                    ) ?>

                </p>

            </div>


            <div class="bg-white rounded-2xl p-5">

                <p class="text-xs uppercase
                          tracking-wider font-bold
                          text-slate-400">
                    Fecha de ingreso
                </p>

                <p class="font-bold text-slate-800 mt-2">

                    <?= htmlspecialchars(
                        $alumno['fecha_ingreso'] ?? 'No registrada'
                    ) ?>

                </p>

            </div>


            <div class="bg-white rounded-2xl p-5">

                <p class="text-xs uppercase
                          tracking-wider font-bold
                          text-slate-400">
                    Correo
                </p>

                <p class="font-bold text-slate-800 mt-2">

                    <?= htmlspecialchars(
                        $alumno['email'] ?? 'No registrado'
                    ) ?>

                </p>

            </div>


            <div class="bg-white rounded-2xl p-5">

                <p class="text-xs uppercase
                          tracking-wider font-bold
                          text-slate-400">
                    Estado
                </p>


                <?php if ((int)($alumno['activo'] ?? 0) === 1): ?>

                    <span class="inline-block mt-2
                                 bg-emerald-50
                                 text-emerald-700
                                 px-3 py-1
                                 rounded-full font-bold">

                        Activo

                    </span>

                <?php else: ?>

                    <span class="inline-block mt-2
                                 bg-red-50
                                 text-red-700
                                 px-3 py-1
                                 rounded-full font-bold">

                        Inactivo

                    </span>

                <?php endif; ?>

            </div>

        </div>

    </div>


    <!-- GRUPOS -->

    <div class="bg-white border border-slate-100
                rounded-3xl p-8 shadow-sm">

        <h3 class="text-xl font-black text-slate-800">
            Grupos asignados
        </h3>


        <?php if (!$grupos): ?>

            <div class="mt-5 bg-slate-50
                        rounded-2xl p-5 text-slate-500">

                Este alumno no tiene grupos asignados.

            </div>

        <?php else: ?>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-6">

                <?php foreach ($grupos as $grupo): ?>

                    <div class="bg-indigo-50
                                border border-indigo-100
                                rounded-2xl p-5">

                        <p class="text-xs uppercase
                                  font-bold text-indigo-400">
                            Grupo
                        </p>

                        <p class="font-black
                                  text-indigo-900 text-lg mt-1">

                            <?= htmlspecialchars($grupo['nombre']) ?>

                        </p>

                    </div>

                <?php endforeach; ?>

            </div>

        <?php endif; ?>

    </div>


    <!-- PADRES / TUTORES -->

    <div class="bg-white border border-slate-100
                rounded-3xl p-8 shadow-sm">

        <h3 class="text-xl font-black text-slate-800">
            Padres o tutores
        </h3>


        <?php if (!$padres): ?>

            <div class="mt-5 bg-amber-50
                        text-amber-700
                        rounded-2xl p-5">

                Este alumno todavía no tiene
                padres o tutores asociados.

            </div>

        <?php else: ?>


            <div class="grid grid-cols-1 md:grid-cols-2 gap-5 mt-6">

                <?php foreach ($padres as $padre): ?>

                    <div class="border border-purple-100
                                bg-purple-50
                                rounded-2xl p-5">

                        <p class="text-xs uppercase
                                  tracking-wider
                                  text-purple-500 font-bold">

                            <?= htmlspecialchars(
                                $padre['relacion'] ?? 'Tutor'
                            ) ?>

                        </p>


                        <p class="font-black
                                  text-purple-900
                                  text-lg mt-2">

                            <?= htmlspecialchars(
                                trim(
                                    ($padre['nombres'] ?? '') . ' ' .
                                    ($padre['apellido_paterno'] ?? '') . ' ' .
                                    ($padre['apellido_materno'] ?? '')
                                )
                            ) ?>

                        </p>


                        <p class="text-sm text-purple-600 mt-2">

                            <?= htmlspecialchars(
                                $padre['email'] ?? 'Sin correo'
                            ) ?>

                        </p>

                    </div>

                <?php endforeach; ?>

            </div>

        <?php endif; ?>

    </div>

</div>