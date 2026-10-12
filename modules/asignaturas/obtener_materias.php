<?php

require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/permisos.php';

header('Content-Type: application/json; charset=utf-8');

/* =========================
   VALIDAR PERMISO
========================= */

if(
    !tienePermiso('ver_asignaturas') &&
    !tienePermiso('editar_mallas') &&
    !tienePermiso('crear_mallas')
){
    http_response_code(403);

    echo json_encode([
        'success' => false,
        'mensaje' => 'No tiene permisos para consultar asignaturas.'
    ]);

    exit;
}

/* =========================
   OBTENER PARÁMETROS
========================= */

$carrera_id = isset($_GET['carrera'])
    ? (int) $_GET['carrera']
    : 0;

$grado = isset($_GET['periodo'])
    ? (int) $_GET['periodo']
    : 0;

/* =========================
   VALIDAR PARÁMETROS
========================= */

if($carrera_id <= 0 || $grado <= 0){

    echo json_encode([
        'success' => true,
        'total' => 0,
        'data' => []
    ]);

    exit;
}

/* =========================
   CONSULTAR MATERIAS
========================= */

try{

    $stmt = $pdo->prepare("
        SELECT
            id,
            clave,
            nombre,
            nombre_corto,
            creditos,
            tipo,
            grado,
            area_formacion,
            es_opcional
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

    echo json_encode([
        'success' => true,
        'total' => count($materias),
        'data' => $materias
    ]);

}catch(PDOException $e){

    http_response_code(500);

    echo json_encode([
        'success' => false,
        'mensaje' => 'Error al obtener las materias.',
        'error' => $e->getMessage()
    ]);
}