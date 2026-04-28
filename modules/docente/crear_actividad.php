<?php
require_once __DIR__ . "/../../includes/db.php";

// Capturamos la asignación y la semana desde la URL
$asignacion_id = $_GET['asignacion_id'] ?? null;
$semana = $_GET['semana'] ?? null;

if (!$asignacion_id || !$semana) {
    echo "<p class='text-red-600 font-bold p-4'>Error: Parámetros de asignación o semana no válidos.</p>";
    exit;
}

$mensaje = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Limpieza de datos
    $nombre = trim($_POST['nombre']);
    $descripcion = trim($_POST['descripcion']);
    $fecha_apertura = $_POST['fecha_apertura'];
    $fecha_cierre = $_POST['fecha_cierre'];
    $valor = $_POST['valor'];
    $tipo_archivo = $_POST['tipo_archivo'];
    $material_url = trim($_POST['material_url']);
    
    $material_archivo = null;
    $error_validacion = false;

    /* 1. VALIDACIÓN ESTRICTA DE CAMPOS VACÍOS */
    if (
        empty($nombre) || empty($descripcion) || empty($fecha_apertura) || 
        empty($fecha_cierre) || empty($valor) || empty($tipo_archivo) || 
        empty($material_url) || !isset($_FILES['material_archivo']) || $_FILES['material_archivo']['error'] != 0
    ) {
        $mensaje = "<div class='bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-4'>
                        <b>Error:</b> Todos los campos son obligatorios, incluyendo el enlace y el archivo de apoyo.
                    </div>";
        $error_validacion = true;
    }

    if (!$error_validacion) {
        /* 2. PROCESO DE SUBIDA DE ARCHIVO */
        $carpeta = "../../uploads/materiales/";
        if (!is_dir($carpeta)) {
            mkdir($carpeta, 0777, true);
        }

        $extension = pathinfo($_FILES['material_archivo']['name'], PATHINFO_EXTENSION);
        $nombre_archivo = "SEM" . $semana . "_" . time() . "." . $extension;
        $ruta_final = $carpeta . $nombre_archivo;

        if (move_uploaded_file($_FILES['material_archivo']['tmp_name'], $ruta_final)) {
            $material_archivo = $nombre_archivo;

            /* 3. GUARDAR EN BASE DE DATOS */
            // Nota: Asegúrate de que tu tabla 'actividades' tenga las columnas fecha_apertura, fecha_cierre y semana
            try {
                $stmt = $pdo->prepare("
                    INSERT INTO actividades 
                    (asignacion_id, semana, nombre, descripcion, fecha_apertura, fecha_cierre, valor, tipo_archivo, material_url, material_archivo) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");

                $stmt->execute([
                    $asignacion_id,
                    $semana,
                    $nombre,
                    $descripcion,
                    $fecha_apertura,
                    $fecha_cierre,
                    $valor,
                    $tipo_archivo,
                    $material_url,
                    $material_archivo
                ]);

                $mensaje = "<div class='bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-4'>
                                <h4 class='font-bold'>¡Éxito!</h4>
                                <p>La actividad para la Semana $semana ha sido creada y publicada correctamente.</p>
                            </div>";
            } catch (PDOException $e) {
                $mensaje = "<p class='text-red-600'>Error en la base de datos: " . $e->getMessage() . "</p>";
            }
        } else {
            $mensaje = "<p class='text-red-600'>Error crítico al subir el archivo al servidor.</p>";
        }
    }
}
?>

