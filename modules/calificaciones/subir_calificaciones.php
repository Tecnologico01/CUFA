<?php
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/permisos.php';

validarPermiso('subir_calificaciones');
require_once __DIR__ . '/../../includes/calificaciones_service.php';

$usuario_id = $_SESSION['user_id'] ?? null;
$asignacion_id = isset($_GET['asignacion_id'])
    ? (int) $_GET['asignacion_id']
    : 0;

if (!$usuario_id) {
    echo "
        <div class='p-6 bg-red-50 text-red-700 rounded-2xl font-bold'>
            Sesión no válida.
        </div>
    ";
    exit;
}

/* =========================================================
   OBTENER DOCENTE
========================================================= */

$stmt = $pdo->prepare("
    SELECT id
    FROM docentes
    WHERE usuario_id = ?
    LIMIT 1
");

$stmt->execute([$usuario_id]);

$docente = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$docente) {
    echo "
        <div class='p-6 bg-red-50 text-red-700 rounded-2xl font-bold'>
            Docente no encontrado.
        </div>
    ";
    exit;
}

$docente_id = (int) $docente['id'];


/* =========================================================
   VALIDAR ASIGNACIÓN
========================================================= */

if ($asignacion_id <= 0) {
    echo "
        <div class='p-6 bg-red-50 text-red-700 rounded-2xl font-bold'>
            Asignación no especificada.
        </div>
    ";
    exit;
}

