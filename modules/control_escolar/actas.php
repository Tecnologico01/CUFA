<?php
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/permisos.php';

validarPermiso('ver_actas');

$puedeGenerar = tienePermiso('generar_actas');
$puedeHabilitar = tienePermiso('habilitar_actas');

$carrera_id = isset($_GET['carrera_id']) ? (int)$_GET['carrera_id'] : 0;
$periodo_id = isset($_GET['periodo_id']) ? (int)$_GET['periodo_id'] : 0;
$grupo_id = isset($_GET['grupo_id']) ? (int)$_GET['grupo_id'] : 0;
$estado = $_GET['estado'] ?? '';

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

$grupos = [];

if($carrera_id > 0 && $periodo_id > 0){
    $stmt = $pdo->prepare("
        SELECT id, nombre
        FROM grupos
        WHERE carrera_id = ?
        AND periodo_id = ?
        ORDER BY nombre ASC
    ");
    $stmt->execute([$carrera_id, $periodo_id]);
    $grupos = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

$where = [];
$params = [];

if($carrera_id > 0){
    $where[] = "g.carrera_id = ?";
    $params[] = $carrera_id;
}

if($periodo_id > 0){
    $where[] = "a.periodo_id = ?";
    $params[] = $periodo_id;
}

if($grupo_id > 0){
    $where[] = "a.grupo_id = ?";
    $params[] = $grupo_id;
}

if($estado !== ''){
    $where[] = "a.estado = ?";
    $params[] = $estado;
}

$sqlWhere = $where ? "WHERE " . implode(" AND ", $where) : "";

$stmtActas = $pdo->prepare("
    SELECT
        a.id,
        a.codigo,
        a.estado,
        a.fecha_generacion,
        a.created_at,
        a.asignacion_id,
        m.nombre AS materia,
        g.nombre AS grupo,
        c.nombre AS carrera,
        p.nombre AS periodo,
        CONCAT(u.nombres, ' ', u.apellido_paterno, ' ', u.apellido_materno) AS docente,
        ug.nombres AS generado_nombre,
        ug.apellido_paterno AS generado_apellido
    FROM actas a
    LEFT JOIN materias m ON m.id = a.materia_id
    LEFT JOIN grupos g ON g.id = a.grupo_id
    LEFT JOIN carreras c ON c.id = g.carrera_id
    LEFT JOIN periodos p ON p.id = a.periodo_id
    LEFT JOIN asignaciones_docentes ad ON ad.id = a.asignacion_id
    LEFT JOIN docentes d ON d.id = ad.docente_id
    LEFT JOIN usuarios u ON u.id = d.usuario_id
    LEFT JOIN usuarios ug ON ug.id = a.generado_por
    $sqlWhere
    ORDER BY a.id DESC
");

$stmtActas->execute($params);
$actas = $stmtActas->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="max-w-7xl mx-auto p-6 bg-slate-50 min-h-screen">

    <div class="mb-10 flex flex-col md:flex-row md:items-end md:justify-between gap-6">
        <div>
            <span class="bg-emerald-100 text-emerald-700 px-4 py-2 rounded-full text-[10px] font-black uppercase tracking-[0.25em]">
                Control Escolar
            </span>

            <h1 class="text-5xl font-black italic uppercase tracking-tighter text-slate-800 mt-5">
                Actas Oficiales
            </h1>

            <p class="text-slate-500 font-semibold mt-3">
                Historial y consulta de actas generadas en el sistema.
            </p>
        </div>

        <div class="flex flex-wrap gap-3">
            <?php if($puedeGenerar): ?>
                <a href="?modulo=generar_actas"
                   class="bg-emerald-600 hover:bg-emerald-700 text-white px-6 py-4 rounded-2xl font-black uppercase text-[10px] tracking-widest shadow-lg">
                    + Generar Acta
                </a>
            <?php endif; ?>

            <?php if($puedeHabilitar): ?>
                <a href="?modulo=habilitar_actas"
                   class="bg-slate-900 hover:bg-slate-700 text-white px-6 py-4 rounded-2xl font-black uppercase text-[10px] tracking-widest">
                    Habilitar Actas
                </a>
            <?php endif; ?>
        </div>
    </div>

    <form method="GET" class="bg-white p-8 rounded-[3rem] border border-slate-100 shadow-sm mb-10">
        <input type="hidden" name="modulo" value="actas">

        <div class="grid grid-cols-1 md:grid-cols-4 gap-6">

            <div>
                <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">
                    Carrera
                </label>

                <select name="carrera_id"
                        onchange="this.form.submit()"
                        class="w-full p-4 rounded-2xl bg-slate-50 border border-slate-200 font-bold outline-none">
                    <option value="">Todas</option>
                    <?php foreach($carreras as $c): ?>
                        <option value="<?= (int)$c['id'] ?>" <?= $carrera_id === (int)$c['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($c['nombre']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div>
                <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">
                    Periodo
                </label>

                <select name="periodo_id"
                        onchange="this.form.submit()"
                        class="w-full p-4 rounded-2xl bg-slate-50 border border-slate-200 font-bold outline-none">
                    <option value="">Todos</option>
                    <?php foreach($periodos as $p): ?>
                        <option value="<?= (int)$p['id'] ?>" <?= $periodo_id === (int)$p['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($p['nombre']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div>
                <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">
                    Grupo
                </label>

                <select name="grupo_id"
                        onchange="this.form.submit()"
                        class="w-full p-4 rounded-2xl bg-slate-50 border border-slate-200 font-bold outline-none"
                        <?= (!$carrera_id || !$periodo_id) ? 'disabled' : '' ?>>
                    <option value="">Todos</option>
                    <?php foreach($grupos as $g): ?>
                        <option value="<?= (int)$g['id'] ?>" <?= $grupo_id === (int)$g['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($g['nombre']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div>
                <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">
                    Estado
                </label>

                <select name="estado"
                        onchange="this.form.submit()"
                        class="w-full p-4 rounded-2xl bg-slate-50 border border-slate-200 font-bold outline-none">
                    <option value="">Todos</option>
                    <option value="generada" <?= $estado === 'generada' ? 'selected' : '' ?>>Generada</option>
                    <option value="abierta" <?= $estado === 'abierta' ? 'selected' : '' ?>>Abierta</option>
                    <option value="cerrada" <?= $estado === 'cerrada' ? 'selected' : '' ?>>Cerrada</option>
                </select>
            </div>

        </div>
    </form>

    <?php if(!$actas): ?>

        <div class="bg-white p-16 rounded-[3rem] text-center border border-slate-100 shadow-sm">
            <h2 class="text-2xl font-black text-slate-700 uppercase">
                Sin actas registradas
            </h2>

            <p class="text-slate-400 font-semibold mt-3">
                Aún no existen actas oficiales generadas.
            </p>
        </div>

    <?php else: ?>

        <div class="bg-white rounded-[3rem] shadow-xl border border-slate-100 overflow-hidden">

            <table class="min-w-full">
                <thead class="bg-slate-900 text-white">
                    <tr>
                        <th class="p-5 text-left text-[10px] font-black uppercase tracking-widest">Código</th>
                        <th class="p-5 text-left text-[10px] font-black uppercase tracking-widest">Carrera</th>
                        <th class="p-5 text-left text-[10px] font-black uppercase tracking-widest">Materia</th>
                        <th class="p-5 text-left text-[10px] font-black uppercase tracking-widest">Grupo</th>
                        <th class="p-5 text-left text-[10px] font-black uppercase tracking-widest">Periodo</th>
                        <th class="p-5 text-center text-[10px] font-black uppercase tracking-widest">Estado</th>
                        <th class="p-5 text-center text-[10px] font-black uppercase tracking-widest">Acciones</th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-slate-100">
                    <?php foreach($actas as $a): ?>

                        <?php
                            $estadoActa = $a['estado'] ?? 'generada';

                            $badge = 'bg-blue-100 text-blue-700';

                            if($estadoActa === 'abierta'){
                                $badge = 'bg-emerald-100 text-emerald-700';
                            }

                            if($estadoActa === 'cerrada'){
                                $badge = 'bg-red-100 text-red-700';
                            }
                        ?>

                        <tr class="hover:bg-slate-50">

                            <td class="p-5">
                                <p class="font-black text-slate-800">
                                    <?= htmlspecialchars($a['codigo'] ?: 'ACTA-' . $a['id']) ?>
                                </p>

                                <p class="text-[10px] text-slate-400 font-bold mt-1">
                                    <?= htmlspecialchars($a['fecha_generacion'] ?: $a['created_at']) ?>
                                </p>
                            </td>

                            <td class="p-5 font-bold text-slate-600">
                                <?= htmlspecialchars($a['carrera'] ?? 'Sin carrera') ?>
                            </td>

                            <td class="p-5 font-bold text-slate-700">
                                <?= htmlspecialchars($a['materia'] ?? 'Sin materia') ?>
                            </td>

                            <td class="p-5 font-bold text-slate-600">
                                <?= htmlspecialchars($a['grupo'] ?? 'Sin grupo') ?>
                            </td>

                            <td class="p-5 font-bold text-slate-600">
                                <?= htmlspecialchars($a['periodo'] ?? 'Sin periodo') ?>
                            </td>

                            <td class="p-5 text-center">
                                <span class="<?= $badge ?> px-4 py-2 rounded-full text-[10px] font-black uppercase tracking-widest">
                                    <?= htmlspecialchars($estadoActa) ?>
                                </span>
                            </td>

                            <td class="p-5 text-center">
                                <div class="flex flex-wrap justify-center gap-2">

                                    <a href="?modulo=ver_acta&id=<?= (int)$a['id'] ?>"
                                       class="bg-slate-900 hover:bg-indigo-600 text-white px-4 py-3 rounded-2xl text-[10px] font-black uppercase tracking-widest">
                                        Ver
                                    </a>

                                    <a href="/sistema_academico/modules/control_escolar/pdf_acta.php?id=<?= (int)$a['id'] ?>"
                                       target="_blank"
                                       class="bg-red-600 hover:bg-red-700 text-white px-4 py-3 rounded-2xl text-[10px] font-black uppercase tracking-widest">
                                        PDF
                                    </a>

                                </div>
                            </td>

                        </tr>

                    <?php endforeach; ?>
                </tbody>
            </table>

        </div>

    <?php endif; ?>

</div>