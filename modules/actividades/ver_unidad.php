<?php
require_once __DIR__ . '/../../includes/db.php';

$asignacion_id = $_GET['asignacion_id'] ?? null;
$unidad = $_GET['unidad'] ?? null;

if (!$asignacion_id || !$unidad) {
    echo "Datos incompletos";
    exit;
}

/* LÓGICA DE TOGGLES (Sin cambios en la funcionalidad) */
if(isset($_GET['toggle_act'])){
    $id = $_GET['toggle_act'];
    $stmt = $pdo->prepare("UPDATE actividades SET activo = NOT activo WHERE id = ?");
    $stmt->execute([$id]);
    header("Location: docente_dashboard.php?modulo=ver_unidad&asignacion_id=$asignacion_id&unidad=$unidad");
    exit;
}

if(isset($_GET['toggle_exam'])){
    $stmt = $pdo->prepare("UPDATE examenes SET activo = NOT activo WHERE asignacion_id=? AND parcial=?");
    $stmt->execute([$asignacion_id,$unidad]);
    header("Location: docente_dashboard.php?modulo=ver_unidad&asignacion_id=$asignacion_id&unidad=$unidad");
    exit;
}

/*
|--------------------------------------------------------------------------
| GUARDAR CALIFICACIÓN
|--------------------------------------------------------------------------
*/

