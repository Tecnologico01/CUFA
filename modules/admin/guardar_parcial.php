<?php
require_once __DIR__ . '/../../includes/db.php';

$periodo_id = $_POST['periodo_id'] ?? null;
$carrera_id = $_POST['carrera_id'] ?? null;
$materia_id = $_POST['materia_id'] ?? null;
$grupo_id = $_POST['grupo_id'] ?? null;
$numero = $_POST['numero'] ?? null;

if(!$periodo_id || !$materia_id || !$grupo_id || !$numero){
    die("Datos incompletos");
}

/* OBTENER AÑO DESDE EL PERIODO */

$stmt = $pdo->prepare("
SELECT YEAR(fecha_inicio) as anio
FROM periodos
WHERE id=?
");

$stmt->execute([$periodo_id]);

$periodo = $stmt->fetch(PDO::FETCH_ASSOC);

if(!$periodo){
    die("Periodo no encontrado");
}

$anio = $periodo['anio'];

/* GUARDAR PARCIAL */

$stmt = $pdo->prepare("
INSERT INTO parciales
(periodo_id,carrera_id,materia_id,grupo_id,numero,anio)
VALUES (?,?,?,?,?,?)
");

$stmt->execute([
$periodo_id,
$carrera_id,
$materia_id,
$grupo_id,
$numero,
$anio
]);

header("Location: /sistema_academico/dashboards/admin_dashboard.php?modulo=parciales_lista");

exit;