<?php

require_once __DIR__ . '/../../includes/db.php';

/* CONSULTAR PARCIALES */

$stmt = $pdo->query("

SELECT 

m.nombre AS materia,
g.nombre AS grupo,
pe.nombre AS periodo,

p.materia_id,
p.grupo_id,
p.periodo_id

FROM parciales p

JOIN materias m ON m.id = p.materia_id
JOIN grupos g ON g.id = p.grupo_id
JOIN periodos pe ON pe.id = p.periodo_id

WHERE pe.activo = 1

GROUP BY p.materia_id,p.grupo_id,p.periodo_id

ORDER BY pe.nombre,g.nombre,m.nombre

");

$datos = $stmt->fetchAll();

?>

<h1 class="text-3xl font-bold mb-6">Habilitar Actas</h1>

<div class="bg-white rounded-xl shadow overflow-hidden">

<table class="w-full">

<thead class="bg-purple-200 text-purple-900">

<tr>

<th class="p-4 text-left">Materia</th>
<th class="p-4 text-left">Grupo</th>
<th class="p-4 text-left">Periodo</th>
<th class="p-4 text-left">Estado</th>
<th class="p-4 text-left">Acción</th>

</tr>

</thead>

<tbody>

<?php foreach($datos as $d){ 

/* verificar si existe acta */

$stmt2 = $pdo->prepare("
SELECT estado
FROM actas
WHERE materia_id=? AND grupo_id=? AND periodo_id=?
");

$stmt2->execute([
$d['materia_id'],
$d['grupo_id'],
$d['periodo_id']
]);

$acta = $stmt2->fetch();

$estado = $acta['estado'] ?? 'cerrada';

?>

<tr class="border-b hover:bg-gray-50">

<td class="p-4"><?= $d['materia'] ?></td>

<td class="p-4"><?= $d['grupo'] ?></td>

<td class="p-4"><?= $d['periodo'] ?></td>

<td class="p-4">

<span class="px-3 py-1 rounded 

<?= $estado=='abierta' ? 'bg-green-200':'bg-red-200' ?>

">

<?= $estado ?>

</span>

</td>

<td class="p-4">

<?php if($estado=='cerrada'){ ?>

<a href="/sistema_academico/modules/coordinador/cambiar_estado_acta.php?m=<?= $d['materia_id'] ?>&g=<?= $d['grupo_id'] ?>&p=<?= $d['periodo_id'] ?>&estado=abierta"
class="bg-green-500 text-white px-3 py-1 rounded">

Habilitar

</a>

<?php } else { ?>

<a href="/sistema_academico/modules/coordinador/cambiar_estado_acta.php?m=<?= $d['materia_id'] ?>&g=<?= $d['grupo_id'] ?>&p=<?= $d['periodo_id'] ?>&estado=cerrada"
class="bg-red-500 text-white px-3 py-1 rounded">

Cerrar

</a>

<?php } ?>

</td>

</tr>

<?php } ?>

</tbody>

</table>

</div>