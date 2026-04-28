<?php
require_once __DIR__ . '/../../includes/db.php';

header('Content-Type: application/json');

$stmt = $pdo->query("SELECT id, nombre FROM subasignaturas ORDER BY nombre ASC");

echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));