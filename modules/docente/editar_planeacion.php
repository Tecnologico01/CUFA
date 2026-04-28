<?php

require_once __DIR__ . "/../../includes/db.php";

$id=$_GET['id'];

$stmt=$pdo->prepare("
SELECT *
FROM planeaciones
WHERE id=?
");

$stmt->execute([$id]);
$plan=$stmt->fetch();

?>

<h1 class="text-2xl font-bold mb-6">
Editar planeación
</h1>

<form method="POST" action="docente_dashboard.php?modulo=actualizar_planeacion">

<input type="hidden" name="id" value="<?= $plan['id'] ?>">
<input type="hidden" name="tema_id" value="<?= $plan['tema_id'] ?>">

<div class="mb-4">
<label class="font-semibold">Objetivo</label>
<textarea name="objetivo" class="border p-2 w-full rounded"><?= htmlspecialchars($plan['objetivo']) ?></textarea>
</div>

<div class="mb-4">
<label class="font-semibold">Actividad</label>
<textarea name="actividad" class="border p-2 w-full rounded"><?= htmlspecialchars($plan['actividad']) ?></textarea>
</div>

<div class="mb-4">
<label class="font-semibold">Material didáctico</label>
<textarea name="descripcion" class="border p-2 w-full rounded"><?= htmlspecialchars($plan['descripcion']) ?></textarea>
</div>

<button class="bg-blue-600 text-white px-6 py-2 rounded hover:bg-blue-700">
Actualizar
</button>

</form>