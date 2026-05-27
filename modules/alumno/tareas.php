<?php
require_once __DIR__ . '/../../includes/db.php';

$usuario_id = $_SESSION['user_id'] ?? null;

if(!$usuario_id){
    die("Sesión no válida");
}

/*
|--------------------------------------------------------------------------
| OBTENER ALUMNO
|--------------------------------------------------------------------------
*/

$stmtAlumno = $pdo->prepare("
    SELECT 
        a.id AS alumno_id,
        a.numero_control,
        a.carrera_id,
        a.grado
    FROM alumnos a
    WHERE a.usuario_id = ?
    LIMIT 1
");

$stmtAlumno->execute([$usuario_id]);

$alumno = $stmtAlumno->fetch(PDO::FETCH_ASSOC);

if(!$alumno){
    echo "
    <div class='bg-red-50 border border-red-200 text-red-700 p-6 rounded-3xl'>
        No existe un alumno vinculado a este usuario.
    </div>
    ";
    exit;
}

$alumno_id = $alumno['alumno_id'];

/*
|--------------------------------------------------------------------------
| OBTENER GRUPO DEL ALUMNO
|--------------------------------------------------------------------------
*/

$stmtGrupo = $pdo->prepare("
    SELECT 
        ga.grupo_id,
        g.nombre AS grupo
    FROM grupos_alumnos ga

    INNER JOIN grupos g
        ON ga.grupo_id = g.id

    WHERE ga.alumno_id = ?

    ORDER BY ga.id DESC
    LIMIT 1
");

$stmtGrupo->execute([
    $alumno_id
]);

$grupo = $stmtGrupo->fetch(PDO::FETCH_ASSOC);

if(!$grupo){

    echo "
    <div class='bg-yellow-50 border border-yellow-200 text-yellow-700 p-6 rounded-3xl'>
        No estás asignado a ningún grupo actualmente.
    </div>
    ";

    exit;
}

$grupo_id = $grupo['grupo_id'];

/*
|--------------------------------------------------------------------------
| OBTENER MATERIA SELECCIONADA
|--------------------------------------------------------------------------
*/

$materia_id = $_GET['materia_id'] ?? null;

if(!$materia_id){

    echo "
    <div class='bg-yellow-50 border border-yellow-200 text-yellow-700 p-6 rounded-3xl'>
        Materia no válida.
    </div>
    ";

    exit;
}

/*
|--------------------------------------------------------------------------
| SUBIR ACTIVIDAD
|--------------------------------------------------------------------------
*/

if(
    $_SERVER['REQUEST_METHOD'] === 'POST'
    && isset($_POST['actividad_id'])
){

    $actividad_id = (int)$_POST['actividad_id'];

    if(
        isset($_FILES['archivo_entrega'])
        && $_FILES['archivo_entrega']['error'] === 0
    ){

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

        $archivo = $_FILES['archivo_entrega'];

        $extension = strtolower(
            pathinfo($archivo['name'], PATHINFO_EXTENSION)
        );

        if(!in_array($extension, $permitidos)){

            $error = "Formato de archivo no permitido.";

        } else {

            $directorio = __DIR__ . "/../../uploads/entregas/";

            if(!is_dir($directorio)){
                mkdir($directorio, 0777, true);
            }

            $nombre_archivo = time() . "_" . uniqid() . "." . $extension;

            $ruta_fisica = $directorio . $nombre_archivo;

            move_uploaded_file(
                $archivo['tmp_name'],
                $ruta_fisica
            );

            $ruta_bd = "uploads/entregas/" . $nombre_archivo;

            /*
            |--------------------------------------------------------------------------
            | VERIFICAR SI YA EXISTE ENTREGA
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

            $existe = $stmtExiste->fetchColumn();

            if($existe){

                $error = "Ya entregaste esta actividad y no puedes volver a enviarla.";

            } else {

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
                    VALUES
                    (?,?,?,?,NOW(),NULL,NULL)
                ");

                $stmtInsert->execute([
                    $actividad_id,
                    $alumno_id,
                    $ruta_bd,
                    $_POST['comentario'] ?? ''
                ]);

                $success = "Actividad entregada correctamente.";
            }
        }
    }
}

/*
|--------------------------------------------------------------------------
| OBTENER ACTIVIDADES
|--------------------------------------------------------------------------
|
| SOLO:
| - DEL GRUPO DEL ALUMNO
| - VISIBLES
| - APROBADAS O ENVIADAS
|
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

        m.nombre AS materia,

        CONCAT(
            u.nombres,
            ' ',
            u.apellido_paterno,
            ' ',
            u.apellido_materno
        ) AS docente,

        ea.id AS entrega_id,
        ea.archivo AS archivo_entrega,
        ea.fecha_entrega

    FROM actividades a

    LEFT JOIN temas_materia tm
    ON a.tema_id = tm.id

    INNER JOIN asignaciones_docentes ad
        ON a.asignacion_id = ad.id

    INNER JOIN materias m
        ON ad.materia_id = m.id

    INNER JOIN docentes d
        ON ad.docente_id = d.id

    INNER JOIN usuarios u
        ON d.usuario_id = u.id

    LEFT JOIN entregas_actividades ea
        ON ea.actividad_id = a.id
        AND ea.alumno_id = ?

    WHERE ad.grupo_id = ?
    AND ad.materia_id = ?

    AND (
        tm.estado = 'aprobado'
        OR tm.estado = 'enviado'
    )

    AND (
        a.activo = 1
    )

    ORDER BY tm.parcial ASC, a.fecha_cierre ASC
");

$stmt->execute([
    $alumno_id,
    $grupo_id,
    $materia_id
]);

$actividades = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>

<div class="max-w-7xl mx-auto">

    <!-- HEADER -->

    <div class="mb-12">

        <div class="flex flex-col lg:flex-row justify-between gap-8">

            <div>

                <span class="text-[10px] uppercase tracking-[0.4em] text-indigo-500 font-black">
                    Portal Académico
                </span>

                <h1 class="text-5xl font-black tracking-tighter text-slate-900 mt-3">
                    Mis <span class="text-indigo-600">Actividades</span>
                </h1>

                <p class="text-slate-500 mt-5 max-w-2xl leading-relaxed">
                    Consulta las actividades publicadas por tus docentes,
                    descarga materiales de apoyo y sube tus evidencias.
                </p>

            </div>

            <div class="bg-white border border-slate-200 rounded-[2rem] shadow-xl px-8 py-6 min-w-[240px]">

                <p class="text-[10px] uppercase tracking-[0.3em] text-slate-400 font-black">
                    Grupo Actual
                </p>

                <h2 class="text-3xl font-black text-indigo-600 mt-3">
                    <?= htmlspecialchars($grupo['grupo']) ?>
                </h2>

                <div class="mt-5 border-t border-slate-100 pt-5">

                    <p class="text-sm text-slate-500">
                        Actividades Disponibles
                    </p>

                    <h3 class="text-4xl font-black text-slate-800 mt-1">
                        <?= count($actividades) ?>
                    </h3>

                </div>

            </div>

        </div>

    </div>

    <?php if(isset($success)): ?>

        <div class="bg-emerald-50 border border-emerald-200 text-emerald-700 p-6 rounded-3xl mb-8 font-bold">
            <?= $success ?>
        </div>

    <?php endif; ?>

    <?php if(isset($error)): ?>

        <div class="bg-red-50 border border-red-200 text-red-700 p-6 rounded-3xl mb-8 font-bold">
            <?= $error ?>
        </div>

    <?php endif; ?>

    <?php if(empty($actividades)): ?>

        <div class="bg-white border border-slate-200 rounded-[3rem] p-16 text-center">

            <h2 class="text-3xl font-black text-slate-700">
                No hay actividades disponibles
            </h2>

            <p class="text-slate-500 mt-4">
                Tus docentes aún no han publicado actividades visibles.
            </p>

        </div>

    <?php else: ?>

        <div class="grid grid-cols-1 xl:grid-cols-2 gap-10">

            <?php foreach($actividades as $act): ?>

                <div class="bg-white border border-slate-200 rounded-[3rem] overflow-hidden shadow-sm hover:shadow-2xl transition-all duration-500">

                    <!-- TOP -->

                    <div class="bg-gradient-to-r from-indigo-500 to-violet-500 p-8 text-white">

                        <div class="flex items-center justify-between mb-5">

                            <span class="bg-white/20 px-4 py-2 rounded-full text-[10px] uppercase tracking-[0.3em] font-black">
                                Unidad <?= $act['parcial'] ?>
                            </span>

                            <span class="bg-white text-indigo-600 px-4 py-2 rounded-full text-xs font-black">
                                <?= $act['valor'] ?>%
                            </span>

                        </div>

                        <h2 class="text-3xl font-black">
                            <?= htmlspecialchars($act['nombre']) ?>
                        </h2>

                        <div class="mt-5 flex flex-wrap gap-3">

                            <span class="bg-white/15 px-4 py-2 rounded-xl text-xs font-bold">
                                <?= htmlspecialchars($act['materia']) ?>
                            </span>

                            <span class="bg-white/15 px-4 py-2 rounded-xl text-xs font-bold">
                                <?= htmlspecialchars($act['docente']) ?>
                            </span>

                        </div>

                    </div>

                    <!-- BODY -->

                    <div class="p-10 space-y-8">

                        <div>

                            <h3 class="text-sm font-black uppercase tracking-[0.25em] text-slate-400 mb-4">
                                Descripción
                            </h3>

                            <div class="bg-slate-50 border border-slate-100 rounded-2xl p-6 text-sm text-slate-600 leading-relaxed">
                                <?= nl2br(htmlspecialchars($act['descripcion'])) ?>
                            </div>

                        </div>

                        <!-- FECHAS -->

                        <div class="grid grid-cols-2 gap-5">

                            <div class="bg-slate-50 border border-slate-100 rounded-2xl p-5">

                                <span class="text-[10px] uppercase tracking-[0.2em] text-slate-400 font-black block mb-2">
                                    Apertura
                                </span>

                                <p class="font-black text-slate-700">
                                    <?= $act['fecha_apertura'] ? date('d/m/Y', strtotime($act['fecha_apertura'])) : '---' ?>
                                </p>

                            </div>

                            <div class="bg-slate-50 border border-slate-100 rounded-2xl p-5">

                                <span class="text-[10px] uppercase tracking-[0.2em] text-slate-400 font-black block mb-2">
                                    Entrega
                                </span>

                                <p class="font-black text-rose-500">
                                    <?= $act['fecha_cierre'] ? date('d/m/Y', strtotime($act['fecha_cierre'])) : '---' ?>
                                </p>

                            </div>

                        </div>

                        <!-- MATERIAL -->

                        <div class="space-y-4">

                            <h3 class="text-sm font-black uppercase tracking-[0.25em] text-slate-400">
                                Material de Apoyo
                            </h3>

                            <?php if(!empty($act['video_referencia'])): ?>

                                <a href="<?= htmlspecialchars($act['video_referencia']) ?>"
                                   target="_blank"
                                   class="block bg-red-50 border border-red-100 rounded-2xl p-5">

                                    <p class="font-black text-red-600">
                                        Video de apoyo
                                    </p>

                                </a>

                            <?php endif; ?>

                            <?php if(!empty($act['presentacion_infografia'])): ?>

                                <a href="<?= htmlspecialchars($act['presentacion_infografia']) ?>"
                                   target="_blank"
                                   class="block bg-cyan-50 border border-cyan-100 rounded-2xl p-5">

                                    <p class="font-black text-cyan-700">
                                        Presentación / Infografía
                                    </p>
                                </a>

                            <?php endif; ?>

                            <?php if(!empty($act['material_archivo'])): ?>

                                <a href="<?= '/sistema_academico/' . htmlspecialchars($act['material_archivo']) ?>"
                                target="_blank"
                                class="block bg-emerald-50 border border-emerald-100 rounded-2xl p-5">

                                    <p class="font-black text-emerald-700">
                                        Descargar rúbrica / material PDF
                                    </p>

                                </a>

                            <?php endif; ?>

                        </div>

                        <!-- ENTREGA -->

                        <div class="border-t border-slate-100 pt-8">

                            <?php if($act['entrega_id']): ?>

                                <div class="bg-emerald-50 border border-emerald-200 rounded-2xl p-5 mb-6">

                                    <p class="text-emerald-700 font-black">
                                        Actividad entregada
                                    </p>

                                    <p class="text-sm text-emerald-600 mt-2">
                                        Fecha:
                                        <?= date('d/m/Y H:i', strtotime($act['fecha_entrega'])) ?>
                                    </p>

                                    <a href="../<?= htmlspecialchars($act['archivo_entrega']) ?>"
                                       target="_blank"
                                       class="inline-block mt-4 text-sm font-black text-emerald-700 underline">

                                        Ver entrega

                                    </a>

                                </div>

                            <?php endif; ?>

                            <!-- FORM -->

                            <!-- FORM -->

                            <?php if(!$act['entrega_id']): ?>

                            <form method="POST"
                                enctype="multipart/form-data"
                                class="space-y-5">

                                <input type="hidden"
                                    name="actividad_id"
                                    value="<?= $act['id'] ?>">

                                <div>

                                    <label class="block text-sm font-black text-slate-700 mb-3">
                                        Subir Evidencia
                                    </label>

                                    <input type="file"
                                        name="archivo_entrega"
                                        required
                                        class="w-full border border-slate-200 rounded-2xl p-4">

                                </div>

                                <div>

                                    <textarea
                                        name="comentario"
                                        rows="4"
                                        placeholder="Comentario opcional..."
                                        class="w-full border border-slate-200 rounded-2xl p-5 resize-none"
                                    ></textarea>

                                </div>

                                <button
                                    type="submit"
                                    class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-black py-4 rounded-2xl transition-all duration-300">

                                    Enviar Actividad

                                </button>

                            </form>

                            <?php else: ?>

                            <div class="bg-slate-50 border border-slate-200 rounded-3xl p-6">

                                <div class="flex items-center gap-3 mb-4">

                                    <div class="w-12 h-12 rounded-2xl bg-emerald-100 flex items-center justify-center">
                                        ✓
                                    </div>

                                    <div>

                                        <h3 class="font-black text-emerald-700">
                                            Actividad enviada
                                        </h3>

                                        <p class="text-sm text-slate-500">
                                            Ya no es posible modificar esta entrega.
                                        </p>

                                    </div>

                                </div>

                                <a href="/sistema_academico/<?= htmlspecialchars($act['archivo_entrega']) ?>"
                                target="_blank"
                                class="inline-flex items-center gap-3 bg-indigo-600 hover:bg-indigo-700 text-white px-6 py-4 rounded-2xl font-black transition-all">

                                    Ver archivo enviado

                                </a>

                            </div>

                            <?php endif; ?>

                        </div>

                    </div>

                </div>

            <?php endforeach; ?>

        </div>

    <?php endif; ?>

</div>