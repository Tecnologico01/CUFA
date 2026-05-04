<?php
require_once __DIR__ . '/../../includes/db.php';

$asignacion_id = $_GET['asignacion_id'] ?? null;

if (!$asignacion_id) {
    echo "<p class='text-red-600 font-bold'>Asignación no especificada</p>";
    exit;
}

/* INFO GENERAL */
$stmtInfo = $pdo->prepare("
    SELECT m.nombre AS materia, g.nombre AS grupo 
    FROM asignaciones_docentes a
    JOIN materias m ON a.materia_id = m.id
    JOIN grupos g ON a.grupo_id = g.id
    WHERE a.id = ?
");
$stmtInfo->execute([$asignacion_id]);
$info = $stmtInfo->fetch(PDO::FETCH_ASSOC);

/* UNIDADES */
$stmt = $pdo->prepare("
    SELECT parcial, MAX(nombre_unidad) as nombre_unidad
    FROM temas_materia
    WHERE asignacion_id = ?
    GROUP BY parcial
    ORDER BY parcial ASC
");
$stmt->execute([$asignacion_id]);
$unidades = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<h1 class="text-3xl font-black mb-2"><?= htmlspecialchars($info['materia']) ?></h1>
<p class="text-gray-600 mb-8">Seleccione una unidad para gestionar actividades y evaluación.</p>

<div class="grid grid-cols-1 md:grid-cols-2 gap-6">
<?php foreach ($unidades as $u): ?>
    <a href="docente_dashboard.php?modulo=ver_unidad&asignacion_id=<?= $asignacion_id ?>&unidad=<?= $u['parcial'] ?>"
       class="block bg-white border-l-8 border-indigo-600 p-6 rounded-xl shadow-md hover:shadow-xl hover:bg-indigo-50 transition-all">

        <div class="flex justify-between mb-3">
            <span class="bg-indigo-100 text-indigo-700 text-xs px-3 py-1 rounded-full font-bold">
                Unidad <?= $u['parcial'] ?>
            </span>
            <span class="text-indigo-600">Entrar →</span>
        </div>

        <h2 class="text-xl font-bold text-gray-800">
            <?= htmlspecialchars($u['nombre_unidad'] ?: 'Sin título') ?>
        </h2>
    </a>
<?php endforeach; ?>
</div>