if(
    $_SERVER['REQUEST_METHOD'] === 'POST'
    && isset($_POST['guardar_calificacion'])
){

    $entrega_id = $_POST['entrega_id'];
    $calificacion = $_POST['calificacion'];
    $retroalimentacion = $_POST['retroalimentacion'];

    $stmtCalificar = $pdo->prepare("
        UPDATE entregas_actividades
        SET
            calificacion = ?,
            retroalimentacion = ?
        WHERE id = ?
    ");

    $stmtCalificar->execute([
        $calificacion,
        $retroalimentacion,
        $entrega_id
    ]);

    header("Location: docente_dashboard.php?modulo=ver_unidad&asignacion_id=$asignacion_id&unidad=$unidad");

    exit;
}

/* CARGA DE DATOS */
$stmt = $pdo->prepare("
    SELECT tm.id as tema_id, tm.orden, tm.tema, a.id as act_id, a.descripcion, a.activo, a.nombre as act_nombre
    FROM temas_materia tm
    LEFT JOIN actividades a ON a.tema_id = tm.id
    WHERE tm.asignacion_id = ? AND tm.parcial = ?
    ORDER BY tm.orden ASC
");
$stmt->execute([$asignacion_id, $unidad]);
$sesiones = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmtEx = $pdo->prepare("SELECT * FROM examenes WHERE asignacion_id=? AND parcial=?");
$stmtEx->execute([$asignacion_id,$unidad]);
$examen = $stmtEx->fetch();

$preguntas = [];
if($examen){
    $stmtP = $pdo->prepare("SELECT * FROM preguntas_examen WHERE examen_id=?");
    $stmtP->execute([$examen['id']]);
    $preguntas = $stmtP->fetchAll(PDO::FETCH_ASSOC);
}
?>

<div class="max-w-6xl mx-auto p-6 animate-fade-in font-sans">

    <!-- HEADER -->

    <div class="mb-10 flex items-center justify-between">

        <div>

            <div class="flex items-center gap-3 mb-2">

                <span class="bg-slate-900 text-white text-[10px] font-black px-3 py-1 rounded-full uppercase tracking-widest">
                    Docente
                </span>

                <span class="text-slate-300 text-[10px] font-bold">
                    /
                </span>

                <span class="text-slate-500 text-[10px] font-black uppercase tracking-widest">
                    Gestión de Unidad
                </span>

            </div>

            <h1 class="text-4xl font-black text-slate-900 tracking-tighter uppercase">

                Panel de Control:

                <span class="text-purple-600 italic text-5xl">
                    Unidad <?= htmlspecialchars($unidad) ?>
                </span>

            </h1>

        </div>

        <a href="docente_dashboard.php"
           class="p-3 bg-white border border-slate-200 rounded-2xl hover:bg-slate-50 transition-all shadow-sm">

            <svg class="w-6 h-6 text-slate-400"
                 fill="none"
                 stroke="currentColor"
                 viewBox="0 0 24 24">

                <path stroke-linecap="round"
                      stroke-linejoin="round"
                      stroke-width="2"
                      d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>

            </svg>

        </a>

    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">

        <!-- IZQUIERDA -->

        <div class="lg:col-span-2 space-y-8">

            <h3 class="text-[11px] font-black text-slate-400 uppercase tracking-[0.3em] mb-4 flex items-center gap-2">

                <span class="w-8 h-[1px] bg-slate-200"></span>

                Secuencia Didáctica

            </h3>

            <?php foreach($sesiones as $s): ?>

                <div class="bg-white rounded-[2.5rem] border border-slate-200 overflow-hidden shadow-sm hover:shadow-md transition-all">

                    <div class="p-8">

                        <!-- HEADER SESIÓN -->

                        <div class="flex justify-between items-start mb-6">

                            <div class="flex gap-4">

                                <span class="text-4xl font-black text-slate-100 italic select-none">

                                    <?= str_pad($s['orden'], 2, '0', STR_PAD_LEFT) ?>

                                </span>

                                <div>

                                    <h2 class="font-black text-xl text-slate-800 leading-tight">

                                        <?= htmlspecialchars($s['tema']) ?>

                                    </h2>

                                    <p class="text-xs font-bold text-purple-500 uppercase tracking-tighter mt-1">

                                        <?= htmlspecialchars($s['act_nombre'] ?? 'Sin actividad') ?>

                                    </p>

                                </div>

                            </div>

                            <?php if(!empty($s['act_id'])): ?>

                                <a href="?modulo=ver_unidad&asignacion_id=<?= $asignacion_id ?>&unidad=<?= $unidad ?>&toggle_act=<?= $s['act_id'] ?>"
                                   class="flex items-center gap-2 px-4 py-2 rounded-xl text-[10px] font-black uppercase tracking-widest transition-all
                                   <?= $s['activo']
                                        ? 'bg-emerald-50 text-emerald-600 border border-emerald-100'
                                        : 'bg-slate-100 text-slate-400 border border-slate-200' ?>">

                                    <span class="w-2 h-2 rounded-full <?= $s['activo']
                                        ? 'bg-emerald-500 animate-pulse'
                                        : 'bg-slate-300' ?>"></span>

                                    <?= $s['activo'] ? 'Visible' : 'Oculto' ?>

                                </a>

                            <?php endif; ?>

                        </div>

                        <!-- ACTIVIDAD -->

                        <?php if(!empty($s['act_id'])): ?>

                            <div class="bg-slate-50 rounded-3xl p-6 border border-slate-100">

                                <div class="flex items-center justify-between mb-6">

                                    <h4 class="text-[10px] font-black text-slate-400 uppercase tracking-widest">

                                        Portafolio de Evidencias

                                    </h4>

                                    <span class="text-[10px] font-bold text-slate-400 italic">

                                        Entregas Recientes

                                    </span>

                                </div>

                                <?php

                                $stmtEnt = $pdo->prepare("

                                    SELECT
                                        ea.*,

                                        u.nombres,
                                        u.apellido_paterno,

                                        a.numero_control

                                    FROM entregas_actividades ea

                                    INNER JOIN alumnos a
                                        ON ea.alumno_id = a.id

                                    INNER JOIN usuarios u
                                        ON a.usuario_id = u.id

                                    WHERE ea.actividad_id = ?

                                    ORDER BY ea.fecha_entrega DESC

                                ");

                                $stmtEnt->execute([$s['act_id']]);

                                $entregas = $stmtEnt->fetchAll(PDO::FETCH_ASSOC);

                                ?>

                                <div class="space-y-4">

                                    <?php if($entregas): ?>

                                        <?php foreach($entregas as $e): ?>

                                            <div class="bg-white p-5 rounded-3xl border border-slate-100 flex flex-col lg:flex-row lg:items-center justify-between gap-6">

                                                <!-- DATOS -->

                                                <div class="flex items-center gap-4">

                                                    <div class="w-12 h-12 rounded-2xl bg-purple-100 text-purple-600 flex items-center justify-center font-black">

                                                        <?= strtoupper(substr($e['nombres'], 0, 1)) ?>

                                                    </div>

                                                    <div>

                                                        <p class="text-sm font-black text-slate-800">

                                                            <?= htmlspecialchars(
                                                                $e['numero_control']
                                                                . ' - ' .
                                                                $e['nombres']
                                                                . ' ' .
                                                                $e['apellido_paterno']
                                                            ) ?>

                                                        </p>

                                                        <p class="text-[10px] uppercase tracking-[0.2em] text-slate-400 font-bold mt-1">

                                                            <?= date('d/m/Y H:i', strtotime($e['fecha_entrega'])) ?>

                                                        </p>

                                                    </div>

                                                </div>

                                                <!-- ACCIONES -->

                                                <div class="flex flex-col lg:flex-row items-start lg:items-center gap-4">

                                                    <!-- VER ARCHIVO -->

                                                    <?php if(!empty($e['archivo'])): ?>

                                                        <a href="/sistema_academico/<?= htmlspecialchars($e['archivo']) ?>"
                                                           target="_blank"
                                                           class="inline-flex items-center gap-3 bg-indigo-50 hover:bg-indigo-100 text-indigo-700 px-5 py-3 rounded-2xl font-black transition-all">

                                                            <svg class="w-5 h-5"
                                                                 fill="none"
                                                                 stroke="currentColor"
                                                                 viewBox="0 0 24 24">

                                                                <path stroke-linecap="round"
                                                                      stroke-linejoin="round"
                                                                      stroke-width="2"
                                                                      d="M12 16v-8m0 0l-3 3m3-3l3 3m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>

                                                            </svg>

                                                            Ver Evidencia

                                                        </a>

                                                    <?php endif; ?>

                                                    <!-- CALIFICACIÓN -->

                                                    <div class="min-w-[220px]">

                                                        <?php if($e['calificacion'] !== null): ?>

                                                            <div class="bg-emerald-50 border border-emerald-100 rounded-2xl p-4">

                                                                <p class="text-[10px] uppercase tracking-[0.2em] text-emerald-500 font-black mb-2">

                                                                    Calificación

                                                                </p>

                                                                <h3 class="text-3xl font-black text-emerald-600">

                                                                    <?= $e['calificacion'] ?>

                                                                </h3>

                                                                <?php if(!empty($e['retroalimentacion'])): ?>

                                                                    <p class="text-xs text-slate-500 mt-3 leading-relaxed">

                                                                        <?= htmlspecialchars($e['retroalimentacion']) ?>

                                                                    </p>

                                                                <?php endif; ?>

                                                            </div>

                                                        <?php else: ?>

                                                            <form method="POST"
                                                                  class="bg-slate-50 border border-slate-200 rounded-2xl p-4 space-y-3">

                                                                <input type="hidden"
                                                                       name="entrega_id"
                                                                       value="<?= $e['id'] ?>">

                                                                <input type="number"
                                                                       name="calificacion"
                                                                       min="0"
                                                                       max="100"
                                                                       step="0.1"
                                                                       required
                                                                       placeholder="Calificación"
                                                                       class="w-full border border-slate-200 rounded-xl px-4 py-3 text-sm">

                                                                <textarea
                                                                    name="retroalimentacion"
                                                                    rows="3"
                                                                    placeholder="Retroalimentación..."
                                                                    class="w-full border border-slate-200 rounded-xl px-4 py-3 text-sm resize-none"
                                                                ></textarea>

                                                                <button
                                                                    type="submit"
                                                                    name="guardar_calificacion"
                                                                    class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-black py-3 rounded-xl transition-all">

                                                                    Guardar Calificación

                                                                </button>

                                                            </form>

                                                        <?php endif; ?>

                                                    </div>

                                                </div>

                                            </div>

                                        <?php endforeach; ?>

                                    <?php else: ?>

                                        <div class="py-10 text-center">

                                            <p class="text-[10px] font-black text-slate-300 uppercase tracking-[0.3em]">

                                                Sin entregas registradas

                                            </p>

                                        </div>

                                    <?php endif; ?>

                                </div>

                            </div>

                        <?php else: ?>

                            <div class="py-8 border-2 border-dashed border-slate-100 rounded-3xl text-center">

                                <p class="text-xs font-bold text-slate-300 uppercase tracking-widest italic">

                                    Sesión Teórica / Sin Evidencia

                                </p>

                            </div>

                        <?php endif; ?>

                    </div>

                </div>

            <?php endforeach; ?>

        </div>

        <!-- DERECHA -->

        <div class="space-y-8">

            <h3 class="text-[11px] font-black text-slate-400 uppercase tracking-[0.3em] mb-4 flex items-center gap-2">

                <span class="w-8 h-[1px] bg-slate-200"></span>

                Evaluación Final

            </h3>

            <div class="bg-slate-900 rounded-[3rem] p-8 text-white shadow-2xl relative overflow-hidden">

                <div class="absolute -right-10 -top-10 w-40 h-40 bg-purple-600/20 rounded-full blur-3xl"></div>

                <div class="relative z-10">

                    <div class="flex justify-between items-center mb-8">

                        <span class="bg-emerald-500 text-slate-900 text-[9px] font-black px-3 py-1 rounded-full uppercase tracking-[0.2em]">

                            Examen Unidad

                        </span>

                        <?php if($examen): ?>

                            <a href="?modulo=ver_unidad&asignacion_id=<?= $asignacion_id ?>&unidad=<?= $unidad ?>&toggle_exam=1"
                               class="p-2 rounded-xl transition-colors
                               <?= $examen['activo']
                                    ? 'text-emerald-400 hover:bg-emerald-400/10'
                                    : 'text-slate-500 hover:bg-slate-500/10' ?>">

                                <svg class="w-6 h-6"
                                     fill="none"
                                     stroke="currentColor"
                                     viewBox="0 0 24 24">

                                    <path stroke-linecap="round"
                                          stroke-linejoin="round"
                                          stroke-width="2"
                                          d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>

                                    <path stroke-linecap="round"
                                          stroke-linejoin="round"
                                          stroke-width="2"
                                          d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>

                                </svg>

                            </a>

                        <?php endif; ?>

                    </div>

                    <?php if($preguntas): ?>

                        <div class="space-y-4">

                            <p class="text-[10px] font-black text-slate-500 uppercase tracking-widest">

                                Reactivos del examen

                            </p>

                            <div class="max-h-[400px] overflow-y-auto pr-2 custom-scrollbar space-y-3">

                                <?php foreach($preguntas as $idx => $p): ?>

                                    <div class="bg-white/5 border border-white/10 p-4 rounded-2xl">

                                        <p class="text-xs font-bold leading-relaxed">

                                            <span class="text-purple-400 mr-2">

                                                <?= $idx + 1 ?>.

                                            </span>

                                            <?= htmlspecialchars($p['pregunta']) ?>

                                        </p>

                                    </div>

                                <?php endforeach; ?>

                            </div>

                        </div>

                    <?php else: ?>

                        <div class="py-12 text-center opacity-50">

                            <p class="text-[10px] font-black uppercase tracking-[0.3em] leading-loose">

                                No hay preguntas configuradas

                            </p>

                        </div>

                    <?php endif; ?>

                </div>

            </div>

        </div>

    </div>

</div>

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

    animation: slide-up 0.6s cubic-bezier(0.2, 0.8, 0.2, 1);

}

.custom-scrollbar::-webkit-scrollbar {

    width: 4px;

}

.custom-scrollbar::-webkit-scrollbar-track {

    background: transparent;

}

.custom-scrollbar::-webkit-scrollbar-thumb {

    background: rgba(255,255,255,0.1);
    border-radius: 10px;

}

</style>