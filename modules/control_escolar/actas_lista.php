<?php
require_once __DIR__ . '/../../includes/db.php';

$stmt = $pdo->query("
SELECT 
a.id,
m.nombre AS materia,
g.nombre AS grupo
FROM actas a
JOIN materias m ON a.materia_id = m.id
JOIN grupos g ON a.grupo_id = g.id
");

$actas = $stmt->fetchAll();
?>

<h1 class="text-2xl font-bold mb-6">Actas Generadas</h1>

<a href="control_dashboard.php?modulo=generar_acta"
class="bg-purple-600 text-white px-4 py-2 rounded">

Nueva Acta

</a>

<table class="w-full bg-white shadow rounded mt-4">

<tr class="bg-purple-200">
<th class="p-3">Materia</th>
<th class="p-3">Grupo</th>
</tr>

<?php foreach($actas as $a){ ?>

<tr class="border-t">
<td class="p-3"><?= $a['materia'] ?></td>
<td class="p-3"><?= $a['grupo'] ?></td>
</tr>

<?php } ?>

</table>