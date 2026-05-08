<?php
require_once __DIR__ . '/../../includes/db.php';

/*
|--------------------------------------------------------------------------
| FILTROS
|--------------------------------------------------------------------------
*/

$carrera_id = $_GET['carrera_id'] ?? null;
$periodo_id = $_GET['periodo_id'] ?? null;

/*
|--------------------------------------------------------------------------
| OBTENER CARRERAS
|--------------------------------------------------------------------------
*/

$stmtCarreras = $pdo->query("
    SELECT DISTINCT
        c.id,
        c.nombre
    FROM parciales p
    INNER JOIN carreras c
        ON c.id = p.carrera_id
    ORDER BY c.nombre ASC
");

$carreras = $stmtCarreras->fetchAll(PDO::FETCH_ASSOC);

/*
|--------------------------------------------------------------------------
| OBTENER PERIODOS SEGÚN CARRERA
|--------------------------------------------------------------------------
*/

$periodos = [];

if ($carrera_id) {

    $stmtPeriodos = $pdo->prepare("
        SELECT DISTINCT
            pe.id,
            pe.nombre
        FROM parciales p
        INNER JOIN periodos pe
            ON pe.id = p.periodo_id
        WHERE p.carrera_id = ?
        ORDER BY pe.nombre DESC
    ");

    $stmtPeriodos->execute([$carrera_id]);

    $periodos = $stmtPeriodos->fetchAll(PDO::FETCH_ASSOC);
}

/*
|--------------------------------------------------------------------------
| OBTENER PARCIALES
|--------------------------------------------------------------------------
*/

$parciales = [];

if ($carrera_id && $periodo_id) {

    $stmt = $pdo->prepare("
        SELECT
            p.id,
            p.numero,
            p.activo,
            p.fecha_inicio,
            p.fecha_fin,
            p.created_at,

            pe.nombre AS periodo,

            c.nombre AS carrera,

            m.nombre AS materia,
            m.clave AS clave_materia,

            g.nombre AS grupo,

            u.nombres AS docente

        FROM parciales p

        INNER JOIN periodos pe
            ON pe.id = p.periodo_id

        INNER JOIN carreras c
            ON c.id = p.carrera_id

        INNER JOIN materias m
            ON m.id = p.materia_id

        INNER JOIN grupos g
            ON g.id = p.grupo_id

        LEFT JOIN asignaciones_docentes ad
            ON ad.grupo_id = p.grupo_id
            AND ad.materia_id = p.materia_id
            AND ad.periodo_id = p.periodo_id

        LEFT JOIN docentes d
            ON d.id = ad.docente_id

        LEFT JOIN usuarios u
            ON u.id = d.usuario_id

        WHERE p.carrera_id = ?
        AND p.periodo_id = ?

        ORDER BY
            g.nombre ASC,
            m.nombre ASC,
            p.numero ASC
    ");

    $stmt->execute([$carrera_id, $periodo_id]);

    $parciales = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/*
|--------------------------------------------------------------------------
| AGRUPAR POR GRUPO
|--------------------------------------------------------------------------
*/

$grupos = [];

foreach ($parciales as $p) {

    $grupoKey = $p['grupo'];

    if (!isset($grupos[$grupoKey])) {
        $grupos[$grupoKey] = [];
    }

    $grupos[$grupoKey][] = $p;
}

/*
|--------------------------------------------------------------------------
| BADGES
|--------------------------------------------------------------------------
*/

function badgeEstado($activo)
{
    if ($activo == 1) {

        return "
            <span class='bg-emerald-100 text-emerald-700 px-4 py-2 rounded-full text-[10px] font-black uppercase tracking-widest'>
                Habilitado
            </span>
        ";
    }

    return "
        <span class='bg-red-100 text-red-700 px-4 py-2 rounded-full text-[10px] font-black uppercase tracking-widest'>
            Cerrado
        </span>
    ";
}

?>

<!DOCTYPE html>
<html lang="es">
<head>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Parciales Anteriores</title>

<script src="https://cdn.tailwindcss.com"></script>

<style>

@import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap');

body{
    font-family:'Plus Jakarta Sans',sans-serif;
}

.fade-up{
    animation:fadeUp .5s ease;
}

@keyframes fadeUp{
    from{
        opacity:0;
        transform:translateY(20px);
    }
    to{
        opacity:1;
        transform:translateY(0);
    }
}

</style>

</head>

<body class="bg-slate-50 min-h-screen p-8">

<div class="max-w-7xl mx-auto fade-up">

    <!-- HEADER -->

    <div class="mb-10">

        <span class="bg-purple-600 text-white px-4 py-2 rounded-full text-[10px] font-black uppercase tracking-[0.25em]">
            Gestión Académica
        </span>

        <h1 class="text-4xl font-black text-slate-900 mt-4 uppercase tracking-tight">
            Parciales
            <span class="text-purple-600 italic">
                Anteriores
            </span>
        </h1>

        <p class="text-slate-500 font-semibold mt-3">
            Consulta y administración histórica de parciales.
        </p>

    </div>

    <!-- FILTROS -->

    <div class="bg-white rounded-[2rem] shadow-xl border border-slate-100 p-8 mb-10">

        <form method="GET" class="grid grid-cols-1 md:grid-cols-2 gap-6">

            <input type="hidden" name="modulo" value="parciales_anteriores">

            <!-- CARRERA -->

            <div>

                <label class="block text-[11px] font-black uppercase tracking-[0.25em] text-slate-400 mb-4">
                    Carrera
                </label>

                <select
                    name="carrera_id"
                    onchange="this.form.submit()"
                    class="w-full bg-slate-50 border-2 border-transparent focus:border-purple-600 rounded-2xl p-5 font-bold outline-none transition-all"
                >

                    <option value="">
                        -- Selecciona una carrera --
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

            <!-- PERIODO -->

            <div>

                <label class="block text-[11px] font-black uppercase tracking-[0.25em] text-slate-400 mb-4">
                    Periodo
                </label>

                <select
                    name="periodo_id"
                    onchange="this.form.submit()"
                    class="w-full bg-slate-50 border-2 border-transparent focus:border-purple-600 rounded-2xl p-5 font-bold outline-none transition-all"
                >

                    <option value="">
                        -- Selecciona un periodo --
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

            </div>

        </form>

    </div>

    <!-- PARCIALES -->

    <?php if($carrera_id && $periodo_id): ?>

        <?php if(count($grupos) > 0): ?>

            <?php foreach($grupos as $grupoNombre => $grupoParciales): ?>

                <div class="bg-white rounded-[2rem] shadow-xl border border-slate-100 overflow-hidden mb-10">

                    <!-- HEADER GRUPO -->

                    <div class="bg-slate-900 px-8 py-6">

                        <h2 class="text-2xl font-black text-white uppercase tracking-tight">
                            Grupo <?= htmlspecialchars($grupoNombre) ?>
                        </h2>

                    </div>

                    <!-- TABLA -->

                    <div class="overflow-x-auto">

                        <table class="w-full">

                            <thead class="bg-slate-100">

                                <tr>

                                    <th class="p-6 text-left text-[10px] uppercase tracking-[0.25em] text-slate-500">
                                        Parcial
                                    </th>

                                    <th class="p-6 text-left text-[10px] uppercase tracking-[0.25em] text-slate-500">
                                        Materia
                                    </th>

                                    <th class="p-6 text-left text-[10px] uppercase tracking-[0.25em] text-slate-500">
                                        Docente
                                    </th>

                                    <th class="p-6 text-left text-[10px] uppercase tracking-[0.25em] text-slate-500">
                                        Inicio
                                    </th>

                                    <th class="p-6 text-left text-[10px] uppercase tracking-[0.25em] text-slate-500">
                                        Fin
                                    </th>

                                    <th class="p-6 text-left text-[10px] uppercase tracking-[0.25em] text-slate-500">
                                        Estado
                                    </th>

                                    <th class="p-6 text-left text-[10px] uppercase tracking-[0.25em] text-slate-500">
                                        Acción
                                    </th>

                                </tr>

                            </thead>

                            <tbody>

                            <?php foreach($grupoParciales as $p): ?>

                                <tr class="border-b border-slate-100 hover:bg-slate-50 transition-all">

                                    <!-- PARCIAL -->

                                    <td class="p-6">

                                        <div class="flex flex-col">

                                            <span class="font-black text-lg text-slate-900">
                                                Parcial <?= $p['numero'] ?>
                                            </span>

                                            <span class="text-xs text-slate-400 font-bold uppercase">
                                                ID #<?= $p['id'] ?>
                                            </span>

                                        </div>

                                    </td>

                                    <!-- MATERIA -->

                                    <td class="p-6">

                                        <div class="flex flex-col">

                                            <span class="font-bold text-slate-800">
                                                <?= htmlspecialchars($p['materia']) ?>
                                            </span>

                                            <span class="text-xs text-slate-400 uppercase font-bold">
                                                <?= htmlspecialchars($p['clave_materia']) ?>
                                            </span>

                                        </div>

                                    </td>

                                    <!-- DOCENTE -->

                                    <td class="p-6">

                                        <?php if($p['docente']): ?>

                                            <span class="font-bold text-slate-700">
                                                <?= htmlspecialchars($p['docente']) ?>
                                            </span>

                                        <?php else: ?>

                                            <span class="text-slate-400 font-bold">
                                                Sin asignar
                                            </span>

                                        <?php endif; ?>

                                    </td>

                                    <!-- FECHAS -->

                                    <td class="p-6 font-bold text-slate-700">
                                        <?= date('d/m/Y', strtotime($p['fecha_inicio'])) ?>
                                    </td>

                                    <td class="p-6 font-bold text-slate-700">
                                        <?= date('d/m/Y', strtotime($p['fecha_fin'])) ?>
                                    </td>

                                    <!-- ESTADO -->

                                    <td class="p-6">

                                        <?= badgeEstado($p['activo']) ?>

                                    </td>

                                    <!-- ACCIONES -->

                                    <td class="p-6">

                                        <?php if($p['activo'] == 1): ?>

                                            <a
                                                href="/sistema_academico/modules/admin/inactivar_parcial.php?id=<?= $p['id'] ?>"
                                                onclick="return confirm('¿Deseas cerrar este parcial?')"
                                                class="bg-red-500 hover:bg-red-600 text-white px-5 py-3 rounded-2xl text-[10px] font-black uppercase tracking-[0.2em] transition-all"
                                            >
                                                Cerrar
                                            </a>

                                        <?php else: ?>

                                            <a
                                                href="/sistema_academico/modules/admin/activar_parcial.php?id=<?= $p['id'] ?>"
                                                onclick="return confirm('¿Deseas habilitar este parcial nuevamente?')"
                                                class="bg-emerald-500 hover:bg-emerald-600 text-white px-5 py-3 rounded-2xl text-[10px] font-black uppercase tracking-[0.2em] transition-all"
                                            >
                                                Habilitar
                                            </a>

                                        <?php endif; ?>

                                    </td>

                                </tr>

                            <?php endforeach; ?>

                            </tbody>

                        </table>

                    </div>

                </div>

            <?php endforeach; ?>

        <?php else: ?>

            <div class="bg-white p-16 rounded-[2rem] shadow-xl text-center border border-slate-100">

                <div class="text-6xl mb-6">
                    📚
                </div>

                <h2 class="text-2xl font-black text-slate-800 uppercase tracking-tight mb-3">
                    Sin Parciales
                </h2>

                <p class="text-slate-500 font-semibold">
                    No existen parciales registrados en este periodo.
                </p>

            </div>

        <?php endif; ?>

    <?php else: ?>

        <div class="bg-white p-16 rounded-[2rem] shadow-xl text-center border border-slate-100">

            <div class="text-6xl mb-6">
                🎓
            </div>

            <h2 class="text-2xl font-black text-slate-800 uppercase tracking-tight mb-3">
                Selecciona una Carrera y un Periodo
            </h2>

            <p class="text-slate-500 font-semibold">
                Primero debes seleccionar una carrera y un periodo para visualizar los parciales.
            </p>

        </div>

    <?php endif; ?>

</div>

</body>
</html>