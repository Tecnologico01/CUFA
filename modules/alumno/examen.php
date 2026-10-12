<?php
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__.'/../../includes/calificaciones_service.php';
if($_SERVER['REQUEST_METHOD']==='POST') cufaValidarToken();

/* ============================================================
   VALIDAR SESIÓN
============================================================ */

$usuario_id = (int)($_SESSION['user_id'] ?? 0);
$asignacion_id = (int)($_GET['asignacion_id'] ?? 0);

if ($usuario_id <= 0) {
    echo "<div class='p-6 bg-red-50 text-red-700 rounded-2xl'>
            Sesión no válida.
          </div>";
    return;
}

if ($asignacion_id <= 0) {
    echo "<div class='p-6 bg-red-50 text-red-700 rounded-2xl'>
            Asignación no válida.
          </div>";
    return;
}


/* ============================================================
   OBTENER ALUMNO
============================================================ */

$stmt = $pdo->prepare("
    SELECT id
    FROM alumnos
    WHERE usuario_id = ?
    LIMIT 1
");

$stmt->execute([$usuario_id]);
$alumno = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$alumno) {
    echo "<div class='p-6 bg-red-50 text-red-700 rounded-2xl'>
            Alumno no encontrado.
          </div>";
    return;
}

$alumno_id = (int)$alumno['id'];


/* ============================================================
   OBTENER GRUPO DEL ALUMNO
============================================================ */

$stmt = $pdo->prepare("
    SELECT
        ga.grupo_id,
        ga.periodo_id
    FROM grupos_alumnos ga
    WHERE ga.alumno_id = ?
    ORDER BY ga.id DESC
    LIMIT 1
");

$stmt->execute([$alumno_id]);
$grupoAlumno = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$grupoAlumno) {
    echo "<div class='p-6 bg-yellow-50 text-yellow-700 rounded-2xl'>
            No tienes un grupo asignado.
          </div>";
    return;
}

$grupo_id = (int)$grupoAlumno['grupo_id'];
$periodo_id = (int)($grupoAlumno['periodo_id'] ?? 0);


/* ============================================================
   VALIDAR ASIGNACIÓN
============================================================ */

$sql = "
    SELECT
        ad.id,
        ad.materia_id,
        ad.grupo_id,
        ad.periodo_id,

        m.nombre AS materia,

        CONCAT_WS(
            ' ',
            u.nombres,
            u.apellido_paterno,
            u.apellido_materno
        ) AS docente

    FROM asignaciones_docentes ad

    INNER JOIN materias m
        ON m.id = ad.materia_id

    INNER JOIN docentes d
        ON d.id = ad.docente_id

    INNER JOIN usuarios u
        ON u.id = d.usuario_id

    WHERE ad.id = ?
      AND ad.grupo_id = ?
";

$params = [
    $asignacion_id,
    $grupo_id
];

if ($periodo_id > 0) {
    $sql .= " AND ad.periodo_id = ?";
    $params[] = $periodo_id;
}

$sql .= " LIMIT 1";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);

$materia = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$materia) {
    echo "<div class='p-6 bg-red-50 text-red-700 rounded-2xl'>
            No tienes autorización para consultar esta asignatura.
          </div>";
    return;
}


/* ============================================================
   GUARDAR EXAMEN
============================================================ */

