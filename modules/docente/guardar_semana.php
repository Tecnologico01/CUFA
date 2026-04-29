<?php
require_once __DIR__ . '/../../includes/db.php';

$asignacion = $_POST['asignacion'];
$semana = $_POST['semana'];

$objetivo = $_POST['objetivo'];
$descripcion = $_POST['descripcion_tema'];
$material = $_POST['material_url'];

$actividad = $_POST['actividad_nombre'];
$actividad_desc = $_POST['actividad_descripcion'];

$fecha_a = $_POST['fecha_apertura'];
$fecha_c = $_POST['fecha_cierre'];

$rubrica = null;

if(!empty($_FILES['rubrica']['name'])){

$nombre = time()."_".$_FILES['rubrica']['name'];

move_uploaded_file(
$_FILES['rubrica']['tmp_name'],
"../../uploads/".$nombre
);

$rubrica = $nombre;

}

$stmt = $pdo->prepare("
SELECT id
FROM planeacion_semanal
WHERE asignacion_id=? AND semana=?
");

$stmt->execute([$asignacion,$semana]);

$existe = $stmt->fetch();

if($existe){

$stmt = $pdo->prepare("

UPDATE planeacion_semanal

SET
objetivo=?,
descripcion_tema=?,
material_url=?,
actividad_nombre=?,
actividad_descripcion=?,
fecha_apertura=?,
fecha_cierre=?

WHERE asignacion_id=? AND semana=?

");

$stmt->execute([
$objetivo,
$descripcion,
$material,
$actividad,
$actividad_desc,
$fecha_a,
$fecha_c,
$asignacion,
$semana
]);

}else{

$stmt = $pdo->prepare("

INSERT INTO planeacion_semanal
(asignacion_id,semana,objetivo,descripcion_tema,material_url,
actividad_nombre,actividad_descripcion,fecha_apertura,fecha_cierre)

VALUES (?,?,?,?,?,?,?,?,?)

");

$stmt->execute([
$asignacion,
$semana,
$objetivo,
$descripcion,
$material,
$actividad,
$actividad_desc,
$fecha_a,
$fecha_c
]);

}

header("Location: ver_materia.php?id=".$asignacion);