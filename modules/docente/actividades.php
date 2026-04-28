<?php

require_once __DIR__ . '/../../includes/db.php';

$asignacion_id = $_GET['asignacion_id'] ?? null;

if(!$asignacion_id){
    echo "Asignación no especificada";
    exit;
}

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

<h1 class="text-2xl font-bold mb-6">

Actividades por Tema

</h1>


<div class="bg-white rounded shadow overflow-hidden">

<table class="w-full">

<thead class="bg-indigo-600 text-white">

<tr>

<th class="p-3">Parcial</th>
<th class="p-3">Tema</th>
<th class="p-3">Opciones</th>

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

<td class="p-3 flex gap-3">

<a href="docente_dashboard.php?modulo=subir_material&tema_id=<?= $t['id'] ?>&asignacion_id=<?= $asignacion_id ?>"
class="bg-blue-600 text-white px-3 py-1 rounded">

Material

</a>

<a href="docente_dashboard.php?modulo=crear_actividad&tema_id=<?= $t['id'] ?>&asignacion_id=<?= $asignacion_id ?>"
class="bg-green-600 text-white px-3 py-1 rounded">

Actividad

</a>

</td>

</tr>

<?php endforeach; ?>

</tbody>

</table>

</div>