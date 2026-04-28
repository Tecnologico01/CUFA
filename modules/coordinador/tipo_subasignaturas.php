<?php
require_once __DIR__ . '/../../includes/db.php';

// Guardar nuevo tipo
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['nombre_tipo'])) {
    $stmt = $pdo->prepare("INSERT INTO tipo_subasignaturas (nombre) VALUES (?)");
    $stmt->execute([$_POST['nombre_tipo']]);
}

$tipos = $pdo->query("SELECT * FROM tipo_subasignaturas ORDER BY id DESC")->fetchAll();
?>

<div class="max-w-4xl mx-auto p-6">
    <div class="bg-white p-8 rounded-3xl shadow-xl border border-gray-100">
        <h2 class="text-2xl font-black uppercase mb-6">Categorías de Subasignaturas</h2>
        
        <form method="POST" class="flex gap-4 mb-8">
            <input type="text" name="nombre_tipo" required placeholder="Ej. Prácticas Profesionales" 
                   class="flex-1 bg-gray-50 border border-gray-200 rounded-xl p-3 outline-none focus:border-indigo-500">
            <button type="submit" class="bg-indigo-600 text-white px-6 py-3 rounded-xl font-bold uppercase text-xs">
                Añadir Tipo
            </button>
        </form>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <?php foreach ($tipos as $t): ?>
                <div class="p-4 bg-gray-50 rounded-2xl border border-gray-100 font-bold text-gray-700 flex justify-between">
                    <?= htmlspecialchars($t['nombre']) ?>
                    <span class="text-[10px] text-gray-300">#<?= $t['id'] ?></span>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>