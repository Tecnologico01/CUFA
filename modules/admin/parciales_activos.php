<?php
require_once __DIR__ . '/../../includes/db.php';

/*
|--------------------------------------------------------------------------
| ACTUALIZAR ESTADO AUTOMÁTICO DE PARCIALES
|--------------------------------------------------------------------------
| - Activo si la fecha actual está dentro del rango
| - Inactivo si está fuera del rango
|--------------------------------------------------------------------------
*/

$pdo->prepare("
    UPDATE parciales
    SET activo = CASE
        WHEN CURDATE() BETWEEN fecha_inicio AND fecha_fin
        THEN 1
        ELSE 0
    END
")->execute();

/*
|--------------------------------------------------------------------------
| OBTENER CARRERAS CON PARCIALES
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
| CARRERA SELECCIONADA
|--------------------------------------------------------------------------
*/

$carrera_id = $_GET['carrera_id'] ?? null;

$parcialesAgrupados = [];

if ($carrera_id) {

    /*
    |--------------------------------------------------------------------------
    | OBTENER PARCIALES
    |--------------------------------------------------------------------------
    */

    $stmt = $pdo->prepare("
        SELECT
            p.*,

            m.nombre AS materia,
            m.clave AS clave_materia,

            g.nombre AS grupo,

            d.id AS docente_id,
            u.nombres AS docente,

            CASE

                WHEN CURDATE() BETWEEN p.fecha_inicio AND p.fecha_fin
                THEN 'activo'

                WHEN CURDATE() < p.fecha_inicio
                THEN 'proximo'

                ELSE 'cerrado'

            END AS estado_actual

        FROM parciales p

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

        ORDER BY
            g.nombre ASC,
            m.nombre ASC,
            p.numero ASC
    ");

    $stmt->execute([$carrera_id]);

    $parciales = $stmt->fetchAll(PDO::FETCH_ASSOC);

    /*
    |--------------------------------------------------------------------------
    | AGRUPAR POR GRUPO
    |--------------------------------------------------------------------------
    */

    foreach ($parciales as $p) {

        $grupoKey = $p['grupo'];

        if (!isset($parcialesAgrupados[$grupoKey])) {
            $parcialesAgrupados[$grupoKey] = [];
        }

        $parcialesAgrupados[$grupoKey][] = $p;
    }
}

/*
|--------------------------------------------------------------------------
| FUNCIONES AUXILIARES
|--------------------------------------------------------------------------
*/

function badgeEstado($estado)
{
    switch ($estado) {

        case 'activo':
            return "
                <span class='bg-emerald-100 text-emerald-700 px-4 py-2 rounded-full text-[10px] font-black uppercase tracking-widest'>
                    Activo
                </span>
            ";

        case 'proximo':
            return "
                <span class='bg-amber-100 text-amber-700 px-4 py-2 rounded-full text-[10px] font-black uppercase tracking-widest'>
                    Próximo
                </span>
            ";

        default:
            return "
                <span class='bg-slate-200 text-slate-600 px-4 py-2 rounded-full text-[10px] font-black uppercase tracking-widest'>
                    Cerrado
                </span>
            ";
    }
}

?>

<!DOCTYPE html>
<html lang="es">
<head>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Parciales Activos</title>

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

    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-6 mb-10">

        <div>

            <span class="bg-purple-600 text-white px-4 py-2 rounded-full text-[10px] font-black uppercase tracking-[0.25em]">
                Gestión Académica
            </span>

            <h1 class="text-4xl font-black text-slate-900 mt-4 uppercase tracking-tight">
                Parciales
                <span class="text-purple-600 italic">
                    Activos
                </span>
            </h1>

            <p class="text-slate-500 font-semibold mt-3">
                Consulta automática de parciales vigentes y próximos.
            </p>

        </div>

    </div>

    <!-- SELECT CARRERA -->

    <div class="bg-white rounded-[2rem] shadow-xl border border-slate-100 p-8 mb-10">

        <form method="GET">

            <input type="hidden" name="modulo" value="parciales_activos">

            <label class="block text-[11px] font-black uppercase tracking-[0.25em] text-slate-400 mb-4">
                Seleccionar Carrera
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

        </form>

    </div>

    <!-- CONTENIDO -->

    <?php if($carrera_id): ?>

        <?php if(count($parcialesAgrupados) > 0): ?>

            <?php foreach($parcialesAgrupados as $grupoNombre => $grupoParciales): ?>

                <!-- TARJETA DEL GRUPO -->

                <div class="bg-white rounded-[2rem] shadow-xl border border-slate-100 overflow-hidden mb-10">

                    <!-- HEADER GRUPO -->

                    <div class="bg-slate-900 px-8 py-6 flex flex-col md:flex-row md:items-center md:justify-between gap-4">

                        <div>

                            <h2 class="text-2xl font-black text-white uppercase tracking-tight">
                                Grupo <?= htmlspecialchars($grupoNombre) ?>
                            </h2>

                            <p class="text-slate-300 text-sm font-semibold mt-1">
                                Parciales correspondientes a este grupo
                            </p>

                        </div>

                    </div>

                    <!-- TABLA -->

                    <div class="overflow-x-auto">

                        <table class="w-full">

                            <thead class="bg-slate-100">

                                <tr class="text-left">

                                    <th class="p-6 text-[10px] uppercase tracking-[0.25em] text-slate-500">
                                        Parcial
                                    </th>

                                    <th class="p-6 text-[10px] uppercase tracking-[0.25em] text-slate-500">
                                        Materia
                                    </th>

                                    <th class="p-6 text-[10px] uppercase tracking-[0.25em] text-slate-500">
                                        Docente
                                    </th>

                                    <th class="p-6 text-[10px] uppercase tracking-[0.25em] text-slate-500">
                                        Inicio
                                    </th>

                                    <th class="p-6 text-[10px] uppercase tracking-[0.25em] text-slate-500">
                                        Fin
                                    </th>

                                    <th class="p-6 text-[10px] uppercase tracking-[0.25em] text-slate-500">
                                        Estado
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

                                    <!-- FECHA INICIO -->

                                    <td class="p-6">

                                        <span class="font-bold text-slate-700">
                                            <?= date('d/m/Y', strtotime($p['fecha_inicio'])) ?>
                                        </span>

                                    </td>

                                    <!-- FECHA FIN -->

                                    <td class="p-6">

                                        <span class="font-bold text-slate-700">
                                            <?= date('d/m/Y', strtotime($p['fecha_fin'])) ?>
                                        </span>

                                    </td>

                                    <!-- ESTADO -->

                                    <td class="p-6">

                                        <?= badgeEstado($p['estado_actual']) ?>

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
                    No existen parciales registrados para esta carrera.
                </p>

            </div>

        <?php endif; ?>

    <?php else: ?>

        <div class="bg-white p-16 rounded-[2rem] shadow-xl text-center border border-slate-100">

            <div class="text-6xl mb-6">
                🎓
            </div>

            <h2 class="text-2xl font-black text-slate-800 uppercase tracking-tight mb-3">
                Selecciona una Carrera
            </h2>

            <p class="text-slate-500 font-semibold">
                Primero debes seleccionar una carrera para visualizar los parciales activos.
            </p>

        </div>

    <?php endif; ?>

</div>

</body>
</html>