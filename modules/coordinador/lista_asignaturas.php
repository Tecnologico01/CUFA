<?php
require_once __DIR__ . '/../../includes/db.php';

/* LEFT JOIN para no perder materias sin carrera y COALESCE para evitar null */
$materias = $pdo->query("
SELECT 
    m.id, 
    m.clave, 
    m.nombre, 
    m.nombre_corto, 
    COALESCE(c.nombre, 'Sin carrera') as carrera, 
    m.tipo
FROM materias m
LEFT JOIN carreras c ON c.id = m.carrera_id
ORDER BY m.nombre ASC
")->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="max-w-7xl mx-auto p-6 bg-slate-50 min-h-screen">

    <div class="mb-10">
        <h1 class="text-5xl font-black italic uppercase tracking-tighter text-slate-800 leading-none">
            Asignaturas
        </h1>
        <p class="text-purple-600 font-bold text-[10px] uppercase tracking-[0.3em] italic mt-2">
            Catálogo General de Asignaturas
        </p>
    </div>

    <div class="relative mb-12">
        <div class="absolute inset-y-0 left-0 pl-6 flex items-center pointer-events-none">
            <svg class="w-5 h-5 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"></path>
            </svg>
        </div>
        <input id="buscador"
            placeholder="Filtrar por clave, nombre o carrera..."
            class="w-full p-6 pl-14 bg-white border border-slate-100 rounded-[2rem] shadow-sm font-bold text-slate-600 focus:ring-4 focus:ring-purple-500/10 focus:border-purple-500 outline-none transition-all placeholder:text-slate-300">
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">

        <?php foreach($materias as $m): ?>
        <div class="group bg-white p-8 rounded-[2.5rem] border border-slate-100 shadow-sm hover:shadow-xl hover:-translate-y-2 transition-all cursor-pointer relative overflow-hidden animate-fade-in"
            
            data-text="<?= strtolower(
                trim($m['clave'] . ' ' . $m['nombre'] . ' ' . $m['nombre_corto'] . ' ' . $m['carrera'])
            ) ?>"

            onclick="verMateria(<?= $m['id'] ?>)">
            
            <div class="absolute top-0 right-0 p-6">
                <span class="px-3 py-1 bg-slate-50 rounded-full text-[8px] font-black uppercase text-slate-400 group-hover:bg-purple-100 group-hover:text-purple-600 transition-colors border border-slate-100">
                    <?= $m['tipo'] ?>
                </span>
            </div>

            <div class="relative z-10">
                <span class="text-[10px] font-black text-purple-600 tracking-widest italic block mb-2">
                    <?= htmlspecialchars($m['clave']) ?>
                </span>

                <h3 class="text-xl font-black text-slate-800 leading-tight mb-6 group-hover:text-purple-700 transition-colors">
                    <?= htmlspecialchars($m['nombre']) ?>
                </h3>

                <div class="pt-6 border-t border-slate-50">
                    <label class="text-[9px] font-black text-slate-300 uppercase tracking-widest block mb-1">Programa Académico</label>
                    <p class="text-xs font-black text-slate-500 italic leading-snug">
                        <?= htmlspecialchars($m['carrera']) ?>
                    </p>
                </div>
            </div>

            <div class="absolute bottom-0 right-0 opacity-0 group-hover:opacity-100 transition-opacity p-4">
                <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path d="M17 8l4 4m0 0l-4 4m4-4H3" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"></path>
                </svg>
            </div>
        </div>
        <?php endforeach; ?>

    </div>

    <div id="no-results" class="hidden py-20 text-center">
        <p class="text-slate-300 font-black italic uppercase tracking-widest text-xl">No se encontraron coincidencias</p>
    </div>

</div>

<script>
document.getElementById("buscador").addEventListener("keyup", function(){
    let f = this.value.toLowerCase().trim();
    let cards = document.querySelectorAll("[data-text]");
    let visibleCount = 0;

    cards.forEach(el => {
        let texto = el.dataset.text;
        if(texto.includes(f)){
            el.style.display = "block";
            visibleCount++;
        } else {
            el.style.display = "none";
        }
    });

    // Mostrar mensaje si no hay resultados
    const noResults = document.getElementById("no-results");
    if(visibleCount === 0) {
        noResults.classList.remove("hidden");
    } else {
        noResults.classList.add("hidden");
    }
});

function verMateria(id){
    window.location.href = 
    "/sistema_academico/dashboards/coordinador_dashboard.php?modulo=ver_asignatura&id=" + id;
}
</script>

<style>
@keyframes slide-up { 
    from { opacity: 0; transform: translateY(15px); } 
    to { opacity: 1; transform: translateY(0); } 
}
.animate-fade-in { animation: slide-up 0.4s ease-out; }
</style>