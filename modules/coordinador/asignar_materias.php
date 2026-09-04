<?php

require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/permisos.php';

validarPermiso('asignar_materias');

$mensaje = null;

$periodo_id = isset($_GET['periodo_id'])
    ? (int) $_GET['periodo_id']
    : 0;

/* =========================
   GUARDAR ASIGNACIÓN
========================= */

if($_SERVER['REQUEST_METHOD'] === 'POST'){

    $docente_id = isset($_POST['docente_id']) ? (int) $_POST['docente_id'] : 0;
    $grupo_id   = isset($_POST['grupo_id']) ? (int) $_POST['grupo_id'] : 0;
    $materia_id = isset($_POST['materia_id']) ? (int) $_POST['materia_id'] : 0;
    $periodo_id = isset($_POST['periodo_id']) ? (int) $_POST['periodo_id'] : 0;

    if($docente_id <= 0 || $grupo_id <= 0 || $materia_id <= 0 || $periodo_id <= 0){

        $mensaje = [
            "tipo" => "error",
            "texto" => "Faltan datos obligatorios."
        ];

    }else{

        try{

            $pdo->beginTransaction();

            /* VALIDAR DOCENTE */

            $stmt = $pdo->prepare("
                SELECT id
                FROM docentes
                WHERE id = ?
            ");

            $stmt->execute([$docente_id]);

            if(!$stmt->fetch()){
                throw new Exception("El docente seleccionado no existe.");
            }

            /* VALIDAR MATERIA */

            $stmt = $pdo->prepare("
                SELECT id, carrera_id, total_unidades
                FROM materias
                WHERE id = ?
            ");

            $stmt->execute([$materia_id]);

            $materia = $stmt->fetch(PDO::FETCH_ASSOC);

            if(!$materia){
                throw new Exception("La asignatura seleccionada no existe.");
            }

            if((int)$materia['total_unidades'] <= 0){
                throw new Exception("La asignatura no tiene parciales configurados.");
            }

            /* VALIDAR GRUPO */

            $stmt = $pdo->prepare("
                SELECT id, carrera_id, periodo_id
                FROM grupos
                WHERE id = ?
            ");

            $stmt->execute([$grupo_id]);

            $grupo = $stmt->fetch(PDO::FETCH_ASSOC);

            if(!$grupo){
                throw new Exception("El grupo seleccionado no existe.");
            }

            if((int)$grupo['periodo_id'] !== $periodo_id){
                throw new Exception("El grupo no pertenece al periodo seleccionado.");
            }

            if((int)$grupo['carrera_id'] !== (int)$materia['carrera_id']){
                throw new Exception("La asignatura no pertenece a la carrera del grupo.");
            }

            /* VALIDAR PERIODO */

            $stmt = $pdo->prepare("
                SELECT id, fecha_inicio, fecha_fin
                FROM periodos
                WHERE id = ?
            ");

            $stmt->execute([$periodo_id]);

            $periodo = $stmt->fetch(PDO::FETCH_ASSOC);

            if(!$periodo || empty($periodo['fecha_inicio']) || empty($periodo['fecha_fin'])){
                throw new Exception("El periodo no tiene fechas válidas.");
            }

            $inicio = new DateTime($periodo['fecha_inicio']);
            $fin = new DateTime($periodo['fecha_fin']);

            if($inicio >= $fin){
                throw new Exception("Las fechas del periodo son inválidas.");
            }

            /* VALIDAR DUPLICADO */

            $stmt = $pdo->prepare("
                SELECT id
                FROM asignaciones_docentes
                WHERE docente_id = ?
                AND grupo_id = ?
                AND materia_id = ?
                AND periodo_id = ?
            ");

            $stmt->execute([
                $docente_id,
                $grupo_id,
                $materia_id,
                $periodo_id
            ]);

            if($stmt->fetch()){
                throw new Exception("Esta asignación ya existe.");
            }

            /* INSERTAR ASIGNACIÓN */

            $stmt = $pdo->prepare("
                INSERT INTO asignaciones_docentes
                (
                    docente_id,
                    grupo_id,
                    materia_id,
                    periodo_id
                )
                VALUES
                (
                    ?, ?, ?, ?
                )
            ");

            $stmt->execute([
                $docente_id,
                $grupo_id,
                $materia_id,
                $periodo_id
            ]);

            /* VALIDAR PARCIALES EXISTENTES */

            $stmt = $pdo->prepare("
                SELECT COUNT(*)
                FROM parciales
                WHERE grupo_id = ?
                AND materia_id = ?
                AND periodo_id = ?
            ");

            $stmt->execute([
                $grupo_id,
                $materia_id,
                $periodo_id
            ]);

            if($stmt->fetchColumn() > 0){
                throw new Exception("Los parciales de esta asignatura ya fueron generados.");
            }

            /* CREAR PARCIALES */

            $total_parciales = (int)$materia['total_unidades'];
            $carrera_id = (int)$materia['carrera_id'];

            $dias_total = $inicio->diff($fin)->days;

            if($dias_total <= 0){
                throw new Exception("No se pueden calcular los parciales con estas fechas.");
            }

            $dias_por_parcial = ceil($dias_total / $total_parciales);

            for($i = 1; $i <= $total_parciales; $i++){

                $fecha_inicio_parcial = clone $inicio;
                $fecha_inicio_parcial->modify('+' . (($i - 1) * $dias_por_parcial) . ' days');

                $fecha_fin_parcial = clone $fecha_inicio_parcial;
                $fecha_fin_parcial->modify('+' . $dias_por_parcial . ' days');

                if($fecha_fin_parcial > $fin){
                    $fecha_fin_parcial = clone $fin;
                }

                $activo = (
                    date('Y-m-d') >= $fecha_inicio_parcial->format('Y-m-d') &&
                    date('Y-m-d') <= $fecha_fin_parcial->format('Y-m-d')
                ) ? 1 : 0;

                $stmt = $pdo->prepare("
                    INSERT INTO parciales
                    (
                        periodo_id,
                        carrera_id,
                        materia_id,
                        grupo_id,
                        numero,
                        fecha_inicio,
                        fecha_fin,
                        activo
                    )
                    VALUES
                    (
                        ?, ?, ?, ?, ?, ?, ?, ?
                    )
                ");

                $stmt->execute([
                    $periodo_id,
                    $carrera_id,
                    $materia_id,
                    $grupo_id,
                    $i,
                    $fecha_inicio_parcial->format('Y-m-d'),
                    $fecha_fin_parcial->format('Y-m-d'),
                    $activo
                ]);
            }

            $pdo->commit();

            $mensaje = [
                "tipo" => "success",
                "texto" => "Asignación y parciales creados correctamente."
            ];

        }catch(Exception $e){

            if($pdo->inTransaction()){
                $pdo->rollBack();
            }

            $mensaje = [
                "tipo" => "error",
                "texto" => $e->getMessage()
            ];
        }
    }
}

/* =========================
   CONSULTAS
========================= */

$periodos = $pdo->query("
    SELECT id, nombre
    FROM periodos
    WHERE activo = 1
    ORDER BY id DESC
")->fetchAll(PDO::FETCH_ASSOC);

$docentes = $pdo->query("
    SELECT
        d.id,
        CONCAT(
            u.nombres, ' ',
            u.apellido_paterno, ' ',
            u.apellido_materno
        ) AS nombre_completo
    FROM docentes d
    INNER JOIN usuarios u
        ON u.id = d.usuario_id
    ORDER BY u.nombres ASC
")->fetchAll(PDO::FETCH_ASSOC);

$materias = [];
$asignadas = [];
$asignaciones = [];

if($periodo_id > 0){

    $stmt = $pdo->prepare("
        SELECT DISTINCT
            m.id,
            m.nombre,
            m.carrera_id
        FROM materias m
        INNER JOIN grupos g
            ON g.carrera_id = m.carrera_id
        WHERE g.periodo_id = ?
        ORDER BY m.nombre ASC
    ");

    $stmt->execute([$periodo_id]);

    $materias = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $stmt = $pdo->prepare("
        SELECT materia_id
        FROM asignaciones_docentes
        WHERE periodo_id = ?
    ");

    $stmt->execute([$periodo_id]);

    $asignadas = $stmt->fetchAll(PDO::FETCH_COLUMN);

    $stmt = $pdo->prepare("
        SELECT
            ad.id,
            CONCAT(
                u.nombres, ' ',
                u.apellido_paterno, ' ',
                u.apellido_materno
            ) AS docente,
            m.nombre AS materia,
            g.nombre AS grupo,
            p.nombre AS periodo
        FROM asignaciones_docentes ad
        INNER JOIN docentes d
            ON d.id = ad.docente_id
        INNER JOIN usuarios u
            ON u.id = d.usuario_id
        INNER JOIN materias m
            ON m.id = ad.materia_id
        INNER JOIN grupos g
            ON g.id = ad.grupo_id
        INNER JOIN periodos p
            ON p.id = ad.periodo_id
        WHERE ad.periodo_id = ?
        ORDER BY g.nombre ASC, m.nombre ASC
    ");

    $stmt->execute([$periodo_id]);

    $asignaciones = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

?>

<div class="max-w-7xl mx-auto p-6 bg-slate-50 min-h-screen font-sans">

    <div class="flex flex-col md:flex-row justify-between items-end mb-10 gap-6">

        <div>
            <h1 class="text-4xl font-black text-slate-800 uppercase italic tracking-tighter leading-none">
                Control de
                <span class="text-indigo-600">
                    Asignaciones
                </span>
            </h1>

            <div class="flex items-center gap-2 mt-2">
                <span class="px-2 py-0.5 bg-indigo-100 text-indigo-700 text-[10px] font-black rounded-lg uppercase italic border border-indigo-200">
                    Gestión Académica
                </span>

                <p class="text-xs text-slate-400 font-bold uppercase tracking-widest italic">
                    Vincular asignaturas y docentes por periodo
                </p>
            </div>
        </div>

        <div class="w-full md:w-72">

            <form method="GET" id="formPeriodo">

                <input type="hidden" name="modulo" value="asignar_materias">

                <label class="text-[9px] font-black text-slate-400 uppercase ml-1 tracking-[0.1em]">
                    Periodo de Gestión
                </label>

                <select
                    name="periodo_id"
                    onchange="this.form.submit()"
                    class="w-full p-3 bg-white border-2 border-slate-200 rounded-2xl font-bold text-slate-700 focus:border-indigo-500 outline-none shadow-sm transition-all cursor-pointer"
                >

                    <option value="">
                        Seleccionar periodo activo...
                    </option>

                    <?php foreach($periodos as $p): ?>

                        <option
                            value="<?= $p['id'] ?>"
                            <?= $periodo_id == $p['id'] ? 'selected' : '' ?>
                        >
                            <?= htmlspecialchars($p['nombre']) ?>
                        </option>

                    <?php endforeach; ?>

                </select>

            </form>

        </div>
    </div>

    <?php if($mensaje): ?>

        <div class="mb-8 animate-fade-in">

            <div class="<?= $mensaje['tipo'] === 'success'
                ? 'bg-emerald-50 border-emerald-200 text-emerald-700'
                : 'bg-red-50 border-red-200 text-red-700'
            ?> p-4 rounded-[1.5rem] border flex items-center gap-3 shadow-sm">

                <div class="w-2 h-2 rounded-full <?= $mensaje['tipo'] === 'success'
                    ? 'bg-emerald-500'
                    : 'bg-red-500'
                ?> animate-pulse"></div>

                <span class="text-[11px] font-black uppercase tracking-widest italic">
                    <?= htmlspecialchars($mensaje['texto']) ?>
                </span>

            </div>

        </div>

    <?php endif; ?>

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">

        <div class="lg:col-span-4">

            <?php if($periodo_id > 0): ?>

                <div class="bg-indigo-900 p-8 rounded-[2.5rem] shadow-xl border border-indigo-800 text-white sticky top-6">

                    <div class="flex items-center gap-2 mb-8 border-b border-indigo-800 pb-4">
                        <div class="w-2 h-2 bg-purple-400 rounded-full"></div>

                        <h3 class="text-[11px] font-black uppercase text-indigo-300 tracking-widest italic">
                            Registrar Asignación
                        </h3>
                    </div>

                    <form method="POST" class="space-y-6">

                        <input type="hidden" name="periodo_id" value="<?= $periodo_id ?>">

                        <div class="space-y-1.5">

                            <label class="text-[9px] font-black text-indigo-300 uppercase ml-1 tracking-widest">
                                Docente
                            </label>

                            <select
                                name="docente_id"
                                required
                                class="w-full p-4 bg-indigo-800 border-none rounded-2xl font-bold text-white text-xs outline-none focus:ring-2 focus:ring-purple-400 transition-all cursor-pointer"
                            >

                                <option value="">
                                    Seleccionar docente...
                                </option>

                                <?php foreach($docentes as $d): ?>

                                    <option value="<?= $d['id'] ?>">
                                        <?= htmlspecialchars($d['nombre_completo']) ?>
                                    </option>

                                <?php endforeach; ?>

                            </select>
                        </div>

                        <div class="space-y-1.5">

                            <label class="text-[9px] font-black text-indigo-300 uppercase ml-1 tracking-widest">
                                Asignatura
                            </label>

                            <select
                                name="materia_id"
                                id="materiaSelect"
                                required
                                class="w-full p-4 bg-indigo-800 border-none rounded-2xl font-bold text-white text-xs outline-none focus:ring-2 focus:ring-purple-400 transition-all cursor-pointer"
                            >

                                <option value="">
                                    Seleccionar asignatura...
                                </option>

                                <?php foreach($materias as $m): ?>

                                    <?php
                                        $status = in_array($m['id'], $asignadas)
                                            ? "● "
                                            : "○ ";
                                    ?>

                                    <option
                                        value="<?= $m['id'] ?>"
                                        data-carrera="<?= $m['carrera_id'] ?>"
                                    >
                                        <?= $status . htmlspecialchars($m['nombre']) ?>
                                    </option>

                                <?php endforeach; ?>

                            </select>
                        </div>

                        <div class="space-y-1.5">

                            <label class="text-[9px] font-black text-indigo-300 uppercase ml-1 tracking-widest">
                                Grupo
                            </label>

                            <select
                                name="grupo_id"
                                id="grupoSelect"
                                required
                                class="w-full p-4 bg-indigo-800 border-none rounded-2xl font-bold text-white text-xs outline-none focus:ring-2 focus:ring-purple-400 transition-all cursor-pointer"
                            >
                                <option value="">
                                    Esperando asignatura...
                                </option>
                            </select>
                        </div>

                        <button
                            type="submit"
                            class="w-full py-5 bg-purple-500 text-white rounded-[2rem] font-black uppercase tracking-[0.2em] shadow-lg hover:bg-white hover:text-indigo-900 transition-all transform hover:-translate-y-1 mt-6 text-[11px]"
                        >
                            Asignar Docente
                        </button>

                    </form>
                </div>

            <?php else: ?>

                <div class="bg-white p-12 rounded-[2.5rem] border-2 border-dashed border-slate-200 text-center">
                    <p class="text-[10px] font-black text-slate-400 uppercase italic tracking-widest leading-relaxed">
                        Selecciona un
                        <span class="text-indigo-500">
                            periodo académico
                        </span>
                        para habilitar el formulario.
                    </p>
                </div>

            <?php endif; ?>
        </div>

        <div class="lg:col-span-8">

            <div class="bg-white rounded-[2.5rem] shadow-sm border border-slate-100 overflow-hidden">

                <div class="p-8 border-b border-slate-50 flex justify-between items-center bg-slate-50/30">

                    <div>
                        <h2 class="text-xl font-black text-slate-800 uppercase italic leading-none tracking-tighter">
                            Registros de Asignaciones
                        </h2>

                        <p class="text-[10px] text-slate-400 font-bold uppercase tracking-widest mt-1 italic">
                            Historial de asignaciones actuales
                        </p>
                    </div>
                </div>

                <div class="overflow-x-auto">

                    <table class="w-full border-collapse">

                        <thead>

                            <tr class="bg-slate-50">
                                <th class="p-5 text-left text-[10px] font-black text-slate-500 uppercase tracking-widest italic border-b border-slate-100">
                                    Docente
                                </th>

                                <th class="p-5 text-left text-[10px] font-black text-slate-500 uppercase tracking-widest italic border-b border-slate-100">
                                    Asignatura
                                </th>

                                <th class="p-5 text-center text-[10px] font-black text-slate-500 uppercase tracking-widest italic border-b border-slate-100">
                                    Grupo
                                </th>

                                <th class="p-5 text-right text-[10px] font-black text-slate-500 uppercase tracking-widest italic border-b border-slate-100">
                                    Periodo
                                </th>
                            </tr>

                        </thead>

                        <tbody class="divide-y divide-slate-50">

                            <?php if(count($asignaciones) > 0): ?>

                                <?php foreach($asignaciones as $a): ?>

                                    <tr class="hover:bg-indigo-50/30 transition-colors group">

                                        <td class="p-5 text-[11px] font-bold text-slate-700 uppercase italic">
                                            <?= htmlspecialchars($a['docente']) ?>
                                        </td>

                                        <td class="p-5">
                                            <span class="text-[11px] font-black text-indigo-900 uppercase italic group-hover:text-purple-600 transition-colors">
                                                <?= htmlspecialchars($a['materia']) ?>
                                            </span>
                                        </td>

                                        <td class="p-5 text-center">
                                            <span class="px-3 py-1 bg-slate-100 text-slate-600 rounded-lg text-[10px] font-black border border-slate-200">
                                                <?= htmlspecialchars($a['grupo']) ?>
                                            </span>
                                        </td>

                                        <td class="p-5 text-right text-[10px] font-black text-slate-400 italic font-mono">
                                            <?= htmlspecialchars($a['periodo']) ?>
                                        </td>
                                    </tr>

                                <?php endforeach; ?>

                            <?php else: ?>

                                <tr>
                                    <td colspan="4" class="p-12 text-center text-slate-400 text-xs font-black uppercase tracking-widest">
                                        No hay asignaciones registradas para este periodo.
                                    </td>
                                </tr>

                            <?php endif; ?>

                        </tbody>

                    </table>

                </div>
            </div>
        </div>
    </div>
</div>

<style>
@keyframes fade-in {
    from {
        opacity: 0;
        transform: translateY(10px);
    }

    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.animate-fade-in {
    animation: fade-in 0.4s ease-out forwards;
}
</style>

<script>
const materiaSelect = document.getElementById("materiaSelect");

if(materiaSelect){

    materiaSelect.addEventListener("change", function(){

        const carrera_id =
            this.options[this.selectedIndex].getAttribute("data-carrera");

        const periodo_id = "<?= $periodo_id ?>";

        const select = document.getElementById("grupoSelect");

        if(!carrera_id || !periodo_id){

            select.innerHTML = "<option value=''>Seleccionar grupo</option>";
            return;
        }

        select.innerHTML = "<option value=''>Procesando grupos...</option>";

        fetch(`?modulo=get_grupos_por_carrera&carrera_id=${carrera_id}&periodo_id=${periodo_id}`)
            .then(res => res.json())
            .then(response => {

                select.innerHTML = "<option value=''>Seleccionar grupo</option>";

                if(!response.success || response.data.length === 0){

                    select.innerHTML = "<option value=''>No hay grupos compatibles</option>";
                    return;
                }

                response.data.forEach(g => {

                    let opt = document.createElement("option");
                    opt.value = g.id;
                    opt.textContent = g.nombre;

                    select.appendChild(opt);
                });
            })
            .catch(() => {
                select.innerHTML = "<option value=''>Error en la conexión</option>";
            });
    });
}
</script>