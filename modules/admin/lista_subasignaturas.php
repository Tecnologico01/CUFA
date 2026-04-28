<?php
require_once __DIR__ . '/../../includes/db.php';

$subas = $pdo->query("
SELECT * FROM subasignaturas
ORDER BY nombre ASC
")->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="max-w-7xl mx-auto p-6 bg-slate-50 min-h-screen animate-fade-in">

    <div class="mb-10">
        <h1 class="text-5xl font-black italic uppercase tracking-tighter text-slate-800 leading-none">
            Subasignaturas
        </h1>
        <p class="text-purple-600 font-bold text-[10px] uppercase tracking-[0.3em] italic mt-2">
            Panel de Control Administrativo
        </p>
    </div>

    <div class="relative mb-12">
        <div class="absolute inset-y-0 left-0 pl-6 flex items-center pointer-events-none">
            <svg class="w-5 h-5 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"></path>
            </svg>
        </div>
        <input id="buscador"
            placeholder="Filtrar por clave o nombre de subasignatura..."
            class="w-full p-6 pl-14 bg-white border border-slate-100 rounded-[2.5rem] shadow-sm font-bold text-slate-600 focus:ring-4 focus:ring-purple-500/10 outline-none transition-all placeholder:text-slate-200">
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">

        <?php foreach($subas as $s): ?>
        <div class="group bg-white p-8 rounded-[2.5rem] border border-slate-100 shadow-sm hover:shadow-xl hover:-translate-y-2 transition-all relative overflow-hidden"
            
            data-text="<?= strtolower(htmlspecialchars($s['clave'] . ' ' . $s['nombre'])) ?>">

            <div class="absolute top-0 right-0 p-6">
                <span class="px-3 py-1 bg-slate-50 rounded-full text-[8px] font-black uppercase text-slate-400 group-hover:bg-purple-100 group-hover:text-purple-600 transition-colors border border-slate-100">
                    <?= htmlspecialchars($s['recurso'] ?? 'Módulo') ?>
                </span>
            </div>

            <div class="relative z-10">
                <span class="text-[10px] font-black text-purple-600 tracking-widest italic block mb-2">
                    <?= htmlspecialchars($s['clave']) ?>
                </span>

                <h3 class="text-xl font-black text-slate-800 leading-tight mb-6 group-hover:text-purple-700 transition-colors">
                    <?= htmlspecialchars($s['nombre']) ?>
                </h3>

                <div class="grid grid-cols-2 gap-4 pt-6 border-t border-slate-50">
                    <div>
                        <label class="text-[8px] font-black text-slate-300 uppercase block mb-1">Carga Horaria</label>
                        <p class="text-[11px] font-black text-slate-500 italic uppercase">
                            <?= $s['horas_frente_grupo'] ?>h / <?= $s['horas_independiente'] ?>h
                        </p>
                    </div>
                    <div>
                        <label class="text-[8px] font-black text-slate-300 uppercase block mb-1">Créditos</label>
                        <p class="text-[11px] font-black text-emerald-600 italic">
                            <?= $s['creditos'] ?> pts
                        </p>
                    </div>
                </div>

                <div class="mt-8 pt-6 border-t border-slate-50 flex gap-3">
                    <button onclick="editarSuba(<?= $s['id'] ?>)"
                        class="flex-1 bg-slate-900 hover:bg-purple-600 text-white py-3 rounded-2xl text-[9px] font-black uppercase tracking-widest transition-all shadow-md shadow-slate-100">
                        Editar
                    </button>
                    <button onclick="eliminarSuba(<?= $s['id'] ?>)"
                        class="flex-1 bg-white border border-slate-100 hover:bg-red-50 hover:border-red-100 text-slate-300 hover:text-red-500 py-3 rounded-2xl text-[9px] font-black uppercase tracking-widest transition-all">
                        Eliminar
                    </button>
                </div>
            </div>
        </div>
        <?php endforeach; ?>

    </div>

    <div id="no-results" class="hidden py-32 text-center">
        <div class="inline-block p-6 bg-slate-100 rounded-full mb-4">
            <svg class="w-10 h-10 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path d="M9.172 9.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" stroke-width="2"></path>
            </svg>
        </div>
        <p class="text-slate-300 font-black italic uppercase tracking-[0.2em] text-xl">
            Sin coincidencias en el archivo
        </p>
    </div>

</div>

<script>
// BUSCADOR
document.getElementById("buscador").addEventListener("keyup", function(){
    let filtro = this.value.toLowerCase().trim();
    let items = document.querySelectorAll("[data-text]");
    let encontrados = 0;

    items.forEach(el => {
        if(el.dataset.text.includes(filtro)){
            el.style.display = "block";
            encontrados++;
        } else {
            el.style.display = "none";
        }
    });

    const empty = document.getElementById("no-results");
    encontrados === 0 ? empty.classList.remove("hidden") : empty.classList.add("hidden");
});

// EDITAR
function editarSuba(id){
    window.location.href = 
    "/sistema_academico/dashboards/admin_dashboard.php?modulo=editar_subasignatura&id=" + id;
}

// ELIMINAR
function eliminarSuba(id){
    if(confirm("¿Confirmar eliminación de la subasignatura? Esta acción es irreversible.")){
        window.location.href = 
        "/sistema_academico/modules/admin/eliminar_subasignatura.php?id=" + id;
    }
}
</script>

<style>
@keyframes slide-up { 
    from { opacity: 0; transform: translateY(20px); } 
    to { opacity: 1; transform: translateY(0); } 
}
.animate-fade-in { animation: slide-up 0.6s cubic-bezier(0.16, 1, 0.3, 1); }
</style>