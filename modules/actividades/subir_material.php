<?php
require_once __DIR__ . "/../../includes/db.php";

$asignacion_id = $_GET['asignacion_id'] ?? null;
$semana = $_GET['semana'] ?? null;
$tema_id = $_GET['tema_id'] ?? null;

if (!$asignacion_id || !$semana || !$tema_id) {
    echo "<div class='p-4 bg-red-50 text-red-700 rounded-lg'><b>Error de sistema:</b> Faltan parámetros (Asignación, Semana o Tema) para procesar la carga.</div>";
    exit;
}

$mensaje = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nombre_recurso = trim($_POST['nombre_recurso']);
    $descripcion = trim($_POST['descripcion']);
    $url_externa = trim($_POST['url_externa']);
    $error_validacion = false;

    // Validación estricta: Todos los campos deben estar llenos
    if (empty($nombre_recurso) || empty($descripcion) || empty($url_externa) || !isset($_FILES['archivo_recurso']) || $_FILES['archivo_recurso']['error'] != 0) {
        $mensaje = "<div class='bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-4'><b>Acción Denegada:</b> Todos los campos son obligatorios para garantizar la calidad del material didáctico.</div>";
        $error_validacion = true;
    }

    if (!$error_validacion) {
        $carpeta = "../../uploads/materiales_puros/";
        if (!is_dir($carpeta)) mkdir($carpeta, 0777, true);

        $nombre_archivo = "MAT_SEM" . $semana . "_" . time() . "_" . $_FILES['archivo_recurso']['name'];
        $ruta_final = $carpeta . $nombre_archivo;

        if (move_uploaded_file($_FILES['archivo_recurso']['tmp_name'], $ruta_final)) {
            // Guardamos en la tabla de actividades pero marcándolo como material (valor 0 o tipo especial)
            // O si tienes una tabla 'materiales', cámbialo aquí.
            $stmt = $pdo->prepare("
                INSERT INTO actividades 
                (asignacion_id, semana, nombre, descripcion, fecha_apertura, fecha_cierre, valor, tipo_archivo, material_url, material_archivo) 
                VALUES (?, ?, ?, ?, NOW(), '2030-12-31 23:59:59', 0, 'material', ?, ?)
            ");

            if ($stmt->execute([$asignacion_id, $semana, $nombre_recurso, $descripcion, $url_externa, $nombre_archivo])) {
                $mensaje = "<div class='bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-4'><b>Éxito:</b> El recurso didáctico ha sido vinculado a la semana $semana.</div>";
            }
        }
    }
}
?>

<div class="max-w-4xl mx-auto">
    <div class="mb-6">
        <h2 class="text-3xl font-extrabold text-gray-800 flex items-center gap-2">
            <span class="text-indigo-600"></span> Subir Material Didáctico
        </h2>
        <p class="text-gray-500">Publicar recursos de apoyo para la <b>Semana <?= $semana ?></b></p>
    </div>

    <?php echo $mensaje; ?>

    <form method="POST" enctype="multipart/form-data" class="space-y-6">
        <div class="bg-white shadow-sm rounded-2xl border border-gray-200 overflow-hidden">
            <div class="bg-gray-50 p-4 border-b">
                <h3 class="font-bold text-gray-700">Información del Recurso</h3>
            </div>
            
            <div class="p-6 space-y-4">
                <div>
                    <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Nombre del Material / Recurso</label>
                    <input type="text" name="nombre_recurso" class="w-full border-gray-300 rounded-lg p-3 border focus:ring-2 focus:ring-indigo-500 outline-none" placeholder="Ej: Lectura complementaria sobre Redes..." required>
                </div>

                <div>
                    <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Breve Explicación para el Alumno</label>
                    <textarea name="descripcion" rows="3" class="w-full border-gray-300 rounded-lg p-3 border focus:ring-2 focus:ring-indigo-500 outline-none" placeholder="Explique por qué este material es importante..." required></textarea>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="bg-white shadow-sm rounded-2xl border border-gray-200 p-6">
                <label class="block text-xs font-bold text-gray-500 uppercase mb-3">Enlace Web (URL)</label>
                <div class="relative">
                    <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-400">🔗</span>
                    <input type="url" name="url_externa" class="w-full border-gray-300 rounded-lg p-2 pl-10 border outline-none" placeholder="https://youtube.com/..." required>
                </div>
            </div>

            <div class="bg-white shadow-sm rounded-2xl border border-gray-200 p-6">
                <label class="block text-xs font-bold text-gray-500 uppercase mb-3">Archivo PDF / Presentación</label>
                <input type="file" name="archivo_recurso" class="w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100" required>
            </div>
        </div>

        <div class="flex justify-end items-center gap-4">
            <a href="docente_dashboard.php?modulo=semanas_actividades&asignacion_id=<?= $asignacion_id ?>&parcial=<?= $_GET['parcial'] ?>" class="text-gray-500 hover:text-gray-800 font-semibold">Regresar</a>
            <button type="submit" class="bg-indigo-600 text-white px-8 py-3 rounded-xl font-bold shadow-lg shadow-indigo-100 hover:bg-indigo-700 transition transform hover:-translate-y-1">
                Publicar Recurso
            </button>
        </div>
    </form>
</div>