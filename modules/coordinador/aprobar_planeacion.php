<?php

require_once __DIR__ . '/../../includes/db.php';

$id = $_GET['id'];
$estado = $_GET['estado'];

/* actualizar estado */

$stmt = $pdo->prepare("
UPDATE planeaciones
SET estado = ?
WHERE id = ?
");

$stmt->execute([$estado,$id]);

/* regresar al modulo revisar planeaciones */

header("Location: /sistema_academico/dashboards/coordinador_dashboard.php?mod=revisar_planeaciones");
exit;