$stmtInfo = $pdo->prepare("
    SELECT
        ad.id,
        ad.grupo_id,
        ad.materia_id,
        ad.periodo_id,

        m.nombre AS materia,
        g.nombre AS grupo,
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

    LIMIT 1
");

$stmtInfo->execute([
    $asignacion_id,
    $docente_id
]);

$info = $stmtInfo->fetch(PDO::FETCH_ASSOC);

if (!$info) {
    echo "
        <div class='p-6 bg-red-50 text-red-700 rounded-2xl font-bold'>
            Asignación no encontrada o no pertenece al docente.
        </div>
    ";
    exit;
}


/* =========================================================
   OBTENER ALUMNOS
========================================================= */

$stmtAlumnos = $pdo->prepare("
    SELECT
        a.id AS alumno_id,

        CONCAT(
            u.nombres, ' ',
            COALESCE(u.apellido_paterno, ''), ' ',
            COALESCE(u.apellido_materno, '')
        ) AS alumno

    FROM grupos_alumnos ga

    INNER JOIN alumnos a
        ON a.id = ga.alumno_id

    INNER JOIN usuarios u
        ON u.id = a.usuario_id

    WHERE ga.grupo_id = ? AND ga.periodo_id = ?

    ORDER BY alumno ASC
");

$stmtAlumnos->execute([
    $info['grupo_id'], $info['periodo_id']
]);

$alumnos = $stmtAlumnos->fetchAll(PDO::FETCH_ASSOC);


/* =========================================================
   OBTENER UNIDADES
========================================================= */

[, , $unidades] = cufaContexto($pdo,$asignacion_id);

/* =========================================================
   ESTADO ACTUAL DE LAS CALIFICACIONES
========================================================= */

[, , , $filasActuales] = cufaContexto($pdo, $asignacion_id);
$estadoActual = cufaEstado($filasActuales);
$puedeEditar = in_array($estadoActual, ['borrador','devuelto'], true);
if ($_SERVER['REQUEST_METHOD'] === 'POST' && (isset($_POST['guardar_calificaciones']) || isset($_POST['enviar_revision']))) {
    try {
        cufaValidarToken();
        cufaGuardar($pdo, $asignacion_id, (int)$usuario_id, $_POST['calificaciones'] ?? [], isset($_POST['enviar_revision']), true);
        $mensaje = isset($_POST['enviar_revision']) ? 'Notas guardadas y enviadas a Control Escolar.' : 'Borrador guardado correctamente.';
        echo '<script>alert('.json_encode($mensaje).');window.location.href="?modulo=subir_calificaciones&asignacion_id='.$asignacion_id.'";</script>';
        exit;
    } catch (Throwable $e) {
        echo '<div class="p-6 bg-red-50 text-red-700 rounded-2xl">'.htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8').'</div>';
    }
}

/* =========================================================
   OBTENER CALIFICACIONES ACTUALES
========================================================= */

$stmtCal = $pdo->prepare("
    SELECT *
    FROM calificaciones_finales
    WHERE asignacion_id = ?
");

$stmtCal->execute([
    $asignacion_id
]);

$calificaciones_raw =
    $stmtCal->fetchAll(PDO::FETCH_ASSOC);

$calificaciones = [];

foreach ($calificaciones_raw as $c) {

    $calificaciones
        [$c['alumno_id']]
        [$c['unidad']] = $c;
}

?>


<div class="max-w-7xl mx-auto p-6 bg-slate-50 min-h-screen">


    <!-- =====================================================
         ENCABEZADO
    ====================================================== -->

    <div class="mb-10 flex flex-col md:flex-row md:items-end md:justify-between gap-6">

        <div>

            <span class="bg-indigo-100 text-indigo-700
                         px-4 py-2 rounded-full text-[10px]
                         font-black uppercase tracking-[0.25em]">

                Captura Manual

            </span>


            <h1 class="text-5xl font-black italic uppercase
                       tracking-tighter text-slate-800 mt-5">

                Subir Calificaciones

            </h1>


            <p class="text-slate-500 font-semibold mt-3">

                <?= htmlspecialchars($info['materia']) ?>

                · Grupo
                <?= htmlspecialchars($info['grupo']) ?>

                ·
                <?= htmlspecialchars($info['periodo']) ?>

            </p>

        </div>


        <a
            href="?modulo=calificaciones&asignacion_id=<?= $asignacion_id ?>"

            class="bg-slate-900 hover:bg-slate-700
                   text-white px-6 py-4 rounded-2xl
                   font-black uppercase text-[10px]
                   tracking-widest"
        >

            ← Volver

        </a>

    </div>


    <!-- =====================================================
         ESTADO
    ====================================================== -->

    <div class="mb-8 bg-white border border-slate-100
                rounded-3xl p-6 shadow-sm">

        <div class="flex flex-wrap items-center justify-between gap-4">

            <div>

                <p class="text-[10px] font-black uppercase
                          tracking-widest text-slate-400">

                    Estado de captura

                </p>


                <?php

                $estadoClase =
                    'bg-slate-100 text-slate-700';

                if ($estadoActual === 'borrador') {
                    $estadoClase =
                        'bg-amber-100 text-amber-700';
                }

                if ($estadoActual === 'cerrado') {
                    $estadoClase =
                        'bg-blue-100 text-blue-700';
                }

                if ($estadoActual === 'aprobado') {
                    $estadoClase =
                        'bg-emerald-100 text-emerald-700';
                }

                if ($estadoActual === 'devuelto') {
                    $estadoClase =
                        'bg-red-100 text-red-700';
                }

                ?>


                <span class="<?= $estadoClase ?>
                             inline-block mt-3
                             px-5 py-2 rounded-full
                             text-xs font-black uppercase
                             tracking-widest">

                    <?= htmlspecialchars($estadoActual) ?>

                </span>

            </div>


            <?php if ($estadoActual === 'cerrado'): ?>

                <div class="text-blue-700 font-bold text-sm">

                    Las calificaciones fueron enviadas
                    a Control Escolar.

                </div>

            <?php elseif ($estadoActual === 'aprobado'): ?>

                <div class="text-emerald-700 font-bold text-sm">

                    Las calificaciones fueron aprobadas.

                </div>

            <?php elseif ($estadoActual === 'devuelto'): ?>

                <div class="text-red-700 font-bold text-sm">

                    Control Escolar devolvió las calificaciones.
                    Puedes corregirlas y enviarlas nuevamente.

                </div>

            <?php endif; ?>

        </div>

    </div>


    <!-- =====================================================
         SIN ALUMNOS
    ====================================================== -->

    <?php if (!$alumnos): ?>

        <div class="bg-white p-16 rounded-[3rem]
                    text-center border border-slate-100 shadow-sm">

            <h2 class="text-2xl font-black
                       text-slate-700 uppercase">

                Sin alumnos asignados

            </h2>

        </div>


    <!-- =====================================================
         SIN UNIDADES
    ====================================================== -->

    <?php elseif (!$unidades): ?>

        <div class="bg-white p-16 rounded-[3rem]
                    text-center border border-slate-100 shadow-sm">

            <h2 class="text-2xl font-black
                       text-slate-700 uppercase">

                Sin unidades registradas

            </h2>

            <p class="text-slate-400 font-semibold mt-3">

                Primero deben definirse las unidades
                de la materia.

            </p>

        </div>


    <?php else: ?>


        <!-- =================================================
             FORMULARIO
        ================================================== -->

        <form method="POST">
<input type="hidden" name="cufa_csrf" value="<?= htmlspecialchars(cufaToken()) ?>">


            <div class="overflow-x-auto bg-white
                        rounded-[3rem] shadow-xl
                        border border-slate-100">


                <table class="min-w-full">


                    <thead class="bg-slate-900 text-white">

                        <tr>

                            <th class="p-5 text-left text-[10px]
                                       font-black uppercase tracking-widest">

                                Alumno

                            </th>


                            <?php foreach ($unidades as $u): ?>

                                <th class="p-5 text-center text-[10px]
                                           font-black uppercase tracking-widest">

                                    Unidad <?= (int) $u ?>

                                </th>

                            <?php endforeach; ?>

                        </tr>

                    </thead>


                    <tbody class="divide-y divide-slate-100">


                    <?php foreach ($alumnos as $a): ?>

                        <?php
                        $alumno_id =
                            (int) $a['alumno_id'];
                        ?>


                        <tr class="hover:bg-slate-50">


                            <td class="p-5 font-black text-slate-700
                                       whitespace-nowrap">

                                <?= htmlspecialchars($a['alumno']) ?>

                            </td>


                            <?php foreach ($unidades as $unidad): ?>


                                <?php

                                $unidad = (int) $unidad;

                                $actual =
                                    $calificaciones
                                        [$alumno_id]
                                        [$unidad]
                                    ?? null;

                                ?>


                                <td class="p-4 min-w-[300px]">


                                    <div class="grid grid-cols-3 gap-2">


                                        <!-- ACTIVIDADES -->

                                        <div>

                                            <label class="text-[9px]
                                                          font-black
                                                          uppercase
                                                          text-slate-400">

                                                Actividades

                                            </label>


                                            <input
                                                type="number"

                                                name="calificaciones[<?= $alumno_id ?>][<?= $unidad ?>][promedio_actividades]"

                                                min="0"
                                                max="100"
                                                step="0.01"

                                                value="<?= htmlspecialchars(
                                                    $actual['promedio_actividades']
                                                    ?? ''
                                                ) ?>"

                                                <?= !$puedeEditar
                                                    ? 'readonly'
                                                    : ''
                                                ?>

                                                class="w-full mt-2
                                                       bg-slate-50
                                                       border border-slate-200
                                                       rounded-xl p-3
                                                       text-center font-bold"
                                            >

                                        </div>


                                        <!-- EXAMEN -->

                                        <div>

                                            <label class="text-[9px]
                                                          font-black
                                                          uppercase
                                                          text-slate-400">

                                                Examen

                                            </label>


                                            <input
                                                type="number"

                                                name="calificaciones[<?= $alumno_id ?>][<?= $unidad ?>][calificacion_examen]"

                                                min="0"
                                                max="100"
                                                step="0.01"

                                                value="<?= htmlspecialchars(
                                                    $actual['calificacion_examen']
                                                    ?? ''
                                                ) ?>"

                                                <?= !$puedeEditar
                                                    ? 'readonly'
                                                    : ''
                                                ?>

                                                class="w-full mt-2
                                                       bg-slate-50
                                                       border border-slate-200
                                                       rounded-xl p-3
                                                       text-center font-bold"
                                            >

                                        </div>


                                        <!-- FINAL -->

                                        <div>

                                            <label class="text-[9px]
                                                          font-black
                                                          uppercase
                                                          text-emerald-500">

                                                Final

                                            </label>


                                            <input
                                                type="number"

                                                name="calificaciones[<?= $alumno_id ?>][<?= $unidad ?>][calificacion_final]"

                                                readonly min="0"
                                                max="100"
                                                step="0.01"

                                                value="<?= htmlspecialchars(
                                                    $actual['calificacion_final']
                                                    ?? ''
                                                ) ?>"

                                                <?= !$puedeEditar
                                                    ? 'readonly'
                                                    : ''
                                                ?>

                                                class="w-full mt-2
                                                       bg-emerald-50
                                                       border border-emerald-200
                                                       rounded-xl p-3
                                                       text-center
                                                       font-black
                                                       text-emerald-700"
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


            <!-- =================================================
                 BOTONES
            ================================================== -->

            <?php if ($puedeEditar): ?>

                <div class="mt-8 flex flex-col
                            md:flex-row justify-end gap-4">


                    <button
                        type="submit"
                        name="guardar_calificaciones"

                        class="bg-slate-800
                               hover:bg-slate-700
                               text-white px-8 py-4
                               rounded-2xl
                               font-black uppercase
                               text-xs tracking-widest"
                    >

                        Guardar borrador

                    </button>


                    <button
                        type="submit"
                        name="enviar_revision"

                        onclick="
                            return confirm(
                                '¿Deseas enviar las calificaciones a revisión? Después del envío no podrás modificarlas hasta que Control Escolar las devuelva.'
                            );
                        "

                        class="bg-indigo-600
                               hover:bg-indigo-700
                               text-white px-8 py-4
                               rounded-2xl
                               font-black uppercase
                               text-xs tracking-widest"
                    >

                        Enviar a revisión

                    </button>


                </div>

            <?php else: ?>


                <div class="mt-8 bg-blue-50
                            border border-blue-200
                            text-blue-700 p-6
                            rounded-2xl font-bold">

                    Las calificaciones están bloqueadas
                    porque ya fueron enviadas a revisión.

                </div>


            <?php endif; ?>


        </form>


    <?php endif; ?>


</div>
<script>
(function(){
 function calcular(celda){
  const a=celda.querySelector('input[name$="[promedio_actividades]"]');
  const e=celda.querySelector('input[name$="[calificacion_examen]"]');
  const f=celda.querySelector('input[name$="[calificacion_final]"]');
  if(a&&e&&f) f.value=a.value!==''&&e.value!==''?((Number(a.value)+Number(e.value))/2).toFixed(2):'';
 }
 document.querySelectorAll('td').forEach(celda=>{
  if(!celda.querySelector('input'))return;
  celda.addEventListener('input',()=>calcular(celda));
  calcular(celda);
 });
})();
</script>
