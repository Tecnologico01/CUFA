<?php

require_once __DIR__ . '/../../includes/db.php';

$stmt = $pdo->query("
SELECT 
ad.id,

-- 🔥 NOMBRE COMPLETO CORRECTO
CONCAT(u.nombres, ' ', u.apellido_paterno, ' ', u.apellido_materno) AS docente,

m.nombre AS materia,
g.nombre AS grupo,
p.nombre AS periodo

FROM asignaciones_docentes ad

JOIN docentes d ON d.id = ad.docente_id
JOIN usuarios u ON u.id = d.usuario_id

JOIN materias m ON m.id = ad.materia_id
JOIN grupos g ON g.id = ad.grupo_id
JOIN periodos p ON p.id = ad.periodo_id

ORDER BY p.nombre, g.nombre, m.nombre
");

?>

<h1 class="text-3xl font-bold mb-6">Asignaciones de docentes</h1>

<div class="bg-white rounded-xl shadow overflow-hidden">

<table class="w-full">

<thead class="bg-purple-200 text-purple-900">
<tr>
<th class="p-4 text-left">Docente</th>
<th class="p-4 text-left">Materia</th>
<th class="p-4 text-left">Grupo</th>
<th class="p-4 text-left">Periodo</th>
<th class="p-4 text-left">Acciones</th>
</tr>
</thead>

<tbody>

<?php while($a = $stmt->fetch()){ ?>

<tr class="border-b hover:bg-gray-50">

<td class="p-4"><?= $a['docente'] ?></td>

<td class="p-4"><?= $a['materia'] ?></td>

<td class="p-4"><?= $a['grupo'] ?></td>

<td class="p-4"><?= $a['periodo'] ?></td>

<td class="p-4">

<a
href="coordinador_dashboard.php?modulo=definir_temas&asignacion=<?= $a['id'] ?>"
class="bg-purple-600 text-white px-3 py-1 rounded"
>
Temas
</a>

<a 
href="/sistema_academico/modules/coordinador/eliminar_asignacion.php?id=<?= $a['id'] ?>"
class="bg-red-500 text-white px-3 py-1 rounded hover:bg-red-600"
onclick="return confirm('¿Eliminar esta asignación?')"
>
Eliminar
</a>

</td>

</tr>

<?php } ?>

</tbody>

</table>

</div>