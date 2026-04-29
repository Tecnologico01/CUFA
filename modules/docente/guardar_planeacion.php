<?php
require_once __DIR__ . '/../../includes/db.php';

$asignacion_id=$_POST['asignacion_id'];
$tema_id=$_POST['tema_id'];
$semana=$_POST['semana'];

$stmt=$pdo->prepare("
INSERT INTO planeacion_semanal
(asignacion_id,tema_id,semana)
VALUES (?,?,?)
");

$stmt->execute([$asignacion_id,$tema_id,$semana]);

header("Location: ../../dashboards/docente_dashboard.php?modulo=planeacion_semanal&asignacion_id=".$asignacion_id);