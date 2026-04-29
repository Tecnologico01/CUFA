<?php
require_once __DIR__ . '/../../includes/db.php';

// Detectar el ID de cualquier forma posible
$asignacion_id = $_GET['asignacion_id'] ?? $_POST['asignacion_id'] ?? null;

if (!$asignacion_id) {
    echo "
    <div class='bg-red-600 text-white p-6 rounded-lg shadow-xl'>
        <h2 class='text-2xl font-bold'>🚨 ERROR CRÍTICO: ID extraviado</h2>
        <p class='mt-2'>El sistema no recibió el código de la materia. 
        URL actual: <span class='font-mono bg-red-800 p-1 rounded'>" . $_SERVER['REQUEST_URI'] . "</span></p>
        <a href='docente_dashboard.php?modulo=mis_materias' class='mt-4 inline-block underline italic'>Volver a Mis Materias</a>
    </div>";
    exit;
}

/* OBTENER MATERIA Y GRUPO */
$stmt = $pdo->prepare("
    SELECT m.nombre AS materia, g.nombre AS grupo
    FROM asignaciones_docentes a
    JOIN materias m ON a.materia_id = m.id
    JOIN grupos g ON a.grupo_id = g.id
    WHERE a.id = ?
");
$stmt->execute([$asignacion_id]);
$info = $stmt->fetch(PDO::FETCH_ASSOC);
?>

<div class="mb-8">
    <h1 class="text-4xl font-black text-gray-800"><?= htmlspecialchars($info['materia'] ?? 'Materia') ?></h1>
    <span class="bg-indigo-100 text-indigo-700 px-3 py-1 rounded-full text-sm font-bold">Grupo: <?= htmlspecialchars($info['grupo'] ?? 'N/A') ?></span>
</div>

<div class="grid grid-cols-1 md:grid-cols-3 gap-6">

    <a href="docente_dashboard.php?modulo=mis_actividades&asignacion_id=<?= $asignacion_id ?>"
       class="bg-green-600 hover:bg-green-700 text-white p-8 rounded-2xl shadow-lg transition transform hover:-translate-y-2 text-center">
        <span class="text-3xl">📝</span>
        <h2 class="text-xl font-bold mt-2">Actividades</h2>
        <p class="text-xs opacity-80">Tareas y materiales</p>
    </a>

    <a href="docente_dashboard.php?modulo=calificaciones&asignacion_id=<?= $asignacion_id ?>"
       class="bg-yellow-500 hover:bg-yellow-600 text-white p-8 rounded-2xl shadow-lg transition transform hover:-translate-y-2 text-center">
        <span class="text-3xl">📊</span>
        <h2 class="text-xl font-bold mt-2">Calificaciones</h2>
        <p class="text-xs opacity-80">Listas y promedios</p>
    </a>

    <a href="docente_dashboard.php?modulo=definir_unidades&asignacion=<?= $asignacion_id ?>" 
       class="bg-indigo-600 hover:bg-indigo-700 text-white p-8 rounded-2xl shadow-lg transition transform hover:-translate-y-2 text-center">
        <span class="text-3xl">📚</span>
        <h2 class="text-xl font-bold mt-2">Unidades Didácticas</h2>
        <p class="text-xs opacity-80">Configurar temas y objetivos</p>
    </a>
    
</div>