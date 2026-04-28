<?php
require_once __DIR__ . '/../../includes/db.php';

$asignacion = $_GET['asignacion'];
$semana = $_GET['semana'];

$stmt = $pdo->prepare("
SELECT *
FROM planeacion_semanal
WHERE asignacion_id=? AND semana=?
");

$stmt->execute([$asignacion,$semana]);

$data = $stmt->fetch();
?>

<h2>Planeación Semana <?= $semana ?></h2>

<form action="guardar_semana.php" method="POST" enctype="multipart/form-data">

<input type="hidden" name="asignacion" value="<?= $asignacion ?>">
<input type="hidden" name="semana" value="<?= $semana ?>">

<h3>Tema</h3>

Objetivo

<textarea name="objetivo"><?= $data['objetivo'] ?? '' ?></textarea>

<br><br>

Descripción del tema

<textarea name="descripcion_tema"><?= $data['descripcion_tema'] ?? '' ?></textarea>

<br><br>

Material (link o archivo)

<input type="text" name="material_url" value="<?= $data['material_url'] ?? '' ?>">

<br><br>

<h3>Actividad</h3>

Nombre actividad

<input type="text" name="actividad_nombre" value="<?= $data['actividad_nombre'] ?? '' ?>">

<br><br>

Descripción actividad

<textarea name="actividad_descripcion"><?= $data['actividad_descripcion'] ?? '' ?></textarea>

<br><br>

Rubrica

<input type="file" name="rubrica">

<br><br>

Fecha apertura

<input type="date" name="fecha_apertura" value="<?= $data['fecha_apertura'] ?? '' ?>">

<br><br>

Fecha cierre

<input type="date" name="fecha_cierre" value="<?= $data['fecha_cierre'] ?? '' ?>">

<br><br>

<button type="submit">

Guardar Planeación

</button>

</form>