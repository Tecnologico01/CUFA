<?php
require_once __DIR__ . '/../../includes/db.php';

$id = $_GET['id'] ?? null;

if(!$id){
    echo "Subasignatura no encontrada";
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM subasignaturas WHERE id=?");
$stmt->execute([$id]);
$suba = $stmt->fetch(PDO::FETCH_ASSOC);

if(!$suba){
    echo "Subasignatura no existe";
    exit;
}
?>

<div class="max-w-5xl mx-auto p-6 bg-slate-50 min-h-screen animate-fade-in">

    <div class="flex items-center justify-between mb-10">
        <div>
            <h1 class="text-5xl font-black italic uppercase tracking-tighter text-slate-800 leading-none">
                Subasignatura
            </h1>
            <p class="text-purple-600 font-bold text-[10px] uppercase tracking-[0.3em] italic mt-2">
                Información Técnica de la Subasignatura
            </p>
        </div>
        <a href="coordinador_dashboard.php?modulo=lista_subasignaturas" 
           class="group flex items-center gap-3 bg-white px-6 py-4 rounded-2xl shadow-sm border border-slate-100 hover:bg-slate-900 transition-all">
            <svg class="w-5 h-5 text-purple-600 group-hover:text-white transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path d="M10 19l-7-7m0 0l7-7m-7 7h18" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"></path>
            </svg>
            <span class="text-[10px] font-black uppercase tracking-widest text-slate-400 group-hover:text-white transition-colors">Volver al listado</span>
        </a>
    </div>

    <div class="bg-white rounded-[3.5rem] shadow-sm border border-slate-100 overflow-hidden relative">
        
        <div class="absolute top-0 right-0 p-12 select-none pointer-events-none">
            <span class="text-[100px] font-black text-slate-50 italic leading-none uppercase">Data</span>
        </div>

        <div class="p-12 relative z-10">
            <div class="mb-12">
                <span class="inline-block px-4 py-1 bg-purple-50 text-purple-600 rounded-full text-[10px] font-black tracking-widest uppercase mb-4 border border-purple-100">
                    ID: <?= $suba['id'] ?> | Clave: <?= $suba['clave'] ?>
                </span>
                <h2 class="text-4xl font-black text-slate-800 leading-tight">
                    <?= $suba['nombre'] ?>
                </h2>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-10">
                
                <div class="space-y-1">
                    <label class="text-[9px] font-black text-slate-300 uppercase tracking-[0.2em] block">Modalidad de Recurso</label>
                    <p class="text-sm font-black text-slate-600 italic uppercase italic uppercase">
                        <?= $suba['recurso'] ?>
                    </p>
                </div>

                <div class="space-y-1 border-l border-slate-100 pl-6 md:pl-10">
                    <label class="text-[9px] font-black text-slate-300 uppercase tracking-[0.2em] block">Horas Frente a Grupo</label>
                    <p class="text-sm font-black text-slate-600">
                        <?= $suba['horas_frente_grupo'] ?> <span class="text-[10px] text-slate-400 uppercase font-bold italic">Horas</span>
                    </p>
                </div>

                <div class="space-y-1 border-l border-slate-100 pl-6 md:pl-10">
                    <label class="text-[9px] font-black text-slate-300 uppercase tracking-[0.2em] block">Horas Independientes</label>
                    <p class="text-sm font-black text-slate-600">
                        <?= $suba['horas_independiente'] ?> <span class="text-[10px] text-slate-400 uppercase font-bold italic">Horas</span>
                    </p>
                </div>

                <div class="space-y-1 border-l border-slate-100 pl-6 md:pl-10">
                    <label class="text-[9px] font-black text-slate-300 uppercase tracking-[0.2em] block">Valor Curricular</label>
                    <p class="text-sm font-black text-emerald-600">
                        <?= $suba['creditos'] ?> <span class="text-[10px] text-emerald-300 uppercase font-bold italic tracking-wider">Créditos</span>
                    </p>
                </div>

            </div>

            <div class="mt-16 pt-10 border-t border-slate-50">
                <div class="flex items-center gap-3 mb-6">
                    <div class="w-1 h-6 bg-purple-600 rounded-full"></div>
                    <h3 class="text-[11px] font-black uppercase tracking-[0.3em] text-slate-400">Descripción del Módulo</h3>
                </div>
                
                <div class="bg-slate-50/50 p-8 rounded-[2.5rem] border border-slate-50">
                    <p class="text-slate-600 leading-relaxed font-medium">
                        <?= $suba['descripcion'] ? nl2br($suba['descripcion']) : '<span class="italic text-slate-300">No se ha proporcionado una descripción detallada para esta subasignatura.</span>' ?>
                    </p>
                </div>
            </div>

            <div class="mt-16 flex justify-between items-end">
                <div class="flex gap-2">
                    <div class="w-8 h-1 bg-slate-100 rounded-full"></div>
                    <div class="w-2 h-1 bg-slate-100 rounded-full"></div>
                </div>
                <p class="text-[8px] font-black text-slate-300 uppercase tracking-widest italic">
                    Sistema Académico v2.0 | Registro Interno
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
.animate-fade-in { animation: slide-up 0.6s cubic-bezier(0.16, 1, 0.3, 1); }
</style>