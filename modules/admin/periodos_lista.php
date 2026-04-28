<?php
require_once __DIR__ . '/../../includes/db.php';

/* OBTENER TIPOS DE PERIODO */

$tipos = $pdo->query("
SELECT id, nombre 
FROM tipos_periodo
ORDER BY id ASC
")->fetchAll(PDO::FETCH_ASSOC);
?>

<h1 class="text-3xl font-bold mb-6">Periodos</h1>

<?php foreach($tipos as $tipo): ?>

<?php
/* OBTENER PERIODOS DE ESTE TIPO */

$stmt = $pdo->prepare("
SELECT *
FROM periodos
WHERE tipo_periodo_id = ?
ORDER BY fecha_inicio DESC
");

$stmt->execute([$tipo['id']]);
$periodos = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="bg-white p-6 rounded-xl shadow mb-8">

<h2 class="text-xl font-bold mb-4 text-purple-700">
<?= htmlspecialchars($tipo['nombre']) ?>
</h2>

<table class="w-full">

<thead>
<tr class="border-b">
<th class="p-2">Nombre</th>
<th class="p-2">Inicio</th>
<th class="p-2">Fin</th>
<th class="p-2">Estado</th>
<th class="p-2">Acciones</th>
</tr>
</thead>

<tbody>

<?php if(!$periodos){ ?>
<tr>
<td colspan="5" class="p-4 text-center text-gray-500">
No hay periodos registrados
</td>
</tr>
<?php } ?>

<?php foreach($periodos as $p){ ?>

<tr class="border-b hover:bg-gray-50">

<td class="p-2"><?= htmlspecialchars($p['nombre']) ?></td>

<td class="p-2"><?= $p['fecha_inicio'] ?></td>

<td class="p-2"><?= $p['fecha_fin'] ?></td>

<td class="p-2">
<?php if($p['activo']){ ?>
<span class="text-green-600 font-semibold">Activo</span>
<?php }else{ ?>
<span class="text-red-600 font-semibold">Inactivo</span>
<?php } ?>
</td>

<td class="p-2 flex gap-2">

<?php if(!$p['activo']){ ?>

<a href="admin_dashboard.php?modulo=periodo_activo&id=<?= $p['id'] ?>&accion=activar"
class="bg-blue-500 text-white px-3 py-1 rounded">
Activar
</a>

<?php }else{ ?>

<a href="admin_dashboard.php?modulo=periodo_activo&id=<?= $p['id'] ?>&accion=desactivar"
class="bg-yellow-500 text-white px-3 py-1 rounded">
Desactivar
</a>

<?php } ?>

<a href="/sistema_academico/modules/admin/editar_periodo.php?id=<?= $p['id'] ?>" 
style="background:#2196F3;color:white;padding:6px 10px;border-radius:4px;text-decoration:none;">
Editar
</a>

<a href="admin_dashboard.php?modulo=eliminar_periodo&id=<?= $p['id'] ?>"
class="bg-red-500 text-white px-3 py-1 rounded"
onclick="return confirm('¿Seguro que deseas eliminar este periodo?')">
Eliminar
</a>

</td>

</tr>

<?php } ?>

</tbody>

</table>

</div>

<?php endforeach; ?>