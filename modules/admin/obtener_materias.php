<?php

require_once __DIR__ . '/../../includes/db.php';

header('Content-Type: application/json');

$carrera=$_GET['carrera'] ?? null;
$periodo=$_GET['periodo'] ?? null;

if(!$carrera || !$periodo){
echo json_encode([]);
exit;
}

$stmt=$pdo->prepare("
SELECT m.id,m.nombre
FROM malla_materias mm
JOIN materias m ON m.id=mm.materia_id
JOIN mallas_curriculares mc ON mc.id=mm.malla_id
WHERE mc.carrera_id=?
AND mm.periodo_numero=?
ORDER BY m.nombre
");

$stmt->execute([$carrera,$periodo]);

echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));