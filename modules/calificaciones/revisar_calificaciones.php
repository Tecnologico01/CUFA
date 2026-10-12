<?php
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/permisos.php';

validarPermiso('revisar_calificaciones');
require_once __DIR__ . '/../../includes/calificaciones_service.php';

/* =========================================================
   FILTROS
========================================================= */

$carrera_id = isset($_GET['carrera_id'])
    ? (int) $_GET['carrera_id']
    : 0;

$periodo_id = isset($_GET['periodo_id'])
    ? (int) $_GET['periodo_id']
    : 0;

$grupo_id = isset($_GET['grupo_id'])
    ? (int) $_GET['grupo_id']
    : 0;

$asignacion_id = isset($_GET['asignacion_id'])
    ? (int) $_GET['asignacion_id']
    : 0;


/* =========================================================
   PROCESAR APROBAR / DEVOLVER
========================================================= */

if (
    $_SERVER['REQUEST_METHOD'] === 'POST'
    && isset($_POST['accion_revision'])
) {

    $asignacionPost = isset($_POST['asignacion_id'])
        ? (int) $_POST['asignacion_id']
        : 0;

    $accion = $_POST['accion_revision'] ?? '';

    if ($asignacionPost <= 0) {
        exit('Asignación no válida.');
    }

    try {

        cufaValidarToken();
        cufaRevisar($pdo,$asignacionPost,$accion);
        $mensaje=$accion==='aprobar'?'Calificaciones aprobadas correctamente.':'Calificaciones devueltas al docente.';

        /* -----------------------------------------
           CONSERVAR FILTROS
        ----------------------------------------- */

        $query = http_build_query([
            'modulo' => 'revisar_calificaciones',
            'carrera_id' => (int) ($_POST['carrera_id'] ?? 0),
            'periodo_id' => (int) ($_POST['periodo_id'] ?? 0),
            'grupo_id' => (int) ($_POST['grupo_id'] ?? 0),
            'asignacion_id' => $asignacionPost
        ]);

        echo "
            <script>
                alert(" . json_encode($mensaje) . ");
                window.location.href = '?' + " . json_encode($query) . ";
            </script>
        ";

        exit;

    } catch (Throwable $e) {

        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        $errorRevision = $e->getMessage();
    }
}


/* =========================================================
   CATÁLOGOS
========================================================= */

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


/* =========================================================
   GRUPOS
========================================================= */

$grupos = [];