if (
    $_SERVER['REQUEST_METHOD'] === 'POST'
    && isset($_POST['guardar_examen'])
) {

    $examen_id = (int)($_POST['examen_id'] ?? 0);

    if ($examen_id <= 0) {
        $error = "Examen no válido.";
    } else {

        /* ====================================================
           VALIDAR QUE EL EXAMEN PERTENEZCA A LA ASIGNACIÓN
        ==================================================== */

        $stmt = $pdo->prepare("
            SELECT *
            FROM examenes
            WHERE id = ?
              AND asignacion_id = ?
              AND activo = 1
            LIMIT 1
        ");

        $stmt->execute([
            $examen_id,
            $asignacion_id
        ]);

        $examenValidado = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$examenValidado) {

            $error = "No tienes autorización para presentar este examen.";

        } else {

            /* ================================================
               VALIDAR SI YA FUE PRESENTADO
            ================================================ */

            $stmt = $pdo->prepare("
                SELECT id
                FROM resultados_examen
                WHERE examen_id = ?
                  AND alumno_id = ?
                LIMIT 1
            ");

            $stmt->execute([
                $examen_id,
                $alumno_id
            ]);

            if ($stmt->fetch()) {

                $error = "Ya presentaste este examen.";

            } else {

                /* ============================================
                   OBTENER PREGUNTAS
                ============================================ */

                $stmt = $pdo->prepare("
                    SELECT
                        id,
                        pregunta,
                        respuesta_correcta
                    FROM preguntas_examen
                    WHERE examen_id = ?
                    ORDER BY id ASC
                ");

                $stmt->execute([$examen_id]);

                $preguntasGuardar =
                    $stmt->fetchAll(PDO::FETCH_ASSOC);

                $total = count($preguntasGuardar);

                if ($total <= 0) {

                    $error = "Este examen no contiene preguntas.";

                } else {

                    /* ========================================
                       CALIFICAR
                    ======================================== */

                    $correctas = 0;

                    foreach ($preguntasGuardar as $pregunta) {

                        $campo =
                            'pregunta_' . (int)$pregunta['id'];

                        $respuestaAlumno =
                            trim(
                                mb_strtolower(
                                    $_POST[$campo] ?? '',
                                    'UTF-8'
                                )
                            );

                        $respuestaCorrecta =
                            trim(
                                mb_strtolower(
                                    $pregunta['respuesta_correcta'],
                                    'UTF-8'
                                )
                            );

                        if (
                            $respuestaAlumno !== ''
                            && $respuestaAlumno === $respuestaCorrecta
                        ) {
                            $correctas++;
                        }
                    }

                    $calificacion =
                        ($correctas / $total) * 100;

                    /* ========================================
                       GUARDAR RESULTADO
                    ======================================== */

                    try {

                        $pdo->beginTransaction();
                        $lock=$pdo->prepare('SELECT id FROM examenes WHERE id=? AND activo=1 FOR UPDATE');
                        $lock->execute([$examen_id]);
                        if(!$lock->fetchColumn()) throw new RuntimeException('El examen ya no está activo.');

                        /*
                         * Comprobación nuevamente dentro
                         * de la transacción.
                         */

                        $stmt = $pdo->prepare("
                            SELECT id
                            FROM resultados_examen
                            WHERE examen_id = ?
                              AND alumno_id = ?
                            LIMIT 1
                        ");

                        $stmt->execute([
                            $examen_id,
                            $alumno_id
                        ]);

                        if ($stmt->fetch()) {
                            throw new Exception(
                                'El examen ya fue presentado.'
                            );
                        }

                        $stmt = $pdo->prepare("
                            INSERT INTO resultados_examen
                            (
                                examen_id,
                                alumno_id,
                                calificacion,
                                respuestas_correctas,
                                total_preguntas
                            )
                            VALUES (?, ?, ?, ?, ?)
                        ");

                        $stmt->execute([
                            $examen_id,
                            $alumno_id,
                            $calificacion,
                            $correctas,
                            $total
                        ]);

                        $pdo->commit();

                        /*
                         * No usamos header() aquí para evitar
                         * problemas de "headers already sent"
                         * al cargar módulos desde dashboard.php.
                         */

                        echo "
                        <script>
                            window.location.href =
                            '?modulo=examen&asignacion_id="
                            . $asignacion_id .
                            "&resultado=ok';
                        </script>
                        ";

                        exit;

                    } catch (Throwable $e) {

                        if ($pdo->inTransaction()) {
                            $pdo->rollBack();
                        }

                        $error =
                            "No fue posible guardar el resultado del examen.";
                    }
                }
            }
        }
    }
}


/* ============================================================
   OBTENER EXÁMENES ACTIVOS
============================================================ */

$stmt = $pdo->prepare("
    SELECT *
    FROM examenes
    WHERE asignacion_id = ?
      AND activo = 1
    ORDER BY parcial ASC, id ASC
");

$stmt->execute([$asignacion_id]);

$examenes = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>

<div class="max-w-6xl mx-auto p-6">

    <!-- HEADER -->

    <div class="mb-10">

        <a
            href="?modulo=mis_examenes_alumnos"
            class="inline-block mb-6
                   bg-slate-100 hover:bg-slate-200
                   text-slate-700 font-bold
                   px-5 py-3 rounded-xl transition"
        >
            ← Regresar
        </a>

        <span class="block text-[10px]
                     uppercase tracking-[0.4em]
                     text-purple-500 font-black">

            Plataforma Académica

        </span>

        <h1 class="text-4xl md:text-5xl
                   font-black tracking-tighter
                   text-slate-900 mt-3">

            Exámenes de

            <span class="text-purple-600">

                <?= htmlspecialchars(
                    $materia['materia'],
                    ENT_QUOTES,
                    'UTF-8'
                ) ?>

            </span>

        </h1>

        <p class="text-slate-500 mt-5 text-lg">

            Docente:

            <span class="font-bold text-slate-700">

                <?= htmlspecialchars(
                    $materia['docente'],
                    ENT_QUOTES,
                    'UTF-8'
                ) ?>

            </span>

        </p>

    </div>


    <!-- RESULTADO -->

    <?php if (isset($_GET['resultado'])): ?>

        <div class="mb-8
                    bg-emerald-50
                    border border-emerald-200
                    text-emerald-700
                    p-6 rounded-3xl">

            <h3 class="font-black text-xl">
                Examen enviado correctamente
            </h3>

            <p class="mt-2">
                Tu calificación fue registrada automáticamente.
            </p>

        </div>

    <?php endif; ?>


    <!-- ERROR -->

    <?php if (isset($error)): ?>

        <div class="mb-8
                    bg-red-50
                    border border-red-200
                    text-red-700
                    p-6 rounded-3xl
                    font-bold">

            <?= htmlspecialchars($error) ?>

        </div>

    <?php endif; ?>


    <!-- SIN EXÁMENES -->

    <?php if (!$examenes): ?>

        <div class="bg-white
                    border border-slate-200
                    rounded-[3rem]
                    p-16 text-center">

            <div class="text-6xl mb-6">
                📝
            </div>

            <h2 class="text-3xl font-black text-slate-700">
                No hay exámenes disponibles
            </h2>

            <p class="text-slate-500 mt-4">
                El docente aún no publica exámenes.
            </p>

        </div>

    <?php else: ?>


        <div class="space-y-10">


            <?php foreach ($examenes as $examen): ?>


                <?php

                /* ============================================
                   RESULTADO DEL ALUMNO
                ============================================ */

                $stmt = $pdo->prepare("
                    SELECT *
                    FROM resultados_examen
                    WHERE examen_id = ?
                      AND alumno_id = ?
                    LIMIT 1
                ");

                $stmt->execute([
                    $examen['id'],
                    $alumno_id
                ]);

                $resultadoAlumno =
                    $stmt->fetch(PDO::FETCH_ASSOC);


                /* ============================================
                   PREGUNTAS
                ============================================ */

                $stmt = $pdo->prepare("
                    SELECT
                        id,
                        pregunta
                    FROM preguntas_examen
                    WHERE examen_id = ?
                    ORDER BY id ASC
                ");

                $stmt->execute([$examen['id']]);

                $preguntas =
                    $stmt->fetchAll(PDO::FETCH_ASSOC);

                ?>


                <div class="bg-white
                            border border-slate-200
                            rounded-[3rem]
                            overflow-hidden
                            shadow-sm">


                    <!-- CABECERA -->

                    <div class="bg-gradient-to-r
                                from-slate-900
                                to-slate-800
                                text-white p-8 md:p-10">


                        <div class="flex flex-col
                                    md:flex-row
                                    md:justify-between
                                    md:items-center
                                    gap-6">


                            <div>

                                <span class="text-[10px]
                                             uppercase
                                             tracking-[0.3em]
                                             text-purple-300
                                             font-black">

                                    Evaluación

                                </span>


                                <h2 class="text-3xl md:text-4xl
                                           font-black mt-3">

                                    Unidad
                                    <?= htmlspecialchars(
                                        $examen['parcial']
                                    ) ?>

                                </h2>


                                <p class="text-slate-300 mt-3">

                                    <?= count($preguntas) ?>
                                    pregunta<?= count($preguntas) !== 1 ? 's' : '' ?>

                                </p>

                            </div>


                            <?php if ($resultadoAlumno): ?>


                                <div class="bg-emerald-400
                                            text-slate-900
                                            px-6 py-4
                                            rounded-3xl
                                            text-center">

                                    <p class="text-[10px]
                                              uppercase
                                              tracking-[0.2em]
                                              font-black">

                                        Calificación

                                    </p>

                                    <h3 class="text-4xl font-black mt-1">

                                        <?= number_format(
                                            (float)$resultadoAlumno['calificacion'],
                                            1
                                        ) ?>

                                    </h3>

                                </div>


                            <?php endif; ?>


                        </div>

                    </div>


                    <!-- CONTENIDO -->

                    <div class="p-6 md:p-10">


                        <?php if ($resultadoAlumno): ?>


                            <!-- EXAMEN YA REALIZADO -->

                            <div class="bg-emerald-50
                                        border border-emerald-100
                                        rounded-3xl p-8">


                                <h3 class="text-2xl
                                           font-black
                                           text-emerald-700">

                                    ✓ Examen completado

                                </h3>


                                <p class="text-emerald-600 mt-2">

                                    Este examen ya fue presentado
                                    y no puede volver a realizarse.

                                </p>


                                <div class="mt-6
                                            grid grid-cols-1
                                            md:grid-cols-3
                                            gap-6">


                                    <div class="bg-white
                                                rounded-2xl p-6
                                                border border-emerald-100">

                                        <p class="text-[10px]
                                                  uppercase
                                                  tracking-[0.2em]
                                                  text-slate-400
                                                  font-black">

                                            Aciertos

                                        </p>

                                        <h4 class="text-4xl
                                                   font-black
                                                   text-slate-800
                                                   mt-2">

                                            <?= (int)$resultadoAlumno['respuestas_correctas'] ?>

                                        </h4>

                                    </div>


                                    <div class="bg-white
                                                rounded-2xl p-6
                                                border border-emerald-100">

                                        <p class="text-[10px]
                                                  uppercase
                                                  tracking-[0.2em]
                                                  text-slate-400
                                                  font-black">

                                            Preguntas

                                        </p>

                                        <h4 class="text-4xl
                                                   font-black
                                                   text-slate-800
                                                   mt-2">

                                            <?= (int)$resultadoAlumno['total_preguntas'] ?>

                                        </h4>

                                    </div>


                                    <div class="bg-white
                                                rounded-2xl p-6
                                                border border-emerald-100">

                                        <p class="text-[10px]
                                                  uppercase
                                                  tracking-[0.2em]
                                                  text-slate-400
                                                  font-black">

                                            Calificación

                                        </p>

                                        <h4 class="text-4xl
                                                   font-black
                                                   text-emerald-600
                                                   mt-2">

                                            <?= number_format(
                                                (float)$resultadoAlumno['calificacion'],
                                                1
                                            ) ?>

                                        </h4>

                                    </div>


                                </div>

                            </div>


                        <?php elseif (!$preguntas): ?>


                            <div class="bg-yellow-50
                                        border border-yellow-200
                                        text-yellow-700
                                        rounded-2xl p-6">

                                Este examen todavía no contiene preguntas.

                            </div>


                        <?php else: ?>


                            <!-- FORMULARIO DEL EXAMEN -->

                            <form
                                method="POST"
                                class="space-y-8"
                                onsubmit="
                                    const boton = this.querySelector('button[type=submit]');
                                    boton.disabled = true;
                                    boton.innerText = 'Enviando examen...';
                                "
                            ><input type="hidden" name="cufa_csrf" value="<?= htmlspecialchars(cufaToken()) ?>">

                                <input
                                    type="hidden"
                                    name="guardar_examen"
                                    value="1"
                                >

                                <input
                                    type="hidden"
                                    name="examen_id"
                                    value="<?= (int)$examen['id'] ?>"
                                >


                                <?php foreach ($preguntas as $index => $pregunta): ?>


                                    <div class="bg-slate-50
                                                border border-slate-100
                                                rounded-3xl
                                                p-6 md:p-8">


                                        <div class="flex
                                                    flex-col
                                                    md:flex-row
                                                    gap-5">


                                            <div class="w-14 h-14
                                                        rounded-2xl
                                                        bg-purple-600
                                                        text-white
                                                        flex items-center
                                                        justify-center
                                                        text-xl
                                                        font-black
                                                        shrink-0">

                                                <?= $index + 1 ?>

                                            </div>


                                            <div class="flex-1">


                                                <h3 class="text-xl
                                                           font-black
                                                           text-slate-800
                                                           leading-relaxed">

                                                    <?= htmlspecialchars(
                                                        $pregunta['pregunta'],
                                                        ENT_QUOTES,
                                                        'UTF-8'
                                                    ) ?>

                                                </h3>


                                                <div class="mt-6">

                                                    <input
                                                        type="text"
                                                        name="pregunta_<?= (int)$pregunta['id'] ?>"
                                                        required
                                                        autocomplete="off"
                                                        class="w-full
                                                               border border-slate-200
                                                               rounded-2xl
                                                               px-6 py-4
                                                               text-lg
                                                               focus:outline-none
                                                               focus:ring-4
                                                               focus:ring-purple-100"
                                                        placeholder="Escribe tu respuesta aquí..."
                                                    >

                                                </div>


                                            </div>

                                        </div>

                                    </div>


                                <?php endforeach; ?>


                                <div class="bg-yellow-50
                                            border border-yellow-100
                                            rounded-2xl p-5
                                            text-yellow-700">

                                    <strong>Importante:</strong>
                                    revisa tus respuestas antes de enviar.
                                    Una vez enviado el examen no podrás
                                    volver a presentarlo.

                                </div>


                                <button
                                    type="submit"
                                    class="w-full
                                           bg-purple-600
                                           hover:bg-purple-700
                                           disabled:opacity-60
                                           disabled:cursor-not-allowed
                                           text-white
                                           py-5
                                           rounded-3xl
                                           text-xl
                                           font-black
                                           transition-all"
                                >

                                    Enviar examen

                                </button>


                            </form>


                        <?php endif; ?>


                    </div>

                </div>


            <?php endforeach; ?>


        </div>


    <?php endif; ?>

</div>