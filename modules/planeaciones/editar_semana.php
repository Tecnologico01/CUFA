<?php

require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/permisos.php';

validarPermiso('editar_planeaciones');

/*
|--------------------------------------------------------------------------
| VALIDAR DATOS
|--------------------------------------------------------------------------
*/

$asignacion = isset($_GET['asignacion']) ? (int) $_GET['asignacion'] : 0;
$semana = isset($_GET['semana']) ? (int) $_GET['semana'] : 0;

if($asignacion <= 0 || $semana <= 0){

    echo "
    <div class='max-w-2xl mx-auto mt-10 bg-red-50 border border-red-200 p-8 rounded-3xl'>
        <h2 class='text-xl font-black text-red-700 uppercase tracking-widest mb-3'>
            Datos inválidos
        </h2>
        <p class='text-red-600 font-semibold'>
            No se recibió una asignación o semana válida.
        </p>
    </div>
    ";

    exit;
}

/*
|--------------------------------------------------------------------------
| VALIDAR DOCENTE ACTUAL
|--------------------------------------------------------------------------
*/

$usuario_id = $_SESSION['user_id'] ?? null;

if(!$usuario_id){
    echo "Sesión no válida.";
    exit;
}

$stmtDocente = $pdo->prepare("
    SELECT id
    FROM docentes
    WHERE usuario_id = ?
");

$stmtDocente->execute([$usuario_id]);
$docente = $stmtDocente->fetch(PDO::FETCH_ASSOC);

if(!$docente){
    echo "El usuario actual no está vinculado a un docente.";
    exit;
}

$docente_id = (int) $docente['id'];

/*
|--------------------------------------------------------------------------
| VALIDAR ASIGNACIÓN
|--------------------------------------------------------------------------
*/

$stmtInfo = $pdo->prepare("
    SELECT
        ad.id,
        m.nombre AS materia,
        g.nombre AS grupo
    FROM asignaciones_docentes ad
    INNER JOIN materias m
        ON m.id = ad.materia_id
    INNER JOIN grupos g
        ON g.id = ad.grupo_id
    WHERE ad.id = ?
    AND ad.docente_id = ?
");

$stmtInfo->execute([
    $asignacion,
    $docente_id
]);

$info = $stmtInfo->fetch(PDO::FETCH_ASSOC);

if(!$info){

    echo "
    <div class='max-w-2xl mx-auto mt-10 bg-red-50 border border-red-200 p-8 rounded-3xl'>
        <h2 class='text-xl font-black text-red-700 uppercase tracking-widest mb-3'>
            Acceso no autorizado
        </h2>
        <p class='text-red-600 font-semibold'>
            Esta asignación no pertenece al docente actual.
        </p>
    </div>
    ";

    exit;
}

/*
|--------------------------------------------------------------------------
| OBTENER PLANEACIÓN SEMANAL
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare("
    SELECT *
    FROM planeacion_semanal
    WHERE asignacion_id = ?
    AND semana = ?
    LIMIT 1
");

$stmt->execute([
    $asignacion,
    $semana
]);

$data = $stmt->fetch(PDO::FETCH_ASSOC);

?>

<div class="max-w-5xl mx-auto p-6 animate-fade-in">

    <div class="mb-10">

        <div class="flex items-center gap-3 mb-3">

            <span class="bg-indigo-600 text-white text-[10px] px-3 py-1 rounded-full font-black uppercase tracking-widest">
                Planeación Semanal
            </span>

            <span class="text-slate-300">/</span>

            <span class="text-slate-500 text-[10px] font-black uppercase tracking-widest italic">
                Semana <?= $semana ?>
            </span>

        </div>

        <h1 class="text-4xl font-black uppercase tracking-tighter text-slate-800 italic leading-none">
            Editar Semana <?= $semana ?>
        </h1>

        <p class="mt-4 text-slate-500 font-semibold">
            <?= htmlspecialchars($info['materia']) ?>
            —
            Grupo
            <span class="text-indigo-600 font-black">
                <?= htmlspecialchars($info['grupo']) ?>
            </span>
        </p>

    </div>

    <form
        action="?modulo=guardar_semana"
        method="POST"
        enctype="multipart/form-data"
        class="bg-white rounded-[3rem] border border-slate-100 shadow-xl shadow-slate-200/40 p-10 space-y-10"
    >

        <input type="hidden" name="asignacion" value="<?= $asignacion ?>">
        <input type="hidden" name="semana" value="<?= $semana ?>">

        <section>

            <h2 class="text-xl font-black uppercase tracking-tight text-slate-800 mb-6">
                Información del Tema
            </h2>

            <div class="space-y-6">

                <div>
                    <label class="block mb-3 text-[11px] font-black uppercase tracking-[0.2em] text-slate-400">
                        Objetivo
                    </label>

                    <textarea
                        name="objetivo"
                        rows="4"
                        class="w-full p-5 rounded-3xl border border-slate-200 bg-slate-50 focus:bg-white focus:ring-4 focus:ring-indigo-100 focus:border-indigo-500 outline-none transition-all resize-none font-medium text-slate-700"
                    ><?= htmlspecialchars($data['objetivo'] ?? '') ?></textarea>
                </div>

                <div>
                    <label class="block mb-3 text-[11px] font-black uppercase tracking-[0.2em] text-slate-400">
                        Descripción del Tema
                    </label>

                    <textarea
                        name="descripcion_tema"
                        rows="4"
                        class="w-full p-5 rounded-3xl border border-slate-200 bg-slate-50 focus:bg-white focus:ring-4 focus:ring-purple-100 focus:border-purple-500 outline-none transition-all resize-none font-medium text-slate-700"
                    ><?= htmlspecialchars($data['descripcion_tema'] ?? '') ?></textarea>
                </div>

                <div>
                    <label class="block mb-3 text-[11px] font-black uppercase tracking-[0.2em] text-slate-400">
                        Material de Apoyo URL
                    </label>

                    <input
                        type="text"
                        name="material_url"
                        value="<?= htmlspecialchars($data['material_url'] ?? '') ?>"
                        placeholder="https://..."
                        class="w-full p-5 rounded-3xl border border-slate-200 bg-slate-50 focus:bg-white focus:ring-4 focus:ring-blue-100 focus:border-blue-500 outline-none transition-all font-medium text-slate-700"
                    >
                </div>

            </div>

        </section>

        <section>

            <h2 class="text-xl font-black uppercase tracking-tight text-slate-800 mb-6">
                Actividad de la Semana
            </h2>

            <div class="space-y-6">

                <div>
                    <label class="block mb-3 text-[11px] font-black uppercase tracking-[0.2em] text-slate-400">
                        Nombre de la Actividad
                    </label>

                    <input
                        type="text"
                        name="actividad_nombre"
                        value="<?= htmlspecialchars($data['actividad_nombre'] ?? '') ?>"
                        class="w-full p-5 rounded-3xl border border-slate-200 bg-slate-50 focus:bg-white focus:ring-4 focus:ring-emerald-100 focus:border-emerald-500 outline-none transition-all font-medium text-slate-700"
                    >
                </div>

                <div>
                    <label class="block mb-3 text-[11px] font-black uppercase tracking-[0.2em] text-slate-400">
                        Descripción de la Actividad
                    </label>

                    <textarea
                        name="actividad_descripcion"
                        rows="4"
                        class="w-full p-5 rounded-3xl border border-slate-200 bg-slate-50 focus:bg-white focus:ring-4 focus:ring-emerald-100 focus:border-emerald-500 outline-none transition-all resize-none font-medium text-slate-700"
                    ><?= htmlspecialchars($data['actividad_descripcion'] ?? '') ?></textarea>
                </div>

                <div>
                    <label class="block mb-3 text-[11px] font-black uppercase tracking-[0.2em] text-slate-400">
                        Rúbrica PDF
                    </label>

                    <input
                        type="file"
                        name="rubrica"
                        accept="application/pdf"
                        class="w-full p-5 rounded-3xl border border-slate-200 bg-slate-50 text-sm font-bold text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-xs file:font-black file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100"
                    >

                    <?php if(!empty($data['rubrica'])): ?>
                        <a
                            href="/sistema_academico/<?= htmlspecialchars($data['rubrica']) ?>"
                            target="_blank"
                            class="inline-block mt-3 text-[10px] font-black uppercase tracking-widest text-indigo-600 hover:underline"
                        >
                            Ver rúbrica actual
                        </a>
                    <?php endif; ?>

                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

                    <div>
                        <label class="block mb-3 text-[11px] font-black uppercase tracking-[0.2em] text-slate-400">
                            Fecha de Apertura
                        </label>

                        <input
                            type="date"
                            name="fecha_apertura"
                            value="<?= htmlspecialchars($data['fecha_apertura'] ?? '') ?>"
                            class="w-full p-5 rounded-3xl border border-slate-200 bg-slate-50 focus:bg-white focus:ring-4 focus:ring-indigo-100 focus:border-indigo-500 outline-none transition-all font-bold text-slate-700"
                        >
                    </div>

                    <div>
                        <label class="block mb-3 text-[11px] font-black uppercase tracking-[0.2em] text-slate-400">
                            Fecha de Cierre
                        </label>

                        <input
                            type="date"
                            name="fecha_cierre"
                            value="<?= htmlspecialchars($data['fecha_cierre'] ?? '') ?>"
                            class="w-full p-5 rounded-3xl border border-slate-200 bg-slate-50 focus:bg-white focus:ring-4 focus:ring-red-100 focus:border-red-500 outline-none transition-all font-bold text-slate-700"
                        >
                    </div>

                </div>

            </div>

        </section>

        <div class="flex flex-col md:flex-row gap-4 pt-6">

            <button
                type="submit"
                class="flex-1 bg-indigo-600 hover:bg-indigo-700 text-white py-5 rounded-3xl font-black uppercase tracking-widest text-sm transition-all shadow-xl shadow-indigo-200 hover:scale-[1.01]"
            >
                Guardar Planeación
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