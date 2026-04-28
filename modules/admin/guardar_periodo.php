<?php
require_once __DIR__ . '/../../includes/db.php';

$id = $_POST['id'];
$nombre = $_POST['nombre'];
$fecha_inicio = $_POST['fecha_inicio'];
$fecha_fin = $_POST['fecha_fin'];
$activo = $_POST['activo'];

$stmt = $pdo->prepare("
UPDATE periodos
SET nombre=?, fecha_inicio=?, fecha_fin=?, activo=?
WHERE id=?
");

$stmt->execute([
$nombre,
$fecha_inicio,
$fecha_fin,
$activo,
$id
]);

header("Location: /sistema_academico/dashboards/admin_dashboard.php?modulo=periodos_lista");
exit;