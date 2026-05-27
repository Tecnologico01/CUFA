<?php
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/permisos.php';

validarPermiso('ver_subasignaturas');

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

if($id <= 0){
    echo "<div class='p-6 bg-red-100 text-red-700 rounded-2xl font-bold'>Subasignatura no encontrada.</div>";
    exit;
}

$puedeEditar = tienePermiso('editar_subasignaturas');
$puedeEliminar = tienePermiso('eliminar_subasignaturas');

$stmt = $pdo->prepare("
    SELECT *
    FROM subasignaturas
    WHERE id = ?
");

$stmt->execute([$id]);
$suba = $stmt->fetch(PDO::FETCH_ASSOC);

if(!$suba){
    echo "<div class='p-6 bg-red-100 text-red-700 rounded-2xl font-bold'>La subasignatura no existe.</div>";
    exit;
}

/* Materias donde se usa */
$stmt = $pdo->prepare("
    SELECT 
        m.id,
        m.clave,
        m.nombre
    FROM materia_subasignatura ms
    INNER JOIN materias m
        ON m.id = ms.materia_id
    WHERE ms.subasignatura_id = ?
    ORDER BY m.nombre ASC
");

$stmt->execute([$id]);
$materias = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="max-w-5xl mx-auto p-6 bg-slate-50 min-h-screen animate-fade-in">

    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-6 mb-10">

        <div class="flex items-center gap-6">

            <a href="?modulo=lista_subasignaturas"
               class="group bg-white p-4 rounded-[1.5rem] shadow-sm border border-slate-100 text-slate-400 hover:text-purple-600 transition-all transform hover:-translate-x-1">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path d="M15 19l-7-7 7-7" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"></path>
                </svg>
            </a>

            <div>
                <h1 class="text-5xl font-black italic uppercase tracking-tighter text-slate-800 leading-none">
                    Subasignatura
                </h1>

                <p class="text-purple-600 font-bold text-[10px] uppercase tracking-[0.3em] italic mt-2">
                    Información técnica del módulo
                </p>
            </div>

        </div>

        <div class="flex gap-3">

            <?php if($puedeEditar): ?>
                <a href="?modulo=editar_subasignatura&id=<?= $suba['id'] ?>"
                   class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-4 rounded-2xl font-black uppercase text-[10px] tracking-widest transition-all">
                    Editar
                </a>
            <?php endif; ?>

            <?php if($puedeEliminar): ?>
                <a href="?modulo=eliminar_subasignatura&id=<?= $suba['id'] ?>"
                   onclick="return confirm('¿Seguro que deseas eliminar esta subasignatura?')"
                   class="bg-red-500 hover:bg-red-600 text-white px-6 py-4 rounded-2xl font-black uppercase text-[10px] tracking-widest transition-all">
                    Eliminar
                </a>
            <?php endif; ?>

        </div>

    </div>

    <div class="bg-white rounded-[3.5rem] shadow-sm border border-slate-100 overflow-hidden relative">

        <div class="absolute top-0 right-0 p-12 select-none pointer-events-none">
            <span class="text-[100px] font-black text-slate-50 italic leading-none uppercase">
                Data
            </span>
        </div>

        <div class="p-12 relative z-10">

            <div class="mb-12">
                <span class="inline-block px-4 py-1 bg-purple-50 text-purple-600 rounded-full text-[10px] font-black tracking-widest uppercase mb-4 border border-purple-100">
                    ID: <?= htmlspecialchars($suba['id']) ?> | Clave: <?= htmlspecialchars($suba['clave']) ?>
                </span>

                <h2 class="text-4xl font-black text-slate-800 leading-tight">
                    <?= htmlspecialchars($suba['nombre']) ?>
                </h2>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-10">

                <div class="space-y-1">
                    <label class="text-[9px] font-black text-slate-300 uppercase tracking-[0.2em] block">
                        Modalidad
                    </label>

                    <p class="text-sm font-black text-slate-600 italic uppercase">
                        <?= htmlspecialchars($suba['recurso'] ?: '—') ?>
                    </p>
                </div>

                <div class="space-y-1 md:border-l border-slate-100 md:pl-10">
                    <label class="text-[9px] font-black text-slate-300 uppercase tracking-[0.2em] block">
                        Horas Frente a Grupo
                    </label>

                    <p class="text-sm font-black text-slate-600">
                        <?= htmlspecialchars($suba['horas_frente_grupo'] ?: '0') ?>
                        <span class="text-[10px] text-slate-400 uppercase font-bold italic">
                            Horas
                        </span>
                    </p>
                </div>

                <div class="space-y-1 md:border-l border-slate-100 md:pl-10">
                    <label class="text-[9px] font-black text-slate-300 uppercase tracking-[0.2em] block">
                        Horas Independientes
                    </label>

                    <p class="text-sm font-black text-slate-600">
                        <?= htmlspecialchars($suba['horas_independiente'] ?: '0') ?>
                        <span class="text-[10px] text-slate-400 uppercase font-bold italic">
                            Horas
                        </span>
                    </p>
                </div>

                <div class="space-y-1 md:border-l border-slate-100 md:pl-10">
                    <label class="text-[9px] font-black text-slate-300 uppercase tracking-[0.2em] block">
                        Créditos
                    </label>

                    <p class="text-sm font-black text-emerald-600">
                        <?= htmlspecialchars($suba['creditos'] ?: '0') ?>
                        <span class="text-[10px] text-emerald-300 uppercase font-bold italic tracking-wider">
                            Créditos
                        </span>
                    </p>
                </div>

            </div>

            <div class="mt-16 pt-10 border-t border-slate-50">

                <div class="flex items-center gap-3 mb-6">
                    <div class="w-1 h-6 bg-purple-600 rounded-full"></div>

                    <h3 class="text-[11px] font-black uppercase tracking-[0.3em] text-slate-400">
                        Descripción
                    </h3>
                </div>

                <div class="bg-slate-50/50 p-8 rounded-[2.5rem] border border-slate-50">

                    <?php if(!empty($suba['descripcion'])): ?>
                        <p class="text-slate-600 leading-relaxed font-medium">
                            <?= nl2br(htmlspecialchars($suba['descripcion'])) ?>
                        </p>
                    <?php else: ?>
                        <p class="italic text-slate-300 font-bold">
                            No se ha proporcionado una descripción detallada para esta subasignatura.
                        </p>
                    <?php endif; ?>

                </div>
            </div>

            <div class="mt-16 pt-10 border-t border-slate-50">

                <div class="flex items-center gap-3 mb-6">
                    <div class="w-1 h-6 bg-indigo-600 rounded-full"></div>

                    <h3 class="text-[11px] font-black uppercase tracking-[0.3em] text-slate-400">
                        Asignaturas vinculadas
                    </h3>
                </div>

                <?php if($materias): ?>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">

                        <?php foreach($materias as $m): ?>
                            <a href="?modulo=ver_asignatura&id=<?= $m['id'] ?>"
                               class="bg-slate-900 p-4 rounded-2xl flex items-center gap-3 group hover:bg-purple-600 transition-all">
                                <div class="w-2 h-2 rounded-full bg-purple-500 group-hover:bg-white"></div>

                                <span class="text-[10px] font-black text-slate-300 italic group-hover:text-white transition-colors">
                                    <?= htmlspecialchars($m['clave'] . ' - ' . $m['nombre']) ?>
                                </span>
                            </a>
                        <?php endforeach; ?>

                    </div>

                <?php else: ?>

                    <div class="bg-slate-50 p-6 rounded-2xl border-2 border-dashed border-slate-200 text-center">
                        <p class="text-xs font-bold text-slate-400 uppercase tracking-widest italic">
                            Esta subasignatura no está vinculada a ninguna asignatura.
                        </p>
                    </div>

                <?php endif; ?>

            </div>

            <div class="mt-16 flex justify-between items-end">
                <div class="flex gap-2">
                    <div class="w-8 h-1 bg-slate-100 rounded-full"></div>
                    <div class="w-2 h-1 bg-slate-100 rounded-full"></div>
                </div>

                <p class="text-[8px] font-black text-slate-300 uppercase tracking-widest italic">
                    Sistema Académico CUFA | Registro Interno
                </p>
            </div>

        </div>
    </div>
</div>

<style>
@keyframes slide-up { 
    from { opacity: 0; transform: translateY(20px); } 
    to { opacity: 1; transform: translateY(0); } 
}

.animate-fade-in { 
    animation: slide-up 0.6s cubic-bezier(0.16, 1, 0.3, 1); 
}
</style>