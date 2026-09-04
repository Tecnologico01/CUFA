<?php
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/permisos.php';

validarPermiso('ver_calificaciones');

$puedeSubir = tienePermiso('subir_calificaciones') || tienePermiso('editar_calificaciones');

$usuario_id = $_SESSION['user_id'] ?? null;
$asignacion_id = isset($_GET['asignacion_id']) ? (int) $_GET['asignacion_id'] : 0;

if(!$usuario_id){
    echo "<div class='p-6 bg-red-50 text-red-700 rounded-2xl font-bold'>Sesión no válida.</div>";
    exit;
}

/* =========================
   OBTENER DOCENTE REAL
========================= */
$stmtDocente = $pdo->prepare("
    SELECT id
    FROM docentes
    WHERE usuario_id = ?
    LIMIT 1
");
$stmtDocente->execute([$usuario_id]);
$docente = $stmtDocente->fetch(PDO::FETCH_ASSOC);

if(!$docente){
    echo "<div class='p-6 bg-red-50 text-red-700 rounded-2xl font-bold'>El usuario actual no está vinculado a un docente.</div>";
    exit;
}

$docente_id = (int)$docente['id'];

/* =========================
   MATERIAS DEL DOCENTE
========================= */
$stmtMaterias = $pdo->prepare("
    SELECT 
        ad.id,
        m.nombre AS materia,
        g.nombre AS grupo,
        p.nombre AS periodo
    FROM asignaciones_docentes ad
    INNER JOIN materias m ON m.id = ad.materia_id
    INNER JOIN grupos g ON g.id = ad.grupo_id
    INNER JOIN periodos p ON p.id = ad.periodo_id
    WHERE ad.docente_id = ?
    ORDER BY m.nombre ASC
");
$stmtMaterias->execute([$docente_id]);
$materias = $stmtMaterias->fetchAll(PDO::FETCH_ASSOC);

/* =========================
   VISTA SELECCIÓN
========================= */
if(!$asignacion_id):
?>

<div class="max-w-7xl mx-auto p-6 bg-slate-50 min-h-screen">

    <div class="mb-10">
        <span class="bg-indigo-100 text-indigo-700 px-4 py-2 rounded-full text-[10px] font-black uppercase tracking-[0.25em]">
            Calificaciones
        </span>

        <h1 class="text-5xl font-black italic uppercase tracking-tighter text-slate-800 mt-5">
            Acta de Calificaciones
        </h1>

        <p class="text-slate-500 font-semibold mt-3">
            Selecciona una asignatura para visualizar o actualizar calificaciones.
        </p>
    </div>

    <?php if($materias): ?>
        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-8">
            <?php foreach($materias as $m): ?>
                <a href="?modulo=calificaciones&asignacion_id=<?= (int)$m['id'] ?>"
                   class="bg-white p-8 rounded-[2.5rem] shadow-sm border border-slate-100 hover:shadow-xl hover:-translate-y-1 transition-all group">

                    <div class="flex justify-between items-center mb-6">
                        <span class="bg-indigo-50 text-indigo-700 text-[10px] px-4 py-2 rounded-full font-black uppercase tracking-widest">
                            Grupo <?= htmlspecialchars($m['grupo']) ?>
                        </span>

                        <span class="text-indigo-600 font-black group-hover:translate-x-1 transition-transform">
                            →
                        </span>
                    </div>

                    <h2 class="text-2xl font-black text-slate-800 uppercase tracking-tight group-hover:text-indigo-600">
                        <?= htmlspecialchars($m['materia']) ?>
                    </h2>

                    <p class="text-[10px] text-slate-400 font-black uppercase tracking-widest mt-4">
                        <?= htmlspecialchars($m['periodo']) ?>
                    </p>
                </a>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="bg-white p-16 rounded-[3rem] text-center border border-slate-100 shadow-sm">
            <h2 class="text-2xl font-black text-slate-700 uppercase">
                Sin asignaturas asignadas
            </h2>
        </div>
    <?php endif; ?>

</div>

<?php
exit;
endif;

/* =========================
   INFO ASIGNACIÓN
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
    echo "<div class='p-6 bg-red-50 text-red-700 rounded-2xl font-bold'>Asignación no encontrada o no pertenece al docente actual.</div>";
    exit;
}

/* =========================
   ALUMNOS DEL GRUPO
========================= */
$stmtAlumnos = $pdo->prepare("
    SELECT 
        a.id AS alumno_id,
        u.id AS usuario_id,
        CONCAT(
            u.nombres, ' ',
            COALESCE(u.apellido_paterno,''), ' ',
            COALESCE(u.apellido_materno,'')
        ) AS nombre_completo
    FROM grupos_alumnos ga
    INNER JOIN alumnos a ON a.id = ga.alumno_id
    INNER JOIN usuarios u ON u.id = a.usuario_id
    WHERE ga.grupo_id = ?
    ORDER BY nombre_completo ASC
");
$stmtAlumnos->execute([$info['grupo_id']]);
$alumnos = $stmtAlumnos->fetchAll(PDO::FETCH_ASSOC);

/* =========================
   UNIDADES DESDE TEMAS
========================= */
$stmtUni = $pdo->prepare("
    SELECT DISTINCT parcial
    FROM temas_materia
    WHERE asignacion_id = ?
    ORDER BY parcial ASC
");
$stmtUni->execute([$asignacion_id]);
$unidades = $stmtUni->fetchAll(PDO::FETCH_COLUMN);

/* =========================
   GUARDAR FINALES
========================= */
if(isset($_POST['guardar_finales'])){

    if(!$puedeSubir){
        echo "<script>alert('No tienes permiso para actualizar calificaciones.');</script>";
    }else{

        try{
            $pdo->beginTransaction();

            foreach($alumnos as $alumno){

                $alumno_id = (int)$alumno['alumno_id'];

                foreach($unidades as $unidad){

                    $unidad = (int)$unidad;

                    $stmtActs = $pdo->prepare("
                        SELECT 
                            act.id,
                            act.valor,
                            COALESCE(e.calificacion, 0) AS calificacion
                        FROM actividades act
                        LEFT JOIN entregas e 
                            ON e.actividad_id = act.id 
                            AND e.alumno_id = ?
                        WHERE act.asignacion_id = ?
                        AND act.parcial = ?
                    ");
                    $stmtActs->execute([$alumno_id, $asignacion_id, $unidad]);
                    $actividades = $stmtActs->fetchAll(PDO::FETCH_ASSOC);

                    $promedio_actividades = 0;

                    foreach($actividades as $act){
                        $valor = (float)$act['valor'];
                        $calif = (float)$act['calificacion'];

                        if($valor > 0){
                            $promedio_actividades += ($calif * $valor) / 100;
                        }
                    }

                    $stmtExam = $pdo->prepare("
                        SELECT ie.calificacion
                        FROM intentos_examen ie
                        INNER JOIN examenes ex ON ex.id = ie.examen_id
                        WHERE ie.alumno_id = ?
                        AND ex.asignacion_id = ?
                        AND ex.parcial = ?
                        ORDER BY ie.id DESC
                        LIMIT 1
                    ");
                    $stmtExam->execute([$alumno_id, $asignacion_id, $unidad]);
                    $calificacion_examen = (float)($stmtExam->fetchColumn() ?: 0);

                    $promedio_final = $promedio_actividades + ($calificacion_examen * 0.20);

                    if($promedio_final > 100){
                        $promedio_final = 100;
                    }

                    $stmtExiste = $pdo->prepare("
                        SELECT id
                        FROM calificaciones_finales
                        WHERE asignacion_id = ?
                        AND alumno_id = ?
                        AND unidad = ?
                        LIMIT 1
                    ");
                    $stmtExiste->execute([$asignacion_id, $alumno_id, $unidad]);
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
                            $promedio_final,
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
                            $promedio_final
                        ]);
                    }
                }
            }

            $pdo->commit();

            echo "
            <div class='mb-6 bg-emerald-50 text-emerald-700 border border-emerald-200 p-5 rounded-2xl font-black uppercase tracking-widest'>
                Calificaciones actualizadas correctamente.
            </div>";

        }catch(Exception $e){

            if($pdo->inTransaction()){
                $pdo->rollBack();
            }

            echo "
            <div class='mb-6 bg-red-50 text-red-700 border border-red-200 p-5 rounded-2xl font-bold'>
                Error: ".htmlspecialchars($e->getMessage())."
            </div>";
        }
    }
}
?>

<div class="max-w-7xl mx-auto p-6 bg-slate-50 min-h-screen">

    <div class="flex flex-col md:flex-row md:items-end md:justify-between gap-6 mb-10">
        <div>
            <span class="bg-indigo-100 text-indigo-700 px-4 py-2 rounded-full text-[10px] font-black uppercase tracking-[0.25em]">
                Acta de Calificaciones
            </span>

            <h1 class="text-5xl font-black italic uppercase tracking-tighter text-slate-800 mt-5">
                <?= htmlspecialchars($info['materia']) ?>
            </h1>

            <p class="text-slate-500 font-semibold mt-3">
                Grupo <?= htmlspecialchars($info['grupo']) ?> · <?= htmlspecialchars($info['periodo']) ?>
            </p>
        </div>

        <div class="flex gap-3">
            <a href="?modulo=calificaciones"
               class="bg-slate-900 hover:bg-slate-700 text-white px-6 py-4 rounded-2xl font-black uppercase text-[10px] tracking-widest">
                ← Volver
            </a>

            <?php if($puedeSubir): ?>
                <form method="POST">
                    <button type="submit" name="guardar_finales"
                            class="bg-indigo-600 hover:bg-indigo-700 text-white px-6 py-4 rounded-2xl font-black uppercase text-[10px] tracking-widest shadow-lg">
                        Actualizar
                    </button>
                </form>
            <?php endif; ?>

            <?php if(tienePermiso('subir_calificaciones')): ?>
                <a href="?modulo=subir_calificaciones&asignacion_id=<?= $asignacion_id ?>"
                class="bg-emerald-600 hover:bg-emerald-700 text-white px-6 py-4 rounded-2xl font-black uppercase text-[10px] tracking-widest shadow-lg">
                    Capturar Manual
                </a>
            <?php endif; ?>
        </div>
    </div>

    <?php if(!$unidades): ?>
        <div class="bg-white p-16 rounded-[3rem] text-center border border-slate-100 shadow-sm">
            <h2 class="text-2xl font-black text-slate-700 uppercase">
                Sin unidades registradas
            </h2>
            <p class="text-slate-400 font-semibold mt-3">
                Primero deben definirse las unidades de la asignatura.
            </p>
        </div>
    <?php else: ?>

        <div class="overflow-x-auto bg-white rounded-[3rem] shadow-xl border border-slate-100">
            <table class="min-w-full">
                <thead class="bg-slate-900 text-white">
                    <tr>
                        <th class="p-5 text-left text-[10px] font-black uppercase tracking-widest">Alumno</th>

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
                    <?php foreach($alumnos as $alumno): ?>

                        <?php
                            $suma_total = 0;
                            $contador_total = 0;
                            $alumno_id = (int)$alumno['alumno_id'];
                        ?>

                        <tr class="hover:bg-slate-50">

                            <td class="p-5 font-bold text-slate-700 whitespace-nowrap">
                                <?= htmlspecialchars($alumno['nombre_completo']) ?>
                            </td>

                            <?php foreach($unidades as $unidad): ?>

                                <?php
                                    $stmtCalif = $pdo->prepare("
                                        SELECT calificacion_final
                                        FROM calificaciones_finales
                                        WHERE asignacion_id = ?
                                        AND alumno_id = ?
                                        AND unidad = ?
                                        LIMIT 1
                                    ");
                                    $stmtCalif->execute([
                                        $asignacion_id,
                                        $alumno_id,
                                        (int)$unidad
                                    ]);

                                    $final_unidad = (float)($stmtCalif->fetchColumn() ?: 0);

                                    $suma_total += $final_unidad;
                                    $contador_total++;
                                ?>

                                <td class="p-5 text-center">
                                    <span class="font-black text-xl <?= $final_unidad >= 70 ? 'text-emerald-600' : 'text-red-500' ?>">
                                        <?= number_format($final_unidad, 1) ?>
                                    </span>
                                </td>

                            <?php endforeach; ?>

                            <td class="p-5 text-center bg-emerald-50">
                                <?php $promedio_general = $contador_total > 0 ? $suma_total / $contador_total : 0; ?>

                                <span class="font-black text-2xl <?= $promedio_general >= 70 ? 'text-emerald-700' : 'text-red-600' ?>">
                                    <?= number_format($promedio_general, 1) ?>
                                </span>
                            </td>

                        </tr>

                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

    <?php endif; ?>

    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mt-10">

        <div class="bg-white p-6 rounded-2xl shadow border border-indigo-100">
            <p class="text-xs uppercase text-indigo-400 font-black mb-2">Total alumnos</p>
            <h3 class="text-4xl font-black text-indigo-700"><?= count($alumnos) ?></h3>
        </div>

        <div class="bg-white p-6 rounded-2xl shadow border border-green-100">
            <p class="text-xs uppercase text-green-400 font-black mb-2">Unidades</p>
            <h3 class="text-4xl font-black text-green-700"><?= count($unidades) ?></h3>
        </div>

        <div class="bg-white p-6 rounded-2xl shadow border border-purple-100">
            <p class="text-xs uppercase text-purple-400 font-black mb-2">Asignatura</p>
            <h3 class="text-lg font-black text-purple-700"><?= htmlspecialchars($info['materia']) ?></h3>
        </div>

        <div class="bg-white p-6 rounded-2xl shadow border border-pink-100">
            <p class="text-xs uppercase text-pink-400 font-black mb-2">Grupo</p>
            <h3 class="text-lg font-black text-pink-700"><?= htmlspecialchars($info['grupo']) ?></h3>
        </div>

    </div>

</div>