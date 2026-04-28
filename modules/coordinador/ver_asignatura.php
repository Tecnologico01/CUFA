<?php
require_once __DIR__ . '/../../includes/db.php';

$id = $_GET['id'] ?? null;

if(!$id){
    echo "Materia no encontrada";
    exit;
}

/* =========================
   MATERIA
========================= */
$stmt = $pdo->prepare("
SELECT 
    m.*, 
    c.nombre as carrera,
    sm.nombre as seriacion_nombre
FROM materias m
JOIN carreras c ON c.id = m.carrera_id
LEFT JOIN materias sm ON sm.id = m.seriacion_id
WHERE m.id = ?
");
$stmt->execute([$id]);
$materia = $stmt->fetch(PDO::FETCH_ASSOC);

/* =========================
   SUBASIGNATURAS
========================= */
$stmt = $pdo->prepare("
SELECT s.nombre
FROM materia_subasignatura ms
JOIN subasignaturas s ON s.id = ms.subasignatura_id
WHERE ms.materia_id = ?
");
$stmt->execute([$id]);
$subas = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="max-w-6xl mx-auto p-6 bg-slate-50 min-h-screen">

    <div class="flex items-center gap-6 mb-8">
        <a href="/sistema_academico/dashboards/coordinador_dashboard.php?modulo=materias"
           class="group bg-white p-4 rounded-[1.5rem] shadow-sm border border-slate-100 text-slate-400 hover:text-purple-600 transition-all transform hover:-translate-x-1">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path d="M15 19l-7-7 7-7" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"></path>
            </svg>
        </a>
        <div>
            <h1 class="text-4xl font-black italic tracking-tighter text-slate-800 leading-none">
                <?= $materia['nombre'] ?>
            </h1>
            <p class="text-purple-600 font-bold text-[10px] tracking-[0.3em] italic mt-1">
                Clave • <?= $materia['clave'] ?>
            </p>
        </div>
    </div>

    <div class="bg-white p-10 rounded-[3rem] shadow-sm border border-slate-100 relative overflow-hidden animate-fade-in">
        
        <div class="absolute top-0 right-0 p-8">
            <span class="text-[80px] font-black text-slate-50 italic select-none leading-none"><?= $materia['clave'] ?></span>
        </div>

        <div class="relative z-10">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8 mb-12">
                <div>
                    <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest block mb-1">Licenciatura / Carrera</label>
                    <p class="text-sm font-black text-slate-700 italic"><?= $materia['carrera'] ?></p>
                </div>
                <div>
                    <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest block mb-1">Nombre Corto</label>
                    <p class="text-sm font-bold text-slate-600"><?= $materia['nombre_corto'] ?: '—' ?></p>
                </div>
                <div>
                    <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest block mb-1">Grado</label>
                    <p class="text-sm font-black text-purple-600 uppercase italic"> <?= $materia['grado'] ?></p>
                </div>
            </div>

            <div class="grid grid-cols-2 md:grid-cols-4 gap-6 p-8 bg-slate-50 rounded-[2rem] border border-slate-100 mb-12">
                <div class="text-center">
                    <label class="text-[9px] font-black text-slate-400 uppercase block mb-1">Créditos</label>
                    <span class="text-3xl font-black text-emerald-600 italic leading-none"><?= $materia['creditos'] ?: '0' ?></span>
                </div>
                <div class="text-center border-l border-slate-200">
                    <label class="text-[9px] font-black text-slate-400 uppercase block mb-1">Modalidad</label>
                    <span class="text-sm font-black text-slate-700 uppercase italic"><?= $materia['tipo'] ?></span>
                </div>
                <div class="text-center border-l border-slate-200">
                    <label class="text-[9px] font-black text-slate-400 uppercase block mb-1">Aula</label>
                    <span class="text-sm font-black text-slate-700 uppercase italic"><?= $materia['aula'] ?: '—' ?></span>
                </div>
                <div class="text-center border-l border-slate-200">
                    <label class="text-[9px] font-black text-slate-400 uppercase block mb-1">Formación</label>
                    <span class="text-sm font-black text-slate-700 uppercase italic"><?= $materia['area_formacion'] ?></span>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-12 mb-12 px-4">
                <div class="space-y-4">
                    <h3 class="text-[10px] font-black text-slate-300 uppercase tracking-[0.3em]">Distribución de Horas</h3>
                    <div class="flex justify-between items-center border-b border-slate-100 pb-2">
                        <span class="text-xs font-bold text-slate-500 uppercase">Horas Frente al Docente</span>
                        <span class="font-black text-slate-800 italic"><?= $materia['horas_docente'] ?: '0' ?>h</span>
                    </div>
                    <div class="flex justify-between items-center border-b border-slate-100 pb-2">
                        <span class="text-xs font-bold text-slate-500 uppercase">Horas Independientes</span>
                        <span class="font-black text-slate-800 italic"><?= $materia['horas_independientes'] ?: '0' ?>h</span>
                    </div>
                </div>

                <div class="space-y-4">
                    <h3 class="text-[10px] font-black text-slate-300 uppercase tracking-[0.3em]">Requisitos y Estatus</h3>
                    <div class="flex justify-between items-center border-b border-slate-100 pb-2">
                        <span class="text-xs font-bold text-slate-500 uppercase">Asignatura Antecesora</span>
                        <span class="font-black text-purple-600 italic text-[10px]"><?= $materia['seriacion_nombre'] ?: 'Ninguna' ?></span>
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
                    </div>
                </div>
            </div>

            <div class="mt-8 pt-8 border-t border-slate-100">
                <div class="flex items-center gap-4 mb-6">
                    <h2 class="text-xl font-black italic uppercase text-slate-800">Subasignaturas añadidas</h2>
                    <div class="h-[2px] flex-1 bg-slate-50"></div>
                </div>

                <?php if($subas): ?>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        <?php foreach($subas as $s): ?>
                            <div class="bg-slate-900 p-4 rounded-2xl flex items-center gap-3 group hover:bg-purple-600 transition-all">
                                <div class="w-2 h-2 rounded-full bg-purple-500 group-hover:bg-white"></div>
                                <span class="text-[10px] font-black text-slate-300 italic group-hover:text-white transition-colors">
                                    <?= $s['nombre'] ?>
                                </span>
                            </div>
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
.animate-fade-in { animation: slide-up 0.5s ease-out; }
</style>