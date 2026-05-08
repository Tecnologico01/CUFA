<?php
require_once __DIR__ . '/../../includes/db.php';

$id = $_GET['id'] ?? null;
$accion = $_GET['accion'] ?? 'activar';

// Función auxiliar para redirigir sin errores de header
function redireccionar() {
    echo "<script>window.location.href='admin_dashboard.php?modulo=periodos_lista';</script>";
    exit;
}

if(!$id){
    redireccionar();
}

/* OBTENER TIPO DEL PERIODO */
$stmt = $pdo->prepare("SELECT tipo_periodo_id FROM periodos WHERE id = ?");
$stmt->execute([$id]);
$periodo = $stmt->fetch(PDO::FETCH_ASSOC);

if(!$periodo){
    redireccionar();
}

$tipo_id = $periodo['tipo_periodo_id'];

if($accion === 'activar'){
    /* DESACTIVA SOLO LOS DEL MISMO TIPO */
    $stmt = $pdo->prepare("UPDATE periodos SET activo = 0 WHERE tipo_periodo_id = ?");
    $stmt->execute([$tipo_id]);

    /* ACTIVA EL SELECCIONADO */
    $stmt = $pdo->prepare("UPDATE periodos SET activo = 1 WHERE id = ?");
    $stmt->execute([$id]);
} else {
    /* DESACTIVAR SOLO ESTE */
    $stmt = $pdo->prepare("UPDATE periodos SET activo = 0 WHERE id = ?");
    $stmt->execute([$id]);
}

// Redirección final
redireccionar();