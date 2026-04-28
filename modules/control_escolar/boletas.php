<?php
require_once __DIR__ . '/../../includes/db.php';

$stmt = $pdo->query("
SELECT 
a.id,
u.nombre
FROM alumnos a
JOIN usuarios u ON a.usuario_id = u.id
");

$alumnos = $stmt->fetchAll();
?>

<h1 class="text-2xl font-bold mb-6">Boletas de Alumnos</h1>

<table class="w-full bg-white shadow rounded">

<tr class="bg-purple-200">
<th class="p-3">Alumno</th>
<th class="p-3">Acción</th>
</tr>

<?php foreach($alumnos as $a){ ?>

<tr class="border-t">

<td class="p-3"><?= $a['nombre'] ?></td>

<td class="p-3">

<a href="control_dashboard.php?modulo=ver_boleta&id=<?= $a['id'] ?>"
class="bg-purple-600 text-white px-3 py-1 rounded">

Ver Boleta

</a>

</td>

</tr>

<?php } ?>

</table>