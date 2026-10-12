<?php

require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/permisos.php';

validarPermiso('asignar_alumnos');

$mensaje = null;

$periodo_id = isset($_GET['periodo_id']) ? (int) $_GET['periodo_id'] : 0;
$carrera_id = isset($_GET['carrera_id']) ? (int) $_GET['carrera_id'] : 0;
$grado      = isset($_GET['grado']) ? (int) $_GET['grado'] : 0;
$grupo_id   = isset($_GET['grupo_id']) ? (int) $_GET['grupo_id'] : 0;

/* =========================
   ASIGNAR ALUMNOS
========================= */

if($_SERVER['REQUEST_METHOD'] === 'POST'){

    $grupo_id   = isset($_POST['grupo_id']) ? (int) $_POST['grupo_id'] : 0;
    $periodo_id = isset($_POST['periodo_id']) ? (int) $_POST['periodo_id'] : 0;
    $alumnos    = $_POST['alumnos'] ?? [];

    if($grupo_id <= 0 || $periodo_id <= 0){

        $mensaje = [
            "tipo" => "error",
            "texto" => "Grupo o periodo no válido."
        ];

    }else{

        try{

            $pdo->beginTransaction();

            /* VALIDAR GRUPO */

            $stmtGrupo = $pdo->prepare("
                SELECT 
                    id,
                    carrera_id,
                    grado,
                    periodo_id
                FROM grupos
                WHERE id = ?
            ");

            $stmtGrupo->execute([$grupo_id]);
            $grupo = $stmtGrupo->fetch(PDO::FETCH_ASSOC);

            if(!$grupo){
                throw new Exception("El grupo no existe.");
            }

            if((int)$grupo['periodo_id'] !== $periodo_id){
                throw new Exception("El grupo no pertenece al periodo seleccionado.");
            }

            /* LIMPIAR ASIGNACIONES ANTERIORES DEL GRUPO */

            $stmtDelete = $pdo->prepare("
                DELETE FROM grupos_alumnos
                WHERE grupo_id = ?
                AND periodo_id = ?
            ");

            $stmtDelete->execute([
                $grupo_id,
                $periodo_id
            ]);

            /* INSERTAR NUEVAS ASIGNACIONES */

            if(!empty($alumnos)){

                $stmtExiste = $pdo->prepare("
                    SELECT id
                    FROM grupos_alumnos
                    WHERE alumno_id = ?
                    AND periodo_id = ?
                    LIMIT 1
                ");

                $stmtAlumno = $pdo->prepare("
                    SELECT id
                    FROM alumnos
                    WHERE id = ?
                    AND carrera_id = ?
                    AND grado = ?
                ");

                $stmtInsert = $pdo->prepare("
                    INSERT INTO grupos_alumnos
                    (
                        grupo_id,
                        alumno_id,
                        periodo_id
                    )
                    VALUES
                    (
                        ?, ?, ?
                    )
                ");

                foreach($alumnos as $alumno_id){

                    $alumno_id = (int)$alumno_id;

                    if($alumno_id <= 0){
                        continue;
                    }

                    /* VALIDAR QUE EL ALUMNO PERTENEZCA A LA CARRERA Y GRADO DEL GRUPO */

                    $stmtAlumno->execute([
                        $alumno_id,
                        $grupo['carrera_id'],
                        $grupo['grado']
                    ]);

                    if(!$stmtAlumno->fetch()){
                        continue;
                    }

                    /* VALIDAR SI YA ESTÁ ASIGNADO A OTRO GRUPO EN EL MISMO PERIODO */

                    $stmtExiste->execute([
                        $alumno_id,
                        $periodo_id
                    ]);

                    if($stmtExiste->fetch()){
                        continue;
                    }

                    $stmtInsert->execute([
                        $grupo_id,
                        $alumno_id,
                        $periodo_id
                    ]);
                }
            }

            $pdo->commit();

            $mensaje = [
                "tipo" => "success",
                "texto" => "Alumnos asignados correctamente."
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
   CONSULTAS BASE
========================= */

$periodos = $pdo->query("
    SELECT id, nombre
    FROM periodos
    WHERE activo = 1
    ORDER BY nombre DESC
")->fetchAll(PDO::FETCH_ASSOC);

$carreras = $pdo->query("
    SELECT id, nombre
    FROM carreras
    ORDER BY nombre ASC
")->fetchAll(PDO::FETCH_ASSOC);

$grupos = [];
$alumnos = [];
$alumnos_asignados = [];

/* =========================
   GRUPOS Y ALUMNOS
========================= */

if($periodo_id > 0 && $carrera_id > 0 && $grado > 0){

    $stmt = $pdo->prepare("
        SELECT id, nombre
        FROM grupos
        WHERE periodo_id = ?
        AND carrera_id = ?
        AND grado = ?
        ORDER BY nombre ASC
    ");

    $stmt->execute([
        $periodo_id,
        $carrera_id,
        $grado
    ]);

    $grupos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $stmt = $pdo->prepare("
        SELECT
            a.id,
            a.numero_control,
            CONCAT(
                u.nombres, ' ',
                u.apellido_paterno, ' ',
                u.apellido_materno
            ) AS nombre,
            (
                SELECT ga.grupo_id
                FROM grupos_alumnos ga
                WHERE ga.alumno_id = a.id
                AND ga.periodo_id = ?
                LIMIT 1
            ) AS grupo_asignado_id
        FROM alumnos a
        INNER JOIN usuarios u
            ON u.id = a.usuario_id
        WHERE a.carrera_id = ?
        AND a.grado = ?
        ORDER BY 
            u.apellido_paterno ASC,
            u.apellido_materno ASC,
            u.nombres ASC
    ");

    $stmt->execute([
        $periodo_id,
        $carrera_id,
        $grado
    ]);

    $alumnos = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/* =========================
   ALUMNOS YA ASIGNADOS AL GRUPO
========================= */

if($grupo_id > 0 && $periodo_id > 0){

    $stmt = $pdo->prepare("
        SELECT alumno_id
        FROM grupos_alumnos
        WHERE grupo_id = ?
        AND periodo_id = ?
    ");

    $stmt->execute([
        $grupo_id,
        $periodo_id
    ]);

    $alumnos_asignados = $stmt->fetchAll(PDO::FETCH_COLUMN);
}

?>

<div class="max-w-7xl mx-auto p-6 min-h-screen animate-fade-in">

    <div class="mb-10 flex flex-col md:flex-row md:items-end md:justify-between gap-6">

        <div>
            <h1 class="text-4xl font-black uppercase italic tracking-tighter text-slate-800">
                Asignar
                <span class="text-purple-600">Alumnos</span>
            </h1>

            <p class="text-xs font-bold uppercase tracking-[0.3em] text-purple-600 mt-2">
                Gestión Escolar
            </p>
        </div>

        <a href="?modulo=ver_asignaciones_alumnos"
           class="bg-slate-900 hover:bg-purple-600 text-white px-6 py-4 rounded-2xl font-black uppercase text-[10px] tracking-widest transition-all">
            Ver Asignaciones
        </a>

    </div>

    <?php if($mensaje): ?>

        <div class="mb-6">
            <div class="<?= $mensaje['tipo'] === 'success'
                ? 'bg-emerald-50 border-emerald-200 text-emerald-700'
                : 'bg-red-50 border-red-200 text-red-700'
            ?> p-4 rounded-2xl border font-bold text-sm shadow-sm">

                <?= htmlspecialchars($mensaje['texto']) ?>

            </div>
        </div>

    <?php endif; ?>

    <div class="bg-white rounded-[2rem] p-8 border border-slate-100 shadow-sm mb-8">

        <form method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-6">

            <input type="hidden" name="modulo" value="asignar_alumnos">

            <div>
                <label class="block text-[10px] font-black uppercase text-slate-400 mb-2">
                    Periodo
                </label>

                <select name="periodo_id"
                    class="w-full p-4 rounded-2xl border-2 border-slate-100 bg-slate-50 font-bold outline-none focus:border-purple-300"
                    onchange="this.form.submit()">

                    <option value="">Seleccionar...</option>

                    <?php foreach($periodos as $p): ?>
                        <option value="<?= $p['id'] ?>" <?= $periodo_id == $p['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($p['nombre']) ?>
                        </option>
                    <?php endforeach; ?>

                </select>
            </div>

            <div>
                <label class="block text-[10px] font-black uppercase text-slate-400 mb-2">
                    Carrera
                </label>

                <select name="carrera_id"
                    class="w-full p-4 rounded-2xl border-2 border-slate-100 bg-slate-50 font-bold outline-none focus:border-purple-300"
                    onchange="this.form.submit()">

                    <option value="">Seleccionar...</option>

                    <?php foreach($carreras as $c): ?>
                        <option value="<?= $c['id'] ?>" <?= $carrera_id == $c['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($c['nombre']) ?>
                        </option>
                    <?php endforeach; ?>

                </select>
            </div>

            <div>
                <label class="block text-[10px] font-black uppercase text-slate-400 mb-2">
                    Grado
                </label>

                <select name="grado"
                    class="w-full p-4 rounded-2xl border-2 border-slate-100 bg-slate-50 font-bold outline-none focus:border-purple-300"
                    onchange="this.form.submit()">

                    <option value="">Seleccionar...</option>

                    <?php for($i = 1; $i <= 12; $i++): ?>
                        <option value="<?= $i ?>" <?= $grado == $i ? 'selected' : '' ?>>
                            <?= $i ?>°
                        </option>
                    <?php endfor; ?>

                </select>
            </div>

            <div>
                <label class="block text-[10px] font-black uppercase text-slate-400 mb-2">
                    Grupo
                </label>

                <select name="grupo_id"
                    class="w-full p-4 rounded-2xl border-2 border-slate-100 bg-slate-50 font-bold outline-none focus:border-purple-300"
                    onchange="this.form.submit()">

                    <option value="">Seleccionar...</option>

                    <?php foreach($grupos as $g): ?>
                        <option value="<?= $g['id'] ?>" <?= $grupo_id == $g['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($g['nombre']) ?>
                        </option>
                    <?php endforeach; ?>

                </select>
            </div>

        </form>

    </div>

    <?php if($grupo_id > 0): ?>

        <form method="POST">

            <input type="hidden" name="grupo_id" value="<?= $grupo_id ?>">
            <input type="hidden" name="periodo_id" value="<?= $periodo_id ?>">

            <div class="bg-white rounded-[2rem] border border-slate-100 shadow-sm overflow-hidden">

                <div class="p-6 border-b border-slate-100 flex items-center justify-between">

                    <div>
                        <h2 class="text-xl font-black uppercase italic text-slate-800">
                            Alumnos Disponibles
                        </h2>

                        <p class="text-[10px] uppercase tracking-[0.2em] text-slate-400 font-bold mt-1">
                            Selecciona los alumnos que pertenecerán al grupo.
                        </p>
                    </div>

                    <div class="text-right">
                        <p class="text-xs font-black text-purple-600 uppercase">
                            <?= count($alumnos_asignados) ?> asignados
                        </p>
                    </div>

                </div>

                <div class="max-h-[550px] overflow-y-auto">

                    <?php if(empty($alumnos)): ?>

                        <div class="p-10 text-center">
                            <p class="text-slate-400 font-black uppercase tracking-[0.2em]">
                                No hay alumnos disponibles.
                            </p>
                        </div>

                    <?php else: ?>

                        <?php foreach($alumnos as $a): ?>

                            <?php
                                $asignadoEsteGrupo = in_array($a['id'], $alumnos_asignados);
                                $asignadoOtroGrupo = !empty($a['grupo_asignado_id']) && !$asignadoEsteGrupo;
                            ?>

                            <label class="flex items-center justify-between p-5 border-b border-slate-50 hover:bg-purple-50 transition-all cursor-pointer <?= $asignadoOtroGrupo ? 'opacity-50' : '' ?>">

                                <div class="flex flex-col">

                                    <p class="font-black text-slate-700 uppercase">
                                        <?= htmlspecialchars($a['nombre']) ?>
                                    </p>

                                    <span class="text-xs text-slate-400 font-bold mt-1">
                                        No. Control:
                                        <?= htmlspecialchars($a['numero_control']) ?>
                                    </span>

                                </div>

                                <div class="flex items-center gap-4">

                                    <?php if($asignadoEsteGrupo): ?>
                                        <span class="text-[10px] uppercase font-black tracking-[0.2em] text-emerald-600">
                                            Asignado
                                        </span>
                                    <?php elseif($asignadoOtroGrupo): ?>
                                        <span class="text-[10px] uppercase font-black tracking-[0.2em] text-red-500">
                                            En otro grupo
                                        </span>
                                    <?php endif; ?>

                                    <input type="checkbox"
                                        name="alumnos[]"
                                        value="<?= $a['id'] ?>"
                                        <?= $asignadoEsteGrupo ? 'checked' : '' ?>
                                        <?= $asignadoOtroGrupo ? 'disabled' : '' ?>
                                        class="w-5 h-5 accent-purple-600 cursor-pointer">

                                </div>

                            </label>

                        <?php endforeach; ?>

                    <?php endif; ?>

                </div>

                <div class="p-6 bg-slate-50 border-t border-slate-100">

                    <button
                        type="submit"
                        class="w-full py-5 rounded-2xl bg-purple-600 hover:bg-slate-900 text-white font-black uppercase tracking-[0.2em] transition-all">

                        Guardar Asignaciones

                    </button>

                </div>

            </div>

        </form>

    <?php else: ?>

        <div class="bg-white p-12 rounded-[2rem] border-2 border-dashed border-slate-200 text-center">
            <p class="text-slate-400 font-black uppercase tracking-[0.2em] text-sm">
                Selecciona periodo, carrera, grado y grupo para asignar alumnos.
            </p>
        </div>

    <?php endif; ?>

</div>

<style>
@keyframes slide-up {
    from {
        opacity: 0;
        transform: translateY(15px);
    }

    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.animate-fade-in {
    animation: slide-up 0.5s ease-out;
}
</style>