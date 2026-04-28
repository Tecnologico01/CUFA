<?php
require_once __DIR__ . '/../../includes/db.php';

$asignacion_id = $_GET['asignacion_id'] ?? null;
$parcial_seleccionado = $_GET['parcial'] ?? null;
$modulo_actual = $_GET['modulo'] ?? 'ver_detalle_planeacion';

if (!$asignacion_id) {
    echo "<div class='p-6 bg-red-100 text-red-700 rounded-xl font-bold'>Error: Asignación no especificada.</div>";
    exit;
}

function aRomano($num) {
    $map = ['I','II','III','IV','V','VI','VII','VIII'];
    return $map[$num-1] ?? $num;
}

/* INFO GENERAL */
$stmtInfo = $pdo->prepare("
    SELECT u.nombres AS docente, m.nombre AS materia, g.nombre AS grupo, ad.estado_planeacion
    FROM asignaciones_docentes ad
    JOIN docentes d ON ad.docente_id = d.id
    JOIN usuarios u ON d.usuario_id = u.id
    JOIN materias m ON ad.materia_id = m.id
    JOIN grupos g ON ad.grupo_id = g.id
    WHERE ad.id = ?
");
$stmtInfo->execute([$asignacion_id]);
$info = $stmtInfo->fetch(PDO::FETCH_ASSOC);

/* PARCIALES/GRADOS */
$stmtParciales = $pdo->prepare("
    SELECT DISTINCT parcial, objetivo_unidad, nombre_unidad 
    FROM temas_materia 
    WHERE asignacion_id=? 
    ORDER BY parcial ASC
");
$stmtParciales->execute([$asignacion_id]);
$lista_parciales = $stmtParciales->fetchAll(PDO::FETCH_ASSOC);

if (!empty($lista_parciales) && !$parcial_seleccionado) {
    $parcial_seleccionado = $lista_parciales[0]['parcial'];
}

/* TEMAS + ACTIVIDADES */
$stmtTemas = $pdo->prepare("
    SELECT tm.*, 
           a.nombre AS act_nombre,
           a.descripcion AS act_desc,
           a.fecha_cierre,
           a.valor,
           a.rubrica
    FROM temas_materia tm
    LEFT JOIN actividades a ON a.tema_id = tm.id
    WHERE tm.asignacion_id=? AND tm.parcial=?
    ORDER BY tm.orden ASC
");
$stmtTemas->execute([$asignacion_id, $parcial_seleccionado]);
$temas = $stmtTemas->fetchAll(PDO::FETCH_ASSOC);

/* EXAMEN */
$stmtEx = $pdo->prepare("SELECT id FROM examenes WHERE asignacion_id=? AND parcial=? LIMIT 1");
$stmtEx->execute([$asignacion_id, $parcial_seleccionado]);
$examen_id = $stmtEx->fetchColumn();

$preguntas = [];
if ($examen_id) {
    $stmtP = $pdo->prepare("SELECT * FROM preguntas_examen WHERE examen_id=?");
    $stmtP->execute([$examen_id]);
    $preguntas = $stmtP->fetchAll(PDO::FETCH_ASSOC);
}
?>

<div class="max-w-7xl mx-auto p-6 bg-slate-50 min-h-screen animate-fade-in">

    <div class="mb-12 flex flex-col md:flex-row md:items-end justify-between gap-6 border-b border-slate-200 pb-10">
        <div>
            <h1 class="text-6xl font-black italic uppercase tracking-tighter text-slate-900 leading-none">
                Revisión <span class="text-indigo-600">Académica</span>
            </h1>
            <p class="text-slate-400 font-bold text-[11px] uppercase tracking-[0.4em] mt-3 flex items-center gap-2">
                <span class="w-8 h-[2px] bg-indigo-500"></span>
                <?= $info['materia'] ?> // GRUPO <?= $info['grupo'] ?>
            </p>
        </div>
        
        <div class="flex items-center gap-4 bg-white p-4 rounded-3xl shadow-sm border border-slate-100">
            <div class="text-right px-4">
                <span class="text-[9px] font-black text-slate-300 uppercase block tracking-widest leading-none mb-1">Estatus Actual</span>
                <span class="px-3 py-1 rounded-full text-[10px] font-black uppercase italic
                    <?= $info['estado_planeacion']=='aprobado' ? 'bg-emerald-100 text-emerald-600' : 'bg-amber-100 text-amber-600' ?>">
                    <?= $info['estado_planeacion'] ?>
                </span>
            </div>
            <div class="w-[1px] h-10 bg-slate-100"></div>
            <div class="px-4">
                <span class="text-[9px] font-black text-slate-300 uppercase block tracking-widest leading-none mb-1">Docente</span>
                <span class="text-xs font-black uppercase text-slate-700"><?= $info['docente'] ?></span>
            </div>
        </div>
    </div>

    <div class="mb-10 flex flex-wrap gap-2">
        <?php foreach ($lista_parciales as $p): ?>
            <a href="?modulo=<?= $modulo_actual ?>&asignacion_id=<?= $asignacion_id ?>&parcial=<?= $p['parcial'] ?>"
               class="px-8 py-4 rounded-[1.5rem] font-black uppercase italic text-[11px] tracking-widest transition-all
               <?= $p['parcial']==$parcial_seleccionado 
                   ? 'bg-indigo-600 text-white shadow-xl shadow-indigo-200 -translate-y-1' 
                   : 'bg-white text-slate-400 border border-slate-100 hover:bg-slate-50' ?>">
                Unidad <?= aRomano($p['parcial']) ?>
            </a>
        <?php endforeach; ?>
    </div>

    <?php 
    $unidad_info = array_filter($lista_parciales, fn($x)=>$x['parcial']==$parcial_seleccionado);
    $unidad_info = array_values($unidad_info)[0] ?? null;
    ?>
    <div class="bg-white p-12 rounded-[4rem] shadow-sm border border-slate-100 mb-12 relative overflow-hidden">
        <div class="absolute -top-6 -right-6 p-10 opacity-[0.03] pointer-events-none">
            <span class="text-[12rem] font-black italic"><?= aRomano($parcial_seleccionado) ?></span>
        </div>
        <div class="relative z-10 grid md:grid-cols-3 gap-12">
            <div class="md:col-span-1 border-r border-slate-50 pr-8">
                <label class="text-[10px] font-black text-indigo-500 uppercase tracking-[0.2em] italic mb-4 block">Nombre de la Unidad</label>
                <h2 class="text-3xl font-black italic uppercase tracking-tighter text-slate-800 leading-tight">
                    <?= htmlspecialchars($unidad_info['nombre_unidad'] ?? 'Sin definir') ?>
                </h2>
            </div>
            <div class="md:col-span-2">
                <label class="text-[10px] font-black text-indigo-500 uppercase tracking-[0.2em] italic mb-4 block">Objetivo de la Unidad</label>
                <p class="text-slate-500 font-medium text-lg leading-relaxed italic">
                    "<?= htmlspecialchars($unidad_info['objetivo_unidad'] ?? 'Objetivo no especificado por el docente.') ?>"
                </p>
            </div>
        </div>
    </div>

    <div class="space-y-12 mb-20">
        <?php foreach ($temas as $index => $t): ?>
        <div class="bg-white rounded-[3.5rem] border border-slate-100 shadow-sm overflow-hidden group">
            <div class="bg-slate-900 p-8 flex justify-between items-center group-hover:bg-indigo-950 transition-all duration-500">
                <div class="flex items-center gap-4">
                    <span class="w-12 h-12 rounded-2xl bg-indigo-600 flex items-center justify-center text-white font-black italic">
                        0<?= $index + 1 ?>
                    </span>
                    <h3 class="text-xl font-black text-white italic uppercase tracking-tight"><?= $t['tema'] ?></h3>
                </div>
                <span class="text-slate-500 font-black text-[10px] uppercase tracking-widest">Módulo Temático</span>
            </div>

            <div class="p-12">
                <div class="grid lg:grid-cols-2 gap-16">
                    <div class="space-y-8">
                        <div>
                            <span class="text-[9px] font-black text-indigo-500 uppercase tracking-widest block mb-3">Sub-temas</span>
                            <p class="text-slate-600 leading-relaxed font-medium bg-slate-50 p-6 rounded-[2rem] border border-slate-100 italic">
                                <?= nl2br($t['subtemas']) ?>
                            </p>
                        </div>

                        <div class="p-6 bg-indigo-50/30 rounded-[2rem] border border-indigo-100/50">
                            <span class="text-[9px] font-black text-indigo-600 uppercase tracking-widest block mb-2">Bibliografía Complementaria</span>
                            <p class="text-xs font-bold text-slate-700 italic">
                                📖 <?= $t['bibliografia_complementaria'] ?: 'Documentación base del programa académico.' ?>
                            </p>
                        </div>

                        <div class="grid grid-cols-2 gap-6">
                            <div class="space-y-4">
                                <span class="text-[9px] font-black text-slate-400 uppercase tracking-widest block">Metodología</span>
                                <div class="flex flex-col gap-2">
                                    <span class="text-[10px] font-black text-slate-700 uppercase bg-slate-100 px-3 py-1 rounded-lg w-fit italic"><?= $t['estrategia_didactica'] ?></span>
                                    <span class="text-[10px] font-black text-indigo-500 uppercase bg-indigo-50 px-3 py-1 rounded-lg w-fit italic"><?= $t['estilos_aprendizaje'] ?></span>
                                </div>
                            </div>
                            <div class="space-y-4">
                                <span class="text-[9px] font-black text-slate-400 uppercase tracking-widest block">Material de Apoyo</span>
                                <div class="flex flex-wrap gap-2">
                                    <?php if($t['video_referencia']): ?>
                                        <a href="<?= $t['video_referencia'] ?>" target="_blank" class="p-2 bg-slate-900 text-white rounded-xl hover:bg-indigo-600 transition-colors">🎬</a>
                                    <?php endif; ?>
                                    <?php if($t['presentacion_infografia']): ?>
                                        <a href="<?= $t['presentacion_infografia'] ?>" target="_blank" class="p-2 bg-slate-900 text-white rounded-xl hover:bg-indigo-600 transition-colors">📊</a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <?php if($t['act_nombre']): ?>
                    <div class="bg-emerald-50/50 p-10 rounded-[3rem] border-2 border-dashed border-emerald-100 relative self-start">
                        <div class="absolute -top-4 right-10 px-6 py-2 bg-emerald-500 text-white text-[10px] font-black uppercase italic rounded-full shadow-lg shadow-emerald-200">
                            Actividad
                        </div>
                        
                        <h4 class="text-2xl font-black text-emerald-900 mb-2 tracking-tighter uppercase italic"><?= $t['act_nombre'] ?></h4>
                        <p class="text-sm text-emerald-700/80 mb-8 font-medium leading-relaxed italic border-l-2 border-emerald-200 pl-4"><?= $t['act_desc'] ?></p>

                        <div class="grid grid-cols-2 gap-8 mb-8 bg-white/50 p-6 rounded-3xl">
                            <div>
                                <span class="block text-[9px] font-black text-emerald-400 uppercase mb-1">Valor de la actividad</span>
                                <span class="text-3xl font-black text-emerald-600 italic leading-none"><?= $t['valor'] ?>%</span>
                            </div>
                            <div>
                                <span class="block text-[9px] font-black text-emerald-400 uppercase mb-1">Fecha de Entrega</span>
                                <span class="text-xs font-black text-emerald-800 uppercase tracking-tighter"><?= $t['fecha_cierre'] ?></span>
                            </div>
                        </div>

                        <div>
                            <span class="text-[9px] font-black text-emerald-400 uppercase block mb-2 tracking-widest">Criterios de Evaluación</span>
                            <p class="text-[11px] text-emerald-700 font-bold bg-white/40 p-4 rounded-2xl italic border border-emerald-100"><?= $t['rubrica'] ?></p>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <?php if($preguntas): ?>
    <div class="mt-32 space-y-12">
        <div class="flex items-center gap-6 ml-8">
            <h2 class="text-4xl font-black italic uppercase tracking-tighter text-slate-900">
                Reactivos de <span class="text-indigo-600">Evaluación</span>
            </h2>
            <div class="h-[2px] flex-grow bg-slate-200"></div>
            <span class="px-6 py-2 bg-slate-900 text-white rounded-full font-black text-xs italic italic">20 TOTAL</span>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
            <?php foreach ($preguntas as $i => $p): ?>
            <div class="bg-white p-10 rounded-[3rem] border border-slate-100 shadow-sm hover:shadow-xl hover:shadow-slate-100 transition-all duration-300">
                <div class="flex gap-6 mb-8">
                    <span class="text-5xl font-black text-slate-100 italic leading-none"><?= ($i+1) ?></span>
                    <p class="text-slate-800 font-black text-sm leading-snug pt-2"><?= $p['pregunta'] ?></p>
                </div>

                <div class="space-y-2 pl-4">
                    <?php foreach(['a','b','c','d'] as $op): ?>
                    <?php $esCorrecta = strtoupper($op) == $p['respuesta_correcta']; ?>
                    <div class="flex items-center gap-4 p-3 rounded-2xl transition-all <?= $esCorrecta ? 'bg-emerald-50 border border-emerald-100 translate-x-2' : 'bg-slate-50/50' ?>">
                        <span class="text-[10px] font-black uppercase <?= $esCorrecta ? 'text-emerald-500' : 'text-slate-300' ?>"><?= $op ?></span>
                        <span class="text-xs font-bold <?= $esCorrecta ? 'text-emerald-700' : 'text-slate-500' ?>"><?= $p['opcion_'.$op] ?></span>
                        <?php if($esCorrecta): ?>
                            <span class="ml-auto text-emerald-500">✔</span>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <div class="mt-24 mb-20 bg-slate-900 rounded-[4rem] p-16 text-white shadow-2xl relative overflow-hidden border-t-[12px] border-indigo-600">
        <div class="absolute top-0 left-0 w-full h-full opacity-10 pointer-events-none" style="background-image: radial-gradient(circle at 2px 2px, white 1px, transparent 0); background-size: 40px 40px;"></div>
        
        <div class="relative z-10">
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-6 mb-12">
                <div>
                    <h3 class="text-4xl font-black italic uppercase tracking-tighter mb-2">Dictamen <span class="text-indigo-400">Técnico</span></h3>
                    <p class="text-slate-400 text-xs font-black uppercase tracking-[0.3em]">Resolución de la Coordinación Académica</p>
                </div>
                <div class="px-6 py-3 bg-white/5 rounded-2xl border border-white/10">
                    <span class="text-[10px] font-black text-indigo-300 uppercase block mb-1 italic">Expediente ID</span>
                    <span class="text-xl font-black tracking-widest italic">#<?= str_pad($asignacion_id, 5, '0', STR_PAD_LEFT) ?></span>
                </div>
            </div>

            <form action="/sistema_academico/modules/coordinador/procesar_revision_planeacion.php" method="POST">
                <input type="hidden" name="asignacion_id" value="<?= $asignacion_id ?>">

                <div class="mb-10">
                    <label class="text-[10px] font-black text-indigo-400 uppercase tracking-widest mb-4 block italic">Observaciones y Retroalimentación</label>
                    <textarea name="observaciones" required
                        class="w-full h-40 p-8 rounded-[2.5rem] bg-slate-800 border border-slate-700 text-white font-medium placeholder-slate-600 focus:outline-none focus:ring-2 focus:ring-indigo-500 transition-all text-lg italic"
                        placeholder="Escriba aquí los puntos de mejora o felicitaciones para el docente..."></textarea>
                </div>

                <div class="grid md:grid-cols-2 gap-8">
                    <button type="submit" name="accion" value="rechazado"
                        class="group bg-transparent border-2 border-red-500/30 hover:border-red-500 text-red-500 py-6 rounded-[2rem] font-black uppercase italic tracking-widest transition-all hover:bg-red-500 hover:text-white">
                        <span class="flex items-center justify-center gap-3">
                            <span class="text-2xl group-hover:rotate-12 transition-transform"></span> Rechazar Planeación
                        </span>
                    </button>

                    <button type="submit" name="accion" value="aprobado"
                        class="group bg-indigo-600 hover:bg-indigo-500 text-white py-6 rounded-[2rem] font-black uppercase italic tracking-widest transition-all shadow-xl shadow-indigo-900/40 hover:-translate-y-1">
                        <span class="flex items-center justify-center gap-3">
                            <span class="text-2xl group-hover:scale-125 transition-transform"></span> Aprobar Planeación
                        </span>
                    </button>
                </div>
            </form>
        </div>
    </div>

</div>

<style>
@keyframes slide-up { from { opacity: 0; transform: translateY(40px); } to { opacity: 1; transform: translateY(0); } }
.animate-fade-in { animation: slide-up 1s cubic-bezier(0.16, 1, 0.3, 1); }
</style>