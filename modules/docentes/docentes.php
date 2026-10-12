<?php

require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/permisos.php';

validarPermiso('ver_docentes');

$puedeCrear = tienePermiso('crear_docentes');

/*
|--------------------------------------------------------------------------
| OBTENER DOCENTES
|--------------------------------------------------------------------------
*/

$stmt = $pdo->query("
    SELECT 
        d.id,
        u.nombres,
        u.apellido_paterno,
        u.apellido_materno,
        u.email,
        u.activo,
        d.rfc,
        d.curp
    FROM docentes d
    INNER JOIN usuarios u 
        ON u.id = d.usuario_id
    ORDER BY 
        u.apellido_paterno ASC,
        u.apellido_materno ASC,
        u.nombres ASC
");

$docentes = $stmt->fetchAll(PDO::FETCH_ASSOC);
$total = count($docentes);

?>

<div class="max-w-7xl mx-auto p-4">

    <div class="flex flex-col md:flex-row md:items-center justify-between mb-10 border-b pb-6 gap-4">

        <div>
            <h1 class="text-3xl font-extrabold text-slate-800 tracking-tight">
                Directorio de Docentes
            </h1>

            <p class="text-slate-500 font-medium">
                Gestión y consulta de expedientes del personal académico activo.
            </p>
        </div>

        <div class="flex items-center gap-3">

            <span class="bg-indigo-50 text-indigo-700 text-xs font-bold px-4 py-2 rounded-full border border-indigo-100">
                Total Registrados: <?= $total ?>
            </span>

            <?php if($puedeCrear): ?>
                <a
                    href="?modulo=crear_docente"
                    class="bg-indigo-600 hover:bg-indigo-700 text-white px-5 py-3 rounded-2xl text-xs font-black uppercase tracking-widest transition-all"
                >
                    Nuevo Docente
                </a>
            <?php endif; ?>

        </div>

    </div>

    <div class="mb-8">

        <input
            id="buscador"
            type="text"
            placeholder="Buscar docente por nombre, correo, RFC o CURP..."
            class="w-full p-5 bg-white border border-slate-100 rounded-[2rem] shadow-sm font-bold text-slate-600 outline-none focus:ring-4 focus:ring-indigo-500/10 placeholder:text-slate-300"
        >

    </div>

    <div id="grid-docentes" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">

        <?php foreach($docentes as $d): ?>

            <?php
                $nombreCompleto = trim(
                    $d['apellido_paterno'] . ' ' .
                    $d['apellido_materno'] . ', ' .
                    $d['nombres']
                );

                $iniciales =
                    mb_substr($d['nombres'], 0, 1) .
                    mb_substr($d['apellido_paterno'], 0, 1);

                $textoBusqueda = strtolower(
                    $nombreCompleto . ' ' .
                    $d['email'] . ' ' .
                    $d['rfc'] . ' ' .
                    $d['curp']
                );
            ?>

            <a
                href="?modulo=detalle_docente&id=<?= $d['id'] ?>"
                class="group docente-card"
                data-text="<?= htmlspecialchars($textoBusqueda) ?>"
            >

                <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-200 transition-all duration-300 group-hover:shadow-xl group-hover:border-indigo-300 group-hover:-translate-y-1 relative overflow-hidden">

                    <div class="absolute left-0 top-0 bottom-0 w-1 bg-slate-200 group-hover:bg-indigo-500 transition-colors"></div>

                    <div class="flex flex-col h-full">

                        <div class="w-12 h-12 bg-slate-100 rounded-full flex items-center justify-center mb-4 group-hover:bg-indigo-50 transition-colors">

                            <span class="text-slate-500 group-hover:text-indigo-600 font-bold text-lg uppercase">
                                <?= htmlspecialchars($iniciales) ?>
                            </span>

                        </div>

                        <div class="space-y-1">

                            <h2 class="font-extrabold text-slate-800 group-hover:text-indigo-700 leading-tight transition-colors">
                                <?= htmlspecialchars($d['apellido_paterno']) ?>
                                <?= htmlspecialchars($d['apellido_materno']) ?>,
                                <br>

                                <span class="font-bold text-base opacity-90">
                                    <?= htmlspecialchars($d['nombres']) ?>
                                </span>
                            </h2>

                            <div class="flex items-center gap-2 pt-3">

                                <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z">
                                    </path>
                                </svg>

                                <p class="text-xs text-slate-500 truncate font-medium">
                                    <?= htmlspecialchars($d['email']) ?>
                                </p>

                            </div>

                            <div class="pt-3 flex flex-wrap gap-2">

                                <?php if($d['rfc']): ?>
                                    <span class="bg-slate-50 text-slate-500 border border-slate-100 px-3 py-1 rounded-full text-[9px] font-black uppercase">
                                        RFC: <?= htmlspecialchars($d['rfc']) ?>
                                    </span>
                                <?php endif; ?>

                                <?php if($d['activo']): ?>
                                    <span class="bg-emerald-50 text-emerald-600 border border-emerald-100 px-3 py-1 rounded-full text-[9px] font-black uppercase">
                                        Activo
                                    </span>
                                <?php else: ?>
                                    <span class="bg-red-50 text-red-600 border border-red-100 px-3 py-1 rounded-full text-[9px] font-black uppercase">
                                        Inactivo
                                    </span>
                                <?php endif; ?>

                            </div>

                        </div>

                        <div class="mt-6 pt-4 border-t border-slate-50 flex justify-between items-center">

                            <span class="text-[10px] font-bold text-indigo-500 uppercase tracking-widest opacity-0 group-hover:opacity-100 transition-opacity">
                                Ver Expediente
                            </span>

                            <svg class="w-4 h-4 text-slate-300 group-hover:text-indigo-500 group-hover:translate-x-1 transition-all" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M9 5l7 7-7 7">
                                </path>
                            </svg>

                        </div>

                    </div>

                </div>

            </a>

        <?php endforeach; ?>

    </div>

    <?php if($total === 0): ?>

        <div class="py-20 text-center">

            <div class="bg-slate-50 border border-dashed border-slate-300 rounded-2xl p-10 max-w-md mx-auto">

                <svg class="w-12 h-12 text-slate-300 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z">
                    </path>
                </svg>

                <p class="text-slate-500 font-bold uppercase tracking-widest text-xs">
                    No se encontraron docentes registrados
                </p>

            </div>

        </div>

    <?php endif; ?>

    <div id="no-results" class="hidden py-20 text-center">

        <p class="text-slate-300 font-black italic uppercase tracking-[0.2em] text-xl">
            Sin coincidencias
        </p>

    </div>

</div>

<script>
document.getElementById("buscador").addEventListener("keyup", function(){

    const filtro = this.value.toLowerCase().trim();
    const cards = document.querySelectorAll(".docente-card");
    let visibles = 0;

    cards.forEach(card => {

        if(card.dataset.text.includes(filtro)){
            card.style.display = "block";
            visibles++;
        }else{
            card.style.display = "none";
        }

    });

    const noResults = document.getElementById("no-results");

    if(visibles === 0 && cards.length > 0){
        noResults.classList.remove("hidden");
    }else{
        noResults.classList.add("hidden");
    }

});
</script>