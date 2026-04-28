<?php
require_once __DIR__ . '/../../includes/db.php';

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

<div class="max-w-7xl mx-auto p-6 bg-slate-50 min-h-screen animate-fade-in">

    <div class="mb-10">
        <h1 class="text-5xl font-black italic uppercase tracking-tighter text-slate-800 leading-none">
            Asignaturas
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
            placeholder="Filtrar por clave, nombre o carrera..."
            class="w-full p-6 pl-14 bg-white border border-slate-100 rounded-[2.5rem] shadow-sm font-bold text-slate-600 focus:ring-4 focus:ring-purple-500/10 outline-none transition-all placeholder:text-slate-200">
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">

        <?php foreach($materias as $m): ?>
        <div class="group bg-white p-8 rounded-[2.5rem] border border-slate-100 shadow-sm hover:shadow-xl hover:-translate-y-2 transition-all relative overflow-hidden"
            
            data-text="<?= strtolower(
                trim($m['clave'] . ' ' . $m['nombre'] . ' ' . $m['nombre_corto'] . ' ' . $m['carrera'])
            ) ?>">
            
            <div class="absolute top-0 right-0 p-6 select-none opacity-[0.03] group-hover:opacity-10 transition-opacity">
                <span class="text-4xl font-black italic uppercase leading-none"><?= $m['tipo'] ?></span>
            </div>

            <div class="relative z-10">
                <div class="mb-4">
                    <span class="text-[10px] font-black text-purple-600 tracking-[0.2em] italic uppercase">
                        <?= htmlspecialchars($m['clave']) ?>
                    </span>
                </div>

                <h3 class="text-xl font-black text-slate-800 leading-tight mb-2 group-hover:text-purple-700 transition-colors">
                    <?= htmlspecialchars($m['nombre']) ?>
                </h3>

                <div class="mb-6">
                    <label class="text-[8px] font-black text-slate-300 uppercase block mb-1">Programa</label>
                    <p class="text-xs font-bold text-slate-500 italic">
                        <?= htmlspecialchars($m['carrera']) ?>
                    </p>
                </div>

                <div class="flex items-center justify-between">
                    <span class="inline-block text-[9px] font-black bg-purple-50 text-purple-600 px-4 py-1 rounded-full uppercase tracking-widest border border-purple-100">
                        <?= $m['tipo'] ?>
                    </span>
                </div>

                <div class="flex gap-3 mt-8 pt-6 border-t border-slate-50">
                    <a href="/sistema_academico/dashboards/admin_dashboard.php?modulo=editar_materia&id=<?= $m['id'] ?>"
                       class="flex-1 text-center bg-slate-900 hover:bg-purple-600 text-white py-3 rounded-2xl text-[9px] font-black uppercase tracking-widest transition-all shadow-md shadow-slate-100">
                        Editar
                    </a>
                    <button onclick="eliminarMateria(<?= $m['id'] ?>)"
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
            <svg class="w-10 h-10 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M9.172 9.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" stroke-width="2"></path></svg>
        </div>
        <p class="text-slate-300 font-black italic uppercase tracking-[0.2em] text-xl">Sin registros en el archivo</p>
    </div>

</div>

<script>
// Lógica de búsqueda (Identificadores mantenidos)
document.getElementById("buscador").addEventListener("keyup", function(){
    let f = this.value.toLowerCase().trim();
    let cards = document.querySelectorAll("[data-text]");
    let visible = 0;

    cards.forEach(el => {
        if(el.dataset.text.includes(f)){
            el.style.display = "block";
            visible++;
        } else {
            el.style.display = "none";
        }
    });

    document.getElementById("no-results").style.display = visible ? "none" : "block";
});

// Lógica de eliminación (Identificadores mantenidos)
function eliminarMateria(id){
    if(!confirm("¿Estás seguro de eliminar esta asignatura? Esta acción es irreversible.")) return;

    window.location.href = 
    "/sistema_academico/modules/admin/eliminar_materia.php?id=" + id;
}
</script>

<style>
@keyframes slide-up { 
    from { opacity: 0; transform: translateY(20px); } 
    to { opacity: 1; transform: translateY(0); } 
}
.animate-fade-in { animation: slide-up 0.5s cubic-bezier(0.16, 1, 0.3, 1); }
</style>