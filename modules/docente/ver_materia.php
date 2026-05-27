<?php

require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/permisos.php';

validarPermiso('ver_materia');

/*
|--------------------------------------------------------------------------
| VALIDAR ASIGNACIÓN
|--------------------------------------------------------------------------
*/

$asignacion_id = $_GET['asignacion_id'] ?? $_POST['asignacion_id'] ?? null;
$asignacion_id = $asignacion_id ? (int) $asignacion_id : 0;

if($asignacion_id <= 0){

    echo "
    <div class='max-w-2xl mx-auto mt-10 animate-fade-in'>
        <div class='bg-white rounded-[2.5rem] p-10 border-2 border-red-100 shadow-xl shadow-red-50'>
            <div class='flex items-center gap-4 mb-4'>
                <div class='bg-red-50 p-4 rounded-3xl'>
                    <svg class='w-8 h-8 text-red-600' fill='none' stroke='currentColor' viewBox='0 0 24 24'>
                        <path stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z'></path>
                    </svg>
                </div>

                <h2 class='text-2xl font-black text-slate-900 uppercase tracking-tighter italic'>
                    Error de Parámetro
                </h2>
            </div>

            <p class='text-slate-500 font-bold leading-relaxed'>
                El sistema no logró identificar la asignatura seleccionada.
            </p>

            <div class='mt-6 p-4 bg-slate-50 rounded-2xl border border-slate-100'>
                <p class='text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] mb-1'>
                    Referencia del Sistema
                </p>

                <code class='text-xs text-slate-400 break-all font-mono'>
                    " . htmlspecialchars($_SERVER['REQUEST_URI']) . "
                </code>
            </div>

            <a href='?modulo=mis_materias' class='mt-8 inline-flex items-center gap-3 text-white bg-slate-900 px-8 py-4 rounded-2xl font-black uppercase text-[10px] tracking-[0.2em] hover:bg-purple-600 transition-all shadow-lg shadow-purple-100'>
                Volver a Mis Materias
            </a>
        </div>
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

    echo "
    <div class='p-8 bg-red-50 border border-red-200 rounded-3xl text-red-700 font-black uppercase text-[10px] tracking-widest italic'>
        Acceso denegado: Usuario no identificado.
    </div>
    ";

    exit;
}

