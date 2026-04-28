<?php
require_once __DIR__ . '/../../includes/db.php';


/* PARCIALES ACTIVOS */

$activos = $pdo->query("
SELECT 
    p.id,
    p.numero,
    p.anio,
    p.activo,
    p.estado_revision,
    p.created_at,

    pe.nombre AS periodo,
    c.nombre AS carrera,
    m.nombre AS materia,
    g.nombre AS grupo

FROM parciales p

LEFT JOIN periodos pe ON pe.id = p.periodo_id
LEFT JOIN carreras c ON c.id = p.carrera_id
LEFT JOIN materias m ON m.id = p.materia_id
LEFT JOIN grupos g ON g.id = p.grupo_id

WHERE p.activo = 1

ORDER BY pe.nombre, g.nombre, m.nombre
")->fetchAll();



/* PARCIALES CERRADOS */

$inactivos = $pdo->query("
SELECT 
    p.id,
    p.numero,
    p.anio,
    p.activo,
    p.estado_revision,
    p.created_at,

    pe.nombre AS periodo,
    c.nombre AS carrera,
    m.nombre AS materia,
    g.nombre AS grupo

FROM parciales p

LEFT JOIN periodos pe ON pe.id = p.periodo_id
LEFT JOIN carreras c ON c.id = p.carrera_id
LEFT JOIN materias m ON m.id = p.materia_id
LEFT JOIN grupos g ON g.id = p.grupo_id

WHERE p.activo = 0

ORDER BY pe.nombre, g.nombre, m.nombre
")->fetchAll();

?>



<h1 class="text-3xl font-bold mb-6">
Parciales Activos
</h1>

<div class="bg-white p-6 rounded-xl shadow mb-10">

<table class="w-full text-sm">

<thead class="border-b bg-gray-50">
<tr>

<th class="p-2 text-left">Periodo</th>
<th class="p-2 text-left">Carrera</th>
<th class="p-2 text-left">Materia</th>
<th class="p-2 text-left">Grupo</th>
<th class="p-2 text-left">Año</th>
<th class="p-2 text-left">Parcial</th>
<th class="p-2 text-left">Estado</th>
<th class="p-2 text-left">Fecha creación</th>
<th class="p-2 text-left">Acciones</th>

</tr>
</thead>

<tbody>

<?php foreach($activos as $p){ ?>

<tr class="border-b hover:bg-gray-50">

<td class="p-2"><?= htmlspecialchars($p['periodo']) ?></td>

<td class="p-2"><?= htmlspecialchars($p['carrera']) ?></td>

<td class="p-2"><?= htmlspecialchars($p['materia']) ?></td>

<td class="p-2"><?= htmlspecialchars($p['grupo']) ?></td>

<td class="p-2"><?= htmlspecialchars($p['anio']) ?></td>

<td class="p-2">
<span class="bg-purple-200 px-2 py-1 rounded">
<?= htmlspecialchars($p['numero']) ?>
</span>
</td>

<td class="p-2">
<span class="bg-green-200 text-green-800 px-2 py-1 rounded">
<?= htmlspecialchars($p['estado_revision']) ?>
</span>
</td>

<td class="p-2"><?= htmlspecialchars($p['created_at']) ?></td>

<td class="p-2">

<a 
href="/sistema_academico/modules/admin/inactivar_parcial.php?id=<?= $p['id'] ?>"
onclick="return confirm('¿Deseas cerrar este parcial?')"
class="bg-red-500 text-white px-3 py-1 rounded hover:bg-red-600">

Inactivar

</a>

</td>

</tr>

<?php } ?>

</tbody>

</table>

</div>



<h1 class="text-3xl font-bold mb-6">
Parciales Cerrados
</h1>

<div class="bg-white p-6 rounded-xl shadow">

<table class="w-full text-sm">

<thead class="border-b bg-gray-50">
<tr>

<th class="p-2 text-left">Periodo</th>
<th class="p-2 text-left">Carrera</th>
<th class="p-2 text-left">Materia</th>
<th class="p-2 text-left">Grupo</th>
<th class="p-2 text-left">Año</th>
<th class="p-2 text-left">Parcial</th>
<th class="p-2 text-left">Estado</th>
<th class="p-2 text-left">Fecha creación</th>
<th class="p-2 text-left">Acciones</th>

</tr>
</thead>

<tbody>

<?php foreach($inactivos as $p){ ?>

<tr class="border-b hover:bg-gray-50">

<td class="p-2"><?= htmlspecialchars($p['periodo']) ?></td>

<td class="p-2"><?= htmlspecialchars($p['carrera']) ?></td>

<td class="p-2"><?= htmlspecialchars($p['materia']) ?></td>

<td class="p-2"><?= htmlspecialchars($p['grupo']) ?></td>

<td class="p-2"><?= htmlspecialchars($p['anio']) ?></td>

<td class="p-2">
<span class="bg-gray-200 px-2 py-1 rounded">
<?= htmlspecialchars($p['numero']) ?>
</span>
</td>

<td class="p-2">
<span class="bg-yellow-200 text-yellow-800 px-2 py-1 rounded">
<?= htmlspecialchars($p['estado_revision']) ?>
</span>
</td>

<td class="p-2"><?= htmlspecialchars($p['created_at']) ?></td>

<td class="p-2">

<a 
href="/sistema_academico/modules/admin/activar_parcial.php?id=<?= $p['id'] ?>"
onclick="return confirm('¿Deseas reactivar este parcial?')"
class="bg-green-500 text-white px-3 py-1 rounded hover:bg-green-600">

Activar

</a>

</td>

</tr>

<?php } ?>

</tbody>

</table>

</div>