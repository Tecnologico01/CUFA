<?php
require_once __DIR__ . '/../../includes/db.php';

$docente_id = $_SESSION['user_id'];

$stmt = $pdo->prepare("
SELECT t.tema,t.descripcion,p.nombre AS parcial,m.nombre AS materia
FROM temas_materia t
JOIN asignaciones_docentes ad ON t.asignacion_id=ad.id
JOIN materias m ON ad.materia_id=m.id
JOIN parciales p ON t.parcial_id=p.id
WHERE ad.docente_id=?
ORDER BY p.id,t.orden
");

$stmt->execute([$docente_id]);
$temas = $stmt->fetchAll();
?>

<h1 class="text-2xl font-bold mb-6">

Temas asignados

</h1>

<table class="w-full bg-white shadow rounded">

<tr class="bg-gray-200">

<th class="p-2">Materia</th>
<th class="p-2">Parcial</th>
<th class="p-2">Tema</th>
<th class="p-2">Descripción</th>

</tr>

<?php foreach($temas as $t): ?>

<tr>

<td class="p-2"><?= $t['materia'] ?></td>
<td class="p-2"><?= $t['parcial'] ?></td>
<td class="p-2"><?= $t['tema'] ?></td>
<td class="p-2"><?= $t['descripcion'] ?></td>

</tr>

<?php endforeach; ?>

</table>