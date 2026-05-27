<?php
require_once __DIR__ . '/../../includes/db.php';

$docente_id    = $_SESSION['user_id'] ?? null;
$asignacion_id = $_GET['asignacion_id'] ?? null;

/* =========================================
    OBTENER MATERIAS DEL DOCENTE
   ========================================= */
$stmtMaterias = $pdo->prepare("
    SELECT 
        ad.id,
        m.nombre AS materia,
        g.nombre AS grupo
    FROM asignaciones_docentes ad
    JOIN materias m ON ad.materia_id = m.id
    JOIN grupos g ON ad.grupo_id = g.id
    WHERE ad.docente_id = ?
    ORDER BY m.nombre ASC
");
$stmtMaterias->execute([$docente_id]);
$materias = $stmtMaterias->fetchAll(PDO::FETCH_ASSOC);

/* =========================================
    VISTA: SELECCIÓN DE MATERIA
   ========================================= */
if (!$asignacion_id):
?>
    <h1 class="text-3xl font-black text-indigo-800 mb-8">
        Acta de Calificaciones
    </h1>

    <p class="text-gray-500 mb-8">
        Selecciona una asignatura para visualizar las calificaciones.
    </p>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <?php foreach ($materias as $m): ?>
            <a href="docente_dashboard.php?modulo=calificaciones&asignacion_id=<?= $m['id'] ?>"
               class="bg-white p-6 rounded-2xl shadow border-l-8 border-indigo-500 hover:bg-indigo-50 hover:shadow-xl transition">
                
                <div class="flex justify-between items-center mb-3">
                    <span class="bg-indigo-100 text-indigo-700 text-xs px-3 py-1 rounded-full font-bold uppercase">
                        Grupo <?= htmlspecialchars($m['grupo']) ?>
                    </span>
                    <span class="text-indigo-600 font-bold">
                        Entrar →
                    </span>
                </div>

                <h2 class="text-2xl font-black text-gray-800">
                    <?= htmlspecialchars($m['materia']) ?>
                </h2>
            </a>
        <?php endforeach; ?>
    </div>
<?php
    exit;
endif;

/* =========================================
    INFORMACIÓN DE LA MATERIA SELECCIONADA
   ========================================= */
$stmtInfo = $pdo->prepare("
    SELECT 
        ad.id,
        ad.grupo_id,
        ad.materia_id,
        m.nombre AS materia,
        g.nombre AS grupo
    FROM asignaciones_docentes ad
    JOIN materias m ON ad.materia_id = m.id
    JOIN grupos g ON ad.grupo_id = g.id
    WHERE ad.id = ?
");
$stmtInfo->execute([$asignacion_id]);
$info = $stmtInfo->fetch(PDO::FETCH_ASSOC);

if (!$info) {
    echo "<p class='text-red-600 font-bold'>Asignación no encontrada.</p>";
    exit;
}

/* =========================================
    OBTENER ALUMNOS DEL GRUPO
   ========================================= */
$stmtAlumnos = $pdo->prepare("
    SELECT 
        u.id,
        CONCAT(
            u.nombres,' ',
            COALESCE(u.apellido_paterno,''),' ',
            COALESCE(u.apellido_materno,'')
        ) AS nombre_completo
    FROM grupos_alumnos ga
    JOIN alumnos a ON ga.alumno_id = a.id
    JOIN usuarios u ON a.usuario_id = u.id
    WHERE ga.grupo_id = ?
    ORDER BY nombre_completo ASC
");
$stmtAlumnos->execute([$info['grupo_id']]);
$alumnos = $stmtAlumnos->fetchAll(PDO::FETCH_ASSOC);

/* =========================================
    PARCIALES OFICIALES
   ========================================= */
$stmtUni = $pdo->prepare("
    SELECT numero
    FROM parciales
    WHERE materia_id = ? AND grupo_id = ? AND activo = 1
    ORDER BY numero ASC
");
$stmtUni->execute([$info['materia_id'], $info['grupo_id']]);
$unidades = $stmtUni->fetchAll(PDO::FETCH_COLUMN);

/* =========================================
    PROCESO: GUARDAR CALIFICACIONES FINALES
   ========================================= */
if (isset($_POST['guardar_finales'])) {
    foreach ($alumnos as $a) {
        foreach ($unidades as $unidad) {
            
            // 1. Calcular Actividades
            $stmtActs = $pdo->prepare("
                SELECT a.id, a.valor, e.calificacion
                FROM actividades a
                LEFT JOIN entregas e ON e.actividad_id = a.id AND e.alumno_id = ?
                WHERE a.asignacion_id = ? AND a.parcial = ?
            ");
            $stmtActs->execute([$a['id'], $asignacion_id, $unidad]);
            $actividades = $stmtActs->fetchAll(PDO::FETCH_ASSOC);

            $promedio_actividades = 0;
            foreach ($actividades as $act) {
                $valor = floatval($act['valor']);
                $calif = floatval($act['calificacion']);
                if ($valor > 0) {
                    $promedio_actividades += ($calif * $valor) / 100;
                }
            }

            // 2. Calcular Examen (20%)
            $stmtExam = $pdo->prepare("
                SELECT calificacion
                FROM intentos_examen ie
                JOIN examenes e ON ie.examen_id = e.id
                WHERE ie.alumno_id = ? AND e.asignacion_id = ? AND e.parcial = ?
                ORDER BY ie.id DESC LIMIT 1
            ");
            $stmtExam->execute([$a['id'], $asignacion_id, $unidad]);
            $calificacion_examen = $stmtExam->fetchColumn() ?: 0;

            // 3. Promedio Final de la Unidad
            $promedio_final = $promedio_actividades + ($calificacion_examen * 0.20);
            if ($promedio_final > 100) $promedio_final = 100;

            // 4. Guardar o Actualizar en la BD
            $stmtExiste = $pdo->prepare("
                SELECT id FROM calificaciones_finales
                WHERE asignacion_id = ? AND alumno_id = ? AND unidad = ?
            ");
            $stmtExiste->execute([$asignacion_id, $a['id'], $unidad]);
            $existe = $stmtExiste->fetchColumn();

            if ($existe) {
                $stmtUpdate = $pdo->prepare("
                    UPDATE calificaciones_finales
                    SET promedio_actividades = ?, calificacion_examen = ?, calificacion_final = ?
                    WHERE id = ?
                ");
                $stmtUpdate->execute([$promedio_actividades, $calificacion_examen, $promedio_final, $existe]);
            } else {
                $stmtInsert = $pdo->prepare("
                    INSERT INTO calificaciones_finales
                    (asignacion_id, alumno_id, unidad, promedio_actividades, calificacion_examen, calificacion_final)
                    VALUES (?,?,?,?,?,?)
                ");
                $stmtInsert->execute([$asignacion_id, $a['id'], $unidad, $promedio_actividades, $calificacion_examen, $promedio_final]);
            }
        }
    }
    echo "<div class='mb-6 bg-green-100 text-green-700 p-4 rounded-xl font-bold'>Calificaciones actualizadas correctamente.</div>";
}
?>

<div class="flex justify-between items-center mb-8">
    <div>
        <h1 class="text-3xl font-black text-indigo-800">Acta de Calificaciones</h1>
        <p class="text-gray-500 mt-2">
            <?= htmlspecialchars($info['materia']) ?> | Grupo <?= htmlspecialchars($info['grupo']) ?>
        </p>
    </div>
    <form method="POST">
        <button type="submit" name="guardar_finales"
                class="bg-indigo-600 hover:bg-indigo-700 text-white px-6 py-3 rounded-2xl font-black shadow-lg transition">
            Actualizar Calificaciones
        </button>
    </form>
</div>

<div class="overflow-x-auto bg-white rounded-3xl shadow border border-gray-100">
    <table class="min-w-full">
        <thead class="bg-indigo-600 text-white">
            <tr>
                <th class="p-4 text-left">Alumno</th>
                <?php foreach ($unidades as $u): ?>
                    <th class="p-4 text-center">Unidad <?= $u ?></th>
                <?php endforeach; ?>
                <th class="p-4 text-center bg-green-600">Final</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($alumnos as $a): ?>
                <tr class="border-b hover:bg-gray-50">
                    <td class="p-4 font-semibold text-gray-700 whitespace-nowrap">
                        <?= htmlspecialchars($a['nombre_completo']) ?>
                    </td>

                    <?php
                    $suma_total = 0;
                    $contador_total = 0;

                    foreach ($unidades as $unidad):
                        $stmtCalif = $pdo->prepare("
                            SELECT calificacion_final FROM calificaciones_finales
                            WHERE asignacion_id = ? AND alumno_id = ? AND unidad = ?
                        ");
                        $stmtCalif->execute([$asignacion_id, $a['id'], $unidad]);
                        $final_unidad = $stmtCalif->fetchColumn() ?: 0;

                        $suma_total += $final_unidad;
                        $contador_total++;
                    ?>
                        <td class="p-4 text-center">
                            <div class="flex flex-col items-center">
                                <span class="font-black text-xl <?= $final_unidad >= 70 ? 'text-green-600' : 'text-red-500' ?>">
                                    <?= number_format($final_unidad, 1) ?>
                                </span>
                                <span class="text-[10px] text-gray-400 uppercase">Unidad</span>
                            </div>
                        </td>
                    <?php endforeach; ?>

                    <td class="p-4 text-center bg-green-50">
                        <?php $promedio_general = ($contador_total > 0) ? ($suma_total / $contador_total) : 0; ?>
                        <span class="font-black text-2xl <?= $promedio_general >= 70 ? 'text-green-700' : 'text-red-600' ?>">
                            <?= number_format($promedio_general, 1) ?>
                        </span>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<div class="grid grid-cols-1 md:grid-cols-4 gap-6 mt-10">
    <div class="bg-white p-6 rounded-2xl shadow border border-indigo-100">
        <p class="text-xs uppercase text-indigo-400 font-black mb-2">Total alumnos</p>
        <h3 class="text-4xl font-black text-indigo-700"><?= count($alumnos) ?></h3>
    </div>

    <div class="bg-white p-6 rounded-2xl shadow border border-green-100">
        <p class="text-xs uppercase text-green-400 font-black mb-2">Unidades</p>
        <h3 class="text-4xl font-black text-green-700"><?= count($unidades) ?></h3>
    </div>

    <div class="bg-white p-6 rounded-2xl shadow border border-purple-100">
        <p class="text-xs uppercase text-purple-400 font-black mb-2">Asignatura</p>
        <h3 class="text-lg font-black text-purple-700"><?= htmlspecialchars($info['materia']) ?></h3>
    </div>

    <div class="bg-white p-6 rounded-2xl shadow border border-pink-100">
        <p class="text-xs uppercase text-pink-400 font-black mb-2">Grupo</p>
        <h3 class="text-lg font-black text-pink-700"><?= htmlspecialchars($info['grupo']) ?></h3>
    </div>
</div>