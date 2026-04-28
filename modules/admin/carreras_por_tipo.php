<?php

require_once __DIR__ . '/../../includes/db.php';

header('Content-Type: application/json');

$tipo = $_GET['tipo'] ?? null;

if(!$tipo){
echo json_encode([]);
exit;
}

$stmt = $pdo->prepare("
SELECT DISTINCT c.id, c.nombre
FROM carreras c
JOIN mallas_curriculares m 
ON m.carrera_id = c.id
WHERE m.tipo_periodo_id = ?
ORDER BY c.nombre
");

$stmt->execute([$tipo]);

$carreras = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode($carreras);