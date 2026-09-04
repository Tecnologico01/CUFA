<?php
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/permisos.php';

validarPermiso('subir_material');

$asignacion_id = isset($_GET['asignacion_id']) ? (int) $_GET['asignacion_id'] : 0;
$semana = isset($_GET['semana']) ? (int) $_GET['semana'] : 0;
$tema_id = isset($_GET['tema_id']) ? (int) $_GET['tema_id'] : 0;
$parcial = isset($_GET['parcial']) ? (int) $_GET['parcial'] : 1;

if(!$asignacion_id || !$semana || !$tema_id){
    echo "
    <div class='max-w-2xl mx-auto'>
        <div class='bg-red-50 border border-red-200 text-red-700 rounded-3xl p-10 text-center'>
            <div class='text-5xl mb-4'>⚠️</div>
            <h2 class='text-2xl font-black uppercase mb-3'>Parámetros incompletos</h2>
            <p class='font-semibold'>Faltan datos de asignación, semana o tema para subir el material.</p>
        </div>
    </div>
    ";
    exit;
}

/* =========================
   INFO GENERAL
========================= */
$stmtInfo = $pdo->prepare("
    SELECT
        ad.id,
        m.nombre AS materia,
        g.nombre AS grupo,
        p.nombre AS periodo
    FROM asignaciones_docentes ad
    INNER JOIN materias m
        ON m.id = ad.materia_id
    INNER JOIN grupos g
        ON g.id = ad.grupo_id
    INNER JOIN periodos p
        ON p.id = ad.periodo_id
    WHERE ad.id = ?
");

$stmtInfo->execute([$asignacion_id]);
$info = $stmtInfo->fetch(PDO::FETCH_ASSOC);

if(!$info){
    echo "
    <div class='bg-red-50 border border-red-200 text-red-700 p-8 rounded-3xl font-bold'>
        La asignación no existe.
    </div>
    ";
    exit;
}

/* =========================
   VALIDAR TEMA
========================= */
$stmtTema = $pdo->prepare("
    SELECT id, tema, parcial, orden
    FROM temas_materia
    WHERE id = ?
    AND asignacion_id = ?
    LIMIT 1
");

$stmtTema->execute([$tema_id, $asignacion_id]);
$tema = $stmtTema->fetch(PDO::FETCH_ASSOC);

if(!$tema){
    echo "
    <div class='bg-red-50 border border-red-200 text-red-700 p-8 rounded-3xl font-bold'>
        El tema seleccionado no pertenece a esta asignación.
    </div>
    ";
    exit;
}

$mensaje = null;

/* =========================
   GUARDAR MATERIAL
========================= */
if($_SERVER['REQUEST_METHOD'] === 'POST'){

    $nombre_recurso = trim($_POST['nombre_recurso'] ?? '');
    $descripcion = trim($_POST['descripcion'] ?? '');
    $url_externa = trim($_POST['url_externa'] ?? '');

    if($nombre_recurso === '' || $descripcion === ''){
        $mensaje = [
            'tipo' => 'error',
            'texto' => 'El nombre del recurso y la descripción son obligatorios.'
        ];
    }else{

        try{

            $material_archivo = null;

            if(
                isset($_FILES['archivo_recurso']) &&
                $_FILES['archivo_recurso']['error'] === UPLOAD_ERR_OK
            ){

                $extension = strtolower(pathinfo(
                    $_FILES['archivo_recurso']['name'],
                    PATHINFO_EXTENSION
                ));

                $extPermitidas = [
                    'pdf',
                    'doc',
                    'docx',
                    'ppt',
                    'pptx',
                    'xls',
                    'xlsx',
                    'jpg',
                    'jpeg',
                    'png',
                    'mp4',
                    'zip'
                ];

                if(!in_array($extension, $extPermitidas)){
                    throw new Exception('Formato de archivo no permitido.');
                }

                $carpetaFisica = __DIR__ . '/uploads/materiales_puros/';

                if(!is_dir($carpetaFisica)){
                    mkdir($carpetaFisica, 0777, true);
                }

                $nombreArchivo = 'material_' . $asignacion_id . '_' . $semana . '_' . uniqid() . '.' . $extension;
                $rutaFisica = $carpetaFisica . $nombreArchivo;

                if(!move_uploaded_file($_FILES['archivo_recurso']['tmp_name'], $rutaFisica)){
                    throw new Exception('No se pudo subir el archivo al servidor.');
                }

                $material_archivo = 'modules/actividades/uploads/materiales_puros/' . $nombreArchivo;
            }

            if($url_externa === '' && !$material_archivo){
                throw new Exception('Debes proporcionar un enlace o subir un archivo.');
            }

            $stmt = $pdo->prepare("
                INSERT INTO actividades (
                    asignacion_id,
                    semana,
                    parcial,
                    tema_id,
                    nombre,
                    descripcion,
                    fecha_apertura,
                    fecha_cierre,
                    valor,
                    tipo_archivo,
                    material_url,
                    material_archivo
                ) VALUES (
                    ?,?,?,?,?,?,NOW(),'2030-12-31 23:59:59',0,'material',?,?
                )
            ");

            $stmt->execute([
                $asignacion_id,
                $semana,
                $parcial,
                $tema_id,
                $nombre_recurso,
                $descripcion,
                $url_externa ?: null,
                $material_archivo
            ]);

            echo "
            <script>
                alert('Material publicado correctamente.');
                window.location.href='?modulo=ver_unidad&asignacion_id={$asignacion_id}&unidad={$parcial}';
            </script>
            ";
            exit;

        }catch(Exception $e){

            $mensaje = [
                'tipo' => 'error',
                'texto' => $e->getMessage()
            ];
        }
    }
}
?>

<div class="max-w-5xl mx-auto">

    <div class="flex flex-col md:flex-row md:items-center justify-between gap-6 mb-10">

        <div>
            <span class="bg-indigo-100 text-indigo-700 px-4 py-2 rounded-full text-[10px] font-black uppercase tracking-[0.25em]">
                Material Didáctico
            </span>

            <h1 class="text-4xl font-black text-slate-900 mt-5 uppercase tracking-tight">
                Subir
                <span class="text-indigo-600 italic">
                    Material
                </span>
            </h1>

            <p class="text-slate-500 font-semibold mt-3">
                <?= htmlspecialchars($info['materia']) ?>
                · Grupo <?= htmlspecialchars($info['grupo']) ?>
                · Semana <?= (int)$semana ?>
            </p>
        </div>

        <a
            href="?modulo=ver_unidad&asignacion_id=<?= $asignacion_id ?>&unidad=<?= $parcial ?>"
            class="bg-slate-900 hover:bg-slate-700 text-white px-6 py-4 rounded-2xl font-black uppercase tracking-[0.2em] text-[10px] transition-all"
        >
            ← Volver
        </a>

    </div>

    <?php if($mensaje): ?>
        <div class="mb-8 rounded-3xl p-6 border <?= $mensaje['tipo'] === 'error' ? 'bg-red-50 border-red-200 text-red-700' : 'bg-emerald-50 border-emerald-200 text-emerald-700' ?>">
            <p class="font-black uppercase tracking-widest text-sm">
                <?= htmlspecialchars($mensaje['texto']) ?>
            </p>
        </div>
    <?php endif; ?>

    <div class="bg-white rounded-[2.5rem] shadow-xl border border-slate-100 overflow-hidden">

        <div class="bg-gradient-to-r from-indigo-600 to-purple-600 p-10 text-white">

            <div class="flex items-center gap-5">

                <div class="w-20 h-20 bg-white/20 rounded-[2rem] flex items-center justify-center text-4xl shadow-lg">
                    📎
                </div>

                <div>
                    <h2 class="text-3xl font-black uppercase tracking-tight">
                        Recurso de apoyo
                    </h2>

                    <p class="text-indigo-100 font-semibold mt-2">
                        Tema:
                        <?= htmlspecialchars($tema['tema']) ?>
                    </p>
                </div>

            </div>

        </div>

        <form method="POST" enctype="multipart/form-data" class="p-10 space-y-8">

            <div>
                <label class="block text-[10px] font-black uppercase tracking-[0.25em] text-slate-400 mb-3">
                    Nombre del material
                </label>

                <input
                    type="text"
                    name="nombre_recurso"
                    required
                    placeholder="Ej. Lectura complementaria, presentación, video de apoyo..."
                    class="w-full bg-slate-50 border-2 border-slate-100 focus:border-indigo-600 rounded-2xl px-6 py-5 font-bold outline-none transition-all"
                >
            </div>

            <div>
                <label class="block text-[10px] font-black uppercase tracking-[0.25em] text-slate-400 mb-3">
                    Descripción para el alumno
                </label>

                <textarea
                    name="descripcion"
                    rows="4"
                    required
                    placeholder="Explica cómo debe usar el alumno este material..."
                    class="w-full bg-slate-50 border-2 border-slate-100 focus:border-indigo-600 rounded-2xl px-6 py-5 font-semibold outline-none transition-all resize-none"
                ></textarea>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-8">

                <div>
                    <label class="block text-[10px] font-black uppercase tracking-[0.25em] text-slate-400 mb-3">
                        Enlace externo
                    </label>

                    <input
                        type="url"
                        name="url_externa"
                        placeholder="https://..."
                        class="w-full bg-slate-50 border-2 border-slate-100 focus:border-indigo-600 rounded-2xl px-6 py-5 font-bold outline-none transition-all"
                    >

                    <p class="text-xs text-slate-400 mt-3 font-semibold">
                        Puede ser un video, documento en línea o recurso web.
                    </p>
                </div>

                <div>
                    <label class="block text-[10px] font-black uppercase tracking-[0.25em] text-slate-400 mb-3">
                        Archivo complementario
                    </label>

                    <input
                        type="file"
                        name="archivo_recurso"
                        class="w-full bg-slate-50 border-2 border-slate-100 rounded-2xl px-5 py-4 text-sm font-semibold"
                    >

                    <p class="text-xs text-slate-400 mt-3 font-semibold">
                        Formatos permitidos: PDF, Word, PowerPoint, Excel, imagen, video o ZIP.
                    </p>
                </div>

            </div>

            <div class="bg-amber-50 border border-amber-200 rounded-3xl p-6">

                <div class="flex gap-4">

                    <div class="w-14 h-14 bg-amber-100 rounded-2xl flex items-center justify-center text-2xl shrink-0">
                        💡
                    </div>

                    <div>
                        <h3 class="font-black uppercase text-amber-700 tracking-wide mb-2">
                            Nota
                        </h3>

                        <p class="text-sm text-amber-700 font-semibold leading-relaxed">
                            Puedes proporcionar solo un enlace, solo un archivo o ambos.
                            El material se publicará como recurso de apoyo con valor 0.
                        </p>
                    </div>

                </div>

            </div>

            <div class="flex justify-end gap-4 pt-6">

                <a
                    href="?modulo=ver_unidad&asignacion_id=<?= $asignacion_id ?>&unidad=<?= $parcial ?>"
                    class="px-8 py-4 rounded-2xl bg-slate-200 hover:bg-slate-300 text-slate-700 font-black uppercase tracking-[0.2em] text-[10px] transition-all"
                >
                    Cancelar
                </a>

                <button
                    type="submit"
                    class="px-10 py-4 rounded-2xl bg-indigo-600 hover:bg-indigo-700 text-white font-black uppercase tracking-[0.25em] text-[10px] shadow-xl shadow-indigo-200 transition-all"
                >
                    Publicar Material
                </button>

            </div>

        </form>

    </div>

</div>