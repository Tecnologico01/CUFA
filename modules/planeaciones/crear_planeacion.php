<?php

require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/permisos.php';

validarPermiso('crear_planeaciones');

/*
|--------------------------------------------------------------------------
| OBTENER DATOS
|--------------------------------------------------------------------------
*/

$tema_id = isset($_GET['tema']) ? (int) $_GET['tema'] : 0;
$semana = isset($_GET['semana']) ? (int) $_GET['semana'] : 0;

/*
|--------------------------------------------------------------------------
| VALIDAR DATOS
|--------------------------------------------------------------------------
*/

if($tema_id <= 0 || $semana <= 0){

    echo "
    <div class='max-w-2xl mx-auto mt-10 bg-red-50 border border-red-200 p-8 rounded-3xl'>
        <h2 class='text-xl font-black text-red-700 uppercase tracking-widest mb-4'>
            Error de Planeación
        </h2>

        <p class='text-red-600 font-semibold'>
            El tema o la semana no fueron especificados correctamente.
        </p>

        <a href='../../dashboard.php'
           class='inline-block mt-6 bg-red-600 hover:bg-red-700 text-white px-6 py-3 rounded-2xl font-black uppercase text-xs tracking-widest transition-all'>
            Volver
        </a>
    </div>
    ";

    exit;
}

/*
|--------------------------------------------------------------------------
| VALIDAR EXISTENCIA DEL TEMA
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare("
    SELECT id, nombre
    FROM temas
    WHERE id = ?
");

$stmt->execute([$tema_id]);

$tema = $stmt->fetch(PDO::FETCH_ASSOC);

if(!$tema){

    echo "
    <div class='max-w-2xl mx-auto mt-10 bg-amber-50 border border-amber-200 p-8 rounded-3xl'>
        <h2 class='text-xl font-black text-amber-700 uppercase tracking-widest mb-4'>
            Tema no encontrado
        </h2>

        <p class='text-amber-600 font-semibold'>
            El tema seleccionado no existe en el sistema.
        </p>
    </div>
    ";

    exit;
}
?>

<div class="max-w-5xl mx-auto p-6 animate-fade-in">

    <!-- HEADER -->
    <div class="mb-10">

        <div class="flex items-center gap-3 mb-3">
            <span class="bg-indigo-600 text-white text-[10px] px-3 py-1 rounded-full font-black uppercase tracking-widest">
                Planeación
            </span>

            <span class="text-slate-300">/</span>

            <span class="text-slate-500 text-[10px] font-black uppercase tracking-widest italic">
                Nueva Planeación Académica
            </span>
        </div>

        <h1 class="text-4xl font-black uppercase tracking-tighter text-slate-800 italic leading-none">
            Crear Planeación
        </h1>

        <p class="mt-4 text-slate-500 font-semibold">
            Tema:
            <span class="text-indigo-600 font-black">
                <?= htmlspecialchars($tema['nombre']) ?>
            </span>
        </p>

    </div>

    <!-- FORMULARIO -->
    <form method="POST"
          action="../../dashboard.php?modulo=guardar_planeacion"
          class="bg-white rounded-[3rem] border border-slate-100 shadow-xl shadow-slate-200/40 p-10 space-y-8">

        <input type="hidden" name="tema_id" value="<?= $tema_id ?>">
        <input type="hidden" name="semana" value="<?= $semana ?>">

        <!-- OBJETIVO -->
        <div>
            <label class="block mb-3 text-[11px] font-black uppercase tracking-[0.2em] text-slate-400">
                Objetivo de Aprendizaje
            </label>

            <textarea
                name="objetivo"
                rows="4"
                required
                class="w-full p-5 rounded-3xl border border-slate-200 bg-slate-50 focus:bg-white focus:ring-4 focus:ring-indigo-100 focus:border-indigo-500 outline-none transition-all resize-none font-medium text-slate-700"
                placeholder="Describe el objetivo principal de la sesión o unidad..."></textarea>
        </div>

        <!-- ACTIVIDAD -->
        <div>
            <label class="block mb-3 text-[11px] font-black uppercase tracking-[0.2em] text-slate-400">
                Actividad de Aprendizaje
            </label>

            <textarea
                name="actividad"
                rows="5"
                required
                class="w-full p-5 rounded-3xl border border-slate-200 bg-slate-50 focus:bg-white focus:ring-4 focus:ring-purple-100 focus:border-purple-500 outline-none transition-all resize-none font-medium text-slate-700"
                placeholder="Describe las actividades que realizará el alumno..."></textarea>
        </div>

        <!-- MATERIAL -->
        <div>
            <label class="block mb-3 text-[11px] font-black uppercase tracking-[0.2em] text-slate-400">
                Material Didáctico
            </label>

            <textarea
                name="descripcion"
                rows="4"
                class="w-full p-5 rounded-3xl border border-slate-200 bg-slate-50 focus:bg-white focus:ring-4 focus:ring-emerald-100 focus:border-emerald-500 outline-none transition-all resize-none font-medium text-slate-700"
                placeholder="Libros, archivos, plataformas, videos, recursos, etc."></textarea>
        </div>

        <!-- BOTONES -->
        <div class="flex flex-col md:flex-row gap-4 pt-6">

            <button
                type="submit"
                class="flex-1 bg-indigo-600 hover:bg-indigo-700 text-white py-5 rounded-3xl font-black uppercase tracking-widest text-sm transition-all shadow-xl shadow-indigo-200 hover:scale-[1.01]">

                Guardar Planeación

            </button>

            <a href="javascript:history.back()"
               class="flex-1 text-center bg-slate-100 hover:bg-slate-200 text-slate-700 py-5 rounded-3xl font-black uppercase tracking-widest text-sm transition-all">

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