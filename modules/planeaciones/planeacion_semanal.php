<?php

require_once __DIR__ . "/../../includes/db.php";
require_once __DIR__ . "/../../includes/permisos.php";

validarPermiso('ver_planeaciones');

$puedeCrear = tienePermiso('crear_planeaciones');
$puedeEditar = tienePermiso('editar_planeaciones');

$asignacion_id = isset($_GET['asignacion_id'])
    ? (int) $_GET['asignacion_id']
    : (isset($_GET['asignacion']) ? (int) $_GET['asignacion'] : 0);

if($asignacion_id <= 0){
    echo "
    <div class='bg-red-50 border border-red-200 text-red-700 p-6 rounded-2xl font-bold'>
        Asignación no válida.
    </div>";
    exit;
}

/* =========================
   OBTENER INFORMACIÓN
========================= */
$stmt = $pdo->prepare("
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

$stmt->execute([$asignacion_id]);
$info = $stmt->fetch(PDO::FETCH_ASSOC);

if(!$info){
    echo "
    <div class='bg-red-50 border border-red-200 text-red-700 p-6 rounded-2xl font-bold'>
        Asignación no encontrada.
    </div>";
    exit;
}

/* =========================
   OBTENER PLANEACIÓN SEMANAL
========================= */
$stmt = $pdo->prepare("
    SELECT
        ps.id,
        ps.asignacion_id,
        ps.tema_id,
        ps.semana,
        ps.objetivo,
        ps.descripcion_tema,
        ps.material_url,
        ps.actividad_nombre,
        ps.actividad_descripcion,
        ps.rubrica_archivo AS rubrica,
        ps.fecha_apertura,
        ps.fecha_cierre,
        tm.parcial,
        tm.tema,
        tm.subtemas,
        tm.nombre_unidad,
        tm.estado
    FROM planeacion_semanal ps
    LEFT JOIN temas_materia tm
        ON tm.id = ps.tema_id
    WHERE ps.asignacion_id = ?
    ORDER BY 
        COALESCE(tm.parcial, 0) ASC,
        ps.semana ASC,
        ps.id ASC
");

$stmt->execute([$asignacion_id]);
$planeaciones = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* =========================
   TEMAS DISPONIBLES
========================= */
$stmtTemas = $pdo->prepare("
    SELECT
        id,
        parcial,
        orden,
        tema,
        nombre_unidad
    FROM temas_materia
    WHERE asignacion_id = ?
    ORDER BY parcial ASC, orden ASC, id ASC
");

$stmtTemas->execute([$asignacion_id]);
$temas = $stmtTemas->fetchAll(PDO::FETCH_ASSOC);

$totalPlaneaciones = count($planeaciones);
?>

<div class="max-w-7xl mx-auto p-6 bg-slate-50 min-h-screen animate-fade-in">

    <div class="mb-10 flex flex-col md:flex-row md:items-end md:justify-between gap-6">

        <div>
            <span class="bg-indigo-100 text-indigo-700 px-4 py-2 rounded-full text-[10px] font-black uppercase tracking-[0.25em]">
                Planeación
            </span>

            <h1 class="text-5xl font-black italic uppercase tracking-tighter text-slate-800 leading-none mt-5">
                Planeación Semanal
            </h1>

            <p class="text-slate-500 font-semibold mt-3">
                <?= htmlspecialchars($info['materia']) ?>
                · Grupo <?= htmlspecialchars($info['grupo']) ?>
                · <?= htmlspecialchars($info['periodo']) ?>
            </p>

            <p class="text-[10px] text-slate-400 font-black uppercase tracking-widest mt-2">
                Docente: <?= htmlspecialchars($info['docente']) ?>
            </p>
        </div>

        <div class="flex flex-wrap gap-3">

            <a href="?modulo=ver_materia&asignacion_id=<?= $asignacion_id ?>"
               class="bg-slate-900 hover:bg-indigo-600 text-white px-6 py-4 rounded-2xl font-black uppercase text-[10px] tracking-widest transition-all">
                ← Volver
            </a>

            <?php if($puedeCrear): ?>
                <a href="?modulo=crear_planeacion&asignacion_id=<?= $asignacion_id ?>"
                   class="bg-emerald-600 hover:bg-emerald-700 text-white px-6 py-4 rounded-2xl font-black uppercase text-[10px] tracking-widest transition-all">
                    + Agregar
                </a>
            <?php endif; ?>

        </div>

    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-10">

        <div class="bg-white p-6 rounded-[2rem] border border-slate-100 shadow-sm">
            <p class="text-[10px] font-black uppercase tracking-widest text-slate-400">
                Registros
            </p>
            <h3 class="text-3xl font-black text-indigo-600 mt-2">
                <?= (int) $totalPlaneaciones ?>
            </h3>
        </div>

        <div class="bg-white p-6 rounded-[2rem] border border-slate-100 shadow-sm">
            <p class="text-[10px] font-black uppercase tracking-widest text-slate-400">
                Temas definidos
            </p>
            <h3 class="text-3xl font-black text-purple-600 mt-2">
                <?= count($temas) ?>
            </h3>
        </div>

        <div class="bg-white p-6 rounded-[2rem] border border-slate-100 shadow-sm">
            <p class="text-[10px] font-black uppercase tracking-widest text-slate-400">
                Asignación
            </p>
            <h3 class="text-xl font-black text-slate-800 mt-2">
                #<?= str_pad($asignacion_id, 4, '0', STR_PAD_LEFT) ?>
            </h3>
        </div>

    </div>

    <?php if($totalPlaneaciones > 0): ?>

        <div class="bg-white shadow-xl shadow-slate-200/50 rounded-[2.5rem] border border-slate-100 overflow-hidden">

            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">

                    <thead class="bg-slate-900 text-white">
                        <tr>
                            <th class="p-5 text-left text-[10px] font-black uppercase tracking-widest">Unidad</th>
                            <th class="p-5 text-center text-[10px] font-black uppercase tracking-widest">Semana</th>
                            <th class="p-5 text-left text-[10px] font-black uppercase tracking-widest">Tema</th>
                            <th class="p-5 text-left text-[10px] font-black uppercase tracking-widest">Objetivo</th>
                            <th class="p-5 text-left text-[10px] font-black uppercase tracking-widest">Actividad</th>
                            <th class="p-5 text-center text-[10px] font-black uppercase tracking-widest">Fechas</th>
                            <th class="p-5 text-center text-[10px] font-black uppercase tracking-widest">Acciones</th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-slate-100">
                        <?php foreach($planeaciones as $p): ?>
                            <tr class="hover:bg-slate-50 transition-colors">

                                <td class="p-5 align-top">
                                    <span class="bg-indigo-50 text-indigo-600 px-3 py-1 rounded-full text-[10px] font-black uppercase">
                                        Unidad <?= (int)($p['parcial'] ?? 0) ?>
                                    </span>

                                    <?php if(!empty($p['nombre_unidad'])): ?>
                                        <p class="text-[10px] font-bold text-slate-400 mt-2">
                                            <?= htmlspecialchars($p['nombre_unidad']) ?>
                                        </p>
                                    <?php endif; ?>
                                </td>

                                <td class="p-5 align-top text-center">
                                    <span class="text-xl font-black text-slate-700">
                                        <?= (int)$p['semana'] ?>
                                    </span>
                                </td>

                                <td class="p-5 align-top max-w-xs">
                                    <strong class="block text-slate-800 font-black">
                                        <?= htmlspecialchars($p['tema'] ?: 'Sin tema vinculado') ?>
                                    </strong>

                                    <?php if(!empty($p['subtemas'])): ?>
                                        <span class="block text-[10px] text-slate-400 font-semibold mt-2">
                                            <?= htmlspecialchars($p['subtemas']) ?>
                                        </span>
                                    <?php endif; ?>
                                </td>

                                <td class="p-5 align-top max-w-xs">
                                    <p class="text-slate-500 font-medium">
                                        <?= nl2br(htmlspecialchars($p['objetivo'] ?? '')) ?>
                                    </p>
                                </td>

                                <td class="p-5 align-top max-w-xs">
                                    <strong class="block text-slate-700">
                                        <?= htmlspecialchars($p['actividad_nombre'] ?: 'Sin actividad') ?>
                                    </strong>

                                    <?php if(!empty($p['actividad_descripcion'])): ?>
                                        <span class="block text-[10px] text-slate-400 font-semibold mt-2">
                                            <?= htmlspecialchars($p['actividad_descripcion']) ?>
                                        </span>
                                    <?php endif; ?>

                                    <?php if(!empty($p['material_url'])): ?>
                                        <a href="<?= htmlspecialchars($p['material_url']) ?>"
                                           target="_blank"
                                           class="inline-block mt-2 text-[10px] font-black text-indigo-600 uppercase">
                                            Ver material
                                        </a>
                                    <?php endif; ?>
                                </td>

                                <td class="p-5 align-top text-center">
                                    <p class="text-[10px] font-black text-slate-400 uppercase">
                                        Apertura
                                    </p>
                                    <p class="text-xs font-bold text-slate-600 mb-2">
                                        <?= htmlspecialchars($p['fecha_apertura'] ?: '—') ?>
                                    </p>

                                    <p class="text-[10px] font-black text-slate-400 uppercase">
                                        Cierre
                                    </p>
                                    <p class="text-xs font-bold text-slate-600">
                                        <?= htmlspecialchars($p['fecha_cierre'] ?: '—') ?>
                                    </p>
                                </td>

                                <td class="p-5 align-top text-center">
                                    <?php if($puedeEditar): ?>
                                        <a href="?modulo=editar_planeacion&id=<?= (int)$p['id'] ?>&asignacion_id=<?= $asignacion_id ?>"
                                           class="inline-block bg-blue-600 hover:bg-blue-700 text-white px-4 py-3 rounded-2xl text-[10px] font-black uppercase tracking-widest transition-all">
                                            Editar
                                        </a>
                                    <?php else: ?>
                                        <span class="text-[10px] text-slate-300 font-black uppercase">
                                            Solo lectura
                                        </span>
                                    <?php endif; ?>
                                </td>

                            </tr>
                        <?php endforeach; ?>
                    </tbody>

                </table>
            </div>

        </div>

    <?php else: ?>

        <div class="bg-white rounded-[2.5rem] border border-slate-100 p-16 text-center shadow-sm">

            <div class="text-6xl mb-4">📅</div>

            <h2 class="text-2xl font-black text-slate-800 uppercase mb-3">
                Sin planeación semanal
            </h2>

            <p class="text-slate-400 font-bold max-w-xl mx-auto mb-8">
                Todavía no se han registrado planeaciones semanales para esta asignación.
            </p>

            <?php if($puedeCrear): ?>
                <a href="?modulo=crear_planeacion&asignacion_id=<?= $asignacion_id ?>"
                   class="inline-block bg-emerald-600 hover:bg-emerald-700 text-white px-8 py-4 rounded-2xl font-black uppercase text-[10px] tracking-widest transition-all">
                    Crear primera planeación
                </a>
            <?php endif; ?>

        </div>

    <?php endif; ?>

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