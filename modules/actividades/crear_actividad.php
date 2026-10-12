<?php
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/permisos.php';

validarPermiso('crear_actividades');

$asignacion_id = $_GET['asignacion_id'] ?? null;
$semana = $_GET['semana'] ?? null;
$parcial = $_GET['parcial'] ?? 1;

if (!$asignacion_id || !$semana) {

    echo "
    <div class='max-w-2xl mx-auto'>
        <div class='bg-red-50 border border-red-200 rounded-3xl p-10 text-center'>
            <div class='text-6xl mb-4'>⚠️</div>

            <h2 class='text-2xl font-black text-red-700 uppercase mb-4'>
                Parámetros inválidos
            </h2>

            <p class='text-red-600 font-semibold'>
                No se recibió la asignación o semana correctamente.
            </p>
        </div>
    </div>
    ";

    exit;
}

/* =========================
INFO DE MATERIA
========================= */

$stmt = $pdo->prepare("
    SELECT
        m.nombre AS materia,
        g.nombre AS grupo,
        p.nombre AS periodo
    FROM asignaciones_docentes ad
    INNER JOIN materias m
        ON m.id = ad.materia_id
    INNER JOIN grupos g
        ON g.id = ad.grupo_id
    INNER JOIN periodos p
        ON p.id = g.periodo_id
    WHERE ad.id = ?
");

$stmt->execute([$asignacion_id]);

$info = $stmt->fetch(PDO::FETCH_ASSOC);

if(!$info){

    echo "
    <div class='bg-red-50 border border-red-200 rounded-3xl p-8 text-red-700 font-bold'>
        Asignación no encontrada.
    </div>
    ";

    exit;
}

$mensaje = null;

/* =========================
GUARDAR ACTIVIDAD
========================= */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $nombre = trim($_POST['nombre']);
    $descripcion = trim($_POST['descripcion']);
    $fecha_apertura = $_POST['fecha_apertura'];
    $fecha_cierre = $_POST['fecha_cierre'];
    $valor = $_POST['valor'];
    $tipo_archivo = $_POST['tipo_archivo'];
    $material_url = trim($_POST['material_url']);

    if (
        empty($nombre) ||
        empty($descripcion) ||
        empty($fecha_apertura) ||
        empty($fecha_cierre) ||
        empty($valor)
    ) {

        $mensaje = [
            'tipo' => 'error',
            'texto' => 'Todos los campos obligatorios deben completarse.'
        ];

    } else {

        try {

            $material_archivo = null;

            /* =========================
            SUBIR ARCHIVO
            ========================= */

            if (
                isset($_FILES['material_archivo']) &&
                $_FILES['material_archivo']['error'] === 0
            ) {

                $upload_dir = __DIR__ . '/uploads/materiales/';

                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }

                $extension = strtolower(pathinfo(
                    $_FILES['material_archivo']['name'],
                    PATHINFO_EXTENSION
                ));

                $nombre_archivo = uniqid('actividad_') . '.' . $extension;

                $ruta_fisica = $upload_dir . $nombre_archivo;

                if (
                    move_uploaded_file(
                        $_FILES['material_archivo']['tmp_name'],
                        $ruta_fisica
                    )
                ) {

                    $material_archivo =
                        'modules/actividades/uploads/materiales/' .
                        $nombre_archivo;
                }
            }

            /* =========================
            INSERTAR ACTIVIDAD
            ========================= */

            $stmt = $pdo->prepare("
                INSERT INTO actividades (
                    asignacion_id,
                    semana,
                    parcial,
                    nombre,
                    descripcion,
                    fecha_apertura,
                    fecha_cierre,
                    valor,
                    tipo_archivo,
                    material_url,
                    material_archivo
                ) VALUES (
                    ?,?,?,?,?,?,?,?,?,?,?
                )
            ");

            $stmt->execute([
                $asignacion_id,
                $semana,
                $parcial,
                $nombre,
                $descripcion,
                $fecha_apertura,
                $fecha_cierre,
                $valor,
                $tipo_archivo,
                $material_url,
                $material_archivo
            ]);

            header("
Location: dashboard.php?modulo=mis_actividades&asignacion_id=$asignacion_id&ok=1
");

            exit;

        } catch (PDOException $e) {

            $mensaje = [
                'tipo' => 'error',
                'texto' => $e->getMessage()
            ];
        }
    }
}
?>

<div class="max-w-6xl mx-auto">

    <!-- HEADER -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-6 mb-10">

        <div>

            <span class="bg-indigo-100 text-indigo-700 px-4 py-2 rounded-full text-[10px] font-black uppercase tracking-[0.25em]">
                Nueva Actividad
            </span>

            <h1 class="text-4xl font-black text-slate-900 mt-5 uppercase tracking-tight">
                Crear
                <span class="text-indigo-600 italic">Actividad</span>
            </h1>

            <p class="text-slate-500 font-semibold mt-3">
                <?= htmlspecialchars($info['materia']) ?>
                · Grupo <?= htmlspecialchars($info['grupo']) ?>
                · Semana <?= $semana ?>
            </p>

        </div>

        <a
            href="?modulo=mis_actividades&asignacion_id=<?= $asignacion_id ?>"
            class="bg-slate-900 hover:bg-slate-800 text-white px-7 py-4 rounded-2xl font-black uppercase tracking-[0.2em] text-[11px] transition-all"
        >
            ← Volver
        </a>

    </div>

    <!-- MENSAJE -->
    <?php if($mensaje): ?>

        <div class="mb-8 rounded-3xl p-6 border
            <?= $mensaje['tipo'] === 'error'
                ? 'bg-red-50 border-red-200 text-red-700'
                : 'bg-emerald-50 border-emerald-200 text-emerald-700'
            ?>">
            <p class="font-black uppercase tracking-widest text-sm">
                <?= htmlspecialchars($mensaje['texto']) ?>
            </p>
        </div>

    <?php endif; ?>

    <!-- FORM -->
    <form method="POST" enctype="multipart/form-data">

        <div class="grid grid-cols-1 xl:grid-cols-3 gap-8">

            <!-- IZQUIERDA -->
            <div class="xl:col-span-2 space-y-8">

                <div class="bg-white rounded-[2rem] border border-slate-100 shadow-sm p-8">

                    <h2 class="text-xl font-black text-slate-900 uppercase mb-8">
                        Información General
                    </h2>

                    <div class="space-y-6">

                        <div>
                            <label class="block text-[10px] font-black uppercase tracking-widest text-slate-400 mb-3">
                                Nombre de la actividad
                            </label>

                            <input
                                type="text"
                                name="nombre"
                                required
                                class="w-full bg-slate-50 border border-slate-200 rounded-2xl p-5 font-bold outline-none focus:ring-4 focus:ring-indigo-100"
                            >
                        </div>

                        <div>
                            <label class="block text-[10px] font-black uppercase tracking-widest text-slate-400 mb-3">
                                Descripción
                            </label>

                            <textarea
                                name="descripcion"
                                rows="6"
                                required
                                class="w-full bg-slate-50 border border-slate-200 rounded-2xl p-5 font-medium outline-none focus:ring-4 focus:ring-indigo-100"
                            ></textarea>
                        </div>

                    </div>

                </div>

                <div class="bg-white rounded-[2rem] border border-slate-100 shadow-sm p-8">

                    <h2 class="text-xl font-black text-slate-900 uppercase mb-8">
                        Material de apoyo
                    </h2>

                    <div class="space-y-6">

                        <div>
                            <label class="block text-[10px] font-black uppercase tracking-widest text-slate-400 mb-3">
                                URL de referencia
                            </label>

                            <input
                                type="url"
                                name="material_url"
                                class="w-full bg-slate-50 border border-slate-200 rounded-2xl p-5 font-medium outline-none focus:ring-4 focus:ring-indigo-100"
                            >
                        </div>

                        <div>
                            <label class="block text-[10px] font-black uppercase tracking-widest text-slate-400 mb-3">
                                Archivo complementario
                            </label>

                            <input
                                type="file"
                                name="material_archivo"
                                class="w-full bg-slate-50 border border-slate-200 rounded-2xl p-4 text-sm"
                            >
                        </div>

                    </div>

                </div>

            </div>

            <!-- DERECHA -->
            <div class="space-y-8">

                <div class="bg-white rounded-[2rem] border border-slate-100 shadow-sm p-8">

                    <h2 class="text-xl font-black text-slate-900 uppercase mb-8">
                        Configuración
                    </h2>

                    <div class="space-y-6">

                        <div>
                            <label class="block text-[10px] font-black uppercase tracking-widest text-slate-400 mb-3">
                                Fecha apertura
                            </label>

                            <input
                                type="datetime-local"
                                name="fecha_apertura"
                                required
                                class="w-full bg-slate-50 border border-slate-200 rounded-2xl p-4 font-bold"
                            >
                        </div>

                        <div>
                            <label class="block text-[10px] font-black uppercase tracking-widest text-slate-400 mb-3">
                                Fecha cierre
                            </label>

                            <input
                                type="datetime-local"
                                name="fecha_cierre"
                                required
                                class="w-full bg-slate-50 border border-slate-200 rounded-2xl p-4 font-bold"
                            >
                        </div>

                        <div>
                            <label class="block text-[10px] font-black uppercase tracking-widest text-slate-400 mb-3">
                                Valor %
                            </label>

                            <input
                                type="number"
                                step="0.01"
                                name="valor"
                                required
                                class="w-full bg-slate-50 border border-slate-200 rounded-2xl p-4 font-bold"
                            >
                        </div>

                        <div>
                            <label class="block text-[10px] font-black uppercase tracking-widest text-slate-400 mb-3">
                                Tipo de archivo
                            </label>

                            <select
                                name="tipo_archivo"
                                required
                                class="w-full bg-slate-50 border border-slate-200 rounded-2xl p-4 font-bold"
                            >
                                <option value="">Seleccionar</option>
                                <option value="pdf">PDF</option>
                                <option value="word">WORD</option>
                                <option value="excel">EXCEL</option>
                                <option value="imagen">IMAGEN</option>
                                <option value="video">VIDEO</option>
                                <option value="zip">ZIP</option>
                                <option value="cualquiera">CUALQUIERA</option>
                            </select>
                        </div>

                    </div>

                </div>

                <button
                    type="submit"
                    class="w-full bg-indigo-600 hover:bg-indigo-700 text-white py-5 rounded-2xl font-black uppercase tracking-[0.25em] shadow-xl shadow-indigo-200 transition-all"
                >
                    Publicar Actividad
                </button>

            </div>

        </div>

    </form>

</div>