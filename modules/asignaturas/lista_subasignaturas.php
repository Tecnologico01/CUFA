<?php
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/permisos.php';

validarPermiso('ver_subasignaturas');

$puedeCrear = tienePermiso('crear_subasignaturas');
$puedeEditar = tienePermiso('editar_subasignaturas');
$puedeEliminar = tienePermiso('eliminar_subasignaturas');
$puedeVerDetalle = tienePermiso('ver_detalle_subasignatura') || tienePermiso('ver_subasignaturas');

$subas = $pdo->query("
    SELECT 
        s.*,
        (
            SELECT COUNT(*)
            FROM materia_subasignatura ms
            WHERE ms.subasignatura_id = s.id
        ) AS total_materias
    FROM subasignaturas s
    ORDER BY s.nombre ASC
")->fetchAll(PDO::FETCH_ASSOC);

$total_subas = count($subas);

$msg = $_GET['msg'] ?? '';
$error = $_GET['error'] ?? '';
?>

<div class="max-w-7xl mx-auto p-6 bg-slate-50 min-h-screen animate-fade-in">

    <div class="mb-10 flex flex-col md:flex-row md:items-end md:justify-between gap-6">
        <div>
            <h1 class="text-5xl font-black italic uppercase tracking-tighter text-slate-800 leading-none">
                Subasignaturas
            </h1>

            <p class="text-purple-600 font-bold text-[10px] uppercase tracking-[0.3em] italic mt-2">
                Catálogo general de subasignaturas registradas
            </p>
        </div>

        <div class="flex items-center gap-4">
            <div class="bg-white px-5 py-3 rounded-2xl shadow-sm border border-slate-100">
                <span class="block text-[9px] font-black text-slate-300 uppercase tracking-widest">
                    Total
                </span>
                <span id="contador-visual" class="text-2xl font-black text-purple-600">
                    <?= (int)$total_subas ?>
                </span>
            </div>

            <?php if($puedeCrear): ?>
                <a href="?modulo=crear_subasignatura"
                   class="bg-purple-600 hover:bg-slate-900 text-white px-6 py-4 rounded-2xl font-black uppercase text-[10px] tracking-widest transition-all shadow-lg">
                    + Nueva Subasignatura
                </a>
            <?php endif; ?>
        </div>
    </div>

    <?php if($msg): ?>
        <div class="bg-emerald-50 border border-emerald-200 text-emerald-700 p-5 rounded-2xl mb-8 font-bold">
            <?= htmlspecialchars($msg === 'eliminado' ? 'Subasignatura eliminada correctamente.' : $msg) ?>
        </div>
    <?php endif; ?>

    <?php if($error): ?>
        <div class="bg-red-50 border border-red-200 text-red-700 p-5 rounded-2xl mb-8 font-bold">
            <?= htmlspecialchars($error === '1' ? 'No se pudo completar la operación.' : $error) ?>
        </div>
    <?php endif; ?>

    <div class="relative mb-12">
        <div class="absolute inset-y-0 left-0 pl-6 flex items-center pointer-events-none">
            <svg class="w-5 h-5 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"
                      stroke-width="3"
                      stroke-linecap="round"
                      stroke-linejoin="round">
                </path>
            </svg>
        </div>

        <input
            id="buscador"
            placeholder="Filtrar por clave, nombre o modalidad..."
            class="w-full p-6 pl-14 bg-white border border-slate-100 rounded-[2.5rem] shadow-sm font-bold text-slate-600 focus:ring-4 focus:ring-purple-500/10 outline-none transition-all placeholder:text-slate-200"
        >
    </div>

    <?php if(count($subas) > 0): ?>

        <div id="contenedor-subas" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">

            <?php foreach($subas as $s): ?>

                <div
                    class="card-suba group bg-white p-8 rounded-[2.5rem] border border-slate-100 shadow-sm hover:shadow-xl hover:-translate-y-2 transition-all relative overflow-hidden"
                    data-text="<?= htmlspecialchars(strtolower(trim(
                        ($s['clave'] ?? '') . ' ' .
                        ($s['nombre'] ?? '') . ' ' .
                        ($s['recurso'] ?? '') . ' ' .
                        ($s['descripcion'] ?? '')
                    ))) ?>"
                >

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
                                <label class="text-[8px] font-black text-slate-300 uppercase block mb-1">
                                    Carga Horaria
                                </label>

                                <p class="text-[11px] font-black text-slate-500 italic uppercase">
                                    <?= (int)$s['horas_frente_grupo'] ?>h /
                                    <?= (int)$s['horas_independiente'] ?>h
                                </p>
                            </div>

                            <div>
                                <label class="text-[8px] font-black text-slate-300 uppercase block mb-1">
                                    Créditos
                                </label>

                                <p class="text-[11px] font-black text-emerald-600 italic">
                                    <?= (int)$s['creditos'] ?> pts
                                </p>
                            </div>

                        </div>

                        <div class="mt-4 pt-4 border-t border-slate-50">
                            <label class="text-[8px] font-black text-slate-300 uppercase block mb-2">
                                Asignada a materias
                            </label>

                            <div class="flex items-center justify-between">
                                <span class="text-xs font-black text-indigo-600 uppercase italic">
                                    <?= (int)$s['total_materias'] ?> Materias
                                </span>

                                <?php if((int)$s['total_materias'] > 0): ?>
                                    <span class="text-[8px] px-3 py-1 rounded-full bg-emerald-50 text-emerald-600 border border-emerald-100 font-black uppercase tracking-widest">
                                        En uso
                                    </span>
                                <?php else: ?>
                                    <span class="text-[8px] px-3 py-1 rounded-full bg-slate-50 text-slate-400 border border-slate-100 font-black uppercase tracking-widest">
                                        Libre
                                    </span>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="mt-8 pt-6 border-t border-slate-50 flex flex-wrap gap-3">

                            <?php if($puedeVerDetalle): ?>
                                <a href="?modulo=ver_subasignatura&id=<?= (int)$s['id'] ?>"
                                   class="flex-1 text-center bg-slate-900 hover:bg-purple-600 text-white py-3 rounded-2xl text-[9px] font-black uppercase tracking-widest transition-all shadow-md shadow-slate-100">
                                    Ver
                                </a>
                            <?php endif; ?>

                            <?php if($puedeEditar): ?>
                                <a href="?modulo=editar_subasignatura&id=<?= (int)$s['id'] ?>"
                                   class="flex-1 text-center bg-blue-500 hover:bg-blue-600 text-white py-3 rounded-2xl text-[9px] font-black uppercase tracking-widest transition-all">
                                    Editar
                                </a>
                            <?php endif; ?>

                            <?php if($puedeEliminar): ?>
                                <button type="button"
                                        onclick="eliminarSuba(<?= (int)$s['id'] ?>)"
                                        class="flex-1 bg-white border border-slate-100 hover:bg-red-50 hover:border-red-100 text-slate-300 hover:text-red-500 py-3 rounded-2xl text-[9px] font-black uppercase tracking-widest transition-all">
                                    Eliminar
                                </button>
                            <?php endif; ?>

                        </div>

                    </div>

                </div>

            <?php endforeach; ?>

        </div>

    <?php else: ?>

        <div class="bg-white rounded-[2.5rem] border border-slate-100 p-16 text-center shadow-sm">
            <div class="text-6xl mb-4">📚</div>

            <h2 class="text-2xl font-black text-slate-800 uppercase mb-3">
                Sin subasignaturas registradas
            </h2>

            <p class="text-slate-400 font-bold">
                Aún no existen subasignaturas en el sistema.
            </p>
        </div>

    <?php endif; ?>

    <div id="no-results" class="hidden py-32 text-center">
        <div class="inline-block p-6 bg-slate-100 rounded-full mb-4">
            <svg class="w-10 h-10 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path d="M9.172 9.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
                      stroke-width="2">
                </path>
            </svg>
        </div>

        <p class="text-slate-300 font-black italic uppercase tracking-[0.2em] text-xl">
            Sin coincidencias en el archivo
        </p>
    </div>

</div>

<script>
const buscadorSubas = document.getElementById("buscador");

if(buscadorSubas){
    buscadorSubas.addEventListener("keyup", function(){

        const filtro = this.value.toLowerCase().trim();
        const items = document.querySelectorAll(".card-suba");
        let encontrados = 0;

        items.forEach(el => {

            const texto = el.dataset.text || "";

            if(texto.includes(filtro)){
                el.style.display = "block";
                encontrados++;
            }else{
                el.style.display = "none";
            }

        });

        const contador = document.getElementById("contador-visual");
        const empty = document.getElementById("no-results");

        if(contador){
            contador.innerText = encontrados;
        }

        if(empty){
            if(encontrados === 0){
                empty.classList.remove("hidden");
            }else{
                empty.classList.add("hidden");
            }
        }
    });
}

function eliminarSuba(id){

    if(!confirm("¿Confirmar eliminación de la subasignatura? Esta acción es irreversible.")){
        return;
    }

    window.location.href = "?modulo=eliminar_subasignatura&id=" + id;
}
</script>

<style>
@keyframes slide-up {
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
    animation: slide-up 0.6s cubic-bezier(0.16, 1, 0.3, 1);
}
</style>