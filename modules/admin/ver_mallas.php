<?php
require_once __DIR__ . '/../../includes/db.php';

/*
|--------------------------------------------------------------------------
| OBTENER CARRERAS
|--------------------------------------------------------------------------
*/

$stmtCarreras = $pdo->query("
    SELECT
        id,
        nombre
    FROM carreras
    ORDER BY nombre ASC
");

$carreras = $stmtCarreras->fetchAll(PDO::FETCH_ASSOC);

/*
|--------------------------------------------------------------------------
| CARRERA SELECCIONADA
|--------------------------------------------------------------------------
*/

$carrera_id = $_GET['carrera_id'] ?? null;

$materias = [];

$carrera_nombre = '';

if($carrera_id){

    /*
    |--------------------------------------------------------------------------
    | OBTENER NOMBRE CARRERA
    |--------------------------------------------------------------------------
    */

    $stmtCarrera = $pdo->prepare("
        SELECT nombre
        FROM carreras
        WHERE id = ?
    ");

    $stmtCarrera->execute([$carrera_id]);

    $carrera_nombre = $stmtCarrera->fetchColumn();

    /*
    |--------------------------------------------------------------------------
    | OBTENER MATERIAS
    |--------------------------------------------------------------------------
    */

    $stmt = $pdo->prepare("
        SELECT
            m.id,
            m.grado,
            m.clave,
            m.nombre,
            m.creditos,
            m.tipo,
            m.seriacion_id,

            s.nombre AS seriacion_nombre

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
}

/*
|--------------------------------------------------------------------------
| AGRUPAR POR GRADO
|--------------------------------------------------------------------------
*/

$grados = [];

foreach($materias as $m){

    $grado = $m['grado'];

    if(!isset($grados[$grado])){
        $grados[$grado] = [];
    }

    $grados[$grado][] = $m;
}

?>

<!DOCTYPE html>
<html lang="es">
<head>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Malla Curricular | CUFA</title>

<script src="https://cdn.tailwindcss.com"></script>

<style>
@import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800;900&display=swap');

body {
    font-family: 'Plus Jakarta Sans', sans-serif;
    background: #f8fafc;
}

.fade-up {
    animation: fadeUp 0.6s cubic-bezier(0.16, 1, 0.3, 1);
}

@keyframes fadeUp {
    from { opacity: 0; transform: translateY(20px); }
    to { opacity: 1; transform: translateY(0); }
}

/* SCROLL ESTILIZADO */
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
.scroll-x::-webkit-scrollbar-thumb:hover {
    background: #94a3b8;
}

/* GRID Y COLUMNAS */
.malla-grid {
    display: grid;
    grid-template-columns: repeat(9, minmax(260px, 1fr));
    gap: 20px;
    min-width: max-content;
    padding-bottom: 20px;
    position: relative; /* Importante para el SVG absoluto */
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
    position: relative;
    overflow: hidden;
}

.grado-header h2 {
    color: white;
    font-size: 11px;
    font-weight: 900;
    text-transform: uppercase;
    letter-spacing: 0.25em;
    position: relative;
    z-index: 10;
}

/* TARJETAS DE MATERIA */
.materia-card {
    background: #ffffff;
    border: 1px solid #f1f5f9;
    border-left: 4px solid #a855f7;
    border-radius: 1.25rem;
    padding: 18px;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    position: relative;
    z-index: 20; /* Por encima de las flechas */
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
}

.materia-card:hover {
    transform: translateY(-4px) scale(1.02);
    box-shadow: 0 20px 25px -5px rgba(147, 51, 234, 0.15);
    border-left-color: #7e22ce;
}

/* SVG CANVAS PARA FLECHAS */
#svg-connections {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    pointer-events: none; /* Permite clickear lo que está debajo */
    z-index: 10;
}

.seriacion-line {
    animation: drawLine 1s ease-out forwards;
}

@keyframes drawLine {
    from { stroke-dashoffset: 1000; }
    to { stroke-dashoffset: 0; }
}
</style>

</head>

<body class="min-h-screen p-6 md:p-10">

<div class="max-w-[98%] mx-auto fade-up">

    <div class="mb-10 flex flex-col md:flex-row justify-between items-start md:items-end gap-6 border-b border-slate-200 pb-8">
        <div>
            <div class="flex items-center gap-2 mb-3">
                <span class="w-2 h-2 bg-purple-600 rounded-full shadow-[0_0_10px_rgba(147,51,234,0.5)]"></span>
                <span class="text-[10px] font-black text-slate-400 uppercase tracking-[0.2em]">Gestión Académica</span>
            </div>
            <h1 class="text-5xl font-black text-slate-900 tracking-tighter uppercase leading-none">
                Malla <span class="text-purple-600 italic">Curricular</span>
            </h1>
            <p class="text-slate-400 text-xs font-bold uppercase tracking-widest mt-4">
                Visualización compacta y conexiones de seriación.
            </p>
        </div>
    </div>

    <div class="bg-white rounded-[2.5rem] shadow-xl shadow-slate-200/50 border border-slate-100 p-8 mb-10 flex flex-col md:flex-row gap-6 items-end relative overflow-hidden">
        
        <div class="absolute -right-10 -top-10 w-40 h-40 bg-slate-50 rounded-full opacity-50"></div>

        <form method="GET" class="flex-1 w-full relative z-10">
            <input type="hidden" name="modulo" value="ver_mallas">
            <label class="block text-[10px] font-black uppercase tracking-[0.25em] text-slate-400 mb-3 ml-2">
                Seleccionar Programa Educativo
            </label>
            <div class="relative">
                <select name="carrera_id" onchange="this.form.submit()" class="w-full bg-slate-50 hover:bg-slate-100 border-2 border-transparent focus:border-purple-600 focus:bg-white rounded-2xl p-4 font-bold text-slate-700 outline-none transition-all appearance-none cursor-pointer uppercase text-sm">
                    <option value="">-- Selecciona una carrera --</option>
                    <?php foreach($carreras as $c): ?>
                        <option value="<?= $c['id'] ?>" <?= $carrera_id == $c['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($c['nombre']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <div class="absolute inset-y-0 right-4 flex items-center pointer-events-none text-slate-400">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M19 9l-7 7-7-7"></path></svg>
                </div>
            </div>
        </form>

        <?php if($carrera_id): ?>
        <a href="/sistema_academico/modules/admin/exportar_malla_pdf.php?carrera_id=<?= $carrera_id ?>" target="_blank" class="w-full md:w-auto bg-slate-900 text-white px-8 py-4 rounded-2xl font-black uppercase text-[10px] tracking-[0.2em] hover:bg-purple-600 transition-all flex items-center justify-center gap-3 shadow-lg shadow-slate-200 active:scale-95 relative z-10">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
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
                    <p class="text-purple-600 font-bold text-xs uppercase tracking-widest mt-2 flex items-center gap-2">
                        <span class="w-1.5 h-1.5 rounded-full bg-purple-600 animate-pulse"></span>
                        Distribución por Grados
                    </p>
                </div>

                <div class="overflow-x-auto scroll-x pb-8 pt-4">
                    
                    <div class="malla-grid" id="malla-container">
                        
                        <svg id="svg-connections"></svg>

                        <?php for($g = 1; $g <= 9; $g++): ?>
                            
                            <div class="grado-columna">
                                
                                <div class="grado-header">
                                    <h2>Grado <?= $g ?></h2>
                                    <div class="absolute inset-0 bg-gradient-to-t from-slate-900 to-slate-800 opacity-50 z-0"></div>
                                </div>

                                <div class="p-4 space-y-4 flex-1 bg-slate-50/30">
                                    
                                    <?php if(isset($grados[$g])): ?>
                                        <?php foreach($grados[$g] as $m): ?>
                                            
                                            <div class="materia-card" 
                                                 id="materia-<?= $m['id'] ?>" 
                                                 <?= $m['seriacion_id'] ? 'data-requiere="'.$m['seriacion_id'].'"' : '' ?>>
                                                
                                                <div class="flex items-center justify-between mb-3">
                                                    <span class="bg-purple-100 text-purple-700 text-[9px] font-black px-2.5 py-1 rounded-md uppercase tracking-wider">
                                                        <?= htmlspecialchars($m['clave']) ?>
                                                    </span>
                                                    <span class="text-[9px] font-black text-slate-400 bg-slate-100 px-2.5 py-1 rounded-md uppercase">
                                                        <?= $m['creditos'] ?> CR
                                                    </span>
                                                </div>

                                                <h3 class="text-xs font-black text-slate-800 uppercase leading-tight mb-2">
                                                    <?= htmlspecialchars($m['nombre']) ?>
                                                </h3>

                                                <div class="text-[9px] font-bold text-slate-400 uppercase tracking-widest mb-3">
                                                    Tipo: <?= htmlspecialchars($m['tipo']) ?>
                                                </div>

                                                <?php if($m['seriacion_id']): ?>
                                                    <div class="bg-amber-50 border border-amber-200 rounded-xl p-3 mt-4">
                                                        <span class="block text-[8px] font-black text-amber-600 uppercase tracking-widest mb-1">
                                                            Asignatura Requerida
                                                        </span>
                                                        <span class="text-[10px] font-bold text-amber-900 uppercase leading-tight block">
                                                            <?= htmlspecialchars($m['seriacion_nombre']) ?>
                                                        </span>
                                                    </div>
                                                <?php endif; ?>

                                            </div>
                                            
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <div class="h-full flex items-center justify-center py-10">
                                            <span class="text-[9px] font-black text-slate-300 uppercase tracking-[0.2em] text-center px-4">
                                                Sin asignaturas asignadas
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
                <div class="w-20 h-20 bg-slate-50 rounded-full flex items-center justify-center mx-auto mb-6">
                    <svg class="w-10 h-10 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path></svg>
                </div>
                <h2 class="text-2xl font-black text-slate-900 uppercase tracking-tight mb-2">
                    Malla curricular Vacía
                </h2>
                <p class="text-slate-400 font-bold text-sm">
                    Este programa educativo aún no tiene asignaturas registradas en el sistema.
                </p>
            </div>
        <?php endif; ?>

    <?php else: ?>
        <div class="bg-white p-20 rounded-[3rem] shadow-xl border border-slate-100 text-center max-w-2xl mx-auto border-dashed border-2">
            <div class="w-20 h-20 bg-purple-50 rounded-full flex items-center justify-center mx-auto mb-6">
                <svg class="w-10 h-10 text-purple-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path></svg>
            </div>
            <h2 class="text-2xl font-black text-slate-900 uppercase tracking-tight mb-2">
                Esperando Selección
            </h2>
            <p class="text-slate-400 font-bold text-sm">
                Selecciona una carrera en la parte superior para visualizar el mapa de materias.
            </p>
        </div>
    <?php endif; ?>

    <div class="mt-16 text-center">
        <p class="text-[10px] font-black text-slate-300 uppercase tracking-[0.5em]">Sistema Académico // CUFA // Malla Curricular</p>
    </div>

</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    
    function drawSeriacionLines() {
        const svg = document.getElementById('svg-connections');
        const container = document.getElementById('malla-container');
        
        if (!svg || !container) return;
        
        // Limpiar lienzo e inyectar definición de la punta de flecha
        svg.innerHTML = `
            <defs>
                <marker id="arrowhead" markerWidth="8" markerHeight="6" refX="7" refY="3" orient="auto">
                    <polygon points="0 0, 8 3, 0 6" fill="#a855f7" />
                </marker>
            </defs>
        `;

        const materiasConSeriacion = document.querySelectorAll('.materia-card[data-requiere]');
        const containerRect = container.getBoundingClientRect();

        materiasConSeriacion.forEach(materia => {
            const reqId = materia.getAttribute('data-requiere');
            const objetivo = document.getElementById('materia-' + reqId);
            
            if (objetivo) {
                const matRect = materia.getBoundingClientRect();
                const objRect = objetivo.getBoundingClientRect();

                // Calcular coordenadas relativas al contenedor de la malla
                const startX = (objRect.right - containerRect.left);
                const startY = (objRect.top - containerRect.top) + (objRect.height / 2);
                
                const endX = (matRect.left - containerRect.left);
                const endY = (matRect.top - containerRect.top) + (matRect.height / 2);

                // Calcular puntos de control para la curva de bezier
                const distanceX = Math.abs(endX - startX);
                const cp1X = startX + (distanceX * 0.4);
                const cp2X = endX - (distanceX * 0.4);

                // Crear el elemento path (línea SVG)
                const path = document.createElementNS('http://www.w3.org/2000/svg', 'path');
                path.setAttribute('d', \`M \${startX} \${startY} C \${cp1X} \${startY}, \${cp2X} \${endY}, \${endX} \${endY}\`);
                path.setAttribute('fill', 'none');
                path.setAttribute('stroke', '#a855f7'); // Color purple-500
                path.setAttribute('stroke-width', '2.5');
                path.setAttribute('stroke-dasharray', '6,6'); // Línea punteada
                path.setAttribute('marker-end', 'url(#arrowhead)');
                path.setAttribute('class', 'seriacion-line');
                // Atributo extra para calcular la animación
                path.setAttribute('stroke-dashoffset', '1000'); 

                svg.appendChild(path);
            }
        });
    }

    // Dibujar flechas al cargar y recalcular si se redimensiona la ventana
    drawSeriacionLines();
    window.addEventListener('resize', drawSeriacionLines);
    
    // Si la fuente tarda en cargar y cambia el tamaño de las cajas, recalculamos
    document.fonts.ready.then(drawSeriacionLines);
});
</script>

</body>
</html>