<div class="max-w-4xl mx-auto">
    <div class="flex justify-between items-center mb-6">
        <h2 class="text-3xl font-extrabold text-gray-800">
            Configuración de Actividad <span class="text-indigo-600">- Semana <?= htmlspecialchars($semana) ?></span>
        </h2>
        <a href="docente_dashboard.php?modulo=semanas_actividades&asignacion_id=<?= $asignacion_id ?>&parcial=<?= $_GET['parcial'] ?? 1 ?>" 
           class="text-gray-500 hover:text-gray-700 font-medium">
           &larr; Cancelar y volver
        </a>
    </div>

    <?php echo $mensaje; ?>

    <form method="POST" enctype="multipart/form-data" class="space-y-6">
        
        <div class="bg-white shadow-sm rounded-xl border border-gray-200 p-6">
            <div class="grid grid-cols-1 gap-6">
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-2 uppercase tracking-wider">Título de la Actividad</label>
                    <input type="text" name="nombre" class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-indigo-500 focus:border-indigo-500 p-3 border" placeholder="Ej: Ensayo sobre la Coreografía Clásica" required>
                </div>

                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-2 uppercase tracking-wider">Instrucciones Detalladas</label>
                    <textarea name="descripcion" rows="5" class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-indigo-500 focus:border-indigo-500 p-3 border" placeholder="Describa paso a paso lo que el alumno debe realizar..." required></textarea>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="bg-white shadow-sm rounded-xl border border-gray-200 p-6">
                <h3 class="text-lg font-bold text-gray-800 mb-4 flex items-center">
                    <span class="mr-2"></span> Disponibilidad
                </h3>
                <div class="space-y-4">
                    <div>
                        <label class="block text-xs font-semibold text-gray-500 uppercase">Fecha de Apertura</label>
                        <input type="datetime-local" name="fecha_apertura" class="mt-1 w-full border-gray-300 rounded-lg p-2 border" required>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-500 uppercase">Fecha de Cierre (Límite)</label>
                        <input type="datetime-local" name="fecha_cierre" class="mt-1 w-full border-gray-300 rounded-lg p-2 border" required>
                    </div>
                </div>
            </div>

            <div class="bg-white shadow-sm rounded-xl border border-gray-200 p-6">
                <h3 class="text-lg font-bold text-gray-800 mb-4 flex items-center">
                    <span class="mr-2"></span> Calificación y Entrega
                </h3>
                <div class="space-y-4">
                    <div>
                        <label class="block text-xs font-semibold text-gray-500 uppercase">Valor (%)</label>
                        <input type="number" step="0.01" name="valor" class="mt-1 w-full border-gray-300 rounded-lg p-2 border" placeholder="0.00" required>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-500 uppercase">Formato de Archivo Permitido</label>
                        <select name="tipo_archivo" class="mt-1 w-full border-gray-300 rounded-lg p-2 border" required>
                            <option value="">-- Seleccionar formato --</option>
                            <option value="pdf">Documento PDF (.pdf)</option>
                            <option value="word">Microsoft Word (.docx)</option>
                            <option value="excel">Microsoft Excel (.xlsx)</option>
                            <option value="imagen">Imagen (JPG, PNG)</option>
                            <option value="video">Video (MP4, MOV)</option>
                            <option value="cualquiera">Cualquier formato</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>

        <div class="bg-indigo-50 shadow-sm rounded-xl border border-indigo-200 p-6">
            <h3 class="text-lg font-bold text-indigo-900 mb-4 flex items-center">
                <span class="mr-2"></span> Material de Apoyo Obligatorio
            </h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-xs font-semibold text-indigo-700 uppercase mb-1">Enlace de Referencia (URL)</label>
                    <input type="url" name="material_url" class="w-full border-indigo-300 rounded-lg p-2 border focus:ring-indigo-500" placeholder="https://ejemplo.com/recurso" required>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-indigo-700 uppercase mb-1">Subir Archivo Complementario</label>
                    <input type="file" name="material_archivo" class="w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-indigo-600 file:text-white hover:file:bg-indigo-700" required>
                </div>
            </div>
        </div>

        <div class="flex justify-end gap-4 pb-10">
            <button type="reset" class="px-6 py-3 bg-white border border-gray-300 text-gray-700 rounded-xl hover:bg-gray-50 font-bold transition">
                Limpiar Formulario
            </button>
            <button type="submit" class="px-10 py-3 bg-indigo-600 text-white rounded-xl hover:bg-indigo-700 font-bold shadow-lg shadow-indigo-200 transition transform hover:-translate-y-1">
                Publicar Actividad
            </button>
        </div>

    </form>
</div>