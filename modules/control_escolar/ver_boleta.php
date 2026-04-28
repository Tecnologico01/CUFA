<?php
require_once __DIR__ . '/../../includes/db.php';

$id=$_GET['id'];

$stmt=$pdo->prepare("
SELECT m.nombre,c.calificacion
FROM calificaciones c
JOIN materias m ON c.materia_id=m.id_materia
WHERE c.alumno_id=?
");

$stmt->execute([$id]);

$materias=$stmt->fetchAll();
?>

<h1 class="text-2xl font-bold mb-6">Boleta</h1>

<table class="w-full bg-white shadow rounded">

<tr class="bg-purple-200">
<th class="p-3">Materia</th>
<th class="p-3">Calificación</th>
</tr>

<?php foreach($materias as $m){ ?>

<tr class="border-t">
<td class="p-3"><?= $m['nombre'] ?></td>
<td class="p-3"><?= $m['calificacion'] ?></td>
</tr>

<?php } ?>

</table>