<?php

require_once __DIR__ . '/../../includes/db.php';

$id = $_GET['id'];

$stmt = $pdo->prepare("

UPDATE calificaciones
SET estado='aprobado'
WHERE id=?

");

$stmt->execute([$id]);

header("Location: /sistema_academico/dashboards/coordinador_dashboard.php?modulo=revisar_calificaciones");