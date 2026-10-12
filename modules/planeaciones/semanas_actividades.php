<?php
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/permisos.php';

validarPermiso('ver_semanas_actividades');

$puedeCrearActividad = tienePermiso('crear_actividades');
$puedeSubirMaterial = tienePermiso('subir_material');
$puedeEditarActividad = tienePermiso('editar_actividades');

$asignacion_id = isset($_GET['asignacion_id'])
    ? (int) $_GET['asignacion_id']
    : (isset($_GET['asignacion']) ? (int) $_GET['asignacion'] : 0);

$parcial = isset($_GET['parcial'])
    ? (int) $_GET['parcial']
    : (isset($_GET['unidad']) ? (int) $_GET['unidad'] : 0);

if($asignacion_id <= 0 || $parcial <= 0){
    echo "
    <div class='bg-red-50 border border-red-200 text-red-700 p-6 rounded-2xl font-bold'>
        Parámetros insuficientes.
    </div>";
    exit;
}

/* =========================
   INFO DE ASIGNACIÓN
========================= */
$stmtInfo = $pdo->prepare("
    SELECT 
        ad.id,
        m.nombre AS materia,
        g.nombre AS grupo,
        p.nombre AS periodo,
        CONCAT(u.nombres, ' ', u.apellido_paterno, ' ', u.apellido_materno) AS docente
    FROM asignaciones_docentes ad
    INNER JOIN materias m 
        ON m.id = ad.materia_id
    INNER JOIN grupos g 
        ON g.id = ad.grupo_id
    INNER JOIN periodos p
        ON p.id = ad.periodo_id
    INNER JOIN docentes d
        ON d.id = ad.docente_id
    INNER JOIN usuarios u
        ON u.id = d.usuario_id
    WHERE ad.id = ?
    LIMIT 1
");

$stmtInfo->execute([$asignacion_id]);
$info = $stmtInfo->fetch(PDO::FETCH_ASSOC);

if(!$info){
    echo "
    <div class='bg-red-50 border border-red-200 text-red-700 p-6 rounded-2xl font-bold'>
        Asignación no encontrada.
    </div>";
    exit;
}

/* =========================
   CALCULAR RANGO DE SEMANAS
========================= */
$semanasPorParcial = 4;
$semana_inicio = (($parcial - 1) * $semanasPorParcial) + 1;
$semana_fin = $semana_inicio + ($semanasPorParcial - 1);

/* =========================
   ACTIVIDADES DEL RANGO
========================= */
$stmtActividades = $pdo->prepare("
    SELECT 
        id,
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
    FROM actividades
    WHERE asignacion_id = ?
    AND parcial = ?
    AND semana BETWEEN ? AND ?
    ORDER BY semana ASC, id ASC
");

$stmtActividades->execute([
    $asignacion_id,
    $parcial,
    $semana_inicio,
    $semana_fin
]);

$todas_las_actividades = $stmtActividades->fetchAll(PDO::FETCH_ASSOC);

$actividades_por_semana = [];

foreach($todas_las_actividades as $act){
    $sem = (int)$act['semana'];

    if(!isset($actividades_por_semana[$sem])){
        $actividades_por_semana[$sem] = [];
    }

    $actividades_por_semana[$sem][] = $act;
}

/* =========================
   TEMA DEL PARCIAL / UNIDAD
========================= */
$stmtTema = $pdo->prepare("
    SELECT 
        id,
        tema,
        nombre_unidad,
        orden
    FROM temas_materia
    WHERE asignacion_id = ?
    AND parcial = ?
    ORDER BY orden ASC, id ASC
");

$stmtTema->execute([
    $asignacion_id,
    $parcial
]);

$temas = $stmtTema->fetchAll(PDO::FETCH_ASSOC);

$tema_id_default = $temas[0]['id'] ?? 0;
$nombre_unidad = $temas[0]['nombre_unidad'] ?? '';
?>

<div class="max-w-7xl mx-auto p-6 bg-slate-50 min-h-screen animate-fade-in">

    <div class="mb-10 flex flex-col md:flex-row md:items-end md:justify-between gap-6">

        <div>
            <div class="flex items-center gap-2 mb-4">
                <a href="?modulo=mis_actividades&asignacion_id=<?= $asignacion_id ?>"
                   class="text-[10px] font-black uppercase tracking-widest text-slate-400 hover:text-indigo-600 transition-all">
                    Parciales
                </a>

                <span class="text-slate-300">/</span>

                <span class="text-[10px] font-black uppercase tracking-widest text-indigo-600">
                    Parcial <?= (int)$parcial ?>
                </span>
            </div>

            <h1 class="text-5xl font-black italic uppercase tracking-tighter text-slate-800 leading-none">
                Semanas <?= (int)$semana_inicio ?> a <?= (int)$semana_fin ?>
            </h1>

            <p class="text-slate-500 font-semibold mt-3">
                <?= htmlspecialchars($info['materia']) ?>
                · Grupo <?= htmlspecialchars($info['grupo']) ?>
                · <?= htmlspecialchars($info['periodo']) ?>
            </p>

            <?php if($nombre_unidad): ?>
                <p class="text-purple-600 font-black text-[10px] uppercase tracking-widest mt-2">
                    <?= htmlspecialchars($nombre_unidad) ?>
                </p>
            <?php endif; ?>
        </div>

        <a href="?modulo=mis_actividades&asignacion_id=<?= $asignacion_id ?>"
           class="bg-slate-900 hover:bg-indigo-600 text-white px-6 py-4 rounded-2xl font-black uppercase text-[10px] tracking-widest transition-all">
            ← Volver
        </a>

    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">

        <?php for($i = 0; $i < $semanasPorParcial; $i++): ?>

            <?php
                $num_semana = $semana_inicio + $i;
                $items = $actividades_por_semana[$num_semana] ?? [];
                $tema_semana = $temas[$i]['id'] ?? $tema_id_default;
            ?>

            <div class="bg-white rounded-[2.5rem] shadow-sm border border-slate-100 flex flex-col overflow-hidden hover:shadow-xl transition-all">

                <div class="bg-slate-900 p-6 flex justify-between items-center">
                    <div>
                        <h3 class="text-xl font-black text-white uppercase tracking-tight">
                            Semana <?= (int)$num_semana ?>
                        </h3>

                        <?php if(!empty($temas[$i]['tema'])): ?>
                            <p class="text-[10px] text-slate-300 font-bold uppercase tracking-widest mt-1">
                                <?= htmlspecialchars($temas[$i]['tema']) ?>
                            </p>
                        <?php endif; ?>
                    </div>

                    <span class="text-[10px] font-black px-3 py-1 bg-white/10 text-white rounded-full uppercase tracking-widest border border-white/10">
                        <?= count($items) ?> elementos
                    </span>
                </div>

                <div class="p-6 flex-grow space-y-4 min-h-[220px]">

                    <?php if(empty($items)): ?>

                        <div class="flex flex-col items-center justify-center h-full text-slate-300 py-10 border-2 border-dashed border-slate-100 rounded-[2rem]">
                            <span class="text-5xl mb-3">📁</span>
                            <p class="text-sm font-black italic uppercase tracking-widest text-center">
                                Sin actividades ni material
                            </p>
                        </div>

                    <?php else: ?>

                        <?php foreach($items as $item): ?>

                            <?php
                                $esMaterial = (($item['tipo_archivo'] ?? '') === 'material') || ((float)$item['valor'] == 0);
                                $cerrada = !empty($item['fecha_cierre']) && strtotime($item['fecha_cierre']) < time();
                            ?>

                            <div class="group p-5 border border-slate-100 rounded-[2rem] hover:border-indigo-200 hover:bg-indigo-50/40 transition-all">

                                <div class="flex items-start justify-between gap-4">

                                    <div class="flex gap-4">
                                        <span class="text-3xl">
                                            <?= $esMaterial ? '📚' : '📝' ?>
                                        </span>

                                        <div>
                                            <h4 class="text-sm font-black text-slate-800 leading-tight uppercase">
                                                <?= htmlspecialchars($item['nombre']) ?>
                                            </h4>

                                            <p class="text-[10px] text-slate-400 font-bold mt-1 uppercase tracking-widest">
                                                <?= $esMaterial ? 'Material de apoyo' : 'Actividad evaluable' ?>
                                            </p>

                                            <?php if(!empty($item['fecha_cierre'])): ?>
                                                <p class="text-xs mt-2 <?= $cerrada ? 'text-red-500' : 'text-emerald-600' ?> font-bold">
                                                    Cierre:
                                                    <?= date('d/m/Y H:i', strtotime($item['fecha_cierre'])) ?>
                                                </p>
                                            <?php endif; ?>
                                        </div>
                                    </div>

                                    <?php if($puedeEditarActividad): ?>
                                        <a href="?modulo=editar_actividad&id=<?= (int)$item['id'] ?>"
                                           class="opacity-0 group-hover:opacity-100 bg-blue-600 text-white px-3 py-2 rounded-xl text-[9px] font-black uppercase tracking-widest transition-all">
                                            Editar
                                        </a>
                                    <?php endif; ?>

                                </div>

                                <?php if(!empty($item['descripcion'])): ?>
                                    <p class="text-xs text-slate-500 font-medium mt-4 leading-relaxed">
                                        <?= htmlspecialchars($item['descripcion']) ?>
                                    </p>
                                <?php endif; ?>

                                <div class="mt-4 flex flex-wrap gap-2">

                                    <?php if(!$esMaterial): ?>
                                        <span class="text-[9px] bg-purple-100 text-purple-700 px-3 py-1 rounded-full border border-purple-200 font-black uppercase tracking-widest">
                                            <?= (float)$item['valor'] ?>%
                                        </span>
                                    <?php endif; ?>

                                    <?php if(!empty($item['material_url'])): ?>
                                        <a href="<?= htmlspecialchars($item['material_url']) ?>"
                                           target="_blank"
                                           class="text-[9px] bg-green-100 text-green-700 px-3 py-1 rounded-full border border-green-200 font-black uppercase tracking-widest">
                                            🔗 Link
                                        </a>
                                    <?php endif; ?>

                                    <?php if(!empty($item['material_archivo'])): ?>
                                        <a href="/sistema_academico/<?= htmlspecialchars($item['material_archivo']) ?>"
                                           target="_blank"
                                           class="text-[9px] bg-orange-100 text-orange-700 px-3 py-1 rounded-full border border-orange-200 font-black uppercase tracking-widest">
                                            📄 Archivo
                                        </a>
                                    <?php endif; ?>

                                    <?php if($cerrada): ?>
                                        <span class="text-[9px] bg-red-100 text-red-700 px-3 py-1 rounded-full border border-red-200 font-black uppercase tracking-widest">
                                            Cerrada
                                        </span>
                                    <?php endif; ?>

                                </div>

                            </div>

                        <?php endforeach; ?>

                    <?php endif; ?>

                </div>

                <div class="bg-slate-50 p-5 border-t border-slate-100 grid grid-cols-1 md:grid-cols-2 gap-3">

                    <?php if($puedeCrearActividad): ?>
                        <a href="?modulo=crear_actividad&asignacion_id=<?= $asignacion_id ?>&semana=<?= $num_semana ?>&parcial=<?= $parcial ?>&tema_id=<?= (int)$tema_semana ?>"
                           class="flex items-center justify-center gap-2 bg-indigo-600 text-white text-[10px] font-black uppercase tracking-widest py-4 rounded-2xl hover:bg-indigo-700 shadow-sm transition-all">
                            ➕ Actividad
                        </a>
                    <?php endif; ?>

                    <?php if($puedeSubirMaterial): ?>
                        <a href="?modulo=subir_material&asignacion_id=<?= $asignacion_id ?>&semana=<?= $num_semana ?>&parcial=<?= $parcial ?>&tema_id=<?= (int)$tema_semana ?>"
                           class="flex items-center justify-center gap-2 bg-white border border-slate-200 text-slate-700 text-[10px] font-black uppercase tracking-widest py-4 rounded-2xl hover:bg-slate-100 transition-all">
                            📚 Material
                        </a>
                    <?php endif; ?>

                </div>

            </div>

        <?php endfor; ?>

    </div>

</div>

<style>
@keyframes slide-up {
    from {
        opacity: 0;
        transform: translateY(20px);
    }

    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.animate-fade-in {
    animation: slide-up 0.6s cubic-bezier(0.16, 1, 0.3, 1);
}
</style>