if ($carrera_id > 0 && $periodo_id > 0) {

    $stmtGrupos = $pdo->prepare("
        SELECT id, nombre
        FROM grupos
        WHERE carrera_id = ?
          AND periodo_id = ?
        ORDER BY nombre ASC
    ");

    $stmtGrupos->execute([
        $carrera_id,
        $periodo_id
    ]);

    $grupos = $stmtGrupos->fetchAll(PDO::FETCH_ASSOC);
}


/* =========================================================
   ASIGNACIONES
========================================================= */

$asignaciones = [];

if ($grupo_id > 0) {

    $stmtAsig = $pdo->prepare("
        SELECT
            ad.id,
            m.nombre AS materia,

            CONCAT(
                u.nombres, ' ',
                COALESCE(u.apellido_paterno, ''), ' ',
                COALESCE(u.apellido_materno, '')
            ) AS docente

        FROM asignaciones_docentes ad

        INNER JOIN materias m
            ON m.id = ad.materia_id

        INNER JOIN docentes d
            ON d.id = ad.docente_id

        INNER JOIN usuarios u
            ON u.id = d.usuario_id

        WHERE ad.grupo_id = ?

        ORDER BY m.nombre ASC
    ");

    $stmtAsig->execute([$grupo_id]);

    $asignaciones = $stmtAsig->fetchAll(PDO::FETCH_ASSOC);
}


/* =========================================================
   VARIABLES
========================================================= */

$info = null;

$alumnos = [];

$unidades = [];

$calificaciones = [];

$estadoActual = null;

$conteoEstados = [
    'borrador' => 0,
    'cerrado' => 0,
    'aprobado' => 0,
    'devuelto' => 0
];


/* =========================================================
   INFORMACIÓN DE LA ASIGNACIÓN
========================================================= */

if ($asignacion_id > 0) {

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

            CONCAT(
                u.nombres, ' ',
                COALESCE(u.apellido_paterno, ''), ' ',
                COALESCE(u.apellido_materno, '')
            ) AS docente

        FROM asignaciones_docentes ad

        INNER JOIN materias m
            ON m.id = ad.materia_id

        INNER JOIN grupos g
            ON g.id = ad.grupo_id

        INNER JOIN periodos p
            ON p.id = ad.periodo_id

        INNER JOIN carreras c
            ON c.id = g.carrera_id

        INNER JOIN docentes d
            ON d.id = ad.docente_id

        INNER JOIN usuarios u
            ON u.id = d.usuario_id

        WHERE ad.id = ?

        LIMIT 1
    ");

    $stmtInfo->execute([
        $asignacion_id
    ]);

    $info = $stmtInfo->fetch(PDO::FETCH_ASSOC);


    /* =====================================================
       SI EXISTE ASIGNACIÓN
    ===================================================== */

    if ($info) {


        /* =================================================
           ALUMNOS
        ================================================= */

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

        $alumnos = $stmtAlumnos->fetchAll(
            PDO::FETCH_ASSOC
        );


        /* =================================================
           UNIDADES DESDE CALIFICACIONES
        ================================================= */

        [, , $unidades, $filasRevision] = cufaContexto($pdo, $asignacion_id);
        $raw = array_values($filasRevision);

        foreach ($raw as $c) {

            $alumno = (int) $c['alumno_id'];

            $unidad = (int) $c['unidad'];

            $calificaciones[$alumno][$unidad] = $c;


            /* =============================================
               CONTAR ESTADOS
            ============================================= */

            $estado = $c['estado'] ?? 'borrador';

            if (isset($conteoEstados[$estado])) {
                $conteoEstados[$estado]++;
            }
        }


        /* =================================================
           DETERMINAR ESTADO GENERAL
        ================================================= */

        $totalCalificaciones = count($raw);

        if ($totalCalificaciones > 0) {

            if (
                $conteoEstados['aprobado']
                === $totalCalificaciones
            ) {

                $estadoActual = 'aprobado';

            } elseif (
                $conteoEstados['cerrado']
                === $totalCalificaciones
            ) {

                $estadoActual = 'cerrado';

            } elseif (
                $conteoEstados['devuelto']
                === $totalCalificaciones
            ) {

                $estadoActual = 'devuelto';

            } elseif (
                $conteoEstados['borrador']
                === $totalCalificaciones
            ) {

                $estadoActual = 'borrador';

            } else {

                $estadoActual = 'mixto';
            }
        }
    }
}

?>


<div class="max-w-7xl mx-auto p-6 bg-slate-50 min-h-screen">


    <!-- =====================================================
         ENCABEZADO
    ====================================================== -->

    <div class="mb-10">

        <span class="bg-purple-100 text-purple-700
                     px-4 py-2 rounded-full
                     text-[10px] font-black uppercase
                     tracking-[0.25em]">

            Revisión Académica

        </span>


        <h1 class="text-5xl font-black italic uppercase
                   tracking-tighter text-slate-800 mt-5">

            Revisar Calificaciones

        </h1>


        <p class="text-slate-500 font-semibold mt-3">

            Consulta y validación de calificaciones
            por grupo y asignatura.

        </p>

    </div>


    <!-- =====================================================
         ERROR
    ====================================================== -->

    <?php if (!empty($errorRevision)): ?>

        <div class="mb-8 bg-red-50
                    border border-red-200
                    text-red-700 p-6
                    rounded-3xl font-bold">

            <?= htmlspecialchars($errorRevision) ?>

        </div>

    <?php endif; ?>


    <!-- =====================================================
         FILTROS
    ====================================================== -->

    <form
        method="GET"
        class="bg-white p-8 rounded-[3rem]
               border border-slate-100
               shadow-sm mb-10"
    >

        <input
            type="hidden"
            name="modulo"
            value="revisar_calificaciones"
        >


        <div class="grid grid-cols-1
                    md:grid-cols-4 gap-6">


            <!-- CARRERA -->

            <div>

                <label class="block text-[10px]
                              font-black text-slate-400
                              uppercase tracking-widest mb-2">

                    Carrera

                </label>


                <select
                    name="carrera_id"

                    onchange="
                        this.form.grupo_id.value='';
                        this.form.asignacion_id.value='';
                        this.form.submit();
                    "

                    class="w-full p-4 rounded-2xl
                           bg-slate-50 border
                           border-slate-200
                           font-bold outline-none"
                >

                    <option value="">
                        Seleccionar
                    </option>


                    <?php foreach ($carreras as $c): ?>

                        <option
                            value="<?= (int) $c['id'] ?>"

                            <?= $carrera_id === (int) $c['id']
                                ? 'selected'
                                : ''
                            ?>
                        >

                            <?= htmlspecialchars($c['nombre']) ?>

                        </option>

                    <?php endforeach; ?>

                </select>

            </div>


            <!-- PERIODO -->

            <div>

                <label class="block text-[10px]
                              font-black text-slate-400
                              uppercase tracking-widest mb-2">

                    Periodo

                </label>


                <select
                    name="periodo_id"

                    onchange="
                        this.form.grupo_id.value='';
                        this.form.asignacion_id.value='';
                        this.form.submit();
                    "

                    class="w-full p-4 rounded-2xl
                           bg-slate-50 border
                           border-slate-200
                           font-bold outline-none"
                >

                    <option value="">
                        Seleccionar
                    </option>


                    <?php foreach ($periodos as $p): ?>

                        <option
                            value="<?= (int) $p['id'] ?>"

                            <?= $periodo_id === (int) $p['id']
                                ? 'selected'
                                : ''
                            ?>
                        >

                            <?= htmlspecialchars($p['nombre']) ?>

                        </option>

                    <?php endforeach; ?>

                </select>

            </div>


            <!-- GRUPO -->

            <div>

                <label class="block text-[10px]
                              font-black text-slate-400
                              uppercase tracking-widest mb-2">

                    Grupo

                </label>


                <select
                    name="grupo_id"

                    onchange="
                        this.form.asignacion_id.value='';
                        this.form.submit();
                    "

                    class="w-full p-4 rounded-2xl
                           bg-slate-50 border
                           border-slate-200
                           font-bold outline-none"

                    <?= (!$carrera_id || !$periodo_id)
                        ? 'disabled'
                        : ''
                    ?>
                >

                    <option value="">
                        Seleccionar
                    </option>


                    <?php foreach ($grupos as $g): ?>

                        <option
                            value="<?= (int) $g['id'] ?>"

                            <?= $grupo_id === (int) $g['id']
                                ? 'selected'
                                : ''
                            ?>
                        >

                            <?= htmlspecialchars($g['nombre']) ?>

                        </option>

                    <?php endforeach; ?>

                </select>

            </div>


            <!-- ASIGNATURA -->

            <div>

                <label class="block text-[10px]
                              font-black text-slate-400
                              uppercase tracking-widest mb-2">

                    Asignatura

                </label>


                <select
                    name="asignacion_id"

                    onchange="this.form.submit()"

                    class="w-full p-4 rounded-2xl
                           bg-slate-50 border
                           border-slate-200
                           font-bold outline-none"

                    <?= !$grupo_id
                        ? 'disabled'
                        : ''
                    ?>
                >

                    <option value="">
                        Seleccionar
                    </option>


                    <?php foreach ($asignaciones as $a): ?>

                        <option
                            value="<?= (int) $a['id'] ?>"

                            <?= $asignacion_id === (int) $a['id']
                                ? 'selected'
                                : ''
                            ?>
                        >

                            <?= htmlspecialchars($a['materia']) ?>
                            -
                            <?= htmlspecialchars($a['docente']) ?>

                        </option>

                    <?php endforeach; ?>

                </select>

            </div>


        </div>

    </form>


    <!-- =====================================================
         ASIGNACIÓN NO EXISTE
    ====================================================== -->

    <?php if ($asignacion_id > 0 && !$info): ?>


        <div class="bg-red-50 text-red-700
                    border border-red-200
                    p-8 rounded-3xl font-bold">

            La asignación seleccionada no existe.

        </div>


    <?php elseif ($info): ?>


        <!-- =================================================
             INFORMACIÓN ASIGNACIÓN
        ================================================== -->

        <div class="bg-white p-8 rounded-[3rem]
                    border border-slate-100
                    shadow-sm mb-6">


            <div class="grid grid-cols-1
                        md:grid-cols-4 gap-6">


                <div>

                    <p class="text-[10px] font-black
                              uppercase text-slate-400
                              tracking-widest">

                        Carrera

                    </p>

                    <h3 class="text-sm font-black
                               text-slate-800 mt-2">

                        <?= htmlspecialchars($info['carrera']) ?>

                    </h3>

                </div>


                <div>

                    <p class="text-[10px] font-black
                              uppercase text-slate-400
                              tracking-widest">

                        Grupo

                    </p>

                    <h3 class="text-sm font-black
                               text-slate-800 mt-2">

                        <?= htmlspecialchars($info['grupo']) ?>

                    </h3>

                </div>


                <div>

                    <p class="text-[10px] font-black
                              uppercase text-slate-400
                              tracking-widest">

                        Asignatura

                    </p>

                    <h3 class="text-sm font-black
                               text-purple-700 mt-2">

                        <?= htmlspecialchars($info['materia']) ?>

                    </h3>

                </div>


                <div>

                    <p class="text-[10px] font-black
                              uppercase text-slate-400
                              tracking-widest">

                        Docente

                    </p>

                    <h3 class="text-sm font-black
                               text-slate-800 mt-2">

                        <?= htmlspecialchars($info['docente']) ?>

                    </h3>

                </div>


            </div>

        </div>


        <!-- =================================================
             ESTADO
        ================================================== -->

        <?php if ($estadoActual): ?>


            <?php

            $estadoClase =
                'bg-slate-100 text-slate-700 border-slate-200';

            $estadoTitulo = 'Estado desconocido';

            $estadoDescripcion = '';


            if ($estadoActual === 'borrador') {

                $estadoClase =
                    'bg-amber-50 text-amber-700 border-amber-200';

                $estadoTitulo =
                    'Borrador';

                $estadoDescripcion =
                    'El docente todavía está capturando las calificaciones.';
            }


            elseif ($estadoActual === 'cerrado') {

                $estadoClase =
                    'bg-blue-50 text-blue-700 border-blue-200';

                $estadoTitulo =
                    'Pendiente de revisión';

                $estadoDescripcion =
                    'El docente terminó la captura y envió las calificaciones para revisión.';
            }


            elseif ($estadoActual === 'aprobado') {

                $estadoClase =
                    'bg-emerald-50 text-emerald-700 border-emerald-200';

                $estadoTitulo =
                    'Calificaciones aprobadas';

                $estadoDescripcion =
                    'Control Escolar ya aprobó estas calificaciones.';
            }


            elseif ($estadoActual === 'devuelto') {

                $estadoClase =
                    'bg-red-50 text-red-700 border-red-200';

                $estadoTitulo =
                    'Devuelto al docente';

                $estadoDescripcion =
                    'Las calificaciones fueron devueltas para realizar correcciones.';
            }


            elseif ($estadoActual === 'mixto') {

                $estadoClase =
                    'bg-orange-50 text-orange-700 border-orange-200';

                $estadoTitulo =
                    'Estados inconsistentes';

                $estadoDescripcion =
                    'Existen calificaciones con diferentes estados dentro de la misma asignación.';
            }

            ?>


            <div class="<?= $estadoClase ?>
                        border rounded-3xl
                        p-6 mb-8">


                <div class="flex flex-col
                            md:flex-row
                            md:items-center
                            md:justify-between
                            gap-5">


                    <div>

                        <p class="text-[10px]
                                  font-black uppercase
                                  tracking-widest opacity-70">

                            Estado de calificaciones

                        </p>


                        <h3 class="text-xl
                                   font-black mt-1">

                            <?= htmlspecialchars($estadoTitulo) ?>

                        </h3>


                        <p class="text-sm
                                  font-semibold mt-1">

                            <?= htmlspecialchars($estadoDescripcion) ?>

                        </p>

                    </div>


                    <span class="bg-white/70
                                 px-5 py-3
                                 rounded-2xl
                                 text-xs
                                 font-black uppercase
                                 tracking-widest">

                        <?= htmlspecialchars($estadoActual) ?>

                    </span>


                </div>

            </div>


        <?php endif; ?>


        <!-- =================================================
             SIN ALUMNOS
        ================================================== -->

        <?php if (!$alumnos): ?>


            <div class="bg-white p-16
                        rounded-[3rem]
                        text-center
                        border border-slate-100
                        shadow-sm">

                <h2 class="text-2xl font-black
                           text-slate-700 uppercase">

                    Sin alumnos asignados

                </h2>

            </div>


        <?php elseif (!$unidades): ?>


            <!-- =============================================
                 SIN CALIFICACIONES
            ============================================== -->

            <div class="bg-white p-16
                        rounded-[3rem]
                        text-center
                        border border-slate-100
                        shadow-sm">

                <h2 class="text-2xl font-black
                           text-slate-700 uppercase">

                    Sin calificaciones registradas

                </h2>

            </div>


        <?php else: ?>


            <!-- =============================================
                 TABLA
            ============================================== -->

            <div class="overflow-x-auto
                        bg-white rounded-[3rem]
                        shadow-xl
                        border border-slate-100">


                <table class="min-w-full">


                    <thead class="bg-slate-900 text-white">


                        <tr>


                            <th class="p-5 text-left
                                       text-[10px]
                                       font-black uppercase
                                       tracking-widest">

                                Alumno

                            </th>


                            <?php foreach ($unidades as $u): ?>


                                <th class="p-5 text-center
                                           text-[10px]
                                           font-black uppercase
                                           tracking-widest">

                                    Unidad <?= (int) $u ?>

                                </th>


                            <?php endforeach; ?>


                            <th class="p-5 text-center
                                       text-[10px]
                                       font-black uppercase
                                       tracking-widest
                                       bg-emerald-600">

                                Final

                            </th>


                        </tr>


                    </thead>


                    <tbody class="divide-y divide-slate-100">


                    <?php foreach ($alumnos as $a): ?>


                        <?php

                        $alumno_id =
                            (int) $a['alumno_id'];

                        $suma = 0;

                        $contador = 0;

                        ?>


                        <tr class="hover:bg-slate-50">


                            <td class="p-5 font-bold
                                       text-slate-700
                                       whitespace-nowrap">

                                <?= htmlspecialchars($a['alumno']) ?>

                            </td>


                            <?php foreach ($unidades as $u): ?>


                                <?php

                                $unidad = (int) $u;

                                $cal =
                                    $calificaciones
                                        [$alumno_id]
                                        [$unidad]
                                    ?? null;

                                $final =
                                    $cal
                                    ? (float) $cal['calificacion_final']
                                    : 0;


                                if ($cal) {

                                    $suma += $final;

                                    $contador++;
                                }

                                ?>


                                <td class="p-5 text-center">


                                    <?php if ($cal): ?>


                                        <div class="flex
                                                    flex-col
                                                    items-center">


                                            <span
                                                class="font-black text-xl
                                                <?= $final >= 70
                                                    ? 'text-emerald-600'
                                                    : 'text-red-500'
                                                ?>"
                                            >

                                                <?= number_format(
                                                    $final,
                                                    1
                                                ) ?>

                                            </span>


                                            <span class="text-[9px]
                                                         text-slate-400
                                                         uppercase
                                                         font-bold">

                                                Act:
                                                <?= number_format(
                                                    (float) $cal['promedio_actividades'],
                                                    1
                                                ) ?>

                                                |

                                                Ex:
                                                <?= number_format(
                                                    (float) $cal['calificacion_examen'],
                                                    1
                                                ) ?>

                                            </span>


                                        </div>


                                    <?php else: ?>


                                        <span class="text-slate-300
                                                     font-black">

                                            —

                                        </span>


                                    <?php endif; ?>


                                </td>


                            <?php endforeach; ?>


                            <!-- PROMEDIO FINAL -->

                            <td class="p-5 text-center
                                       bg-emerald-50">


                                <?php

                                $promedio =
                                    $contador > 0
                                    ? $suma / $contador
                                    : 0;

                                ?>


                                <span
                                    class="font-black text-2xl
                                    <?= $promedio >= 70
                                        ? 'text-emerald-700'
                                        : 'text-red-600'
                                    ?>"
                                >

                                    <?= number_format(
                                        $promedio,
                                        1
                                    ) ?>

                                </span>


                            </td>


                        </tr>


                    <?php endforeach; ?>


                    </tbody>


                </table>


            </div>


            <!-- =============================================
                 ACCIONES DE CONTROL ESCOLAR
            ============================================== -->

            <?php if ($estadoActual === 'cerrado'): ?>


                <form
                    method="POST"
                    class="mt-8 bg-white
                           border border-slate-100
                           rounded-[2.5rem]
                           shadow-sm p-8"
                ><input type="hidden" name="cufa_csrf" value="<?= htmlspecialchars(cufaToken()) ?>">


                    <input
                        type="hidden"
                        name="asignacion_id"
                        value="<?= $asignacion_id ?>"
                    >


                    <input
                        type="hidden"
                        name="carrera_id"
                        value="<?= $carrera_id ?>"
                    >


                    <input
                        type="hidden"
                        name="periodo_id"
                        value="<?= $periodo_id ?>"
                    >


                    <input
                        type="hidden"
                        name="grupo_id"
                        value="<?= $grupo_id ?>"
                    >


                    <div class="flex flex-col
                                lg:flex-row
                                lg:items-center
                                lg:justify-between
                                gap-6">


                        <div>


                            <h3 class="text-xl
                                       font-black
                                       text-slate-800">

                                Revisión de calificaciones

                            </h3>


                            <p class="text-slate-500
                                      font-semibold mt-2">

                                Verifica las calificaciones
                                antes de aprobarlas.

                            </p>


                        </div>


                        <div class="flex flex-col
                                    sm:flex-row gap-4">


                            <!-- DEVOLVER -->

                            <button
                                type="submit"

                                name="accion_revision"

                                value="devolver"

                                onclick="
                                    return confirm(
                                        '¿Deseas devolver estas calificaciones al docente para que realice correcciones?'
                                    );
                                "

                                class="bg-red-100
                                       hover:bg-red-200
                                       text-red-700
                                       px-8 py-4
                                       rounded-2xl
                                       font-black
                                       uppercase
                                       text-xs
                                       tracking-widest"
                            >

                                Devolver al docente

                            </button>


                            <!-- APROBAR -->

                            <button
                                type="submit"

                                name="accion_revision"

                                value="aprobar"

                                onclick="
                                    return confirm(
                                        '¿Confirmas que revisaste las calificaciones y deseas aprobarlas?'
                                    );
                                "

                                class="bg-emerald-600
                                       hover:bg-emerald-700
                                       text-white
                                       px-8 py-4
                                       rounded-2xl
                                       font-black
                                       uppercase
                                       text-xs
                                       tracking-widest"
                            >

                                Aprobar calificaciones

                            </button>


                        </div>


                    </div>


                </form>


            <?php elseif ($estadoActual === 'aprobado'): ?>


                <div class="mt-8
                            bg-emerald-50
                            border border-emerald-200
                            rounded-3xl p-7
                            text-emerald-700">


                    <h3 class="font-black text-lg">

                        ✓ Calificaciones aprobadas

                    </h3>


                    <p class="font-semibold mt-2">

                        Estas calificaciones ya fueron
                        aprobadas por Control Escolar.

                    </p>


                    <p class="font-semibold mt-1">

                        Ya pueden utilizarse para
                        generar el acta correspondiente.

                    </p>


                </div>


            <?php elseif ($estadoActual === 'borrador'): ?>


                <div class="mt-8
                            bg-amber-50
                            border border-amber-200
                            rounded-3xl p-7
                            text-amber-700">


                    <h3 class="font-black text-lg">

                        Captura en proceso

                    </h3>


                    <p class="font-semibold mt-2">

                        El docente todavía no ha enviado
                        estas calificaciones a revisión.

                    </p>


                </div>


            <?php elseif ($estadoActual === 'devuelto'): ?>


                <div class="mt-8
                            bg-red-50
                            border border-red-200
                            rounded-3xl p-7
                            text-red-700">


                    <h3 class="font-black text-lg">

                        Esperando correcciones

                    </h3>


                    <p class="font-semibold mt-2">

                        Las calificaciones fueron devueltas
                        al docente.

                    </p>


                    <p class="font-semibold mt-1">

                        Cuando el docente termine las
                        correcciones deberá enviarlas
                        nuevamente a revisión.

                    </p>


                </div>


            <?php elseif ($estadoActual === 'mixto'): ?>


                <div class="mt-8
                            bg-orange-50
                            border border-orange-200
                            rounded-3xl p-7
                            text-orange-700">


                    <h3 class="font-black text-lg">

                        Atención: estados inconsistentes

                    </h3>


                    <p class="font-semibold mt-2">

                        Existen registros de esta asignación
                        con diferentes estados.

                    </p>


                    <p class="font-semibold mt-1">

                        No se puede aprobar ni devolver
                        hasta corregir esta inconsistencia.

                    </p>


                </div>


            <?php endif; ?>


        <?php endif; ?>


    <?php else: ?>


        <!-- =================================================
             SIN SELECCIÓN
        ================================================== -->

        <div class="bg-white p-16
                    rounded-[3rem]
                    text-center
                    border border-slate-100
                    shadow-sm">


            <h2 class="text-2xl font-black
                       text-slate-700 uppercase">

                Selecciona una asignatura

            </h2>


            <p class="text-slate-400
                      font-semibold mt-3">

                Usa los filtros superiores para
                revisar las calificaciones.

            </p>


        </div>


    <?php endif; ?>


</div>