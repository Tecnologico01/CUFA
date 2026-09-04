<?php
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/permisos.php';

validarPermiso('subir_calificaciones');

$usuario_id = $_SESSION['user_id'] ?? null;
$asignacion_id = isset($_GET['asignacion_id']) ? (int)$_GET['asignacion_id'] : 0;

if(!$usuario_id){
    echo "<div class='p-6 bg-red-50 text-red-700 rounded-2xl font-bold'>Sesión no válida.</div>";
    exit;
}

/* =========================
   DOCENTE
========================= */
$stmt = $pdo->prepare("
    SELECT id
    FROM docentes
    WHERE usuario_id = ?
    LIMIT 1
");
$stmt->execute([$usuario_id]);
$docente = $stmt->fetch(PDO::FETCH_ASSOC);

if(!$docente){
    echo "<div class='p-6 bg-red-50 text-red-700 rounded-2xl font-bold'>Docente no encontrado.</div>";
    exit;
}

$docente_id = (int)$docente['id'];

if($asignacion_id <= 0){
    echo "<div class='p-6 bg-red-50 text-red-700 rounded-2xl font-bold'>Asignación no especificada.</div>";
    exit;
}

/* =========================
   VALIDAR ASIGNACIÓN
========================= */
$stmtInfo = $pdo->prepare("
    SELECT 
        ad.id,
        ad.grupo_id,
        ad.materia_id,
        m.nombre AS materia,
        g.nombre AS grupo,
        p.nombre AS periodo
    FROM asignaciones_docentes ad
    INNER JOIN materias m ON m.id = ad.materia_id
    INNER JOIN grupos g ON g.id = ad.grupo_id
    INNER JOIN periodos p ON p.id = ad.periodo_id
    WHERE ad.id = ?
    AND ad.docente_id = ?
    LIMIT 1
");
$stmtInfo->execute([$asignacion_id, $docente_id]);
$info = $stmtInfo->fetch(PDO::FETCH_ASSOC);

if(!$info){
    echo "<div class='p-6 bg-red-50 text-red-700 rounded-2xl font-bold'>Asignación no encontrada o no pertenece al docente.</div>";
    exit;
}

/* =========================
   ALUMNOS
========================= */
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

/* =========================
   UNIDADES
========================= */
$stmtUnidades = $pdo->prepare("
    SELECT DISTINCT parcial
    FROM temas_materia
    WHERE asignacion_id = ?
    ORDER BY parcial ASC
");
$stmtUnidades->execute([$asignacion_id]);
$unidades = $stmtUnidades->fetchAll(PDO::FETCH_COLUMN);

/* =========================
   GUARDAR
========================= */
if($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['guardar_calificaciones'])){

    try{
        $pdo->beginTransaction();

        $calificaciones = $_POST['calificaciones'] ?? [];

        foreach($calificaciones as $alumno_id => $unidadesAlumno){

            $alumno_id = (int)$alumno_id;

            foreach($unidadesAlumno as $unidad => $datos){

                $unidad = (int)$unidad;

                $promedio_actividades = isset($datos['promedio_actividades'])
                    ? (float)$datos['promedio_actividades']
                    : 0;

                $calificacion_examen = isset($datos['calificacion_examen'])
                    ? (float)$datos['calificacion_examen']
                    : 0;

                $calificacion_final = isset($datos['calificacion_final'])
                    ? (float)$datos['calificacion_final']
                    : 0;

                if($promedio_actividades < 0) $promedio_actividades = 0;
                if($calificacion_examen < 0) $calificacion_examen = 0;
                if($calificacion_final < 0) $calificacion_final = 0;

                if($promedio_actividades > 100) $promedio_actividades = 100;
                if($calificacion_examen > 100) $calificacion_examen = 100;
                if($calificacion_final > 100) $calificacion_final = 100;

                $stmtExiste = $pdo->prepare("
                    SELECT id
                    FROM calificaciones_finales
                    WHERE asignacion_id = ?
                    AND alumno_id = ?
                    AND unidad = ?
                    LIMIT 1
                ");
                $stmtExiste->execute([
                    $asignacion_id,
                    $alumno_id,
                    $unidad
                ]);

                $existe = $stmtExiste->fetchColumn();

                if($existe){

                    $stmtUpdate = $pdo->prepare("
                        UPDATE calificaciones_finales
                        SET 
                            promedio_actividades = ?,
                            calificacion_examen = ?,
                            calificacion_final = ?
                        WHERE id = ?
                    ");

                    $stmtUpdate->execute([
                        $promedio_actividades,
                        $calificacion_examen,
                        $calificacion_final,
                        $existe
                    ]);

                }else{

                    $stmtInsert = $pdo->prepare("
                        INSERT INTO calificaciones_finales (
                            asignacion_id,
                            alumno_id,
                            unidad,
                            promedio_actividades,
                            calificacion_examen,
                            calificacion_final
                        ) VALUES (?,?,?,?,?,?)
                    ");

                    $stmtInsert->execute([
                        $asignacion_id,
                        $alumno_id,
                        $unidad,
                        $promedio_actividades,
                        $calificacion_examen,
                        $calificacion_final
                    ]);
                }
            }
        }

        $pdo->commit();

        echo "
        <script>
            alert('Calificaciones guardadas correctamente');
            window.location.href='?modulo=calificaciones&asignacion_id={$asignacion_id}';
        </script>
        ";
        exit;

    }catch(Exception $e){

        if($pdo->inTransaction()){
            $pdo->rollBack();
        }

        $msg = htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');

        echo "
        <script>
            alert('Error al guardar calificaciones: {$msg}');
        </script>
        ";
    }
}

/* =========================
   CALIFICACIONES ACTUALES
========================= */
$stmtCal = $pdo->prepare("
    SELECT *
    FROM calificaciones_finales
    WHERE asignacion_id = ?
");
$stmtCal->execute([$asignacion_id]);
$calificaciones_raw = $stmtCal->fetchAll(PDO::FETCH_ASSOC);

$calificaciones = [];

foreach($calificaciones_raw as $c){
    $calificaciones[$c['alumno_id']][$c['unidad']] = $c;
}
?>

<div class="max-w-7xl mx-auto p-6 bg-slate-50 min-h-screen">

    <div class="mb-10 flex flex-col md:flex-row md:items-end md:justify-between gap-6">
        <div>
            <span class="bg-indigo-100 text-indigo-700 px-4 py-2 rounded-full text-[10px] font-black uppercase tracking-[0.25em]">
                Captura Manual
            </span>

            <h1 class="text-5xl font-black italic uppercase tracking-tighter text-slate-800 mt-5">
                Subir Calificaciones
            </h1>

            <p class="text-slate-500 font-semibold mt-3">
                <?= htmlspecialchars($info['materia']) ?>
                · Grupo <?= htmlspecialchars($info['grupo']) ?>
                · <?= htmlspecialchars($info['periodo']) ?>
            </p>
        </div>

        <a href="?modulo=calificaciones&asignacion_id=<?= $asignacion_id ?>"
           class="bg-slate-900 hover:bg-slate-700 text-white px-6 py-4 rounded-2xl font-black uppercase text-[10px] tracking-widest">
            ← Volver
        </a>
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
                Sin unidades registradas
            </h2>
            <p class="text-slate-400 font-semibold mt-3">
                Primero deben definirse las unidades de la materia.
            </p>
        </div>

    <?php else: ?>

        <form method="POST">

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
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-slate-100">

                        <?php foreach($alumnos as $a): ?>

                            <?php $alumno_id = (int)$a['alumno_id']; ?>

                            <tr class="hover:bg-slate-50">

                                <td class="p-5 font-black text-slate-700 whitespace-nowrap">
                                    <?= htmlspecialchars($a['alumno']) ?>
                                </td>

                                <?php foreach($unidades as $unidad): ?>

                                    <?php
                                        $unidad = (int)$unidad;

                                        $actual = $calificaciones[$alumno_id][$unidad] ?? [];

                                        $pa = $actual['promedio_actividades'] ?? '';
                                        $ex = $actual['calificacion_examen'] ?? '';
                                        $fn = $actual['calificacion_final'] ?? '';
                                    ?>

                                    <td class="p-5 min-w-[260px]">

                                        <div class="space-y-3">

                                            <div>
                                                <label class="block text-[9px] font-black text-slate-400 uppercase mb-1">
                                                    Actividades
                                                </label>
                                                <input
                                                    type="number"
                                                    step="0.01"
                                                    min="0"
                                                    max="100"
                                                    name="calificaciones[<?= $alumno_id ?>][<?= $unidad ?>][promedio_actividades]"
                                                    value="<?= htmlspecialchars($pa) ?>"
                                                    class="w-full p-3 rounded-xl border border-slate-200 bg-slate-50 font-bold text-center outline-none focus:border-indigo-500"
                                                >
                                            </div>

                                            <div>
                                                <label class="block text-[9px] font-black text-slate-400 uppercase mb-1">
                                                    Examen
                                                </label>
                                                <input
                                                    type="number"
                                                    step="0.01"
                                                    min="0"
                                                    max="100"
                                                    name="calificaciones[<?= $alumno_id ?>][<?= $unidad ?>][calificacion_examen]"
                                                    value="<?= htmlspecialchars($ex) ?>"
                                                    class="w-full p-3 rounded-xl border border-slate-200 bg-slate-50 font-bold text-center outline-none focus:border-indigo-500"
                                                >
                                            </div>

                                            <div>
                                                <label class="block text-[9px] font-black text-emerald-500 uppercase mb-1">
                                                    Final
                                                </label>
                                                <input
                                                    type="number"
                                                    step="0.01"
                                                    min="0"
                                                    max="100"
                                                    name="calificaciones[<?= $alumno_id ?>][<?= $unidad ?>][calificacion_final]"
                                                    value="<?= htmlspecialchars($fn) ?>"
                                                    class="w-full p-3 rounded-xl border border-emerald-200 bg-emerald-50 font-black text-center text-emerald-700 outline-none focus:border-emerald-500"
                                                >
                                            </div>

                                        </div>

                                    </td>

                                <?php endforeach; ?>

                            </tr>

                        <?php endforeach; ?>

                    </tbody>
                </table>

            </div>

            <div class="mt-8 flex justify-end">
                <button
                    type="submit"
                    name="guardar_calificaciones"
                    class="bg-indigo-600 hover:bg-indigo-700 text-white px-10 py-5 rounded-2xl font-black uppercase tracking-[0.25em] text-[10px] shadow-xl"
                >
                    Guardar Calificaciones
                </button>
            </div>

        </form>

    <?php endif; ?>

</div>