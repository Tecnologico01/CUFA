<?php

require_once __DIR__ . '/../../includes/db.php';

/* ==============================
   OBTENER USUARIO LOGUEADO
============================== */

$usuario_id = $_SESSION['user_id'] ?? null;

if (!$usuario_id) {
    echo "<p class='text-red-600'>Usuario no identificado.</p>";
    exit;
}


/* ==============================
   OBTENER DOCENTE
============================== */

$stmt = $pdo->prepare("
SELECT id
FROM docentes
WHERE usuario_id = ?
");

$stmt->execute([$usuario_id]);

$docente = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$docente) {
    echo "<p class='text-red-600'>Docente no encontrado.</p>";
    exit;
}

$docente_id = $docente['id'];


/* ==============================
   OBTENER MATERIAS ASIGNADAS
============================== */

$stmt = $pdo->prepare("
SELECT 
ad.id,
m.nombre AS materia,
g.nombre AS grupo,
p.nombre AS periodo
FROM asignaciones_docentes ad
JOIN materias m ON ad.materia_id = m.id
JOIN grupos g ON ad.grupo_id = g.id
JOIN periodos p ON g.periodo_id = p.id
WHERE ad.docente_id = ?
ORDER BY m.nombre
");

$stmt->execute([$docente_id]);

$materias = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>


<div class="bg-white shadow rounded-xl p-6 mb-6">

<h1 class="text-2xl font-bold text-gray-800">
Mis Materias
</h1>

<p class="text-gray-600 mt-1">
Materias asignadas al docente
</p>

</div>


<div class="bg-white shadow rounded-xl p-6">

<?php if (!$materias): ?>

<p class="text-gray-600">
No tienes materias asignadas actualmente.
</p>

<?php else: ?>

<table class="min-w-full border text-sm">

<thead class="bg-gray-100">

<tr>

<th class="border p-3">Materia</th>
<th class="border p-3">Grupo</th>
<th class="border p-3">Periodo</th>
<th class="border p-3">Acciones</th>

</tr>

</thead>

<tbody>

<?php foreach ($materias as $m): ?>
<tr class="hover:bg-gray-50 transition">
    <td class="border p-3 font-bold text-gray-700">
        <?= htmlspecialchars($m['materia']) ?>
    </td>

    <td class="border p-3 text-center text-gray-600 font-medium">
        <?= htmlspecialchars($m['grupo']) ?>
    </td>

    <td class="border p-3 text-center text-gray-600">
        <?= htmlspecialchars($m['periodo']) ?>
    </td>

    <td class="border p-3">
        <div class="flex gap-2 justify-center">
            <a href="docente_dashboard.php?modulo=ver_materia&asignacion_id=<?= $m['id'] ?>"
               class="bg-blue-600 text-white px-4 py-1.5 rounded-lg hover:bg-blue-700 text-xs font-bold shadow-sm transition">
                ENTRAR
            </a>

            <a href="docente_dashboard.php?modulo=planeacion_materia&asignacion_id=<?= $m['id'] ?>"
               class="bg-purple-600 text-white px-4 py-1.5 rounded-lg hover:bg-purple-700 text-xs font-bold shadow-sm transition">
                PLANEACIÓN
            </a>
        </div>
    </td>
</tr>
<?php endforeach; ?>

</tbody>

</table>

<?php endif; ?>

</div>