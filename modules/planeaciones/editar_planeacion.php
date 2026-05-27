<?php

require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/permisos.php';

validarPermiso('editar_planeaciones');

/*
|--------------------------------------------------------------------------
| VALIDAR ID
|--------------------------------------------------------------------------
*/

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

if($id <= 0){

    echo "
    <div class='max-w-2xl mx-auto mt-10 bg-red-50 border border-red-200 p-8 rounded-3xl'>
        <h2 class='text-xl font-black text-red-700 uppercase tracking-widest mb-3'>
            Planeación no especificada
        </h2>
        <p class='text-red-600 font-semibold'>
            No se recibió un ID válido para editar.
        </p>
    </div>
    ";

    exit;
}

/*
|--------------------------------------------------------------------------
| OBTENER PLANEACIÓN
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare("
    SELECT *
    FROM planeaciones
    WHERE id = ?
");

$stmt->execute([$id]);

$plan = $stmt->fetch(PDO::FETCH_ASSOC);

if(!$plan){

    echo "
    <div class='max-w-2xl mx-auto mt-10 bg-amber-50 border border-amber-200 p-8 rounded-3xl'>
        <h2 class='text-xl font-black text-amber-700 uppercase tracking-widest mb-3'>
            Planeación no encontrada
        </h2>
        <p class='text-amber-600 font-semibold'>
            La planeación solicitada no existe.
        </p>
    </div>
    ";

    exit;
}

?>

<div class="max-w-5xl mx-auto p-6 animate-fade-in">

    <div class="mb-10">

        <div class="flex items-center gap-3 mb-3">
            <span class="bg-blue-600 text-white text-[10px] px-3 py-1 rounded-full font-black uppercase tracking-widest">
                Planeación
            </span>

            <span class="text-slate-300">/</span>

            <span class="text-slate-500 text-[10px] font-black uppercase tracking-widest italic">
                Edición Académica
            </span>
        </div>

        <h1 class="text-4xl font-black uppercase tracking-tighter text-slate-800 italic leading-none">
            Editar Planeación
        </h1>

    </div>

    <form
        method="POST"
        action="?modulo=actualizar_planeacion"
        class="bg-white rounded-[3rem] border border-slate-100 shadow-xl shadow-slate-200/40 p-10 space-y-8"
    >

        <input type="hidden" name="id" value="<?= (int) $plan['id'] ?>">
        <input type="hidden" name="tema_id" value="<?= (int) $plan['tema_id'] ?>">

        <div>
            <label class="block mb-3 text-[11px] font-black uppercase tracking-[0.2em] text-slate-400">
                Objetivo de Aprendizaje
            </label>

            <textarea
                name="objetivo"
                rows="4"
                required
                class="w-full p-5 rounded-3xl border border-slate-200 bg-slate-50 focus:bg-white focus:ring-4 focus:ring-blue-100 focus:border-blue-500 outline-none transition-all resize-none font-medium text-slate-700"
            ><?= htmlspecialchars($plan['objetivo'] ?? '') ?></textarea>
        </div>

        <div>
            <label class="block mb-3 text-[11px] font-black uppercase tracking-[0.2em] text-slate-400">
                Actividad
            </label>

            <textarea
                name="actividad"
                rows="5"
                required
                class="w-full p-5 rounded-3xl border border-slate-200 bg-slate-50 focus:bg-white focus:ring-4 focus:ring-purple-100 focus:border-purple-500 outline-none transition-all resize-none font-medium text-slate-700"
            ><?= htmlspecialchars($plan['actividad'] ?? '') ?></textarea>
        </div>

        <div>
            <label class="block mb-3 text-[11px] font-black uppercase tracking-[0.2em] text-slate-400">
                Material Didáctico
            </label>

            <textarea
                name="descripcion"
                rows="4"
                class="w-full p-5 rounded-3xl border border-slate-200 bg-slate-50 focus:bg-white focus:ring-4 focus:ring-emerald-100 focus:border-emerald-500 outline-none transition-all resize-none font-medium text-slate-700"
            ><?= htmlspecialchars($plan['descripcion'] ?? '') ?></textarea>
        </div>

        <div class="flex flex-col md:flex-row gap-4 pt-6">

            <button
                type="submit"
                class="flex-1 bg-blue-600 hover:bg-blue-700 text-white py-5 rounded-3xl font-black uppercase tracking-widest text-sm transition-all shadow-xl shadow-blue-200 hover:scale-[1.01]"
            >
                Actualizar Planeación
            </button>

            <a
                href="javascript:history.back()"
                class="flex-1 text-center bg-slate-100 hover:bg-slate-200 text-slate-700 py-5 rounded-3xl font-black uppercase tracking-widest text-sm transition-all"
            >
                Cancelar
            </a>

        </div>

    </form>

</div>

<style>
@keyframes fade-up{
    from{
        opacity:0;
        transform:translateY(20px);
    }

    to{
        opacity:1;
        transform:translateY(0);
    }
}

.animate-fade-in{
    animation:fade-up .5s ease-out;
}
</style>