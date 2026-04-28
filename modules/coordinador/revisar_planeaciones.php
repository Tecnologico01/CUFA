<?php

require_once __DIR__ . '/../../includes/db.php';

$stmt = $pdo->query("
SELECT 
pl.id,
u.nombres AS docente,
m.nombre AS materia,
g.nombre AS grupo,
pe.nombre AS periodo,
pl.archivo,
pl.estado

FROM planeaciones pl

JOIN docentes d ON d.id = pl.docente_id
JOIN usuarios u ON u.id = d.usuario_id
JOIN materias m ON m.id = pl.materia_id
JOIN grupos g ON g.id = pl.grupo_id
JOIN periodos pe ON pe.id = pl.periodo_id

ORDER BY pe.nombre DESC
");

?>

<h1 class="text-3xl font-bold mb-6">Revisión de planeaciones</h1>

<div class="bg-white rounded-xl shadow overflow-hidden">

<table class="w-full">

<thead class="bg-purple-200 text-purple-900">
<tr>
<th class="p-4 text-left">Docente</th>
<th class="p-4 text-left">Materia</th>
<th class="p-4 text-left">Grupo</th>
<th class="p-4 text-left">Periodo</th>
<th class="p-4 text-left">Archivo</th>
<th class="p-4 text-left">Estado</th>
<th class="p-4 text-left">Acciones</th>
</tr>
</thead>

<tbody>

<?php while($p = $stmt->fetch()){ ?>

<tr class="border-b hover:bg-gray-50">

<td class="p-4"><?= $p['docente'] ?></td>
<td class="p-4"><?= $p['materia'] ?></td>
<td class="p-4"><?= $p['grupo'] ?></td>
<td class="p-4"><?= $p['periodo'] ?></td>

<td class="p-4">

<?php if($p['archivo']){ ?>

<a href="/sistema_academico/uploads/planeaciones/<?= $p['archivo'] ?>" 
target="_blank"
class="text-blue-600 underline">
Ver archivo
</a>

<?php } ?>

</td>

<td class="p-4">

<span class="px-3 py-1 rounded
<?php

if($p['estado']=="aprobado") echo "bg-green-200";
elseif($p['estado']=="rechazado") echo "bg-red-200";
else echo "bg-yellow-200";

?>">

<?= $p['estado'] ?>

</span>

</td>

<td class="p-4 flex gap-2">

<a href="/sistema_academico/modules/coordinador/aprobar_planeacion.php?id=<?= $p['id'] ?>&estado=aprobado"
class="bg-green-500 text-white px-3 py-1 rounded">
Aprobar
</a>

<a href="/sistema_academico/modules/coordinador/aprobar_planeacion.php?id=<?= $p['id'] ?>&estado=rechazado"
class="bg-red-500 text-white px-3 py-1 rounded">
Rechazar
</a>


</td>

</tr>

<?php } ?>

</tbody>

</table>

</div>