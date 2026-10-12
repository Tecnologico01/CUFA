<?php
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/permisos.php';

validarPermiso('ver_mis_actividades');

$asignacion_id = isset($_GET['asignacion_id'])
    ? (int) $_GET['asignacion_id']
    : 0;

if(!$asignacion_id){
    echo "
    <div class='bg-red-50 border border-red-200 text-red-700 p-8 rounded-3xl font-bold'>
        Asignación no especificada.
    </div>
    ";
    exit;
}

/* =========================
   INFO GENERAL
========================= */
$stmtInfo = $pdo->prepare("
    SELECT 
        ad.id,
        m.nombre AS materia,
        g.nombre AS grupo,
        p.nombre AS periodo
    FROM asignaciones_docentes ad
    INNER JOIN materias m 
        ON m.id = ad.materia_id
    INNER JOIN grupos g 
        ON g.id = ad.grupo_id
    INNER JOIN periodos p
        ON p.id = ad.periodo_id
    WHERE ad.id = ?
");

$stmtInfo->execute([$asignacion_id]);
$info = $stmtInfo->fetch(PDO::FETCH_ASSOC);

if(!$info){
    echo "
    <div class='bg-red-50 border border-red-200 text-red-700 p-8 rounded-3xl font-bold'>
        La asignación no existe.
    </div>
    ";
    exit;
}

/* =========================
   UNIDADES / PARCIALES
========================= */
$stmt = $pdo->prepare("
    SELECT 
        tm.parcial,
        COALESCE(MAX(tm.nombre_unidad), CONCAT('Unidad ', tm.parcial)) AS nombre_unidad,
        COUNT(DISTINCT tm.id) AS total_temas,
        COUNT(DISTINCT a.id) AS total_actividades,
        COUNT(DISTINCT e.id) AS total_examenes,
        MAX(tm.estado) AS estado
    FROM temas_materia tm
    LEFT JOIN actividades a
        ON a.asignacion_id = tm.asignacion_id
        AND a.parcial = tm.parcial
    LEFT JOIN examenes e
        ON e.asignacion_id = tm.asignacion_id
        AND e.parcial = tm.parcial
    WHERE tm.asignacion_id = ?
    GROUP BY tm.parcial
    ORDER BY tm.parcial ASC
");

$stmt->execute([$asignacion_id]);
$unidades = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>

<div class="max-w-7xl mx-auto">

    <!-- HEADER -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-6 mb-10">

        <div>
            <span class="bg-indigo-100 text-indigo-700 px-4 py-2 rounded-full text-[10px] font-black uppercase tracking-[0.25em]">
                Actividades
            </span>

            <h1 class="text-4xl font-black text-slate-900 mt-5 uppercase tracking-tight">
                Mis
                <span class="text-indigo-600 italic">
                    Actividades
                </span>
            </h1>

            <p class="text-slate-500 font-semibold mt-3">
                <?= htmlspecialchars($info['materia']) ?>
                · Grupo <?= htmlspecialchars($info['grupo']) ?>
                · <?= htmlspecialchars($info['periodo']) ?>
            </p>
        </div>

        <a
            href="?modulo=ver_materia&asignacion_id=<?= $asignacion_id ?>"
            class="bg-slate-900 hover:bg-slate-700 text-white px-6 py-4 rounded-2xl font-black uppercase tracking-[0.2em] text-[10px] transition-all"
        >
            ← Volver
        </a>

    </div>

    <?php if(isset($_GET['ok'])): ?>
        <div class="bg-emerald-50 border border-emerald-200 text-emerald-700 rounded-3xl p-6 mb-8">
            <div class="flex items-center gap-4">
                <div class="w-14 h-14 rounded-2xl bg-emerald-100 flex items-center justify-center text-2xl">
                    ✓
                </div>
                <div>
                    <h3 class="font-black uppercase tracking-wide mb-1">
                        Actividad registrada
                    </h3>
                    <p class="font-semibold">
                        La actividad fue creada correctamente.
                    </p>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <?php if(count($unidades) > 0): ?>

        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-8">

            <?php foreach($unidades as $u): ?>

                <?php
                    $estado = $u['estado'] ?? 'borrador';

                    $badgeClass = 'bg-slate-100 text-slate-500';
                    if($estado === 'enviado'){
                        $badgeClass = 'bg-amber-100 text-amber-600';
                    }elseif($estado === 'aprobado'){
                        $badgeClass = 'bg-emerald-100 text-emerald-600';
                    }elseif($estado === 'rechazado'){
                        $badgeClass = 'bg-red-100 text-red-600';
                    }
                ?>

                <a
                    href="?modulo=ver_unidad&asignacion_id=<?= $asignacion_id ?>&unidad=<?= (int)$u['parcial'] ?>"
                    class="group bg-white rounded-[2.5rem] shadow-sm hover:shadow-xl border border-slate-100 p-8 transition-all hover:-translate-y-1 block"
                >

                    <div class="flex items-start justify-between gap-4 mb-8">

                        <div class="w-16 h-16 bg-indigo-100 text-indigo-700 rounded-2xl flex items-center justify-center text-3xl">
                            📘
                        </div>

                        <span class="<?= $badgeClass ?> px-3 py-1 rounded-full text-[9px] font-black uppercase tracking-widest">
                            <?= htmlspecialchars($estado) ?>
                        </span>

                    </div>

                    <div class="mb-6">

                        <span class="inline-block bg-indigo-50 text-indigo-600 px-4 py-2 rounded-full text-[10px] font-black uppercase tracking-widest mb-4">
                            Unidad <?= (int)$u['parcial'] ?>
                        </span>

                        <h2 class="text-2xl font-black text-slate-900 uppercase tracking-tight group-hover:text-indigo-600 transition-colors">
                            <?= htmlspecialchars($u['nombre_unidad'] ?: 'Sin título') ?>
                        </h2>

                    </div>

                    <div class="grid grid-cols-3 gap-3 mb-8">

                        <div class="bg-slate-50 rounded-2xl p-4 text-center">
                            <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest">
                                Temas
                            </p>
                            <p class="text-xl font-black text-slate-700">
                                <?= (int)$u['total_temas'] ?>
                            </p>
                        </div>

                        <div class="bg-indigo-50 rounded-2xl p-4 text-center">
                            <p class="text-[9px] font-black text-indigo-400 uppercase tracking-widest">
                                Acts.
                            </p>
                            <p class="text-xl font-black text-indigo-700">
                                <?= (int)$u['total_actividades'] ?>
                            </p>
                        </div>

                        <div class="bg-purple-50 rounded-2xl p-4 text-center">
                            <p class="text-[9px] font-black text-purple-400 uppercase tracking-widest">
                                Exam.
                            </p>
                            <p class="text-xl font-black text-purple-700">
                                <?= (int)$u['total_examenes'] ?>
                            </p>
                        </div>

                    </div>

                    <div class="flex justify-between items-center pt-6 border-t border-slate-100">

                        <span class="text-[10px] font-black text-slate-400 uppercase tracking-widest">
                            Gestionar unidad
                        </span>

                        <span class="w-10 h-10 rounded-xl bg-slate-900 text-white flex items-center justify-center group-hover:bg-indigo-600 transition-all">
                            →
                        </span>

                    </div>

                </a>

            <?php endforeach; ?>

        </div>

    <?php else: ?>

        <div class="bg-white rounded-[2.5rem] shadow-sm border border-slate-100 p-16 text-center">

            <div class="w-20 h-20 bg-indigo-50 rounded-full flex items-center justify-center mx-auto mb-6 text-4xl">
                📘
            </div>

            <h2 class="text-2xl font-black text-slate-900 uppercase mb-4">
                Sin unidades registradas
            </h2>

            <p class="text-slate-500 font-semibold max-w-2xl mx-auto mb-8">
                Aún no existen unidades o temas registrados para esta asignación.
                Primero debes definir la planeación de la materia.
            </p>

            <?php if(tienePermiso('definir_unidades')): ?>
                <a
                    href="?modulo=definir_unidades&asignacion=<?= $asignacion_id ?>"
                    class="inline-block bg-indigo-600 hover:bg-indigo-700 text-white px-8 py-4 rounded-2xl font-black uppercase tracking-[0.2em] text-[10px] shadow-xl shadow-indigo-200 transition-all"
                >
                    Definir unidades
                </a>
            <?php endif; ?>

        </div>

    <?php endif; ?>

</div>