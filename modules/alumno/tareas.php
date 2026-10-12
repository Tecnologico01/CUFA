<?php
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__.'/../../includes/calificaciones_service.php';
if($_SERVER['REQUEST_METHOD']==='POST') cufaValidarToken();

$usuario_id = (int)($_SESSION['user_id'] ?? 0);

if ($usuario_id <= 0) {
    echo "<div class='bg-red-50 border border-red-200 text-red-700 p-6 rounded-3xl'>
            Sesión no válida.
          </div>";
    return;
}

/*
|--------------------------------------------------------------------------
| OBTENER ALUMNO
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare("
    SELECT id, numero_control
    FROM alumnos
    WHERE usuario_id = ?
    LIMIT 1
");
$stmt->execute([$usuario_id]);
$alumno = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$alumno) {
    echo "<div class='bg-red-50 border border-red-200 text-red-700 p-6 rounded-3xl'>
            No existe un alumno vinculado a este usuario.
          </div>";
    return;
}

$alumno_id = (int)$alumno['id'];

/*
|--------------------------------------------------------------------------
| GRUPO ACTUAL
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare("
    SELECT
        ga.grupo_id,
        ga.periodo_id,
        g.nombre AS grupo
    FROM grupos_alumnos ga
    INNER JOIN grupos g ON g.id = ga.grupo_id
    WHERE ga.alumno_id = ?
    ORDER BY ga.id DESC
    LIMIT 1
");
$stmt->execute([$alumno_id]);

$grupo = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$grupo) {
    echo "<div class='bg-yellow-50 border border-yellow-200 text-yellow-700 p-6 rounded-3xl'>
            No estás asignado a ningún grupo.
          </div>";
    return;
}

$grupo_id   = (int)$grupo['grupo_id'];
$periodo_id = (int)($grupo['periodo_id'] ?? 0);

/*
|--------------------------------------------------------------------------
| MATERIA
|--------------------------------------------------------------------------
*/

$materia_id = (int)($_GET['materia_id'] ?? 0);

if ($materia_id <= 0) {
    echo "<div class='bg-yellow-50 border border-yellow-200 text-yellow-700 p-6 rounded-3xl'>
            Materia no válida.
          </div>";
    return;
}

/*
|--------------------------------------------------------------------------
| VALIDAR QUE LA MATERIA PERTENECE AL ALUMNO
|--------------------------------------------------------------------------
*/

$sql = "
    SELECT
        ad.id AS asignacion_id,
        m.id AS materia_id,
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

    WHERE ad.grupo_id = ?
      AND ad.materia_id = ?
";

$params = [$grupo_id, $materia_id];

if ($periodo_id > 0) {
    $sql .= " AND ad.periodo_id = ?";
    $params[] = $periodo_id;
}

$sql .= " LIMIT 1";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);

$asignacion = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$asignacion) {
    echo "<div class='bg-red-50 border border-red-200 text-red-700 p-6 rounded-3xl'>
            No tienes autorización para consultar esta materia.
          </div>";
    return;
}

$asignacion_id = (int)$asignacion['asignacion_id'];

/*
|--------------------------------------------------------------------------
| PROCESAR ENTREGA
|--------------------------------------------------------------------------
*/

