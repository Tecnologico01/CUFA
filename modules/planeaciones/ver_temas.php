<?php

require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/permisos.php';

validarPermiso('ver_planeaciones');

/* =========================
   OBTENER DOCENTE
========================= */

$usuario_id = $_SESSION['user_id'] ?? 0;

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
    <div class='bg-red-50 border border-red-200 text-red-700 p-6 rounded-2xl font-bold'>
        Docente no encontrado.
    </div>";
    exit;
}

$docente_id = $docente['id'];

/* =========================
   TEMAS DEL DOCENTE
========================= */

$stmt = $pdo->prepare("
    SELECT
        tm.id,
        tm.parcial,
        tm.tema,
        tm.subtemas,
        tm.nombre_unidad,
        tm.estado,

        m.nombre AS materia,
        g.nombre AS grupo

    FROM temas_materia tm

    INNER JOIN asignaciones_docentes ad
        ON ad.id = tm.asignacion_id

    INNER JOIN materias m
        ON m.id = ad.materia_id

    INNER JOIN grupos g
        ON g.id = ad.grupo_id

    WHERE ad.docente_id = ?

    ORDER BY
        m.nombre ASC,
        tm.parcial ASC,
        tm.orden ASC
");

$stmt->execute([$docente_id]);

$temas = $stmt->fetchAll(PDO::FETCH_ASSOC);

$totalTemas = count($temas);

?>

<div class="max-w-7xl mx-auto p-6 bg-slate-50 min-h-screen animate-fade-in">

    <!-- HEADER -->

    <div class="mb-10">

        <span class="bg-indigo-100 text-indigo-700 px-4 py-2 rounded-full text-[10px] font-black uppercase tracking-[0.25em]">
            Planeación Docente
        </span>

        <h1 class="text-5xl font-black italic uppercase tracking-tighter text-slate-800 mt-5">
            Temas Asignados
        </h1>

        <p class="text-slate-500 font-semibold mt-3">
            Consulta de unidades, temas y planeaciones registradas.
        </p>

    </div>

    <!-- RESUMEN -->

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-10">

        <div class="bg-white p-6 rounded-[2rem] border border-slate-100 shadow-sm">
            <p class="text-[10px] font-black uppercase tracking-widest text-slate-400">
                Total temas
            </p>

            <h3 class="text-3xl font-black text-indigo-600 mt-2">
                <?= $totalTemas ?>
            </h3>
        </div>

        <div class="bg-white p-6 rounded-[2rem] border border-slate-100 shadow-sm">
            <p class="text-[10px] font-black uppercase tracking-widest text-slate-400">
                Estado
            </p>

            <h3 class="text-3xl font-black text-emerald-600 mt-2">
                Activo
            </h3>
        </div>

        <div class="bg-white p-6 rounded-[2rem] border border-slate-100 shadow-sm">
            <p class="text-[10px] font-black uppercase tracking-widest text-slate-400">
                Planeación
            </p>

            <h3 class="text-3xl font-black text-purple-600 mt-2">
                Docente
            </h3>
        </div>

    </div>

    <?php if ($totalTemas > 0): ?>

        <div class="bg-white rounded-[2.5rem] border border-slate-100 shadow-xl overflow-hidden">

            <div class="overflow-x-auto">

                <table class="min-w-full">

                    <thead class="bg-slate-900 text-white">

                        <tr>

                            <th class="p-5 text-left text-[10px] uppercase tracking-widest">
                                Materia
                            </th>

                            <th class="p-5 text-left text-[10px] uppercase tracking-widest">
                                Grupo
                            </th>

                            <th class="p-5 text-center text-[10px] uppercase tracking-widest">
                                Unidad
                            </th>

                            <th class="p-5 text-left text-[10px] uppercase tracking-widest">
                                Tema
                            </th>

                            <th class="p-5 text-left text-[10px] uppercase tracking-widest">
                                Subtemas
                            </th>

                            <th class="p-5 text-center text-[10px] uppercase tracking-widest">
                                Estado
                            </th>

                        </tr>

                    </thead>

                    <tbody class="divide-y divide-slate-100">

                        <?php foreach($temas as $t): ?>

                            <tr class="hover:bg-slate-50">

                                <td class="p-5">
                                    <strong>
                                        <?= htmlspecialchars($t['materia']) ?>
                                    </strong>
                                </td>

                                <td class="p-5">
                                    <?= htmlspecialchars($t['grupo']) ?>
                                </td>

                                <td class="p-5 text-center">

                                    <span class="bg-indigo-50 text-indigo-600 px-3 py-1 rounded-full text-[10px] font-black uppercase">
                                        <?= htmlspecialchars($t['parcial']) ?>
                                    </span>

                                </td>

                                <td class="p-5">

                                    <strong class="text-slate-800">
                                        <?= htmlspecialchars($t['tema']) ?>
                                    </strong>

                                    <?php if(!empty($t['nombre_unidad'])): ?>
                                        <div class="text-[10px] text-slate-400 mt-1">
                                            <?= htmlspecialchars($t['nombre_unidad']) ?>
                                        </div>
                                    <?php endif; ?>

                                </td>

                                <td class="p-5 text-slate-600">

                                    <?= nl2br(htmlspecialchars($t['subtemas'])) ?>

                                </td>

                                <td class="p-5 text-center">

                                    <?php

                                    $estado = $t['estado'] ?? 'borrador';

                                    $color = 'bg-gray-100 text-gray-600';

                                    if($estado == 'enviado'){
                                        $color = 'bg-amber-100 text-amber-700';
                                    }

                                    if($estado == 'aprobado'){
                                        $color = 'bg-emerald-100 text-emerald-700';
                                    }

                                    ?>

                                    <span class="<?= $color ?> px-3 py-1 rounded-full text-[10px] font-black uppercase">

                                        <?= htmlspecialchars($estado) ?>

                                    </span>

                                </td>

                            </tr>

                        <?php endforeach; ?>

                    </tbody>

                </table>

            </div>

        </div>

    <?php else: ?>

        <div class="bg-white p-16 rounded-[3rem] border border-slate-100 shadow-sm text-center">

            <div class="text-6xl mb-4">
                📚
            </div>

            <h2 class="text-2xl font-black text-slate-700 uppercase">
                Sin temas registrados
            </h2>

            <p class="text-slate-400 font-semibold mt-3">
                Todavía no existen temas asignados para este docente.
            </p>

        </div>

    <?php endif; ?>

</div>

<style>

@keyframes slide-up {

    from {
        opacity: 0;
        transform: translateY(20px);
    }

    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.animate-fade-in{
    animation: slide-up .6s ease;
}

</style>