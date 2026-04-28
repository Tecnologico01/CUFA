<?php
require_once __DIR__ . '/../../includes/db.php';

$id = $_GET['id'] ?? null;

if(!$id){
die("Periodo inválido");
}

/* VERIFICAR SI TIENE GRUPOS */

$stmt = $pdo->prepare("SELECT COUNT(*) FROM grupos WHERE periodo_id=?");
$stmt->execute([$id]);
$total = $stmt->fetchColumn();

if($total > 0){

die("No se puede eliminar el periodo porque tiene grupos asociados.");

}

/* ELIMINAR */

$stmt = $pdo->prepare("DELETE FROM periodos WHERE id=?");
$stmt->execute([$id]);

header("Location: /sistema_academico/dashboards/admin_dashboard.php?modulo=periodos_lista");
exit;