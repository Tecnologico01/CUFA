<?php

require_once __DIR__ . '/../../includes/db.php';

$stmt = $pdo->query("

SELECT 

u.nombres AS docente,
m.nombre AS materia,
g.nombre AS grupo,
pe.nombre AS periodo,
pa.numero AS parcial,
c.calificacion

FROM calificaciones c

JOIN parciales pa ON pa.id = c.parcial_id

JOIN actividades a ON a.id = c.actividad_id
JOIN asignaciones_docentes ad ON ad.id = a.asignacion_id

JOIN docentes d ON d.id = ad.docente_id
JOIN usuarios u ON u.id = d.usuario_id

JOIN materias m ON m.id = ad.materia_id
JOIN grupos g ON g.id = ad.grupo_id
JOIN periodos pe ON pe.id = ad.periodo_id

ORDER BY pe.nombre, g.nombre, m.nombre

");

?>

<h1 class="text-3xl font-bold mb-6">
Ver Calificaciones
</h1>

<div class="bg-white rounded-xl shadow overflow-hidden">

<table class="w-full">

<thead class="bg-purple-200 text-purple-900">

<tr>

<th class="p-3 text-left">Docente</th>
<th class="p-3 text-left">Materia</th>
<th class="p-3 text-left">Grupo</th>
<th class="p-3 text-left">Periodo</th>
<th class="p-3 text-left">Parcial</th>
<th class="p-3 text-left">Calificación</th>

</tr>

</thead>

<tbody>

<?php while($c = $stmt->fetch()){ ?>

<tr class="border-b hover:bg-gray-50">

<td class="p-3"><?= $c['docente'] ?></td>
<td class="p-3"><?= $c['materia'] ?></td>
<td class="p-3"><?= $c['grupo'] ?></td>
<td class="p-3"><?= $c['periodo'] ?></td>
<td class="p-3"><?= $c['parcial'] ?></td>
<td class="p-3"><?= $c['calificacion'] ?></td>

</tr>

<?php } ?>

</tbody>

</table>

</div>