<?php
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/permisos.php';

validarPermiso('generar_actas');

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
            ad.periodo_id,
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

/* =========================
   GENERAR ACTA
========================= */
if($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['generar_acta'])){

    $asignacion_post = isset($_POST['asignacion_id']) ? (int) $_POST['asignacion_id'] : 0;

    if($asignacion_post <= 0){
        echo "<script>alert('Asignación no válida');</script>";
    }else{

        try{

            $pdo->beginTransaction();

            $stmtInfoActa = $pdo->prepare("
                SELECT 
                    ad.id,
                    ad.materia_id,
                    ad.grupo_id,
                    ad.periodo_id
                FROM asignaciones_docentes ad
                WHERE ad.id = ?
                LIMIT 1
            ");
            $stmtInfoActa->execute([$asignacion_post]);
            $datosActa = $stmtInfoActa->fetch(PDO::FETCH_ASSOC);

            if(!$datosActa){
                throw new Exception("La asignación no existe.");
            }

            $stmtExiste = $pdo->prepare("
                SELECT id
                FROM actas
                WHERE asignacion_id = ?
                LIMIT 1
            ");
            $stmtExiste->execute([$asignacion_post]);
            $actaExistente = $stmtExiste->fetchColumn();

            if($actaExistente){
                throw new Exception("Ya existe un acta generada para esta asignación.");
            }

            $stmtAlumnosActa = $pdo->prepare("
                SELECT 
                    a.id AS alumno_id
                FROM grupos_alumnos ga
                INNER JOIN alumnos a ON a.id = ga.alumno_id
                WHERE ga.grupo_id = ?
            ");
            $stmtAlumnosActa->execute([(int)$datosActa['grupo_id']]);
            $alumnosActa = $stmtAlumnosActa->fetchAll(PDO::FETCH_ASSOC);

            if(!$alumnosActa){
                throw new Exception("El grupo no tiene alumnos asignados.");
            }

            $stmtUnidadesActa = $pdo->prepare("
                SELECT DISTINCT unidad
                FROM calificaciones_finales
                WHERE asignacion_id = ?
                ORDER BY unidad ASC
            ");
            $stmtUnidadesActa->execute([$asignacion_post]);
            $unidadesActa = $stmtUnidadesActa->fetchAll(PDO::FETCH_COLUMN);

            if(!$unidadesActa){
                throw new Exception("No hay calificaciones registradas para generar el acta.");
            }

            foreach($alumnosActa as $alumno){
                $alumno_id = (int)$alumno['alumno_id'];

                foreach($unidadesActa as $unidad){
                    $stmtCalifCheck = $pdo->prepare("
                        SELECT id
                        FROM calificaciones_finales
                        WHERE asignacion_id = ?
                        AND alumno_id = ?
                        AND unidad = ?
                        LIMIT 1
                    ");
                    $stmtCalifCheck->execute([
                        $asignacion_post,
                        $alumno_id,
                        (int)$unidad
                    ]);

                    if(!$stmtCalifCheck->fetch()){
                        throw new Exception("Faltan calificaciones de uno o más alumnos.");
                    }
                }
            }

            $codigoActa = 'ACTA-' . date('Ymd-His') . '-' . $asignacion_post;

            $stmtInsert = $pdo->prepare("
                INSERT INTO actas (
                    codigo,
                    asignacion_id,
                    materia_id,
                    grupo_id,
                    periodo_id,
                    estado,
                    fecha_generacion,
                    generado_por
                ) VALUES (
                    ?, ?, ?, ?, ?, 'generada', NOW(), ?
                )
            ");

            $stmtInsert->execute([
                $codigoActa,
                $asignacion_post,
                (int)$datosActa['materia_id'],
                (int)$datosActa['grupo_id'],
                (int)$datosActa['periodo_id'],
                $_SESSION['user_id'] ?? null
            ]);

            $pdo->commit();

            echo "
            <script>
                alert('Acta generada correctamente');
                window.location.href='?modulo=generar_actas&asignacion_id={$asignacion_post}';
            </script>";
            exit;

        }catch(Exception $e){

            if($pdo->inTransaction()){
                $pdo->rollBack();
            }

            $msg = htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');

            echo "
            <script>
                alert('{$msg}');
            </script>";
        }
    }
}

/* =========================
   ACTA EXISTENTE
========================= */
$acta = null;

if($asignacion_id > 0){
    $stmtActa = $pdo->prepare("
        SELECT *
        FROM actas
        WHERE asignacion_id = ?
        LIMIT 1
    ");
    $stmtActa->execute([$asignacion_id]);
    $acta = $stmtActa->fetch(PDO::FETCH_ASSOC);
}
?>

<div class="max-w-7xl mx-auto p-6 bg-slate-50 min-h-screen">

    <div class="mb-10">
        <span class="bg-emerald-100 text-emerald-700 px-4 py-2 rounded-full text-[10px] font-black uppercase tracking-[0.25em]">
            Actas Oficiales
        </span>

        <h1 class="text-5xl font-black italic uppercase tracking-tighter text-slate-800 mt-5">
            Generar Actas
        </h1>

        <p class="text-slate-500 font-semibold mt-3">
            Emisión de actas oficiales a partir de las calificaciones finales.
        </p>
    </div>

    <form method="GET" class="bg-white p-8 rounded-[3rem] border border-slate-100 shadow-sm mb-10">
        <input type="hidden" name="modulo" value="generar_actas">

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

    <?php if($info): ?>

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
                    <h3 class="text-sm font-black text-emerald-700 mt-2"><?= htmlspecialchars($info['materia']) ?></h3>
                </div>

                <div>
                    <p class="text-[10px] font-black uppercase text-slate-400 tracking-widest">Docente</p>
                    <h3 class="text-sm font-black text-slate-800 mt-2"><?= htmlspecialchars($info['docente']) ?></h3>
                </div>
            </div>
        </div>

        <?php if($acta): ?>

            <div class="bg-emerald-50 border border-emerald-200 text-emerald-700 p-8 rounded-[2rem] mb-10">
                <h2 class="text-2xl font-black uppercase mb-2">
                    Acta ya generada
                </h2>

                <p class="font-bold">
                    Código: <?= htmlspecialchars($acta['codigo'] ?? 'SIN-CODIGO') ?>
                </p>

                <p class="font-bold">
                    Estado: <?= htmlspecialchars($acta['estado'] ?? 'generada') ?>
                </p>
            </div>

        <?php endif; ?>

        <?php if(!$alumnos || !$unidades): ?>

            <div class="bg-white p-16 rounded-[3rem] text-center border border-slate-100 shadow-sm">
                <h2 class="text-2xl font-black text-slate-700 uppercase">
                    No hay datos suficientes
                </h2>

                <p class="text-slate-400 font-semibold mt-3">
                    Deben existir alumnos y calificaciones finales antes de generar el acta.
                </p>
            </div>

        <?php else: ?>

            <div class="overflow-x-auto bg-white rounded-[3rem] shadow-xl border border-slate-100 mb-10">
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
                                            <span class="font-black text-xl <?= $final >= 70 ? 'text-emerald-600' : 'text-red-500' ?>">
                                                <?= number_format($final, 1) ?>
                                            </span>
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

            <?php if(!$acta): ?>
                <form method="POST" onsubmit="return confirm('¿Confirmas generar el acta oficial? Esta acción no debe repetirse.');">
                    <input type="hidden" name="asignacion_id" value="<?= (int)$asignacion_id ?>">

                    <button
                        type="submit"
                        name="generar_acta"
                        class="bg-emerald-600 hover:bg-emerald-700 text-white px-10 py-5 rounded-2xl font-black uppercase tracking-[0.25em] text-[10px] shadow-xl"
                    >
                        Generar Acta Oficial
                    </button>
                </form>
            <?php endif; ?>

        <?php endif; ?>

    <?php else: ?>

        <div class="bg-white p-16 rounded-[3rem] text-center border border-slate-100 shadow-sm">
            <h2 class="text-2xl font-black text-slate-700 uppercase">
                Selecciona una asignatura
            </h2>

            <p class="text-slate-400 font-semibold mt-3">
                Usa los filtros superiores para generar un acta.
            </p>
        </div>

    <?php endif; ?>

</div>