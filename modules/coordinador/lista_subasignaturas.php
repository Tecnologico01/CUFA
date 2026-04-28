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
            Catálogo de Subasignaturas en el Sistema
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
            class="w-full p-6 pl-14 bg-white border border-slate-100 rounded-[2.5rem] shadow-sm font-bold text-slate-600 focus:ring-4 focus:ring-purple-500/10 focus:border-purple-500 outline-none transition-all placeholder:text-slate-300">
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">

        <?php foreach($subas as $s): ?>
        <div class="group bg-white p-8 rounded-[2.5rem] border border-slate-100 shadow-sm hover:shadow-xl hover:-translate-y-2 transition-all cursor-pointer relative overflow-hidden"
            
            data-text="<?= strtolower(
                htmlspecialchars($s['clave'] . ' ' . $s['nombre'])
            ) ?>"
            
            onclick="verSuba(<?= $s['id'] ?>)">

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
                        <p class="text-[11px] font-black text-slate-500 italic">
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
            </div>

            <div class="absolute bottom-0 right-0 opacity-0 group-hover:opacity-100 transition-all p-4 transform translate-x-2 group-hover:translate-x-0">
                <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path d="M13 7l5 5m0 0l-5 5m5-5H6" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"></path>
                </svg>
            </div>
        </div>
        <?php endforeach; ?>

    </div>

    <div id="no-results" class="hidden py-20 text-center">
        <p class="text-slate-300 font-black italic uppercase tracking-widest text-xl">Sin coincidencias en el archivo</p>
    </div>

</div>

<script>
// BUSCADOR DINÁMICO
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

// REDIRECCIÓN A DETALLE
function verSuba(id){
    window.location.href = "?modulo=ver_subasignatura&id=" + id;
}
</script>

<style>
@keyframes slide-up { 
    from { opacity: 0; transform: translateY(15px); } 
    to { opacity: 1; transform: translateY(0); } 
}
.animate-fade-in { animation: slide-up 0.4s ease-out; }
</style>