<?php
require_once __DIR__ . '/../../includes/db.php';

$usuario_id = $_SESSION['user_id'] ?? null;

if(!$usuario_id){
    die("Sesión no válida");
}

$asignacion_id = $_GET['asignacion_id'] ?? null;

if(!$asignacion_id){
    die("Asignación no válida");
}

/*
|--------------------------------------------------------------------------
| OBTENER ALUMNO
|--------------------------------------------------------------------------
*/

$stmtAlumno = $pdo->prepare("
    SELECT id
    FROM alumnos
    WHERE usuario_id = ?
    LIMIT 1
");

$stmtAlumno->execute([$usuario_id]);

$alumno = $stmtAlumno->fetch(PDO::FETCH_ASSOC);

if(!$alumno){
    die("Alumno no encontrado");
}

$alumno_id = $alumno['id'];

/*
|--------------------------------------------------------------------------
| GUARDAR EXAMEN
|--------------------------------------------------------------------------
*/

if(
    $_SERVER['REQUEST_METHOD'] === 'POST'
    && isset($_POST['guardar_examen'])
){

    $examen_id = $_POST['examen_id'];

    /*
    |--------------------------------------------------------------------------
    | VALIDAR SI YA LO PRESENTÓ
    |--------------------------------------------------------------------------
    */

    $stmtValidar = $pdo->prepare("
        SELECT id
        FROM resultados_examen
        WHERE examen_id = ?
        AND alumno_id = ?
        LIMIT 1
    ");

    $stmtValidar->execute([
        $examen_id,
        $alumno_id
    ]);

    if($stmtValidar->fetch()){

        echo "
        <div class='max-w-3xl mx-auto mt-10'>
            <div class='bg-yellow-50 border border-yellow-200 text-yellow-700 p-6 rounded-3xl font-bold'>
                Ya presentaste este examen.
            </div>
        </div>
        ";

        exit;
    }

    /*
    |--------------------------------------------------------------------------
    | OBTENER PREGUNTAS
    |--------------------------------------------------------------------------
    */

    $stmtPreguntas = $pdo->prepare("
        SELECT *
        FROM preguntas_examen
        WHERE examen_id = ?
    ");

    $stmtPreguntas->execute([$examen_id]);

    $preguntasGuardar = $stmtPreguntas->fetchAll(PDO::FETCH_ASSOC);

    $correctas = 0;
    $total = count($preguntasGuardar);

    /*
    |--------------------------------------------------------------------------
    | CALIFICAR
    |--------------------------------------------------------------------------
    */

    foreach($preguntasGuardar as $pregunta){

        $respuestaAlumno =
            trim(
                strtolower(
                    $_POST['pregunta_'.$pregunta['id']] ?? ''
                )
            );

        $respuestaCorrecta =
            trim(
                strtolower(
                    $pregunta['respuesta_correcta']
                )
            );

        if($respuestaAlumno === $respuestaCorrecta){
            $correctas++;
        }

    }

    /*
    |--------------------------------------------------------------------------
    | CALIFICACIÓN FINAL
    |--------------------------------------------------------------------------
    */

    $calificacion = 0;

    if($total > 0){

        $calificacion =
            ($correctas / $total) * 100;

    }

    /*
    |--------------------------------------------------------------------------
    | GUARDAR RESULTADO
    |--------------------------------------------------------------------------
    */

    $stmtResultado = $pdo->prepare("
        INSERT INTO resultados_examen
        (
            examen_id,
            alumno_id,
            calificacion,
            respuestas_correctas,
            total_preguntas
        )
        VALUES (?,?,?,?,?)
    ");

    $stmtResultado->execute([
        $examen_id,
        $alumno_id,
        $calificacion,
        $correctas,
        $total
    ]);

    /*
    |--------------------------------------------------------------------------
    | REDIRECT
    |--------------------------------------------------------------------------
    */

    header("
        Location:
        alumno_dashboard.php?modulo=examen
        &asignacion_id=$asignacion_id
        &resultado=ok
    ");

    exit;
}

/*
|--------------------------------------------------------------------------
| OBTENER MATERIA
|--------------------------------------------------------------------------
*/

$stmtMateria = $pdo->prepare("
    SELECT

        m.nombre AS materia,

        CONCAT(
            u.nombres,
            ' ',
            u.apellido_paterno,
            ' ',
            u.apellido_materno
        ) AS docente

    FROM asignaciones_docentes ad

    INNER JOIN materias m
        ON ad.materia_id = m.id

    INNER JOIN docentes d
        ON ad.docente_id = d.id

    INNER JOIN usuarios u
        ON d.usuario_id = u.id

    WHERE ad.id = ?
    LIMIT 1
");

$stmtMateria->execute([$asignacion_id]);

$materia = $stmtMateria->fetch(PDO::FETCH_ASSOC);

if(!$materia){
    die("Materia no encontrada");
}

/*
|--------------------------------------------------------------------------
| OBTENER EXÁMENES ACTIVOS
|--------------------------------------------------------------------------
*/

$stmtExamenes = $pdo->prepare("
    SELECT *
    FROM examenes
    WHERE asignacion_id = ?
    AND activo = 1
    ORDER BY parcial ASC
");

$stmtExamenes->execute([$asignacion_id]);

$examenes = $stmtExamenes->fetchAll(PDO::FETCH_ASSOC);

?>

<div class="max-w-6xl mx-auto p-6">

    <!-- HEADER -->

    <div class="mb-12">

        <span class="text-[10px] uppercase tracking-[0.4em] text-purple-500 font-black">
            Plataforma Académica
        </span>

        <h1 class="text-5xl font-black tracking-tighter text-slate-900 mt-3">

            Exámenes de

            <span class="text-purple-600">
                <?= htmlspecialchars($materia['materia']) ?>
            </span>

        </h1>

        <p class="text-slate-500 mt-5 text-lg">
            Docente:
            <span class="font-bold text-slate-700">
                <?= htmlspecialchars($materia['docente']) ?>
            </span>
        </p>

    </div>

    <?php if(isset($_GET['resultado'])): ?>

        <div class="mb-10 bg-emerald-50 border border-emerald-200 text-emerald-700 p-6 rounded-3xl">

            <h3 class="font-black text-xl">
                Examen enviado correctamente
            </h3>

            <p class="mt-2">
                Tu calificación fue registrada automáticamente.
            </p>

        </div>

    <?php endif; ?>

    <?php if(empty($examenes)): ?>

        <div class="bg-white border border-slate-200 rounded-[3rem] p-16 text-center">

            <h2 class="text-3xl font-black text-slate-700">
                No hay exámenes disponibles
            </h2>

            <p class="text-slate-500 mt-4">
                El docente aún no publica exámenes.
            </p>

        </div>

    <?php else: ?>

        <div class="space-y-12">

            <?php foreach($examenes as $examen): ?>

                <?php

                /*
                |--------------------------------------------------------------------------
                | VALIDAR SI YA PRESENTÓ
                |--------------------------------------------------------------------------
                */

                $stmtExiste = $pdo->prepare("
                    SELECT *
                    FROM resultados_examen
                    WHERE examen_id = ?
                    AND alumno_id = ?
                    LIMIT 1
                ");

                $stmtExiste->execute([
                    $examen['id'],
                    $alumno_id
                ]);

                $resultadoAlumno = $stmtExiste->fetch(PDO::FETCH_ASSOC);

                /*
                |--------------------------------------------------------------------------
                | OBTENER PREGUNTAS
                |--------------------------------------------------------------------------
                */

                $stmtPreguntas = $pdo->prepare("
                    SELECT *
                    FROM preguntas_examen
                    WHERE examen_id = ?
                ");

                $stmtPreguntas->execute([$examen['id']]);

                $preguntas = $stmtPreguntas->fetchAll(PDO::FETCH_ASSOC);

                ?>

                <div class="bg-white border border-slate-200 rounded-[3rem] overflow-hidden shadow-sm">

                    <!-- TOP -->

                    <div class="bg-gradient-to-r from-slate-900 to-slate-800 text-white p-10">

                        <div class="flex justify-between items-center">

                            <div>

                                <span class="text-[10px] uppercase tracking-[0.3em] text-purple-300 font-black">
                                    Evaluación
                                </span>

                                <h2 class="text-4xl font-black mt-3">
                                    Unidad <?= htmlspecialchars($examen['parcial']) ?>
                                </h2>

                            </div>

                            <?php if($resultadoAlumno): ?>

                                <div class="bg-emerald-500 text-slate-900 px-6 py-4 rounded-3xl text-center">

                                    <p class="text-[10px] uppercase tracking-[0.2em] font-black">
                                        Calificación
                                    </p>

                                    <h3 class="text-4xl font-black mt-1">
                                        <?= number_format($resultadoAlumno['calificacion'],1) ?>
                                    </h3>

                                </div>

                            <?php endif; ?>

                        </div>

                    </div>

                    <!-- BODY -->

                    <div class="p-10">

                        <?php if($resultadoAlumno): ?>

                            <div class="bg-emerald-50 border border-emerald-100 rounded-3xl p-8">

                                <h3 class="text-2xl font-black text-emerald-700">
                                    Examen completado
                                </h3>

                                <div class="mt-6 grid grid-cols-1 md:grid-cols-3 gap-6">

                                    <div class="bg-white rounded-2xl p-6 border border-emerald-100">

                                        <p class="text-[10px] uppercase tracking-[0.2em] text-slate-400 font-black">
                                            Aciertos
                                        </p>

                                        <h4 class="text-4xl font-black text-slate-800 mt-2">
                                            <?= $resultadoAlumno['respuestas_correctas'] ?>
                                        </h4>

                                    </div>

                                    <div class="bg-white rounded-2xl p-6 border border-emerald-100">

                                        <p class="text-[10px] uppercase tracking-[0.2em] text-slate-400 font-black">
                                            Preguntas
                                        </p>

                                        <h4 class="text-4xl font-black text-slate-800 mt-2">
                                            <?= $resultadoAlumno['total_preguntas'] ?>
                                        </h4>

                                    </div>

                                    <div class="bg-white rounded-2xl p-6 border border-emerald-100">

                                        <p class="text-[10px] uppercase tracking-[0.2em] text-slate-400 font-black">
                                            Calificación
                                        </p>

                                        <h4 class="text-4xl font-black text-emerald-600 mt-2">
                                            <?= number_format($resultadoAlumno['calificacion'],1) ?>
                                        </h4>

                                    </div>

                                </div>

                            </div>

                        <?php else: ?>

                            <form method="POST" class="space-y-8">

                                <input type="hidden"
                                       name="guardar_examen"
                                       value="1">

                                <input type="hidden"
                                       name="examen_id"
                                       value="<?= $examen['id'] ?>">

                                <?php foreach($preguntas as $index => $pregunta): ?>

                                    <div class="bg-slate-50 border border-slate-100 rounded-3xl p-8">

                                        <div class="flex gap-5">

                                            <div class="w-14 h-14 rounded-2xl bg-purple-600 text-white flex items-center justify-center text-xl font-black shrink-0">

                                                <?= $index + 1 ?>

                                            </div>

                                            <div class="flex-1">

                                                <h3 class="text-xl font-black text-slate-800 leading-relaxed">

                                                    <?= htmlspecialchars($pregunta['pregunta']) ?>

                                                </h3>

                                                <div class="mt-6">

                                                    <input type="text"
                                                           name="pregunta_<?= $pregunta['id'] ?>"
                                                           required
                                                           class="w-full border border-slate-200 rounded-2xl px-6 py-4 text-lg focus:outline-none focus:ring-4 focus:ring-purple-100"
                                                           placeholder="Escribe tu respuesta aquí...">

                                                </div>

                                            </div>

                                        </div>

                                    </div>

                                <?php endforeach; ?>

                                <button type="submit"
                                        class="w-full bg-purple-600 hover:bg-purple-700 text-white py-5 rounded-3xl text-xl font-black transition-all">

                                    Enviar Examen

                                </button>

                            </form>

                        <?php endif; ?>

                    </div>

                </div>

            <?php endforeach; ?>

        </div>

    <?php endif; ?>

</div>