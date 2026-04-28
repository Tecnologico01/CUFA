<?php
require_once __DIR__ . '/../../includes/db.php';

$id = $_GET['id'] ?? null;

if(!$id){
header("Location: /sistema_academico/dashboards/admin_dashboard.php?modulo=parciales_anteriores");
exit;
}

$stmt = $pdo->prepare("
UPDATE parciales
SET activo = 1
WHERE id = ?
");

$stmt->execute([$id]);

header("Location: /sistema_academico/dashboards/admin_dashboard.php?modulo=parciales_anteriores");
exit;