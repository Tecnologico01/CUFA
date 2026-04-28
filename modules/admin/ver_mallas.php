<?php

require_once __DIR__ . '/../../includes/db.php';

$mallas = $pdo->query("

SELECT
m.id,
c.nombre carrera,
tp.nombre tipo,
m.total_periodos

FROM mallas_curriculares m
JOIN carreras c ON c.id = m.carrera_id
JOIN tipos_periodo tp ON tp.id = m.tipo_periodo_id

")->fetchAll();

?>

<h1 class="text-3xl font-bold mb-6">Mallas Curriculares</h1>

<div class="bg-white rounded-xl shadow p-6">

<table class="w-full">

<thead class="border-b">

<tr>
<th class="text-left p-2">Carrera</th>
<th class="text-left p-2">Tipo</th>
<th class="text-left p-2">Total de cursos</th>
<th class="text-left p-2">Acción</th>
</tr>

</thead>

<tbody>

<?php foreach($mallas as $m){ ?>

<tr class="border-b">

<td class="p-2"><?= $m['carrera'] ?></td>
<td class="p-2"><?= $m['tipo'] ?></td>
<td class="p-2"><?= $m['total_periodos'] ?></td>

<td class="space-x-2">

<a href="admin_dashboard.php?modulo=editar_malla&id=<?= $m['id'] ?>"
class="bg-blue-500 text-white px-3 py-1 rounded">
Editar
</a>

<a href="../modules/admin/eliminar_malla.php?id=<?= $m['id'] ?>"
onclick="return confirm('¿Eliminar esta malla?')"
class="bg-red-500 text-white px-3 py-1 rounded">
Eliminar
</a>

</td>

</tr>

<?php } ?>

</tbody>

</table>

</div>