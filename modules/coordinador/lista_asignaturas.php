<?php
require_once __DIR__ . '/../../includes/db.php';

/* Consulta optimizada */
$materias = $pdo->query("
SELECT 
    m.id, 
    m.clave, 
    m.nombre, 
    m.nombre_corto, 
    COALESCE(c.nombre, 'Sin carrera') AS carrera, 
    m.tipo
FROM materias m
LEFT JOIN carreras c ON m.carrera_id = c.id
ORDER BY m.nombre ASC
")->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="max-w-7xl mx-auto p-6 bg-slate-50 min-h-screen animate-fade-in">

    <!-- HEADER SECCIÓN -->
    <div class="mb-12 flex flex-col md:flex-row md:items-end justify-between gap-6 border-b border-slate-200 pb-10">
        <div>
            <h1 class="text-6xl font-black italic uppercase tracking-tighter text-slate-900 leading-none">
                Asignaturas <span class="text-indigo-600">Creadas</span>
            </h1>
            <p class="text-slate-400 font-bold text-[11px] uppercase tracking-[0.4em] mt-3 flex items-center gap-2">
                <span class="w-8 h-[2px] bg-indigo-500"></span>
                Catalogo de asignaturas registradas en el sistema
            </p>
        </div>
        
        <div class="bg-white px-6 py-3 rounded-2xl shadow-sm border border-slate-100 flex items-center gap-4">
            <span class="text-[10px] font-black text-slate-300 uppercase tracking-widest italic">Asignaturas Totales</span>
            <span id="contador-visual" class="text-2xl font-black text-indigo-600 italic leading-none"><?= $total_materias ?></span>
        </div>
    </div>

    <!-- BARRA DE BÚSQUEDA PREMIUM -->
    <div class="relative mb-16">
        <div class="absolute inset-y-0 left-0 pl-8 flex items-center pointer-events-none">
            <svg class="w-6 h-6 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"></path>
            </svg>
        </div>
        <input id="buscador"
            placeholder="Filtrar por clave, nombre de asignatura o programa académico..."
            class="w-full p-8 pl-18 bg-white border-none rounded-[3rem] shadow-xl shadow-slate-200/50 font-bold text-slate-600 focus:ring-4 focus:ring-indigo-500/10 outline-none transition-all placeholder:text-slate-300 text-lg italic">
    </div>

    <!-- GRID DE ASIGNATURAS -->
    <div id="grid-materias" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">

        <?php foreach($materias as $m): ?>
        <div class="card-materia group bg-white p-10 rounded-[3.5rem] border border-slate-100 shadow-sm hover:shadow-2xl hover:-translate-y-3 transition-all duration-500 cursor-pointer relative overflow-hidden"
            
            data-text="<?= strtolower(trim($m['clave'] . ' ' . $m['nombre'] . ' ' . $m['carrera'])) ?>"
            onclick="verMateria(<?= $m['id'] ?>)">
            
            <!-- Badge de Tipo -->
            <div class="absolute top-0 right-0 p-8">
                <span class="px-4 py-1.5 bg-slate-50 rounded-full text-[9px] font-black uppercase tracking-widest text-slate-400 group-hover:bg-indigo-600 group-hover:text-white transition-all duration-500 border border-slate-100 group-hover:border-indigo-600 italic">
                    <?= $m['tipo'] ?>
                </span>
            </div>

            <div class="relative z-10">
                <!-- Clave e Indicador -->
                <div class="flex items-center gap-3 mb-4">
                    <span class="w-2 h-2 rounded-full bg-indigo-500 shadow-[0_0_10px_rgba(79,70,229,0.5)]"></span>
                    <span class="text-[11px] font-black text-indigo-500 tracking-[0.2em] uppercase italic">
                        <?= htmlspecialchars($m['clave']) ?>
                    </span>
                </div>

                <!-- Nombre -->
                <h3 class="text-2xl font-black text-slate-800 leading-tight mb-8 group-hover:text-indigo-600 transition-colors uppercase italic tracking-tighter">
                    <?= htmlspecialchars($m['nombre']) ?>
                </h3>

                <!-- Footer de la Card -->
                <div class="pt-8 border-t border-slate-50 flex justify-between items-end">
                    <div>
                        <label class="text-[9px] font-black text-slate-300 uppercase tracking-widest block mb-2 italic">Licenciatura</label>
                        <p class="text-xs font-bold text-slate-500 italic leading-snug max-w-[200px]">
                            <?= htmlspecialchars($m['carrera']) ?>
                        </p>
                    </div>
                    
                    <!-- Icono de Acción -->
                    <div class="w-12 h-12 rounded-2xl bg-slate-900 flex items-center justify-center -mb-2 -mr-2 opacity-0 group-hover:opacity-100 group-hover:translate-x-0 translate-x-4 transition-all duration-500 shadow-lg shadow-indigo-200">
                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path d="M17 8l4 4m0 0l-4 4m4-4H3" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"></path>
                        </svg>
                    </div>
                </div>
            </div>

            <!-- Efecto Decorativo de Fondo -->
            <div class="absolute -bottom-10 -left-10 w-32 h-32 bg-slate-50 rounded-full opacity-0 group-hover:opacity-100 transition-opacity duration-700 pointer-events-none"></div>
        </div>
        <?php endforeach; ?>

    </div>

    <!-- EMPTY STATE -->
    <div id="no-results" class="hidden py-32 text-center animate-fade-in">
        <div class="inline-flex p-10 bg-white rounded-[3rem] shadow-sm border border-slate-100 mb-6">
            <svg class="w-16 h-16 text-slate-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"></path>
            </svg>
        </div>
        <p class="text-slate-400 font-black italic uppercase tracking-[0.3em] text-xl">Sin coincidencias en el registro</p>
        <p class="text-slate-300 text-sm mt-2">Intente con otra clave o nombre de asignatura</p>
    </div>

</div>

<script>
document.getElementById("buscador").addEventListener("keyup", function(){
    let f = this.value.toLowerCase().trim();
    let cards = document.querySelectorAll(".card-materia");
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

    // Actualizar contador dinámico
    document.getElementById("contador-visual").innerText = visibleCount;

    // Gestionar Empty State
    const noResults = document.getElementById("no-results");
    if(visibleCount === 0) {
        noResults.classList.remove("hidden");
        document.getElementById("grid-materias").classList.add("hidden");
    } else {
        noResults.classList.add("hidden");
        document.getElementById("grid-materias").classList.remove("hidden");
    }
});

function verMateria(id){
    window.location.href = "?modulo=ver_asignatura&id=" + id;
}
</script>

<style>
@keyframes slide-up { 
    from { opacity: 0; transform: translateY(30px); } 
    to { opacity: 1; transform: translateY(0); } 
}
.animate-fade-in { animation: slide-up 0.8s cubic-bezier(0.16, 1, 0.3, 1); }

.pl-18 { padding-left: 4.5rem; }

/* Efecto suave para las cards al filtrar */
.card-materia {
    transition: all 0.5s cubic-bezier(0.16, 1, 0.3, 1), display 0s;
}
</style>