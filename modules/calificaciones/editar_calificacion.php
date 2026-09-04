<?php
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/permisos.php';

validarPermiso('editar_calificaciones');

$usuario_id = $_SESSION['user_id'] ?? null;

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

if(!$usuario_id){
    echo "<div class='p-6 bg-red-50 text-red-700 rounded-2xl font-bold'>Sesión no válida.</div>";
    exit;
}

if($id <= 0){
    echo "<div class='p-6 bg-red-50 text-red-700 rounded-2xl font-bold'>Calificación no especificada.</div>";
    exit;
}

/* =========================
   VALIDAR DOCENTE
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
    echo "<div class='p-6 bg-red-50 text-red-700 rounded-2xl font-bold'>Docente no encontrado.</div>";
    exit;
}

$docente_id = (int)$docente['id'];

/* =========================
   OBTENER CALIFICACIÓN
========================= */
$stmt = $pdo->prepare("
    SELECT 
        cf.id,
        cf.asignacion_id,
        cf.alumno_id,
        cf.unidad,
        cf.promedio_actividades,
        cf.calificacion_examen,
        cf.calificacion_final,

        m.nombre AS materia,
        g.nombre AS grupo,
        p.nombre AS periodo,

        CONCAT(
            u.nombres, ' ',
            COALESCE(u.apellido_paterno,''), ' ',
            COALESCE(u.apellido_materno,'')
        ) AS alumno

    FROM calificaciones_finales cf
    INNER JOIN asignaciones_docentes ad
        ON ad.id = cf.asignacion_id
    INNER JOIN materias m
        ON m.id = ad.materia_id
    INNER JOIN grupos g
        ON g.id = ad.grupo_id
    INNER JOIN periodos p
        ON p.id = ad.periodo_id
    INNER JOIN alumnos a
        ON a.id = cf.alumno_id
    INNER JOIN usuarios u
        ON u.id = a.usuario_id
    WHERE cf.id = ?
    AND ad.docente_id = ?
    LIMIT 1
");

$stmt->execute([$id, $docente_id]);
$calificacion = $stmt->fetch(PDO::FETCH_ASSOC);

if(!$calificacion){
    echo "<div class='p-6 bg-red-50 text-red-700 rounded-2xl font-bold'>Calificación no encontrada o no pertenece al docente actual.</div>";
    exit;
}

/* =========================
   ACTUALIZAR
========================= */
if($_SERVER['REQUEST_METHOD'] === 'POST'){

    $promedio_actividades = isset($_POST['promedio_actividades'])
        ? (float) $_POST['promedio_actividades']
        : 0;

    $calificacion_examen = isset($_POST['calificacion_examen'])
        ? (float) $_POST['calificacion_examen']
        : 0;

    $calificacion_final = isset($_POST['calificacion_final'])
        ? (float) $_POST['calificacion_final']
        : 0;

    $observaciones = trim($_POST['observaciones'] ?? '');

    $promedio_actividades = max(0, min(100, $promedio_actividades));
    $calificacion_examen = max(0, min(100, $calificacion_examen));
    $calificacion_final = max(0, min(100, $calificacion_final));

    try{

        $pdo->beginTransaction();

        /*
        |--------------------------------------------------------------------------
        | VERIFICAR SI EXISTE COLUMNA OBSERVACIONES
        |--------------------------------------------------------------------------
        */
        $stmtColumna = $pdo->prepare("
            SHOW COLUMNS FROM calificaciones_finales LIKE 'observaciones'
        ");
        $stmtColumna->execute();
        $tieneObservaciones = $stmtColumna->fetch(PDO::FETCH_ASSOC);

        if($tieneObservaciones){

            $stmtUpdate = $pdo->prepare("
                UPDATE calificaciones_finales
                SET
                    promedio_actividades = ?,
                    calificacion_examen = ?,
                    calificacion_final = ?,
                    observaciones = ?
                WHERE id = ?
            ");

            $stmtUpdate->execute([
                $promedio_actividades,
                $calificacion_examen,
                $calificacion_final,
                $observaciones,
                $id
            ]);

        }else{

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
                $id
            ]);
        }

        $pdo->commit();

        echo "
        <script>
            alert('Calificación actualizada correctamente');
            window.location.href='?modulo=calificaciones&asignacion_id=".(int)$calificacion['asignacion_id']."';
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
            alert('Error al actualizar la calificación: {$msg}');
        </script>
        ";
    }
}
?>

<div class="max-w-4xl mx-auto p-6 bg-slate-50 min-h-screen">

    <div class="mb-10">
        <span class="bg-blue-100 text-blue-700 px-4 py-2 rounded-full text-[10px] font-black uppercase tracking-[0.25em]">
            Edición de Calificación
        </span>

        <h1 class="text-5xl font-black italic uppercase tracking-tighter text-slate-800 mt-5">
            Editar Calificación
        </h1>

        <p class="text-slate-500 font-semibold mt-3">
            <?= htmlspecialchars($calificacion['materia']) ?>
            · Grupo <?= htmlspecialchars($calificacion['grupo']) ?>
            · <?= htmlspecialchars($calificacion['periodo']) ?>
        </p>
    </div>

    <div class="bg-white rounded-[3rem] shadow-xl border border-slate-100 p-10">

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-10">

            <div class="bg-slate-50 p-5 rounded-2xl border border-slate-100">
                <p class="text-[10px] font-black uppercase text-slate-400 tracking-widest">
                    Alumno
                </p>
                <h3 class="text-lg font-black text-slate-800 mt-2">
                    <?= htmlspecialchars($calificacion['alumno']) ?>
                </h3>
            </div>

            <div class="bg-indigo-50 p-5 rounded-2xl border border-indigo-100">
                <p class="text-[10px] font-black uppercase text-indigo-400 tracking-widest">
                    Unidad
                </p>
                <h3 class="text-3xl font-black text-indigo-700 mt-2">
                    <?= (int)$calificacion['unidad'] ?>
                </h3>
            </div>

            <div class="bg-emerald-50 p-5 rounded-2xl border border-emerald-100">
                <p class="text-[10px] font-black uppercase text-emerald-400 tracking-widest">
                    Final actual
                </p>
                <h3 class="text-3xl font-black <?= $calificacion['calificacion_final'] >= 70 ? 'text-emerald-700' : 'text-red-600' ?> mt-2">
                    <?= number_format((float)$calificacion['calificacion_final'], 1) ?>
                </h3>
            </div>

        </div>

        <form method="POST" class="space-y-6">

            <div>
                <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">
                    Promedio de Actividades
                </label>

                <input
                    type="number"
                    name="promedio_actividades"
                    min="0"
                    max="100"
                    step="0.01"
                    value="<?= htmlspecialchars($calificacion['promedio_actividades']) ?>"
                    class="w-full p-5 rounded-2xl border border-slate-200 bg-slate-50 font-black text-center text-xl outline-none focus:border-blue-500"
                    required
                >
            </div>

            <div>
                <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">
                    Calificación de Examen
                </label>

                <input
                    type="number"
                    name="calificacion_examen"
                    min="0"
                    max="100"
                    step="0.01"
                    value="<?= htmlspecialchars($calificacion['calificacion_examen']) ?>"
                    class="w-full p-5 rounded-2xl border border-slate-200 bg-slate-50 font-black text-center text-xl outline-none focus:border-blue-500"
                    required
                >
            </div>

            <div>
                <label class="block text-[10px] font-black text-emerald-500 uppercase tracking-widest mb-2">
                    Calificación Final
                </label>

                <input
                    type="number"
                    name="calificacion_final"
                    min="0"
                    max="100"
                    step="0.01"
                    value="<?= htmlspecialchars($calificacion['calificacion_final']) ?>"
                    class="w-full p-5 rounded-2xl border border-emerald-200 bg-emerald-50 font-black text-center text-2xl text-emerald-700 outline-none focus:border-emerald-500"
                    required
                >
            </div>

            <div>
                <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">
                    Observaciones
                </label>

                <textarea
                    name="observaciones"
                    rows="4"
                    class="w-full p-5 rounded-2xl border border-slate-200 bg-slate-50 font-semibold outline-none focus:border-blue-500"
                    placeholder="Opcional"
                ></textarea>
            </div>

            <div class="flex flex-col md:flex-row gap-4 pt-6">

                <button
                    type="submit"
                    class="flex-1 bg-blue-600 hover:bg-blue-700 text-white px-8 py-5 rounded-2xl font-black uppercase tracking-[0.25em] text-[10px] shadow-xl"
                >
                    Guardar Cambios
                </button>

                <a
                    href="?modulo=calificaciones&asignacion_id=<?= (int)$calificacion['asignacion_id'] ?>"
                    class="flex-1 text-center bg-slate-100 hover:bg-slate-200 text-slate-700 px-8 py-5 rounded-2xl font-black uppercase tracking-[0.25em] text-[10px]"
                >
                    Cancelar
                </a>

            </div>

        </form>

    </div>

</div>