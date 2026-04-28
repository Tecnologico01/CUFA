<?php
require_once __DIR__ . '/../../includes/db.php';

$carrera_id = $_GET['carrera_id'] ?? null;
$tipo = $_GET['tipo'] ?? null;

if(!$carrera_id || !$tipo){
    echo json_encode([]);
    exit;
}

/* BUSCAR MALLA */

$stmt = $pdo->prepare("
SELECT total_periodos, tipo_periodo_id
FROM mallas_curriculares
WHERE carrera_id = ?
AND tipo_periodo_id = ?
LIMIT 1
");

$stmt->execute([$carrera_id,$tipo]);
$malla = $stmt->fetch(PDO::FETCH_ASSOC);

if(!$malla){
    echo json_encode([]);
    exit;
}

/* DEFINIR NOMBRE DEL PERIODO */

$nombre = "Periodo";

switch($malla['tipo_periodo_id']){

    case 1:
        $nombre = "Semestre";
    break;

    case 2:
        $nombre = "Cuatrimestre";
    break;

    case 3:
        $nombre = "Trimestre";
    break;

    case 4:
        $nombre = "Bimestre";
    break;
}

/* GENERAR PERIODOS */

$periodos = [];

for($i=1;$i<=$malla['total_periodos'];$i++){

    $periodos[]=[
        "numero"=>$i,
        "nombre"=>$nombre." ".$i
    ];
}

echo json_encode($periodos);