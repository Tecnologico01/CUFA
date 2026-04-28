<?php
require_once __DIR__ . '/../../includes/db.php';

$id = $_GET['id'] ?? null;
$accion = $_GET['accion'] ?? 'activar';

if(!$id){
    header("Location: admin_dashboard.php?modulo=periodos_lista");
    exit;
}

/* OBTENER TIPO DEL PERIODO */

$stmt = $pdo->prepare("
SELECT tipo_periodo_id 
FROM periodos 
WHERE id = ?
");

$stmt->execute([$id]);
$periodo = $stmt->fetch(PDO::FETCH_ASSOC);

if(!$periodo){
    header("Location: admin_dashboard.php?modulo=periodos_lista");
    exit;
}

$tipo_id = $periodo['tipo_periodo_id'];

if($accion === 'activar'){

    /* DESACTIVA SOLO LOS DEL MISMO TIPO */
    $stmt = $pdo->prepare("
    UPDATE periodos 
    SET activo = 0 
    WHERE tipo_periodo_id = ?
    ");
    $stmt->execute([$tipo_id]);

    /* ACTIVA EL SELECCIONADO */
    $stmt = $pdo->prepare("
    UPDATE periodos 
    SET activo = 1 
    WHERE id = ?
    ");
    $stmt->execute([$id]);

}else{

    /* DESACTIVAR SOLO ESTE */
    $stmt = $pdo->prepare("
    UPDATE periodos 
    SET activo = 0 
    WHERE id = ?
    ");
    $stmt->execute([$id]);

}

header("Location: admin_dashboard.php?modulo=periodos_lista");
exit;