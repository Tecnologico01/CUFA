<?php

require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/permisos.php';

header('Content-Type: application/json');

if(session_status() === PHP_SESSION_NONE){
    session_start();
}

/* =========================
VALIDAR PERISO
========================= */

if(!tienePermiso('gestionar_asignaturas')){
    echo json_encode([
        'error' => 'Sin permisos'
    ]);
    exit;
}

/* =========================
OBTENER DATOS
========================= */

$carrera_id = isset($_GET['carrera'])
    ? (int) $_GET['carrera']
    : 0;

$grado = isset($_GET['periodo'])
    ? (int) $_GET['periodo']
    : 0;

/* =========================
VALIDAR
========================= */

if($carrera_id <= 0 || $grado <= 0){

    echo json_encode([]);
    exit;
}

/* =========================
CONSULTAR MATERIAS
========================= */

$stmt = $pdo->prepare("
    SELECT
        id,
        clave,
        nombre,
        creditos,
        tipo
    FROM materias
    WHERE carrera_id = ?
    AND grado = ?
    ORDER BY nombre ASC
");

$stmt->execute([
    $carrera_id,
    $grado
]);

$materias = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* =========================
RESPUESTA JSON
========================= */

echo json_encode($materias);