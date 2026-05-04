<?php
require_once __DIR__ . '/../../includes/db.php';

header('Content-Type: application/json');

$carrera_nombre = $_GET['carrera_nombre'] ?? null;
$periodo_id = $_GET['periodo_id'] ?? null;

if(!$carrera_nombre || !$periodo_id){
    echo json_encode([]);
    exit;
}

try{

    $stmt = $pdo->prepare("
        SELECT id, nombre
        FROM grupos
        WHERE 
            LOWER(TRIM(carrera_nombre)) = LOWER(TRIM(?))
        AND periodo_id = ?
        ORDER BY nombre
    ");

    $stmt->execute([$carrera_nombre, $periodo_id]);

    $grupos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($grupos);

}catch(Exception $e){
    echo json_encode([]);
}