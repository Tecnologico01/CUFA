<?php
require_once __DIR__ . '/../../includes/db.php';

$asignacion_id = $_GET['asignacion_id'] ?? null;

if (!$asignacion_id) {
    echo "<p class='text-red-600 font-bold'>Asignación no especificada</p>";
    exit;
}

/* OBTENER INFORMACIÓN GENERAL */
$stmtInfo = $pdo->prepare("
    SELECT m.nombre AS materia, g.nombre AS grupo 
    FROM asignaciones_docentes a
    JOIN materias m ON a.materia_id = m.id
    JOIN grupos g ON a.grupo_id = g.id
    WHERE a.id = ?
");
$stmtInfo->execute([$asignacion_id]);
$info = $stmtInfo->fetch(PDO::FETCH_ASSOC);

/* OBTENER LOS 4 PARCIALES/TEMAS */
$stmtTemas = $pdo->prepare("
    SELECT parcial, tema, descripcion 
    FROM temas_materia 
    WHERE asignacion_id = ? 
    ORDER BY parcial ASC
");
$stmtTemas->execute([$asignacion_id]);
$temas = $stmtTemas->fetchAll(PDO::FETCH_ASSOC);
?>

<h1 class="text-3xl font-bold mb-2"><?= htmlspecialchars($info['materia']) ?></h1>
<p class="text-gray-600 mb-8">Seleccione un parcial para gestionar las actividades por semana.</p>

<div class="grid grid-cols-1 md:grid-cols-2 gap-6">
    <?php foreach ($temas as $t): ?>
        <a href="docente_dashboard.php?modulo=semanas_actividades&asignacion_id=<?= $asignacion_id ?>&parcial=<?= $t['parcial'] ?>" 
           class="block bg-white border-l-8 border-indigo-600 p-6 rounded-xl shadow-md hover:shadow-xl hover:bg-indigo-50 transition-all group">
            
            <div class="flex justify-between items-center mb-4">
                <span class="bg-indigo-100 text-indigo-700 text-xs font-bold px-3 py-1 rounded-full uppercase">
                    Parcial <?= $t['parcial'] ?>
                </span>
                <span class="text-indigo-600 group-hover:translate-x-2 transition-transform">
                    Ver semanas →
                </span>
            </div>

            <h2 class="text-xl font-bold text-gray-800 mb-2">
                <?= htmlspecialchars($t['tema']) ?>
            </h2>
            
            <p class="text-gray-500 text-sm line-clamp-2">
                <?= htmlspecialchars($t['descripcion']) ?>
            </p>
        </a>
    <?php endforeach; ?>
</div>