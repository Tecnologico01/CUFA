<?php

require_once __DIR__ . '/../../includes/db.php';

$stmt = $pdo->query("

SELECT 

u.nombres AS alumno,
g.nombre AS grupo,
m.nombre AS materia,
c.calificacion

FROM calificaciones c

JOIN alumnos a ON a.id = c.alumno_id
JOIN usuarios u ON u.id = a.usuario_id

JOIN parciales p ON p.id = c.parcial_id
JOIN grupos g ON g.id = p.grupo_id
JOIN materias m ON m.id = p.materia_id

WHERE c.calificacion < 70

ORDER BY u.nombres

");

?>

<h1 class="text-3xl font-bold mb-6">
Alumnos en riesgo académico
</h1>

<div class="bg-white rounded-xl shadow overflow-hidden">

<table class="w-full">

<thead class="bg-red-200 text-red-900">

<tr>

<th class="p-4 text-left">Alumno</th>
<th class="p-4 text-left">Grupo</th>
<th class="p-4 text-left">Materia</th>
<th class="p-4 text-left">Calificación</th>

</tr>

</thead>

<tbody>

<?php while($r = $stmt->fetch()){ ?>

<tr class="border-b hover:bg-gray-50">

<td class="p-4"><?= $r['alumno'] ?></td>
<td class="p-4"><?= $r['grupo'] ?></td>
<td class="p-4"><?= $r['materia'] ?></td>

<td class="p-4 text-red-600 font-bold">
<?= $r['calificacion'] ?>
</td>

</tr>

<?php } ?>

</tbody>

</table>

</div>