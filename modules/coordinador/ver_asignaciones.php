<?php

require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/permisos.php';

validarPermiso('ver_asignaciones');

$puedeEliminar = tienePermiso('eliminar_asignaciones');

/*
|--------------------------------------------------------------------------
| OBTENER ASIGNACIONES
|--------------------------------------------------------------------------
*/

$stmt = $pdo->query("
    SELECT 
        ad.id,
        CONCAT(
            u.nombres, ' ',
            u.apellido_paterno, ' ',
            u.apellido_materno
        ) AS docente,
        m.nombre AS materia,
        m.clave AS clave_materia,
        g.nombre AS grupo,
        g.grado,
        p.nombre AS periodo,
        c.nombre AS carrera
    FROM asignaciones_docentes ad
    INNER JOIN docentes d 
        ON d.id = ad.docente_id
    INNER JOIN usuarios u 
        ON u.id = d.usuario_id
    INNER JOIN materias m 
        ON m.id = ad.materia_id
    INNER JOIN grupos g 
        ON g.id = ad.grupo_id
    INNER JOIN periodos p 
        ON p.id = ad.periodo_id
    INNER JOIN carreras c
        ON c.id = g.carrera_id
    ORDER BY 
        p.nombre DESC,
        c.nombre ASC,
        g.grado ASC,
        g.nombre ASC,
        m.nombre ASC
");

$asignaciones = $stmt->fetchAll(PDO::FETCH_ASSOC);
$total = count($asignaciones);

?>

<div class="max-w-7xl mx-auto p-6 animate-fade-in">

    <div class="mb-8 flex flex-col md:flex-row md:items-end md:justify-between gap-6">

        <div>
            <div class="flex items-center gap-2 mb-2">
                <span class="bg-purple-600 text-white text-[10px] font-black px-3 py-1 rounded-full uppercase tracking-widest">
                    Gestión Académica
                </span>
            </div>

            <h1 class="text-4xl font-black text-slate-900 tracking-tighter uppercase">
                Asignaciones
                <span class="text-purple-600 italic">
                    Docentes
                </span>
            </h1>

            <p class="text-slate-400 text-xs font-bold uppercase tracking-widest mt-2">
                Relación de docentes, asignaturas y grupos
            </p>
        </div>

        <div class="text-right bg-white px-6 py-4 rounded-2xl border border-slate-100 shadow-sm">
            <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest">
                Registros Totales
            </p>

            <p id="contador-visual" class="text-2xl font-black text-slate-700">
                <?= $total ?>
            </p>
        </div>

    </div>

    <div class="relative mb-8">

        <input
            id="buscador"
            type="text"
            placeholder="Buscar por docente, asignatura, grupo, carrera o periodo..."
            class="w-full p-5 bg-white border border-slate-100 rounded-[2rem] shadow-sm font-bold text-slate-600 focus:ring-4 focus:ring-purple-500/10 outline-none transition-all placeholder:text-slate-300"
        >

    </div>

    <div class="bg-white rounded-[2rem] shadow-xl shadow-slate-200/50 border border-slate-100 overflow-hidden">

        <div class="overflow-x-auto">

            <table class="w-full border-collapse min-w-[1000px]">

                <thead>
                    <tr class="bg-slate-900 text-white">

                        <th class="p-5 text-left text-[10px] font-black uppercase tracking-[0.2em]">
                            Docente
                        </th>

                        <th class="p-5 text-left text-[10px] font-black uppercase tracking-[0.2em]">
                            Asignatura
                        </th>

                        <th class="p-5 text-left text-[10px] font-black uppercase tracking-[0.2em]">
                            Carrera
                        </th>

                        <th class="p-5 text-center text-[10px] font-black uppercase tracking-[0.2em]">
                            Grupo
                        </th>

                        <th class="p-5 text-left text-[10px] font-black uppercase tracking-[0.2em]">
                            Periodo
                        </th>

                        <?php if($puedeEliminar): ?>
                            <th class="p-5 text-center text-[10px] font-black uppercase tracking-[0.2em]">
                                Gestión
                            </th>
                        <?php endif; ?>

                    </tr>
                </thead>

                <tbody class="divide-y divide-slate-100">

                    <?php if($asignaciones): ?>

                        <?php foreach($asignaciones as $a): ?>

                            <tr
                                class="fila-asignacion hover:bg-slate-50/80 transition-colors group"
                                data-text="<?= htmlspecialchars(strtolower(
                                    $a['docente'].' '.
                                    $a['materia'].' '.
                                    $a['clave_materia'].' '.
                                    $a['grupo'].' '.
                                    $a['grado'].' '.
                                    $a['periodo'].' '.
                                    $a['carrera']
                                )) ?>"
                            >

                                <td class="p-5">
                                    <div class="flex flex-col">

                                        <span class="text-sm font-bold text-slate-800">
                                            <?= htmlspecialchars($a['docente']) ?>
                                        </span>

                                        <span class="text-[10px] font-medium text-slate-400">
                                            ID Asignación: #<?= $a['id'] ?>
                                        </span>

                                    </div>
                                </td>

                                <td class="p-5">
                                    <div class="flex flex-col">

                                        <span class="text-sm font-black text-slate-700 italic">
                                            <?= htmlspecialchars($a['materia']) ?>
                                        </span>

                                        <span class="text-[10px] font-black text-purple-500 uppercase tracking-widest">
                                            <?= htmlspecialchars($a['clave_materia']) ?>
                                        </span>

                                    </div>
                                </td>

                                <td class="p-5 text-xs font-bold text-slate-500 uppercase">
                                    <?= htmlspecialchars($a['carrera']) ?>
                                </td>

                                <td class="p-5 text-center">
                                    <span class="px-3 py-1 bg-purple-50 text-purple-700 rounded-lg text-xs font-black uppercase">
                                        <?= htmlspecialchars($a['grupo']) ?>
                                    </span>

                                    <span class="block text-[9px] font-black text-slate-400 uppercase mt-1">
                                        <?= (int)$a['grado'] ?>° Grado
                                    </span>
                                </td>

                                <td class="p-5 text-xs font-bold text-slate-500">
                                    <?= htmlspecialchars($a['periodo']) ?>
                                </td>

                                <?php if($puedeEliminar): ?>

                                    <td class="p-5">

                                        <div class="flex items-center justify-center gap-2">

                                            <a
                                                href="?modulo=eliminar_asignacion&id=<?= $a['id'] ?>"
                                                onclick="return confirm('¿Confirmas la eliminación permanente de esta asignación?')"
                                                class="p-2 text-slate-300 hover:text-red-500 transition-colors"
                                                title="Eliminar Asignación"
                                            >
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path
                                                        stroke-linecap="round"
                                                        stroke-linejoin="round"
                                                        stroke-width="2"
                                                        d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16">
                                                    </path>
                                                </svg>
                                            </a>

                                        </div>

                                    </td>

                                <?php endif; ?>

                            </tr>

                        <?php endforeach; ?>

                    <?php else: ?>

                        <tr>
                            <td
                                colspan="<?= $puedeEliminar ? 6 : 5 ?>"
                                class="p-16 text-center text-slate-400 text-xs font-black uppercase tracking-widest"
                            >
                                No hay asignaciones registradas.
                            </td>
                        </tr>

                    <?php endif; ?>

                </tbody>

            </table>

        </div>

    </div>

    <div id="no-results" class="hidden text-center py-20">
        <p class="text-slate-300 font-black italic uppercase tracking-[0.2em] text-xl">
            Sin coincidencias
        </p>
    </div>

</div>

<script>
document.getElementById("buscador").addEventListener("keyup", function(){

    let filtro = this.value.toLowerCase().trim();
    let filas = document.querySelectorAll(".fila-asignacion");
    let visibles = 0;

    filas.forEach(fila => {

        if(fila.dataset.text.includes(filtro)){
            fila.style.display = "";
            visibles++;
        }else{
            fila.style.display = "none";
        }

    });

    document.getElementById("contador-visual").innerText = visibles;

    if(visibles === 0 && filas.length > 0){
        document.getElementById("no-results").classList.remove("hidden");
    }else{
        document.getElementById("no-results").classList.add("hidden");
    }
});
</script>

<style>
@keyframes slide-up {
    from {
        opacity: 0;
        transform: translateY(15px);
    }

    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.animate-fade-in {
    animation: slide-up 0.5s ease-out;
}
</style>