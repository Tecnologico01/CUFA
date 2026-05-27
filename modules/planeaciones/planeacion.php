<?php

require_once __DIR__ . '/../../includes/db.php';

$asignacion_id = $_GET['asignacion_id'] ?? null;

if(!$asignacion_id){
echo "Asignación no especificada";
exit;
}

/* OBTENER MATERIA */

$stmt = $pdo->prepare("
SELECT 
m.nombre AS materia,
g.nombre AS grupo
FROM asignaciones_docentes a
JOIN materias m ON a.materia_id = m.id
JOIN grupos g ON a.grupo_id = g.id
WHERE a.id = ?
");

$stmt->execute([$asignacion_id]);

$info = $stmt->fetch(PDO::FETCH_ASSOC);


/* OBTENER TEMAS */

$stmt = $pdo->prepare("
SELECT *
FROM temas_materia
WHERE asignacion_id = ?
ORDER BY parcial, orden
");

$stmt->execute([$asignacion_id]);

$temas = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>

<h1 class="text-3xl font-bold mb-6">

Planeación de la materia

</h1>


<div class="bg-white p-6 rounded shadow mb-6">

<p><b>Materia:</b> <?= $info['materia'] ?></p>
<p><b>Grupo:</b> <?= $info['grupo'] ?></p>

</div>


<div class="bg-white rounded shadow overflow-hidden">

<table class="w-full">

<thead class="bg-indigo-600 text-white">

<tr>

<th class="p-3">Parcial</th>
<th class="p-3">Tema</th>
<th class="p-3">Descripción</th>

</tr>

</thead>

<tbody>

<?php foreach($temas as $t): ?>

<tr class="border-b">

<td class="p-3">

Parcial <?= $t['parcial'] ?>

</td>

<td class="p-3">

<?= $t['tema'] ?>

</td>

<td class="p-3">

<?= $t['descripcion'] ?>

</td>

</tr>

<?php endforeach; ?>

</tbody>

</table>

</div>