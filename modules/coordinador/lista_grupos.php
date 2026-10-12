<?php

require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/permisos.php';

validarPermiso('ver_grupos');

$puedeCrear = tienePermiso('crear_grupos');
$puedeEditar = tienePermiso('editar_grupos');
$puedeEliminar = tienePermiso('eliminar_grupos');

/* =========================
   OBTENER GRUPOS
========================= */

$stmt = $pdo->query("
    SELECT 
        g.id,
        g.nombre,
        g.grado,

        p.nombre AS periodo,
        c.nombre AS carrera

    FROM grupos g

    INNER JOIN periodos p
        ON p.id = g.periodo_id

    INNER JOIN carreras c
        ON c.id = g.carrera_id

    ORDER BY 
        c.nombre ASC,
        g.grado ASC,
        g.nombre ASC
");

$grupos = $stmt->fetchAll(PDO::FETCH_ASSOC);

$total_grupos = count($grupos);

?>

<style>
@import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;700;800&display=swap');

.cufa-wrapper {
    font-family: 'Plus Jakarta Sans', sans-serif;
}

@keyframes slideUp {
    from {
        opacity: 0;
        transform: translateY(20px);
    }

    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.animate-fade-in {
    animation: slideUp 0.6s cubic-bezier(0.16, 1, 0.3, 1);
}
</style>

<div class="cufa-wrapper max-w-7xl mx-auto p-6 md:p-10 bg-slate-50 min-h-screen animate-fade-in">

    <div class="mb-12 flex flex-col sm:flex-row sm:items-end justify-between gap-4 border-b border-slate-200/60 pb-8">

        <div>
            <div class="flex items-center gap-2 mb-2">

                <span class="w-2 h-2 bg-purple-600 rounded-full shadow-[0_0_10px_rgba(147,51,234,0.5)]"></span>

                <span class="text-[10px] font-black text-slate-400 uppercase tracking-[0.2em]">
                    Panel Académico
                </span>

            </div>

            <h1 class="text-5xl font-black uppercase tracking-tighter text-slate-900 leading-none">
                Gestión de
                <span class="text-purple-600 italic">
                    Grupos
                </span>
            </h1>

            <p class="text-slate-400 text-xs font-bold uppercase tracking-widest mt-3">
                Listado de grupos académicos registrados
            </p>
        </div>

        <div class="flex items-center gap-4">

            <div class="bg-white px-5 py-3 rounded-2xl border border-slate-100 shadow-sm text-right">
                <span class="text-[9px] font-black text-slate-400 uppercase tracking-widest block">
                    Total
                </span>

                <span id="contador-visual" class="text-2xl font-mono font-black text-slate-800">
                    <?= $total_grupos ?>
                </span>
            </div>

            <?php if($puedeCrear): ?>

                <a
                    href="?modulo=grupos"
                    class="bg-purple-600 hover:bg-slate-900 text-white px-6 py-4 rounded-2xl font-black uppercase text-[10px] tracking-widest transition-all shadow-lg"
                >
                    + Nuevo Grupo
                </a>

            <?php endif; ?>

        </div>

    </div>

    <div class="relative mb-10">

        <input
            id="buscador"
            type="text"
            placeholder="Buscar por grupo, carrera, grado o periodo..."
            class="w-full p-5 bg-white border border-slate-100 rounded-[2rem] shadow-sm font-bold text-slate-600 focus:ring-4 focus:ring-purple-500/10 outline-none transition-all placeholder:text-slate-300"
        >

    </div>

    <div id="grid-grupos" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">

        <?php foreach($grupos as $g): ?>

            <div
                class="card-grupo bg-white p-8 rounded-[2.5rem] border border-slate-100 shadow-xl shadow-slate-200/40 hover:shadow-2xl hover:shadow-purple-100 hover:-translate-y-1 transition-all group relative overflow-hidden flex flex-col justify-between min-h-[270px]"
                data-text="<?= htmlspecialchars(strtolower(trim($g['nombre'].' '.$g['carrera'].' '.$g['periodo'].' '.$g['grado']))) ?>"
            >

                <div class="absolute -top-10 -right-10 w-24 h-24 bg-slate-50 rounded-full group-hover:bg-purple-50 transition-colors duration-500"></div>

                <div class="relative z-10">

                    <div class="flex justify-between items-start gap-4 mb-6">

                        <div>
                            <span class="text-[9px] font-black text-purple-600 bg-purple-50 px-2.5 py-1 rounded-md uppercase tracking-wider block w-max mb-1.5">
                                ID #<?= $g['id'] ?>
                            </span>

                            <h2 class="text-2xl font-black text-slate-800 tracking-tight group-hover:text-purple-600 transition-colors uppercase">
                                Grupo <?= htmlspecialchars($g['nombre']) ?>
                            </h2>

                            <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mt-1">
                                <?= (int)$g['grado'] ?>° Grado
                            </p>
                        </div>

                    </div>

                    <div class="space-y-4 border-t border-slate-50 pt-4">

                        <div>
                            <span class="font-black text-slate-400 uppercase text-[9px] tracking-widest block mb-0.5">
                                Carrera
                            </span>

                            <p class="text-xs font-bold text-slate-700 uppercase tracking-wide leading-relaxed">
                                <?= htmlspecialchars($g['carrera']) ?>
                            </p>
                        </div>

                        <div>
                            <span class="font-black text-slate-400 uppercase text-[9px] tracking-widest block mb-0.5">
                                Periodo
                            </span>

                            <p class="text-xs font-bold text-slate-500 uppercase tracking-wide">
                                <?= htmlspecialchars($g['periodo']) ?>
                            </p>
                        </div>

                    </div>

                </div>

                <div class="relative z-10 flex flex-wrap gap-3 mt-8 pt-6 border-t border-slate-50">

                    <a
                        href="?modulo=detalle_grupo&id=<?= $g['id'] ?>"
                        class="flex-1 text-center bg-slate-900 hover:bg-purple-600 text-white py-3 rounded-2xl text-[9px] font-black uppercase tracking-widest transition-all shadow-md shadow-slate-100"
                    >
                        Ver
                    </a>

                    <?php if($puedeEditar): ?>

                        <a
                            href="?modulo=editar_grupo&id=<?= $g['id'] ?>"
                            class="flex-1 text-center bg-blue-500 hover:bg-blue-600 text-white py-3 rounded-2xl text-[9px] font-black uppercase tracking-widest transition-all"
                        >
                            Editar
                        </a>

                    <?php endif; ?>

                    <?php if($puedeEliminar): ?>

                        <button
                            type="button"
                            onclick="eliminarGrupo(<?= $g['id'] ?>)"
                            class="flex-1 bg-white border border-slate-100 hover:bg-red-50 hover:border-red-100 text-slate-300 hover:text-red-500 py-3 rounded-2xl text-[9px] font-black uppercase tracking-widest transition-all"
                        >
                            Eliminar
                        </button>

                    <?php endif; ?>

                </div>

            </div>

        <?php endforeach; ?>

    </div>

    <div id="no-results" class="hidden text-center py-24 bg-white rounded-[3rem] border border-dashed border-slate-200 shadow-inner max-w-md mx-auto mt-12 p-8">

        <div class="bg-slate-50 w-16 h-16 rounded-3xl flex items-center justify-center mx-auto mb-4 text-slate-300">
            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"
                ></path>
            </svg>
        </div>

        <p class="text-slate-700 font-black uppercase text-sm tracking-wider mb-1">
            Sin resultados
        </p>

        <p class="text-slate-400 text-xs font-medium">
            No se encontraron grupos con esa búsqueda.
        </p>

    </div>

    <?php if(empty($grupos)): ?>

        <div class="text-center py-24 bg-white rounded-[3rem] border border-dashed border-slate-200 shadow-inner max-w-md mx-auto mt-12 p-8">

            <div class="bg-slate-50 w-16 h-16 rounded-3xl flex items-center justify-center mx-auto mb-4 text-slate-300">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"
                    ></path>
                </svg>
            </div>

            <p class="text-slate-700 font-black uppercase text-sm tracking-wider mb-1">
                Sin registros
            </p>

            <p class="text-slate-400 text-xs font-medium">
                No se encontraron grupos configurados.
            </p>

        </div>

    <?php endif; ?>

    <p class="text-center mt-16 text-[10px] font-black text-slate-300 uppercase tracking-[0.5em]">
        GRUPOS ACADÉMICOS CUFA
    </p>

</div>

<script>
document.getElementById("buscador").addEventListener("keyup", function(){

    let filtro = this.value.toLowerCase().trim();
    let cards = document.querySelectorAll(".card-grupo");
    let visibles = 0;

    cards.forEach(card => {

        if(card.dataset.text.includes(filtro)){
            card.style.display = "flex";
            visibles++;
        }else{
            card.style.display = "none";
        }

    });

    document.getElementById("contador-visual").innerText = visibles;

    if(visibles === 0 && cards.length > 0){
        document.getElementById("no-results").classList.remove("hidden");
        document.getElementById("grid-grupos").classList.add("hidden");
    }else{
        document.getElementById("no-results").classList.add("hidden");
        document.getElementById("grid-grupos").classList.remove("hidden");
    }
});

function eliminarGrupo(id){

    if(!confirm("¿Seguro que deseas eliminar este grupo? Esta acción no se puede deshacer.")){
        return;
    }

    window.location.href = "?modulo=eliminar_grupo&id=" + id;
}
</script>