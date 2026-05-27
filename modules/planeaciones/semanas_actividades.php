<?php
require_once __DIR__ . '/../../includes/db.php';

$asignacion_id = $_GET['asignacion_id'] ?? null;
$parcial = $_GET['parcial'] ?? null;

if (!$asignacion_id || !$parcial) {
    echo "<p class='text-red-600 font-bold'>Parámetros insuficientes.</p>";
    exit;
}

// 1. CALCULAR RANGO DE SEMANAS
$semana_inicio = (($parcial - 1) * 4) + 1;
$semana_fin = $semana_inicio + 3;

// 2. OBTENER ACTIVIDADES YA CREADAS EN ESTE RANGO
$stmtActividades = $pdo->prepare("
    SELECT * FROM actividades 
    WHERE asignacion_id = ? AND semana BETWEEN ? AND ?
    ORDER BY semana ASC, id ASC
");
$stmtActividades->execute([$asignacion_id, $semana_inicio, $semana_fin]);
$todas_las_actividades = $stmtActividades->fetchAll(PDO::FETCH_ASSOC);

// Agrupamos por semana para facilitar la impresión en el HTML
$actividades_por_semana = [];
foreach ($todas_las_actividades as $act) {
    $actividades_por_semana[$act['semana']][] = $act;
}

// OBTENER EL ID DEL TEMA PARA ESTE PARCIAL
$stmtTema = $pdo->prepare("SELECT id FROM temas_materia WHERE asignacion_id = ? AND parcial = ?");
$stmtTema->execute([$asignacion_id, $parcial]);
$tema_data = $stmtTema->fetch();
$tema_id = $tema_data['id'] ?? 0;
?>

<div class="mb-8 flex justify-between items-end">
    <div>
        <nav class="text-sm text-gray-500 mb-2">
            <a href="docente_dashboard.php?modulo=mis_actividades&asignacion_id=<?= $asignacion_id ?>" class="hover:text-indigo-600 underline">Parciales</a> 
            <span>/</span> 
            <span class="text-gray-800">Parcial <?= $parcial ?></span>
        </nav>
        <h2 class="text-3xl font-bold text-gray-800">Gestión de Semanas <?= $semana_inicio ?> a <?= $semana_fin ?></h2>
    </div>
</div>

<div class="grid grid-cols-1 md:grid-cols-2 gap-8">
    <?php for ($i = 0; $i < 4; $i++): 
        $num_semana = $semana_inicio + $i;
        $items = $actividades_por_semana[$num_semana] ?? [];
    ?>
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 flex flex-col overflow-hidden">
            <div class="bg-gray-50 border-b p-4 flex justify-between items-center">
                <h3 class="text-lg font-bold text-gray-700 uppercase tracking-tight">Semana <?= $num_semana ?></h3>
                <span class="text-xs font-medium px-2 py-1 bg-indigo-100 text-indigo-700 rounded-md">
                    <?= count($items) ?> Elementos
                </span>
            </div>

            <div class="p-4 flex-grow space-y-3 min-h-[150px]">
                <?php if (empty($items)): ?>
                    <div class="flex flex-col items-center justify-center h-full text-gray-400 py-8">
                        <span class="text-3xl mb-2">📁</span>
                        <p class="text-sm italic">Sin actividades ni material publicado</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($items as $item): ?>
                        <div class="group p-3 border rounded-lg hover:border-indigo-300 hover:bg-indigo-50 transition">
                            <div class="flex items-start justify-between">
                                <div class="flex gap-3">
                                    <span class="text-xl">📝</span>
                                    <div>
                                        <h4 class="text-sm font-bold text-gray-800 leading-tight"><?= htmlspecialchars($item['nombre']) ?></h4>
                                        <p class="text-xs text-gray-500 mt-1">
                                            Vence: <?= date('d/m/Y H:i', strtotime($item['fecha_cierre'])) ?>
                                        </p>
                                    </div>
                                </div>
                                <div class="flex gap-1 opacity-0 group-hover:opacity-100 transition">
                                    <button class="text-blue-500 hover:text-blue-700 text-xs font-bold">Editar</button>
                                </div>
                            </div>
                            
                            <div class="mt-2 flex gap-2">
                                <?php if (!empty($item['material_url'])): ?>
                                    <span class="text-[10px] bg-green-100 text-green-700 px-2 py-0.5 rounded border border-green-200">🔗 Link</span>
                                <?php endif; ?>
                                <?php if (!empty($item['material_archivo'])): ?>
                                    <span class="text-[10px] bg-orange-100 text-orange-700 px-2 py-0.5 rounded border border-orange-200">📄 Archivo</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <div class="bg-gray-50 p-4 border-t grid grid-cols-2 gap-3">
                <a href="docente_dashboard.php?modulo=crear_actividad&asignacion_id=<?= $asignacion_id ?>&semana=<?= $num_semana ?>&parcial=<?= $parcial ?>" 
                   class="flex items-center justify-center gap-1 bg-indigo-600 text-white text-xs font-bold py-2.5 rounded-lg hover:bg-indigo-700 shadow-sm transition">
                   <span>➕ Actividad</span>
                </a>
                <a href="docente_dashboard.php?modulo=subir_material&asignacion_id=<?= $asignacion_id ?>&semana=<?= $num_semana ?>&parcial=<?= $parcial ?>&tema_id=<?= $tema_id ?>" 
   class="flex items-center justify-center gap-1 bg-white border border-gray-300 text-gray-700 text-xs font-bold py-2.5 rounded-lg hover:bg-gray-50 transition">
   <span>📚 Material</span>
</a>
            </div>
        </div>
    <?php endfor; ?>
</div>