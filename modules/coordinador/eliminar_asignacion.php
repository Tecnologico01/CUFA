<?php
require_once __DIR__ . '/../../includes/db.php';

if(isset($_GET['id'])){

$id = $_GET['id'];

$stmt=$pdo->prepare("
DELETE FROM asignaciones_docentes
WHERE id=?
");

$stmt->execute([$id]);

}

header("Location: /sistema_academico/dashboards/coordinador_dashboard.php?modulo=asignar_materias");

exit;