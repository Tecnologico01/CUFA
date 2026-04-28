<?php

require_once __DIR__ . "/../../includes/db.php";

$id=$_POST['id'];
$tema_id=$_POST['tema_id'];
$objetivo=$_POST['objetivo'];
$actividad=$_POST['actividad'];
$descripcion=$_POST['descripcion'];

$stmt=$pdo->prepare("
UPDATE planeaciones
SET objetivo=?,actividad=?,descripcion=?
WHERE id=?
");

$stmt->execute([
$objetivo,
$actividad,
$descripcion,
$id
]);

header("Location: docente_dashboard.php?modulo=planeacion_semanal&tema=".$tema_id);
exit;