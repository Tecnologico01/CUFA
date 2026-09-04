<?php
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/permisos.php';

validarPermiso('ver_mallas');

$carrera_id = isset($_GET['carrera_id']) ? (int) $_GET['carrera_id'] : 0;

$carreras = $pdo->query("
    SELECT id, nombre
    FROM carreras
    ORDER BY nombre ASC
")->fetchAll(PDO::FETCH_ASSOC);

$materias = [];
$grados = [];
$carrera_nombre = '';

if($carrera_id > 0){

    $stmtCarrera = $pdo->prepare("
        SELECT nombre
        FROM carreras
        WHERE id = ?
        LIMIT 1
    ");
    $stmtCarrera->execute([$carrera_id]);
    $carrera_nombre = $stmtCarrera->fetchColumn();

    if(!$carrera_nombre){
        $carrera_id = 0;
    }
}

if($carrera_id > 0){

    $stmt = $pdo->prepare("
        SELECT
            m.id,
            m.grado,
            m.clave,
            m.nombre,
            m.nombre_corto,
            m.creditos,
            m.tipo,
            m.seriacion_id,
            m.es_opcional,
            m.area_formacion,
            s.nombre AS seriacion_nombre,
            s.clave AS seriacion_clave
        FROM materias m
        LEFT JOIN materias s
            ON s.id = m.seriacion_id
        WHERE m.carrera_id = ?
        ORDER BY
            m.grado ASC,
            m.nombre ASC
    ");

    $stmt->execute([$carrera_id]);
    $materias = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach($materias as $m){
        $grado = (int)$m['grado'];

        if(!isset($grados[$grado])){
            $grados[$grado] = [];
        }

        $grados[$grado][] = $m;
    }
}

$totalGrados = 10;
?>

<div class="max-w-[98%] mx-auto p-6 bg-slate-50 min-h-screen animate-fade-in">

    <div class="mb-10 flex flex-col md:flex-row justify-between items-start md:items-end gap-6 border-b border-slate-200 pb-8">
        <div>
            <h1 class="text-5xl font-black text-slate-900 tracking-tighter uppercase leading-none">
                Malla <span class="text-purple-600 italic">Curricular</span>
            </h1>

            <p class="text-slate-400 text-xs font-bold uppercase tracking-widest mt-4">
                Visualización académica por grados y seriación.
            </p>
        </div>
    </div>

    <div class="bg-white rounded-[2.5rem] shadow-xl shadow-slate-200/50 border border-slate-100 p-8 mb-10 flex flex-col md:flex-row gap-6 items-end relative overflow-hidden">

        <form method="GET" class="flex-1 w-full relative z-10">
            <input type="hidden" name="modulo" value="ver_mallas">

            <label class="block text-[10px] font-black uppercase tracking-[0.25em] text-slate-400 mb-3 ml-2">
                Seleccionar Programa Educativo
            </label>

            <select
                name="carrera_id"
                onchange="this.form.submit()"
                class="w-full bg-slate-50 hover:bg-slate-100 border-2 border-transparent focus:border-purple-600 focus:bg-white rounded-2xl p-4 font-bold text-slate-700 outline-none transition-all uppercase text-sm"
            >
                <option value="">-- Selecciona una carrera --</option>

                <?php foreach($carreras as $c): ?>
                    <option value="<?= (int)$c['id'] ?>" <?= $carrera_id === (int)$c['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($c['nombre']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </form>

        <?php if($carrera_id): ?>
            <a
                href="/sistema_academico/modules/asignaturas/exportar_malla_pdf.php?carrera_id=<?= (int)$carrera_id ?>"
                target="_blank"
                class="w-full md:w-auto bg-slate-900 text-white px-8 py-4 rounded-2xl font-black uppercase text-[10px] tracking-[0.2em] hover:bg-purple-600 transition-all flex items-center justify-center gap-3 shadow-lg"
            >
                Exportar PDF
            </a>
        <?php endif; ?>
    </div>

    <?php if($carrera_id): ?>

        <?php if(count($materias) > 0): ?>

            <div class="bg-white rounded-[3rem] shadow-2xl shadow-slate-200/60 border border-slate-100 p-8 overflow-hidden relative">

                <div class="mb-8 pl-4">
                    <h2 class="text-3xl font-black uppercase tracking-tighter text-slate-800">
                        <?= htmlspecialchars($carrera_nombre) ?>
                    </h2>

                    <p class="text-purple-600 font-bold text-xs uppercase tracking-widest mt-2">
                        <?= count($materias) ?> asignaturas registradas
                    </p>
                </div>

                <div class="overflow-x-auto scroll-x pb-8 pt-4">

                    <div class="malla-grid" id="malla-container">

                        <svg id="svg-connections"></svg>

                        <?php for($g = 1; $g <= $totalGrados; $g++): ?>

                            <div class="grado-columna">

                                <div class="grado-header">
                                    <h2>Grado <?= $g ?></h2>
                                </div>

                                <div class="p-4 space-y-4 flex-1 bg-slate-50/30">

                                    <?php if(isset($grados[$g])): ?>

                                        <?php foreach($grados[$g] as $m): ?>

                                            <div
                                                class="materia-card"
                                                id="materia-<?= (int)$m['id'] ?>"
                                                <?= !empty($m['seriacion_id']) ? 'data-requiere="'.(int)$m['seriacion_id'].'"' : '' ?>
                                            >

                                                <div class="flex items-center justify-between mb-3">
                                                    <span class="bg-purple-100 text-purple-700 text-[9px] font-black px-2.5 py-1 rounded-md uppercase tracking-wider">
                                                        <?= htmlspecialchars($m['clave']) ?>
                                                    </span>

                                                    <span class="text-[9px] font-black text-slate-400 bg-slate-100 px-2.5 py-1 rounded-md uppercase">
                                                        <?= (int)$m['creditos'] ?> CR
                                                    </span>
                                                </div>

                                                <h3 class="text-xs font-black text-slate-800 uppercase leading-tight mb-3">
                                                    <?= htmlspecialchars($m['nombre_corto'] ?: $m['nombre']) ?>
                                                </h3>

                                                <div class="flex flex-wrap gap-2">
                                                    <span class="bg-slate-100 text-slate-500 text-[8px] px-2 py-1 rounded-lg font-black uppercase tracking-wider">
                                                        <?= htmlspecialchars($m['tipo'] ?: 'Presencial') ?>
                                                    </span>

                                                    <?php if(!empty($m['area_formacion'])): ?>
                                                        <span class="bg-blue-50 text-blue-600 text-[8px] px-2 py-1 rounded-lg font-black uppercase tracking-wider">
                                                            <?= htmlspecialchars($m['area_formacion']) ?>
                                                        </span>
                                                    <?php endif; ?>

                                                    <?php if(!empty($m['es_opcional'])): ?>
                                                        <span class="bg-amber-100 text-amber-700 text-[8px] px-2 py-1 rounded-lg font-black uppercase tracking-wider">
                                                            Opcional
                                                        </span>
                                                    <?php endif; ?>
                                                </div>

                                                <?php if(!empty($m['seriacion_id'])): ?>
                                                    <div class="bg-amber-50 border border-amber-200 rounded-xl p-3 mt-4">
                                                        <span class="block text-[8px] font-black text-amber-600 uppercase tracking-widest mb-1">
                                                            Requiere
                                                        </span>

                                                        <span class="text-[10px] font-bold text-amber-900 uppercase leading-tight block">
                                                            <?= htmlspecialchars(($m['seriacion_clave'] ?: '') . ' ' . ($m['seriacion_nombre'] ?: '')) ?>
                                                        </span>
                                                    </div>
                                                <?php endif; ?>

                                            </div>

                                        <?php endforeach; ?>

                                    <?php else: ?>

                                        <div class="h-full flex items-center justify-center py-10">
                                            <span class="text-[9px] font-black text-slate-300 uppercase tracking-[0.2em] text-center px-4">
                                                Sin asignaturas
                                            </span>
                                        </div>

                                    <?php endif; ?>

                                </div>
                            </div>

                        <?php endfor; ?>

                    </div>
                </div>
            </div>

        <?php else: ?>

            <div class="bg-white p-20 rounded-[3rem] shadow-xl border border-slate-100 text-center max-w-2xl mx-auto">
                <h2 class="text-2xl font-black text-slate-900 uppercase tracking-tight mb-2">
                    Malla curricular vacía
                </h2>

                <p class="text-slate-400 font-bold text-sm">
                    Esta carrera aún no tiene asignaturas registradas.
                </p>
            </div>

        <?php endif; ?>

    <?php else: ?>

        <div class="bg-white p-20 rounded-[3rem] shadow-xl border border-slate-100 text-center max-w-2xl mx-auto border-dashed border-2">
            <h2 class="text-2xl font-black text-slate-900 uppercase tracking-tight mb-2">
                Esperando selección
            </h2>

            <p class="text-slate-400 font-bold text-sm">
                Selecciona una carrera para visualizar la malla curricular.
            </p>
        </div>

    <?php endif; ?>

</div>

<script>
document.addEventListener('DOMContentLoaded', () => {

    const svg = document.getElementById('svg-connections');
    const container = document.getElementById('malla-container');

    if(!svg || !container) return;

    function drawSeriacionLines(){

        svg.innerHTML = `
            <defs>
                <marker id="arrowhead" markerWidth="10" markerHeight="8" refX="8" refY="4" orient="auto">
                    <polygon points="0 0, 10 4, 0 8" fill="#a855f7"></polygon>
                </marker>
            </defs>
        `;

        const width = container.scrollWidth;
        const height = container.scrollHeight;

        svg.setAttribute('viewBox', `0 0 ${width} ${height}`);
        svg.setAttribute('width', width);
        svg.setAttribute('height', height);

        const cards = document.querySelectorAll('.materia-card[data-requiere]');
        const containerRect = container.getBoundingClientRect();

        cards.forEach(card => {

            const reqId = card.dataset.requiere;
            const origen = document.getElementById(`materia-${reqId}`);

            if(!origen) return;

            const origenRect = origen.getBoundingClientRect();
            const destinoRect = card.getBoundingClientRect();

            const startX = origenRect.right - containerRect.left + container.scrollLeft;
            const startY = origenRect.top - containerRect.top + origenRect.height / 2 + container.scrollTop;

            const endX = destinoRect.left - containerRect.left + container.scrollLeft;
            const endY = destinoRect.top - containerRect.top + destinoRect.height / 2 + container.scrollTop;

            const distanceX = Math.abs(endX - startX);
            const cp1X = startX + distanceX * 0.45;
            const cp2X = endX - distanceX * 0.45;

            const path = document.createElementNS('http://www.w3.org/2000/svg', 'path');

            path.setAttribute('d', `M ${startX} ${startY} C ${cp1X} ${startY}, ${cp2X} ${endY}, ${endX} ${endY}`);
            path.setAttribute('fill', 'none');
            path.setAttribute('stroke', '#a855f7');
            path.setAttribute('stroke-width', '2.5');
            path.setAttribute('stroke-linecap', 'round');
            path.setAttribute('stroke-dasharray', '7 7');
            path.setAttribute('marker-end', 'url(#arrowhead)');

            svg.appendChild(path);
        });
    }

    drawSeriacionLines();

    window.addEventListener('resize', () => {
        setTimeout(drawSeriacionLines, 150);
    });

    if(document.fonts && document.fonts.ready){
        document.fonts.ready.then(drawSeriacionLines);
    }
});
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

.scroll-x::-webkit-scrollbar {
    height: 12px;
}

.scroll-x::-webkit-scrollbar-track {
    background: #f1f5f9;
    border-radius: 999px;
}

.scroll-x::-webkit-scrollbar-thumb {
    background: #cbd5e1;
    border-radius: 999px;
    border: 3px solid #f1f5f9;
}

.malla-grid {
    display: grid;
    grid-template-columns: repeat(10, minmax(260px, 1fr));
    gap: 20px;
    min-width: max-content;
    padding-bottom: 20px;
    position: relative;
}

.grado-columna {
    background: #ffffff;
    border-radius: 2rem;
    border: 1px dashed #cbd5e1;
    box-shadow: 0 10px 30px rgba(226, 232, 240, 0.5);
    display: flex;
    flex-direction: column;
}

.grado-header {
    background: #0f172a;
    padding: 20px;
    border-radius: 2rem 2rem 0 0;
    text-align: center;
}

.grado-header h2 {
    color: white;
    font-size: 11px;
    font-weight: 900;
    text-transform: uppercase;
    letter-spacing: 0.25em;
}

.materia-card {
    background: #ffffff;
    border: 1px solid #f1f5f9;
    border-left: 4px solid #a855f7;
    border-radius: 1.25rem;
    padding: 18px;
    transition: all 0.3s ease;
    position: relative;
    z-index: 20;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
}

.materia-card:hover {
    transform: translateY(-4px) scale(1.02);
    box-shadow: 0 20px 25px -5px rgba(147, 51, 234, 0.15);
}

#svg-connections {
    position: absolute;
    top: 0;
    left: 0;
    overflow: visible;
    pointer-events: none;
    z-index: 10;
}
</style>