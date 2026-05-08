<?php
require_once __DIR__ . '/../../includes/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die("Acceso no permitido");
}

$id = $_POST['id'] ?? null;
$nombre = $_POST['nombre'] ?? '';
$fecha_inicio = $_POST['fecha_inicio'] ?? null;
$fecha_fin = $_POST['fecha_fin'] ?? null;
$activo = $_POST['activo'] ?? 0;
$tipo_periodo_id = $_POST['tipo_periodo_id'] ?? null;

/* Validaciones */
if (!$id || !$nombre || !$fecha_inicio || !$fecha_fin || !$tipo_periodo_id) {
    die("Datos incompletos");
}

if ($fecha_inicio > $fecha_fin) {
    die("La fecha de inicio no puede ser mayor a la fecha fin");
}

/* Actualizar */
$stmt = $pdo->prepare("
    UPDATE periodos
    SET nombre=?, fecha_inicio=?, fecha_fin=?, activo=?, tipo_periodo_id=?
    WHERE id=?
");

$stmt->execute([
    $nombre,
    $fecha_inicio,
    $fecha_fin,
    $activo,
    $tipo_periodo_id,
    $id
]);

/* Redirección */
header("Location: /sistema_academico/dashboards/admin_dashboard.php?modulo=periodos_lista");
exit;