<?php

require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/permisos.php';

validarPermiso('ver_asignaciones_alumnos');

$puedeRemover = tienePermiso('asignar_alumnos');

$carrera_id = isset($_GET['carrera_id']) ? (int) $_GET['carrera_id'] : 0;
$grupo_id   = isset($_GET['grupo_id']) ? (int) $_GET['grupo_id'] : 0;

/* =========================
   REMOVER ASIGNACIÓN
========================= */

if(isset($_GET['eliminar'])){

    if(!$puedeRemover){
        die("Sin permisos");
    }

    $ga_id = (int) $_GET['eliminar'];

    if($ga_id > 0){

        $stmt = $pdo->prepare("
            DELETE FROM grupos_alumnos
            WHERE id = ?
        ");

        $stmt->execute([$ga_id]);
    }

    header("Location: ?modulo=ver_asignaciones_alumnos&carrera_id={$carrera_id}&grupo_id={$grupo_id}");
    exit;
}

/* =========================
   CARRERAS
========================= */

$carreras = $pdo->query("
    SELECT id, nombre
    FROM carreras
    ORDER BY nombre ASC
")->fetchAll(PDO::FETCH_ASSOC);

/* =========================
   GRUPOS
========================= */

$grupos = [];

if($carrera_id > 0){

    $stmt = $pdo->prepare("
        SELECT
            g.id,
            g.nombre,
            g.grado,
            p.nombre AS periodo
        FROM grupos g
        INNER JOIN periodos p
            ON p.id = g.periodo_id
        WHERE g.carrera_id = ?
        ORDER BY g.grado ASC, g.nombre ASC
    ");

    $stmt->execute([$carrera_id]);
    $grupos = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/* =========================
   ALUMNOS DEL GRUPO
========================= */

$alumnos = [];

if($grupo_id > 0){

    $stmt = $pdo->prepare("
        SELECT
            ga.id AS asignacion_id,
            a.numero_control,
            a.grado,
            CONCAT(
                u.nombres, ' ',
                u.apellido_paterno, ' ',
                u.apellido_materno
            ) AS alumno
        FROM grupos_alumnos ga
        INNER JOIN alumnos a
            ON a.id = ga.alumno_id
        INNER JOIN usuarios u
            ON u.id = a.usuario_id
        WHERE ga.grupo_id = ?
        ORDER BY 
            u.apellido_paterno ASC,
            u.apellido_materno ASC,
            u.nombres ASC
    ");

    $stmt->execute([$grupo_id]);
    $alumnos = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

?>

<div class="max-w-7xl mx-auto p-6 min-h-screen animate-fade-in">

    <div class="mb-10 flex flex-col md:flex-row justify-between items-end gap-6">

        <div>
            <div class="flex items-center gap-2 mb-2">

                <span class="bg-purple-600 text-white text-[10px] font-black px-3 py-1 rounded-full uppercase tracking-widest">
                    Control Escolar
                </span>

                <span class="text-[10px] font-black text-slate-300 uppercase tracking-widest">
                    Gestión de grupos
                </span>

            </div>

            <h1 class="text-4xl font-black uppercase tracking-tighter text-slate-900 leading-none">
                Asignaciones
                <span class="text-purple-600 italic">
                    de Alumnos
                </span>
            </h1>
        </div>

        <div class="text-right">
            <p class="text-[10px] font-black uppercase tracking-[0.2em] text-slate-400">
                Total alumnos
            </p>

            <p class="text-2xl font-black text-slate-700">
                <?= count($alumnos) ?>
            </p>
        </div>

    </div>

    <div class="bg-white rounded-[2.5rem] border border-slate-100 shadow-sm p-8 mb-8">

        <form method="GET" class="grid grid-cols-1 md:grid-cols-3 gap-6">

            <input type="hidden" name="modulo" value="ver_asignaciones_alumnos">

            <div>

                <label class="text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] ml-2 mb-2 block">
                    Carrera
                </label>

                <select
                    name="carrera_id"
                    onchange="this.form.submit()"
                    class="w-full p-4 bg-slate-50 border-2 border-slate-100 rounded-2xl font-bold text-slate-700 outline-none focus:border-purple-500"
                >

                    <option value="">
                        Seleccionar carrera...
                    </option>

                    <?php foreach($carreras as $c): ?>

                        <option
                            value="<?= $c['id'] ?>"
                            <?= $carrera_id == $c['id'] ? 'selected' : '' ?>
                        >
                            <?= htmlspecialchars($c['nombre']) ?>
                        </option>

                    <?php endforeach; ?>

                </select>

            </div>

            <div>

                <label class="text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] ml-2 mb-2 block">
                    Grupo
                </label>

                <select
                    name="grupo_id"
                    onchange="this.form.submit()"
                    class="w-full p-4 bg-slate-50 border-2 border-slate-100 rounded-2xl font-bold text-slate-700 outline-none focus:border-purple-500"
                >

                    <option value="">
                        Seleccionar grupo...
                    </option>

                    <?php foreach($grupos as $g): ?>

                        <option
                            value="<?= $g['id'] ?>"
                            <?= $grupo_id == $g['id'] ? 'selected' : '' ?>
                        >
                            <?= htmlspecialchars($g['nombre']) ?>
                            | <?= (int)$g['grado'] ?>°
                            | <?= htmlspecialchars($g['periodo']) ?>
                        </option>

                    <?php endforeach; ?>

                </select>

            </div>

            <div class="flex items-end">

                <div class="w-full bg-purple-50 border border-purple-100 rounded-2xl p-4">

                    <p class="text-[9px] font-black uppercase tracking-widest text-purple-600 mb-1">
                        Gestión Académica
                    </p>

                    <p class="text-xs font-bold text-slate-600 leading-relaxed">
                        Selecciona una carrera y un grupo para visualizar los alumnos asignados.
                    </p>

                </div>

            </div>

        </form>

    </div>

    <div class="bg-white rounded-[2.5rem] border border-slate-100 shadow-sm overflow-hidden">

        <div class="p-8 border-b border-slate-100 bg-slate-50">

            <h2 class="text-xl font-black uppercase italic tracking-tight text-slate-800">
                Alumnos Integrados al Grupo
            </h2>

            <p class="text-[10px] text-slate-400 font-black uppercase tracking-widest mt-1">
                Gestión de asignaciones escolares
            </p>

        </div>

        <?php if($grupo_id > 0): ?>

            <?php if(count($alumnos) > 0): ?>

                <div class="overflow-x-auto">

                    <table class="w-full border-collapse">

                        <thead>

                            <tr class="bg-slate-50">

                                <th class="p-5 text-left text-[10px] font-black uppercase tracking-[0.2em] text-slate-500">
                                    Alumno
                                </th>

                                <th class="p-5 text-left text-[10px] font-black uppercase tracking-[0.2em] text-slate-500">
                                    Número de Control
                                </th>

                                <th class="p-5 text-center text-[10px] font-black uppercase tracking-[0.2em] text-slate-500">
                                    Grado
                                </th>

                                <?php if($puedeRemover): ?>
                                    <th class="p-5 text-center text-[10px] font-black uppercase tracking-[0.2em] text-slate-500">
                                        Gestión
                                    </th>
                                <?php endif; ?>

                            </tr>

                        </thead>

                        <tbody class="divide-y divide-slate-100">

                            <?php foreach($alumnos as $a): ?>

                                <tr class="hover:bg-purple-50/30 transition-all">

                                    <td class="p-5">

                                        <div class="flex flex-col">

                                            <span class="text-sm font-black text-slate-800 uppercase">
                                                <?= htmlspecialchars($a['alumno']) ?>
                                            </span>

                                            <span class="text-[10px] text-slate-400 font-bold uppercase">
                                                Alumno activo
                                            </span>

                                        </div>

                                    </td>

                                    <td class="p-5">

                                        <span class="text-xs font-black text-slate-600">
                                            <?= htmlspecialchars($a['numero_control']) ?>
                                        </span>

                                    </td>

                                    <td class="p-5 text-center">

                                        <span class="px-3 py-1 bg-indigo-50 text-indigo-700 rounded-xl text-[10px] font-black border border-indigo-100">
                                            <?= (int)$a['grado'] ?>°
                                        </span>

                                    </td>

                                    <?php if($puedeRemover): ?>

                                        <td class="p-5">

                                            <div class="flex justify-center">

                                                <a
                                                    href="?modulo=ver_asignaciones_alumnos&carrera_id=<?= $carrera_id ?>&grupo_id=<?= $grupo_id ?>&eliminar=<?= $a['asignacion_id'] ?>"
                                                    onclick="return confirm('¿Deseas remover este alumno del grupo?')"
                                                    class="px-4 py-2 bg-red-50 text-red-600 rounded-xl text-[10px] font-black uppercase tracking-widest border border-red-100 hover:bg-red-500 hover:text-white transition-all"
                                                >
                                                    Remover
                                                </a>

                                            </div>

                                        </td>

                                    <?php endif; ?>

                                </tr>

                            <?php endforeach; ?>

                        </tbody>

                    </table>

                </div>

            <?php else: ?>

                <div class="p-20 text-center">

                    <h3 class="text-lg font-black uppercase tracking-widest text-slate-300 italic">
                        No hay alumnos asignados
                    </h3>

                </div>

            <?php endif; ?>

        <?php else: ?>

            <div class="p-20 text-center">

                <h3 class="text-lg font-black uppercase tracking-widest text-slate-300 italic">
                    Selecciona un grupo
                </h3>

            </div>

        <?php endif; ?>

    </div>

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
    animation: slide-up .5s ease-out;
}
</style>