if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    isset($_POST['actividad_id'])
) {

    $actividad_id = (int)$_POST['actividad_id'];

    /*
    |--------------------------------------------------------------------------
    | VALIDAR ACTIVIDAD
    |--------------------------------------------------------------------------
    */

    $stmtActividad = $pdo->prepare("
        SELECT
            a.id,
            a.fecha_apertura,
            a.fecha_cierre,
            a.activo
        FROM actividades a
        WHERE a.id = ?
          AND a.asignacion_id = ?
        LIMIT 1
    ");

    $stmtActividad->execute([
        $actividad_id,
        $asignacion_id
    ]);

    $actividadValida = $stmtActividad->fetch(PDO::FETCH_ASSOC);

    if (!$actividadValida) {

        $error = "La actividad seleccionada no pertenece a esta materia.";

    } elseif ((int)$actividadValida['activo'] !== 1) {

        $error = "Esta actividad no está disponible.";

    } else {

        /*
        |--------------------------------------------------------------------------
        | VALIDAR FECHAS
        |--------------------------------------------------------------------------
        */

        $ahora = new DateTime();

        $apertura = !empty($actividadValida['fecha_apertura'])
            ? new DateTime($actividadValida['fecha_apertura'])
            : null;

        $cierre = !empty($actividadValida['fecha_cierre'])
            ? new DateTime($actividadValida['fecha_cierre'])
            : null;

        if ($apertura && $ahora < $apertura) {

            $error = "La actividad todavía no está abierta.";

        } elseif ($cierre && $ahora > $cierre) {

            $error = "La fecha límite de entrega ya terminó.";

        } else {

            /*
            |--------------------------------------------------------------------------
            | COMPROBAR ENTREGA EXISTENTE ANTES DE GUARDAR ARCHIVO
            |--------------------------------------------------------------------------
            */

            $stmtExiste = $pdo->prepare("
                SELECT id
                FROM entregas_actividades
                WHERE actividad_id = ?
                  AND alumno_id = ?
                LIMIT 1
            ");

            $stmtExiste->execute([
                $actividad_id,
                $alumno_id
            ]);

            if ($stmtExiste->fetchColumn()) {

                $error = "Ya entregaste esta actividad.";

            } elseif (
                !isset($_FILES['archivo_entrega']) ||
                $_FILES['archivo_entrega']['error'] !== UPLOAD_ERR_OK
            ) {

                $error = "Debes seleccionar un archivo válido.";

            } else {

                /*
                |--------------------------------------------------------------------------
                | VALIDAR ARCHIVO
                |--------------------------------------------------------------------------
                */

                $archivo = $_FILES['archivo_entrega'];

                $permitidos = [
                    'pdf',
                    'doc',
                    'docx',
                    'xls',
                    'xlsx',
                    'ppt',
                    'pptx',
                    'zip',
                    'rar',
                    'mp4',
                    'avi',
                    'mov',
                    'png',
                    'jpg',
                    'jpeg'
                ];

                $extension = strtolower(
                    pathinfo($archivo['name'], PATHINFO_EXTENSION)
                );

                // Máximo 20 MB
                $maxSize = 20 * 1024 * 1024;

                if (!in_array($extension, $permitidos, true)) {

                    $error = "Formato de archivo no permitido.";

                } elseif ($archivo['size'] > $maxSize) {

                    $error = "El archivo supera el límite de 20 MB.";

                } else {

                    /*
                    |--------------------------------------------------------------------------
                    | CREAR DIRECTORIO
                    |--------------------------------------------------------------------------
                    */

                    $directorio = __DIR__ . '/../../uploads/entregas/';

                    if (!is_dir($directorio)) {
                        mkdir($directorio, 0777, true);
                    }

                    $nombre_archivo =
                        time() . '_' .
                        bin2hex(random_bytes(8)) . '.' .
                        $extension;

                    $ruta_fisica = $directorio . $nombre_archivo;
                    $ruta_bd = 'uploads/entregas/' . $nombre_archivo;

                    /*
                    |--------------------------------------------------------------------------
                    | GUARDAR ARCHIVO
                    |--------------------------------------------------------------------------
                    */

                    if (!move_uploaded_file(
                        $archivo['tmp_name'],
                        $ruta_fisica
                    )) {

                        $error = "No fue posible guardar el archivo.";

                    } else {

                        /*
                        |--------------------------------------------------------------------------
                        | GUARDAR ENTREGA
                        |--------------------------------------------------------------------------
                        */

                        try {

                            $stmtInsert = $pdo->prepare("
                                INSERT INTO entregas_actividades
                                (
                                    actividad_id,
                                    alumno_id,
                                    archivo,
                                    comentario,
                                    fecha_entrega,
                                    calificacion,
                                    retroalimentacion
                                )
                                VALUES (?, ?, ?, ?, NOW(), NULL, NULL)
                            ");

                            $stmtInsert->execute([
                                $actividad_id,
                                $alumno_id,
                                $ruta_bd,
                                trim($_POST['comentario'] ?? '')
                            ]);

                            $success = "Actividad entregada correctamente.";

                        } catch (Throwable $e) {

                            // Si falla BD, eliminar archivo huérfano.
                            if (file_exists($ruta_fisica)) {
                                unlink($ruta_fisica);
                            }

                            $error = "No fue posible registrar la entrega.";
                        }
                    }
                }
            }
        }
    }
}

/*
|--------------------------------------------------------------------------
| OBTENER ACTIVIDADES
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare("
    SELECT
        a.id,
        a.nombre,
        a.descripcion,
        a.fecha_apertura,
        a.fecha_cierre,
        a.valor,
        a.material_archivo,
        a.activo,

        tm.tema,
        tm.subtemas,
        tm.video_referencia,
        tm.presentacion_infografia,
        tm.bibliografia_complementaria,
        tm.estilos_aprendizaje,
        tm.nombre_unidad,
        tm.parcial,

        ea.id AS entrega_id,
        ea.archivo AS archivo_entrega,
        ea.fecha_entrega,
        ea.calificacion,
        ea.retroalimentacion

    FROM actividades a

    LEFT JOIN temas_materia tm
        ON tm.id = a.tema_id

    LEFT JOIN entregas_actividades ea
        ON ea.actividad_id = a.id
       AND ea.alumno_id = ?

    WHERE a.asignacion_id = ?
      AND a.activo = 1

      AND (
            tm.estado = 'aprobado'
            OR tm.estado = 'enviado'
            OR tm.id IS NULL
      )

    ORDER BY
        tm.parcial ASC,
        a.fecha_cierre ASC
");

$stmt->execute([
    $alumno_id,
    $asignacion_id
]);

$actividades = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>

<div class="max-w-7xl mx-auto space-y-8">

    <!-- HEADER -->

    <div class="flex flex-col lg:flex-row justify-between gap-6">

        <div>

            <span class="text-[10px] uppercase tracking-[0.4em]
                         text-indigo-500 font-black">
                Portal Académico
            </span>

            <h1 class="text-4xl lg:text-5xl font-black
                       text-slate-900 mt-2">

                <?= htmlspecialchars($asignacion['materia']) ?>

            </h1>

            <p class="text-slate-500 mt-3">
                Docente:
                <strong>
                    <?= htmlspecialchars($asignacion['docente']) ?>
                </strong>
            </p>

        </div>

        <a
            href="?modulo=mis_actividades_alumno"
            class="self-start px-6 py-3 bg-slate-100
                   hover:bg-slate-200 rounded-2xl
                   font-black text-slate-700 transition"
        >
            ← Regresar
        </a>

    </div>

    <!-- MENSAJES -->

    <?php if (isset($success)): ?>

        <div class="bg-emerald-50 border border-emerald-200
                    text-emerald-700 p-5 rounded-2xl font-bold">
            <?= htmlspecialchars($success) ?>
        </div>

    <?php endif; ?>


    <?php if (isset($error)): ?>

        <div class="bg-red-50 border border-red-200
                    text-red-700 p-5 rounded-2xl font-bold">
            <?= htmlspecialchars($error) ?>
        </div>

    <?php endif; ?>


    <!-- SIN ACTIVIDADES -->

    <?php if (!$actividades): ?>

        <div class="bg-white border border-slate-200
                    rounded-3xl p-12 text-center">

            <div class="text-5xl mb-5">
                📝
            </div>

            <h2 class="text-2xl font-black text-slate-700">
                No hay actividades disponibles
            </h2>

            <p class="text-slate-500 mt-3">
                El docente todavía no ha publicado actividades
                para esta materia.
            </p>

        </div>

    <?php else: ?>

        <div class="grid grid-cols-1 xl:grid-cols-2 gap-8">

            <?php foreach ($actividades as $act): ?>

                <?php
                    $ahora = new DateTime();

                    $aperturaAct = !empty($act['fecha_apertura'])
                        ? new DateTime($act['fecha_apertura'])
                        : null;

                    $cierreAct = !empty($act['fecha_cierre'])
                        ? new DateTime($act['fecha_cierre'])
                        : null;

                    $todaviaNoAbre =
                        $aperturaAct &&
                        $ahora < $aperturaAct;

                    $vencida =
                        $cierreAct &&
                        $ahora > $cierreAct;

                    $puedeEntregar =
                        !$act['entrega_id'] &&
                        !$todaviaNoAbre &&
                        !$vencida;
                ?>

                <div class="bg-white border border-slate-200
                            rounded-[2.5rem] overflow-hidden shadow-sm">

                    <!-- TOP -->

                    <div class="bg-gradient-to-r
                                from-indigo-500 to-violet-500
                                p-7 text-white">

                        <div class="flex justify-between gap-4">

                            <div>

                                <p class="text-xs uppercase
                                          tracking-widest text-white/70
                                          font-black">

                                    <?= !empty($act['nombre_unidad'])
                                        ? htmlspecialchars($act['nombre_unidad'])
                                        : 'Actividad académica' ?>

                                </p>

                                <h2 class="text-2xl font-black mt-2">
                                    <?= htmlspecialchars($act['nombre']) ?>
                                </h2>

                            </div>

                            <?php if ($act['valor'] !== null): ?>

                                <span class="bg-white text-indigo-600
                                             px-4 py-2 rounded-xl
                                             h-fit font-black">

                                    <?= number_format((float)$act['valor'], 2) ?> pts

                                </span>

                            <?php endif; ?>

                        </div>

                    </div>


                    <div class="p-7 space-y-6">

                        <!-- DESCRIPCIÓN -->

                        <div>

                            <p class="text-xs uppercase tracking-widest
                                      font-black text-slate-400 mb-2">
                                Descripción
                            </p>

                            <div class="bg-slate-50 rounded-2xl p-5
                                        text-slate-600">

                                <?= nl2br(
                                    htmlspecialchars(
                                        $act['descripcion'] ?: 'Sin descripción.'
                                    )
                                ) ?>

                            </div>

                        </div>


                        <!-- FECHAS -->

                        <div class="grid grid-cols-2 gap-4">

                            <div class="bg-slate-50 rounded-2xl p-4">

                                <p class="text-xs text-slate-400 font-bold">
                                    Apertura
                                </p>

                                <p class="font-black text-slate-700 mt-1">

                                    <?= $act['fecha_apertura']
                                        ? date(
                                            'd/m/Y H:i',
                                            strtotime($act['fecha_apertura'])
                                        )
                                        : '—' ?>

                                </p>

                            </div>

                            <div class="bg-slate-50 rounded-2xl p-4">

                                <p class="text-xs text-slate-400 font-bold">
                                    Fecha límite
                                </p>

                                <p class="font-black text-slate-700 mt-1">

                                    <?= $act['fecha_cierre']
                                        ? date(
                                            'd/m/Y H:i',
                                            strtotime($act['fecha_cierre'])
                                        )
                                        : '—' ?>

                                </p>

                            </div>

                        </div>


                        <!-- MATERIAL -->

                        <?php if (!empty($act['video_referencia'])): ?>

                            <a
                                href="<?= htmlspecialchars($act['video_referencia']) ?>"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="block bg-red-50 border border-red-100
                                       text-red-700 rounded-2xl p-4 font-bold"
                            >
                                ▶ Ver video de apoyo
                            </a>

                        <?php endif; ?>


                        <?php if (!empty($act['presentacion_infografia'])): ?>

                            <a
                                href="<?= htmlspecialchars($act['presentacion_infografia']) ?>"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="block bg-cyan-50 border border-cyan-100
                                       text-cyan-700 rounded-2xl p-4 font-bold"
                            >
                                📊 Ver presentación / infografía
                            </a>

                        <?php endif; ?>


                        <?php if (!empty($act['material_archivo'])): ?>

                            <a
                                href="/sistema_academico/<?= htmlspecialchars($act['material_archivo']) ?>"
                                target="_blank"
                                class="block bg-emerald-50 border border-emerald-100
                                       text-emerald-700 rounded-2xl p-4 font-bold"
                            >
                                📎 Descargar material
                            </a>

                        <?php endif; ?>


                        <!-- ENTREGA REALIZADA -->

                        <?php if ($act['entrega_id']): ?>

                            <div class="bg-emerald-50 border border-emerald-200
                                        rounded-2xl p-5">

                                <p class="font-black text-emerald-700">
                                    ✓ Actividad entregada
                                </p>

                                <p class="text-sm text-emerald-600 mt-2">

                                    <?= $act['fecha_entrega']
                                        ? 'Entregada el ' .
                                          date(
                                              'd/m/Y H:i',
                                              strtotime($act['fecha_entrega'])
                                          )
                                        : '' ?>

                                </p>

                                <?php if (!empty($act['archivo_entrega'])): ?>

                                    <a
                                        href="/sistema_academico/<?= htmlspecialchars($act['archivo_entrega']) ?>"
                                        target="_blank"
                                        class="inline-block mt-4
                                               bg-emerald-600 text-white
                                               px-5 py-3 rounded-xl font-bold"
                                    >
                                        Ver archivo enviado
                                    </a>

                                <?php endif; ?>


                                <?php if ($act['calificacion'] !== null): ?>

                                    <div class="mt-4 bg-white rounded-xl p-4">

                                        <p class="text-xs uppercase
                                                  text-slate-400 font-bold">
                                            Calificación
                                        </p>

                                        <p class="text-2xl font-black
                                                  text-indigo-600 mt-1">

                                            <?= number_format(
                                                (float)$act['calificacion'],
                                                2
                                            ) ?>

                                        </p>

                                    </div>

                                <?php endif; ?>


                                <?php if (!empty($act['retroalimentacion'])): ?>

                                    <div class="mt-4 bg-white rounded-xl p-4">

                                        <p class="text-xs uppercase
                                                  text-slate-400 font-bold">
                                            Retroalimentación
                                        </p>

                                        <p class="text-slate-600 mt-2">

                                            <?= nl2br(
                                                htmlspecialchars(
                                                    $act['retroalimentacion']
                                                )
                                            ) ?>

                                        </p>

                                    </div>

                                <?php endif; ?>

                            </div>


                        <?php elseif ($todaviaNoAbre): ?>

                            <div class="bg-amber-50 border border-amber-200
                                        text-amber-700 p-5 rounded-2xl font-bold">

                                Esta actividad todavía no está disponible
                                para entrega.

                            </div>


                        <?php elseif ($vencida): ?>

                            <div class="bg-red-50 border border-red-200
                                        text-red-700 p-5 rounded-2xl font-bold">

                                La fecha límite de esta actividad ya terminó.

                            </div>


                        <?php elseif ($puedeEntregar): ?>

                            <!-- FORMULARIO -->

                            <form
                                method="POST"
                                enctype="multipart/form-data"
                                class="border-t border-slate-100
                                       pt-6 space-y-4"
                            ><input type="hidden" name="cufa_csrf" value="<?= htmlspecialchars(cufaToken()) ?>">

                                <input
                                    type="hidden"
                                    name="actividad_id"
                                    value="<?= (int)$act['id'] ?>"
                                >

                                <div>

                                    <label class="block font-black
                                                  text-slate-700 mb-2">
                                        Subir evidencia
                                    </label>

                                    <input
                                        type="file"
                                        name="archivo_entrega"
                                        required
                                        class="w-full border border-slate-200
                                               rounded-2xl p-4"
                                    >

                                    <p class="text-xs text-slate-400 mt-2">
                                        Tamaño máximo: 20 MB.
                                    </p>

                                </div>


                                <textarea
                                    name="comentario"
                                    rows="3"
                                    maxlength="1000"
                                    placeholder="Comentario opcional..."
                                    class="w-full border border-slate-200
                                           rounded-2xl p-4 resize-none"
                                ></textarea>


                                <button
                                    type="submit"
                                    class="w-full bg-indigo-600
                                           hover:bg-indigo-700
                                           text-white font-black
                                           py-4 rounded-2xl transition"
                                >
                                    Enviar actividad
                                </button>

                            </form>

                        <?php endif; ?>

                    </div>

                </div>

            <?php endforeach; ?>

        </div>

    <?php endif; ?>

</div>