$stmt = $pdo->prepare("
    SELECT id
    FROM docentes
    WHERE usuario_id = ?
");

$stmt->execute([$usuario_id]);

$docente = $stmt->fetch(PDO::FETCH_ASSOC);

if(!$docente){

    echo "
    <div class='p-8 bg-amber-50 border border-amber-200 rounded-3xl text-amber-700 font-black uppercase text-[10px] tracking-widest italic'>
        Error de perfil: Docente no vinculado.
    </div>
    ";

    exit;
}

$docente_id = (int) $docente['id'];

/*
|--------------------------------------------------------------------------
| OBTENER MATERIA Y GRUPO
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare("
    SELECT
        ad.id AS asignacion_id,
        ad.docente_id,
        m.id AS materia_id,
        m.nombre AS materia,
        m.clave,
        m.tipo,
        m.total_unidades,
        g.id AS grupo_id,
        g.nombre AS grupo,
        p.id AS periodo_id,
        p.nombre AS periodo
    FROM asignaciones_docentes ad
    INNER JOIN materias m
        ON m.id = ad.materia_id
    INNER JOIN grupos g
        ON g.id = ad.grupo_id
    INNER JOIN periodos p
        ON p.id = ad.periodo_id
    WHERE ad.id = ?
    AND ad.docente_id = ?
");

$stmt->execute([
    $asignacion_id,
    $docente_id
]);

$info = $stmt->fetch(PDO::FETCH_ASSOC);

if(!$info){

    echo "
    <div class='max-w-2xl mx-auto mt-10'>
        <div class='bg-white rounded-[2.5rem] p-10 border-2 border-red-100 shadow-xl shadow-red-50'>
            <h2 class='text-2xl font-black text-red-600 uppercase italic mb-3'>
                Acceso no autorizado
            </h2>

            <p class='text-slate-500 font-bold leading-relaxed'>
                Esta asignatura no está vinculada a tu cuenta docente.
            </p>

            <a href='?modulo=mis_materias' class='mt-8 inline-flex items-center gap-3 text-white bg-slate-900 px-8 py-4 rounded-2xl font-black uppercase text-[10px] tracking-[0.2em] hover:bg-purple-600 transition-all'>
                Volver a Mis Materias
            </a>
        </div>
    </div>
    ";

    exit;
}

?>

<div class="max-w-6xl mx-auto p-6 animate-fade-in font-sans text-slate-900">

    <div class="mb-12">

        <div class="flex items-center gap-3 mb-4">

            <span class="bg-purple-600 text-white text-[10px] font-black px-3 py-1 rounded-full uppercase tracking-widest">
                Docente
            </span>

            <span class="text-slate-300 text-[10px] font-bold">
                /
            </span>

            <span class="text-slate-500 text-[10px] font-black uppercase tracking-widest italic">
                Gestión Académica
            </span>

        </div>

        <div class="flex flex-col md:flex-row md:items-end justify-between gap-6">

            <div>

                <h1 class="text-4xl font-black tracking-tighter uppercase leading-none mb-2">
                    <?= htmlspecialchars($info['materia']) ?>
                </h1>

                <div class="flex flex-wrap items-center gap-3">

                    <div class="w-12 h-[3px] bg-purple-600"></div>

                    <span class="text-slate-400 font-black uppercase text-[11px] tracking-[0.3em]">
                        Grupo:
                        <span class="text-purple-600">
                            <?= htmlspecialchars($info['grupo']) ?>
                        </span>
                    </span>

                    <span class="text-slate-300 font-black uppercase text-[11px] tracking-[0.3em]">
                        <?= htmlspecialchars($info['periodo']) ?>
                    </span>

                </div>

            </div>

            <div class="hidden md:block border-l-2 border-slate-100 pl-6">

                <span class="text-[9px] font-black text-slate-400 uppercase tracking-widest block mb-1">
                    Identificador Único
                </span>

                <span class="text-sm font-mono font-bold text-slate-300">
                    #<?= str_pad($asignacion_id, 4, '0', STR_PAD_LEFT) ?>
                </span>

            </div>

        </div>

    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-8">

        <?php if(tienePermiso('ver_mis_actividades')): ?>

            <a
                href="?modulo=mis_actividades&asignacion_id=<?= $asignacion_id ?>"
                class="group bg-white border border-slate-100 rounded-[3rem] p-10 shadow-xl shadow-slate-200/50 transition-all hover:-translate-y-2 hover:shadow-purple-200/40"
            >

                <div class="w-16 h-16 bg-purple-50 text-purple-600 rounded-[1.5rem] flex items-center justify-center mb-8 group-hover:scale-110 transition-transform">

                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                    </svg>

                </div>

                <h2 class="text-2xl font-black uppercase tracking-tighter mb-2">
                    Actividades
                </h2>

                <p class="text-slate-400 text-[10px] font-black uppercase tracking-widest leading-relaxed">
                    Control de actividades
                </p>

                <div class="mt-10 h-1 w-12 bg-slate-100 group-hover:w-full group-hover:bg-purple-600 transition-all duration-500"></div>

            </a>

        <?php endif; ?>

        <?php if(tienePermiso('ver_calificaciones') || tienePermiso('subir_calificaciones')): ?>

            <a
                href="?modulo=calificaciones&asignacion_id=<?= $asignacion_id ?>"
                class="group bg-white border border-slate-100 rounded-[3rem] p-10 shadow-xl shadow-slate-200/50 transition-all hover:-translate-y-2 hover:shadow-emerald-200/40"
            >

                <div class="w-16 h-16 bg-emerald-50 text-emerald-600 rounded-[1.5rem] flex items-center justify-center mb-8 group-hover:scale-110 transition-transform">

                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                    </svg>

                </div>

                <h2 class="text-2xl font-black uppercase tracking-tighter mb-2">
                    Calificaciones
                </h2>

                <p class="text-slate-400 text-[10px] font-black uppercase tracking-widest leading-relaxed">
                    Notas y control de promedios
                </p>

                <div class="mt-10 h-1 w-12 bg-slate-100 group-hover:w-full group-hover:bg-emerald-500 transition-all duration-500"></div>

            </a>

        <?php endif; ?>

        <?php if(tienePermiso('definir_unidades') || tienePermiso('ver_planeaciones')): ?>

            <a
                href="?modulo=definir_unidades&asignacion=<?= $asignacion_id ?>"
                class="group bg-white border border-slate-100 rounded-[3rem] p-10 shadow-xl shadow-slate-200/50 transition-all hover:-translate-y-2 hover:shadow-indigo-200/40"
            >

                <div class="w-16 h-16 bg-indigo-50 text-indigo-600 rounded-[1.5rem] flex items-center justify-center mb-8 group-hover:scale-110 transition-transform">

                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path>
                    </svg>

                </div>

                <h2 class="text-2xl font-black uppercase tracking-tighter mb-2">
                    Planeación
                </h2>

                <p class="text-slate-400 text-[10px] font-black uppercase tracking-widest leading-relaxed">
                    Planificación de contenidos
                </p>

                <div class="mt-10 h-1 w-12 bg-slate-100 group-hover:w-full group-hover:bg-indigo-600 transition-all duration-500"></div>

            </a>

        <?php endif; ?>

    </div>

</div>

<style>
@keyframes slide-up {
    from {
        opacity: 0;
        transform: translateY(30px);
    }

    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.animate-fade-in {
    animation: slide-up 0.7s cubic-bezier(0.16, 1, 0.3, 1);
}
</style>