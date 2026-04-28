<?php

require_once __DIR__ . "/../../includes/db.php";

$tema_id=$_GET['tema'];
$semana=$_GET['semana'];

?>

<h1 class="text-2xl font-bold mb-6">
Crear planeación
</h1>

<form method="POST" action="docente_dashboard.php?modulo=guardar_planeacion">

<input type="hidden" name="tema_id" value="<?= $tema_id ?>">
<input type="hidden" name="semana" value="<?= $semana ?>">

<div class="mb-4">
<label class="font-semibold">Objetivo</label>
<textarea name="objetivo" class="border p-2 w-full rounded"></textarea>
</div>

<div class="mb-4">
<label class="font-semibold">Actividad</label>
<textarea name="actividad" class="border p-2 w-full rounded"></textarea>
</div>

<div class="mb-4">
<label class="font-semibold">Material didáctico</label>
<textarea name="descripcion" class="border p-2 w-full rounded"></textarea>
</div>

<button class="bg-green-600 text-white px-6 py-2 rounded hover:bg-green-700">
Guardar
</button>

</form>