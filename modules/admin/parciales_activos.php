<?php
require_once __DIR__ . '/../../includes/db.php';

$stmt = $pdo->query("
SELECT 
p.id,
p.numero,
p.anio,
c.nombre AS carrera,
m.nombre AS materia,
g.nombre AS grupo,
per.nombre AS periodo

FROM parciales p

JOIN carreras c ON p.carrera_id = c.id
JOIN materias m ON p.materia_id = m.id
JOIN grupos g ON p.grupo_id = g.id
JOIN periodos per ON p.periodo_id = per.id

WHERE p.activo = 1

ORDER BY per.nombre, g.nombre, m.nombre
");

$parciales = $stmt->fetchAll();
?>

<h1 class="text-3xl font-bold mb-6">
Parciales Activos
</h1>

<div class="bg-white p-6 rounded-xl shadow">

<table class="w-full">

<thead>
<tr class="border-b">
<th class="p-2 text-left">Periodo</th>
<th class="p-2 text-left">Grupo</th>
<th class="p-2 text-left">Materia</th>
<th class="p-2 text-left">Parcial</th>
<th class="p-2 text-left">Año</th>
</tr>
</thead>

<tbody>

<?php foreach($parciales as $p){ ?>

<tr class="border-b hover:bg-gray-50">

<td class="p-2">
<?= htmlspecialchars($p['periodo']) ?>
</td>

<td class="p-2">
<?= htmlspecialchars($p['grupo']) ?>
</td>

<td class="p-2">
<?= htmlspecialchars($p['materia']) ?>
</td>

<td class="p-2">
<?= htmlspecialchars($p['numero']) ?>
</td>

<td class="p-2">
<?= htmlspecialchars($p['anio']) ?>
</td>

</tr>

<?php } ?>

</tbody>

</table>

</div>