<?php
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/permisos.php';

validarPermiso('revisar_calificaciones');

$carrera_id = isset($_GET['carrera_id']) ? (int) $_GET['carrera_id'] : 0;
$periodo_id = isset($_GET['periodo_id']) ? (int) $_GET['periodo_id'] : 0;
$grupo_id = isset($_GET['grupo_id']) ? (int) $_GET['grupo_id'] : 0;
$asignacion_id = isset($_GET['asignacion_id']) ? (int) $_GET['asignacion_id'] : 0;

/* =========================
   CATÁLOGOS
========================= */
$carreras = $pdo->query("
    SELECT id, nombre
    FROM carreras
    ORDER BY nombre ASC
")->fetchAll(PDO::FETCH_ASSOC);

$periodos = $pdo->query("
    SELECT id, nombre
    FROM periodos
    ORDER BY id DESC
")->fetchAll(PDO::FETCH_ASSOC);

$grupos = [];

if($carrera_id > 0 && $periodo_id > 0){
    $stmtGrupos = $pdo->prepare("
        SELECT id, nombre
        FROM grupos
        WHERE carrera_id = ?
        AND periodo_id = ?
        ORDER BY nombre ASC
    ");
    $stmtGrupos->execute([$carrera_id, $periodo_id]);
    $grupos = $stmtGrupos->fetchAll(PDO::FETCH_ASSOC);
}

$asignaciones = [];

if($grupo_id > 0){
    $stmtAsig = $pdo->prepare("
        SELECT 
            ad.id,
            m.nombre AS materia,
            CONCAT(u.nombres, ' ', u.apellido_paterno, ' ', u.apellido_materno) AS docente
        FROM asignaciones_docentes ad
        INNER JOIN materias m ON m.id = ad.materia_id
        INNER JOIN docentes d ON d.id = ad.docente_id
        INNER JOIN usuarios u ON u.id = d.usuario_id
        WHERE ad.grupo_id = ?
        ORDER BY m.nombre ASC
    ");
    $stmtAsig->execute([$grupo_id]);
    $asignaciones = $stmtAsig->fetchAll(PDO::FETCH_ASSOC);
}

/* =========================
   INFO ASIGNACIÓN
========================= */
$info = null;
$alumnos = [];
$unidades = [];
$calificaciones = [];

if($asignacion_id > 0){

    $stmtInfo = $pdo->prepare("
        SELECT 
            ad.id,
            ad.grupo_id,
            ad.materia_id,
            m.nombre AS materia,
            g.nombre AS grupo,
            p.nombre AS periodo,
            c.nombre AS carrera,
            CONCAT(u.nombres, ' ', u.apellido_paterno, ' ', u.apellido_materno) AS docente
        FROM asignaciones_docentes ad
        INNER JOIN materias m ON m.id = ad.materia_id
        INNER JOIN grupos g ON g.id = ad.grupo_id
        INNER JOIN periodos p ON p.id = ad.periodo_id
        INNER JOIN carreras c ON c.id = g.carrera_id
        INNER JOIN docentes d ON d.id = ad.docente_id
        INNER JOIN usuarios u ON u.id = d.usuario_id
        WHERE ad.id = ?
        LIMIT 1
    ");
    $stmtInfo->execute([$asignacion_id]);
    $info = $stmtInfo->fetch(PDO::FETCH_ASSOC);

    if($info){

        $stmtAlumnos = $pdo->prepare("
            SELECT 
                a.id AS alumno_id,
                CONCAT(
                    u.nombres, ' ',
                    COALESCE(u.apellido_paterno,''), ' ',
                    COALESCE(u.apellido_materno,'')
                ) AS alumno
            FROM grupos_alumnos ga
            INNER JOIN alumnos a ON a.id = ga.alumno_id
            INNER JOIN usuarios u ON u.id = a.usuario_id
            WHERE ga.grupo_id = ?
            ORDER BY alumno ASC
        ");
        $stmtAlumnos->execute([$info['grupo_id']]);
        $alumnos = $stmtAlumnos->fetchAll(PDO::FETCH_ASSOC);

        $stmtUnidades = $pdo->prepare("
            SELECT DISTINCT unidad
            FROM calificaciones_finales
            WHERE asignacion_id = ?
            ORDER BY unidad ASC
        ");
        $stmtUnidades->execute([$asignacion_id]);
        $unidades = $stmtUnidades->fetchAll(PDO::FETCH_COLUMN);

        if(!$unidades){
            $stmtUnidades = $pdo->prepare("
                SELECT DISTINCT parcial
                FROM temas_materia
                WHERE asignacion_id = ?
                ORDER BY parcial ASC
            ");
            $stmtUnidades->execute([$asignacion_id]);
            $unidades = $stmtUnidades->fetchAll(PDO::FETCH_COLUMN);
        }

        $stmtCalif = $pdo->prepare("
            SELECT *
            FROM calificaciones_finales
            WHERE asignacion_id = ?
        ");
        $stmtCalif->execute([$asignacion_id]);
        $raw = $stmtCalif->fetchAll(PDO::FETCH_ASSOC);

        foreach($raw as $c){
            $calificaciones[$c['alumno_id']][$c['unidad']] = $c;
        }
    }
}
?>

<div class="max-w-7xl mx-auto p-6 bg-slate-50 min-h-screen">

    <div class="mb-10">
        <span class="bg-purple-100 text-purple-700 px-4 py-2 rounded-full text-[10px] font-black uppercase tracking-[0.25em]">
            Revisión Académica
        </span>

        <h1 class="text-5xl font-black italic uppercase tracking-tighter text-slate-800 mt-5">
            Revisar Calificaciones
        </h1>

        <p class="text-slate-500 font-semibold mt-3">
            Consulta y validación de actas de calificaciones por grupo y asignatura.
        </p>
    </div>

    <form method="GET" class="bg-white p-8 rounded-[3rem] border border-slate-100 shadow-sm mb-10">
        <input type="hidden" name="modulo" value="revisar_calificaciones">

        <div class="grid grid-cols-1 md:grid-cols-4 gap-6">

            <div>
                <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">
                    Carrera
                </label>

                <select name="carrera_id"
                        onchange="this.form.submit()"
                        class="w-full p-4 rounded-2xl bg-slate-50 border border-slate-200 font-bold outline-none">
                    <option value="">Seleccionar</option>
                    <?php foreach($carreras as $c): ?>
                        <option value="<?= (int)$c['id'] ?>" <?= $carrera_id === (int)$c['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($c['nombre']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div>
                <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">
                    Periodo
                </label>

                <select name="periodo_id"
                        onchange="this.form.submit()"
                        class="w-full p-4 rounded-2xl bg-slate-50 border border-slate-200 font-bold outline-none">
                    <option value="">Seleccionar</option>
                    <?php foreach($periodos as $p): ?>
                        <option value="<?= (int)$p['id'] ?>" <?= $periodo_id === (int)$p['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($p['nombre']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div>
                <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">
                    Grupo
                </label>

                <select name="grupo_id"
                        onchange="this.form.submit()"
                        class="w-full p-4 rounded-2xl bg-slate-50 border border-slate-200 font-bold outline-none"
                        <?= (!$carrera_id || !$periodo_id) ? 'disabled' : '' ?>>
                    <option value="">Seleccionar</option>
                    <?php foreach($grupos as $g): ?>
                        <option value="<?= (int)$g['id'] ?>" <?= $grupo_id === (int)$g['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($g['nombre']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div>
                <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">
                    Asignatura
                </label>

                <select name="asignacion_id"
                        onchange="this.form.submit()"
                        class="w-full p-4 rounded-2xl bg-slate-50 border border-slate-200 font-bold outline-none"
                        <?= !$grupo_id ? 'disabled' : '' ?>>
                    <option value="">Seleccionar</option>
                    <?php foreach($asignaciones as $a): ?>
                        <option value="<?= (int)$a['id'] ?>" <?= $asignacion_id === (int)$a['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($a['materia']) ?> - <?= htmlspecialchars($a['docente']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

        </div>
    </form>

    <?php if($asignacion_id > 0 && !$info): ?>

        <div class="bg-red-50 text-red-700 border border-red-200 p-8 rounded-3xl font-bold">
            La asignación seleccionada no existe.
        </div>

    <?php elseif($info): ?>

        <div class="bg-white p-8 rounded-[3rem] border border-slate-100 shadow-sm mb-10">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                <div>
                    <p class="text-[10px] font-black uppercase text-slate-400 tracking-widest">Carrera</p>
                    <h3 class="text-sm font-black text-slate-800 mt-2"><?= htmlspecialchars($info['carrera']) ?></h3>
                </div>

                <div>
                    <p class="text-[10px] font-black uppercase text-slate-400 tracking-widest">Grupo</p>
                    <h3 class="text-sm font-black text-slate-800 mt-2"><?= htmlspecialchars($info['grupo']) ?></h3>
                </div>

                <div>
                    <p class="text-[10px] font-black uppercase text-slate-400 tracking-widest">Asignatura</p>
                    <h3 class="text-sm font-black text-purple-700 mt-2"><?= htmlspecialchars($info['materia']) ?></h3>
                </div>

                <div>
                    <p class="text-[10px] font-black uppercase text-slate-400 tracking-widest">Docente</p>
                    <h3 class="text-sm font-black text-slate-800 mt-2"><?= htmlspecialchars($info['docente']) ?></h3>
                </div>
            </div>
        </div>

        <?php if(!$alumnos): ?>

            <div class="bg-white p-16 rounded-[3rem] text-center border border-slate-100 shadow-sm">
                <h2 class="text-2xl font-black text-slate-700 uppercase">
                    Sin alumnos asignados
                </h2>
            </div>

        <?php elseif(!$unidades): ?>

            <div class="bg-white p-16 rounded-[3rem] text-center border border-slate-100 shadow-sm">
                <h2 class="text-2xl font-black text-slate-700 uppercase">
                    Sin calificaciones registradas
                </h2>
            </div>

        <?php else: ?>

            <div class="overflow-x-auto bg-white rounded-[3rem] shadow-xl border border-slate-100">
                <table class="min-w-full">
                    <thead class="bg-slate-900 text-white">
                        <tr>
                            <th class="p-5 text-left text-[10px] font-black uppercase tracking-widest">
                                Alumno
                            </th>

                            <?php foreach($unidades as $u): ?>
                                <th class="p-5 text-center text-[10px] font-black uppercase tracking-widest">
                                    Unidad <?= (int)$u ?>
                                </th>
                            <?php endforeach; ?>

                            <th class="p-5 text-center text-[10px] font-black uppercase tracking-widest bg-emerald-600">
                                Final
                            </th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-slate-100">
                        <?php foreach($alumnos as $a): ?>
                            <?php
                                $alumno_id = (int)$a['alumno_id'];
                                $suma = 0;
                                $contador = 0;
                            ?>

                            <tr class="hover:bg-slate-50">
                                <td class="p-5 font-bold text-slate-700 whitespace-nowrap">
                                    <?= htmlspecialchars($a['alumno']) ?>
                                </td>

                                <?php foreach($unidades as $u): ?>
                                    <?php
                                        $unidad = (int)$u;
                                        $cal = $calificaciones[$alumno_id][$unidad] ?? null;
                                        $final = $cal ? (float)$cal['calificacion_final'] : 0;

                                        if($cal){
                                            $suma += $final;
                                            $contador++;
                                        }
                                    ?>

                                    <td class="p-5 text-center">
                                        <?php if($cal): ?>
                                            <div class="flex flex-col items-center">
                                                <span class="font-black text-xl <?= $final >= 70 ? 'text-emerald-600' : 'text-red-500' ?>">
                                                    <?= number_format($final, 1) ?>
                                                </span>

                                                <span class="text-[9px] text-slate-400 uppercase font-bold">
                                                    Act: <?= number_format((float)$cal['promedio_actividades'], 1) ?> |
                                                    Ex: <?= number_format((float)$cal['calificacion_examen'], 1) ?>
                                                </span>
                                            </div>
                                        <?php else: ?>
                                            <span class="text-slate-300 font-black">—</span>
                                        <?php endif; ?>
                                    </td>
                                <?php endforeach; ?>

                                <td class="p-5 text-center bg-emerald-50">
                                    <?php $promedio = $contador > 0 ? $suma / $contador : 0; ?>

                                    <span class="font-black text-2xl <?= $promedio >= 70 ? 'text-emerald-700' : 'text-red-600' ?>">
                                        <?= number_format($promedio, 1) ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

        <?php endif; ?>

    <?php else: ?>

        <div class="bg-white p-16 rounded-[3rem] text-center border border-slate-100 shadow-sm">
            <h2 class="text-2xl font-black text-slate-700 uppercase">
                Selecciona una asignatura
            </h2>

            <p class="text-slate-400 font-semibold mt-3">
                Usa los filtros superiores para revisar las calificaciones.
            </p>
        </div>

    <?php endif; ?>

</div>