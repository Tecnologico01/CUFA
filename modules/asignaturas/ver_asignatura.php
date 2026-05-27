<?php
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/permisos.php';

validarPermiso('ver_asignaturas');

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

if($id <= 0){
    echo "<div class='p-6 bg-red-100 text-red-700 rounded-2xl font-bold'>Materia no encontrada.</div>";
    exit;
}

$puedeEditar = tienePermiso('editar_asignaturas');
$puedeEliminar = tienePermiso('eliminar_asignaturas');

/* MATERIA */
$stmt = $pdo->prepare("
    SELECT 
        m.*, 
        COALESCE(c.nombre, 'Sin carrera') AS carrera,
        sm.nombre AS seriacion_nombre
    FROM materias m
    LEFT JOIN carreras c ON c.id = m.carrera_id
    LEFT JOIN materias sm ON sm.id = m.seriacion_id
    WHERE m.id = ?
");

$stmt->execute([$id]);
$materia = $stmt->fetch(PDO::FETCH_ASSOC);

if(!$materia){
    echo "<div class='p-6 bg-red-100 text-red-700 rounded-2xl font-bold'>La asignatura no existe.</div>";
    exit;
}

/* SUBASIGNATURAS */
$stmt = $pdo->prepare("
    SELECT 
        s.id,
        s.clave,
        s.nombre
    FROM materia_subasignatura ms
    INNER JOIN subasignaturas s 
        ON s.id = ms.subasignatura_id
    WHERE ms.materia_id = ?
    ORDER BY s.nombre ASC
");

$stmt->execute([$id]);
$subas = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="max-w-6xl mx-auto p-6 bg-slate-50 min-h-screen animate-fade-in">

    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-6 mb-8">

        <div class="flex items-center gap-6">

            <a href="?modulo=lista_asignaturas"
               class="group bg-white p-4 rounded-[1.5rem] shadow-sm border border-slate-100 text-slate-400 hover:text-purple-600 transition-all transform hover:-translate-x-1">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path d="M15 19l-7-7 7-7" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"></path>
                </svg>
            </a>

            <div>
                <h1 class="text-4xl font-black italic tracking-tighter text-slate-800 leading-none">
                    <?= htmlspecialchars($materia['nombre']) ?>
                </h1>

                <p class="text-purple-600 font-bold text-[10px] tracking-[0.3em] italic mt-2 uppercase">
                    Clave • <?= htmlspecialchars($materia['clave']) ?>
                </p>
            </div>

        </div>

        <div class="flex gap-3">

            <?php if($puedeEditar): ?>
                <a href="?modulo=editar_materia&id=<?= $materia['id'] ?>"
                   class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-4 rounded-2xl font-black uppercase text-[10px] tracking-widest transition-all">
                    Editar
                </a>
            <?php endif; ?>

            <?php if($puedeEliminar): ?>
                <a href="?modulo=eliminar_materia&id=<?= $materia['id'] ?>"
                   onclick="return confirm('¿Seguro que deseas eliminar esta asignatura?')"
                   class="bg-red-500 hover:bg-red-600 text-white px-6 py-4 rounded-2xl font-black uppercase text-[10px] tracking-widest transition-all">
                    Eliminar
                </a>
            <?php endif; ?>

        </div>

    </div>

    <div class="bg-white p-10 rounded-[3rem] shadow-sm border border-slate-100 relative overflow-hidden">

        <div class="absolute top-0 right-0 p-8">
            <span class="text-[80px] font-black text-slate-50 italic select-none leading-none">
                <?= htmlspecialchars($materia['clave']) ?>
            </span>
        </div>

        <div class="relative z-10">

            <div class="grid grid-cols-1 md:grid-cols-3 gap-8 mb-12">

                <div>
                    <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest block mb-1">
                        Carrera
                    </label>
                    <p class="text-sm font-black text-slate-700 italic">
                        <?= htmlspecialchars($materia['carrera']) ?>
                    </p>
                </div>

                <div>
                    <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest block mb-1">
                        Nombre Corto
                    </label>
                    <p class="text-sm font-bold text-slate-600">
                        <?= htmlspecialchars($materia['nombre_corto'] ?: '—') ?>
                    </p>
                </div>

                <div>
                    <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest block mb-1">
                        Grado
                    </label>
                    <p class="text-sm font-black text-purple-600 uppercase italic">
                        <?= htmlspecialchars($materia['grado']) ?>
                    </p>
                </div>

            </div>

            <div class="grid grid-cols-2 md:grid-cols-4 gap-6 p-8 bg-slate-50 rounded-[2rem] border border-slate-100 mb-12">

                <div class="text-center">
                    <label class="text-[9px] font-black text-slate-400 uppercase block mb-1">Créditos</label>
                    <span class="text-3xl font-black text-emerald-600 italic leading-none">
                        <?= htmlspecialchars($materia['creditos'] ?: '0') ?>
                    </span>
                </div>

                <div class="text-center md:border-l border-slate-200">
                    <label class="text-[9px] font-black text-slate-400 uppercase block mb-1">Modalidad</label>
                    <span class="text-sm font-black text-slate-700 uppercase italic">
                        <?= htmlspecialchars($materia['tipo'] ?: '—') ?>
                    </span>
                </div>

                <div class="text-center md:border-l border-slate-200">
                    <label class="text-[9px] font-black text-slate-400 uppercase block mb-1">Aula</label>
                    <span class="text-sm font-black text-slate-700 uppercase italic">
                        <?= htmlspecialchars($materia['aula'] ?: '—') ?>
                    </span>
                </div>

                <div class="text-center md:border-l border-slate-200">
                    <label class="text-[9px] font-black text-slate-400 uppercase block mb-1">Formación</label>
                    <span class="text-sm font-black text-slate-700 uppercase italic">
                        <?= htmlspecialchars($materia['area_formacion'] ?: '—') ?>
                    </span>
                </div>

            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-12 mb-12 px-4">

                <div class="space-y-4">
                    <h3 class="text-[10px] font-black text-slate-300 uppercase tracking-[0.3em]">
                        Distribución de Horas
                    </h3>

                    <div class="flex justify-between items-center border-b border-slate-100 pb-2">
                        <span class="text-xs font-bold text-slate-500 uppercase">Horas Docente</span>
                        <span class="font-black text-slate-800 italic">
                            <?= htmlspecialchars($materia['horas_docente'] ?: '0') ?>h
                        </span>
                    </div>

                    <div class="flex justify-between items-center border-b border-slate-100 pb-2">
                        <span class="text-xs font-bold text-slate-500 uppercase">Horas Independientes</span>
                        <span class="font-black text-slate-800 italic">
                            <?= htmlspecialchars($materia['horas_independientes'] ?: '0') ?>h
                        </span>
                    </div>
                </div>

                <div class="space-y-4">
                    <h3 class="text-[10px] font-black text-slate-300 uppercase tracking-[0.3em]">
                        Requisitos y Estatus
                    </h3>

                    <div class="flex justify-between items-center border-b border-slate-100 pb-2">
                        <span class="text-xs font-bold text-slate-500 uppercase">Asignatura Antecesora</span>
                        <span class="font-black text-purple-600 italic text-[10px]">
                            <?= htmlspecialchars($materia['seriacion_nombre'] ?: 'Ninguna') ?>
                        </span>
                    </div>

                    <div class="flex gap-4">
                        <div class="flex-1 bg-white border border-slate-100 p-3 rounded-xl text-center">
                            <span class="text-[8px] font-black text-slate-400 uppercase block">Opcional</span>
                            <span class="text-[10px] font-black uppercase <?= $materia['es_opcional'] ? 'text-emerald-500' : 'text-slate-300' ?>">
                                <?= $materia['es_opcional'] ? 'Sí' : 'No' ?>
                            </span>
                        </div>

                        <div class="flex-1 bg-white border border-slate-100 p-3 rounded-xl text-center">
                            <span class="text-[8px] font-black text-slate-400 uppercase block">Niveles</span>
                            <span class="text-[10px] font-black uppercase <?= $materia['maneja_niveles'] ? 'text-emerald-500' : 'text-slate-300' ?>">
                                <?= $materia['maneja_niveles'] ? 'Sí' : 'No' ?>
                            </span>
                        </div>

                        <div class="flex-1 bg-white border border-slate-100 p-3 rounded-xl text-center">
                            <span class="text-[8px] font-black text-slate-400 uppercase block">Parciales</span>
                            <span class="text-[10px] font-black uppercase text-indigo-600">
                                <?= htmlspecialchars($materia['total_unidades'] ?: '0') ?>
                            </span>
                        </div>
                    </div>
                </div>

            </div>

            <div class="mt-8 pt-8 border-t border-slate-100">
                <div class="flex items-center gap-4 mb-6">
                    <h2 class="text-xl font-black italic uppercase text-slate-800">
                        Subasignaturas añadidas
                    </h2>
                    <div class="h-[2px] flex-1 bg-slate-50"></div>
                </div>

                <?php if($subas): ?>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        <?php foreach($subas as $s): ?>
                            <a href="?modulo=ver_subasignatura&id=<?= $s['id'] ?>"
                               class="bg-slate-900 p-4 rounded-2xl flex items-center gap-3 group hover:bg-purple-600 transition-all">
                                <div class="w-2 h-2 rounded-full bg-purple-500 group-hover:bg-white"></div>
                                <span class="text-[10px] font-black text-slate-300 italic group-hover:text-white transition-colors">
                                    <?= htmlspecialchars($s['clave'] . ' - ' . $s['nombre']) ?>
                                </span>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="bg-slate-50 p-6 rounded-2xl border-2 border-dashed border-slate-200 text-center">
                        <p class="text-xs font-bold text-slate-400 uppercase tracking-widest italic">
                            No se han añadido subasignaturas
                        </p>
                    </div>
                <?php endif; ?>
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
    animation: slide-up 0.5s ease-out; 
}
</style>