<?php

require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/permisos.php';

/*
|--------------------------------------------------------------------------
| PERMISO
|--------------------------------------------------------------------------
*/

validarPermiso('ver_grupos');

/*
|--------------------------------------------------------------------------
| RESPUESTA JSON
|--------------------------------------------------------------------------
*/

header('Content-Type: application/json; charset=utf-8');

/*
|--------------------------------------------------------------------------
| OBTENER DATOS
|--------------------------------------------------------------------------
*/

$carrera_id = isset($_GET['carrera_id'])
    ? (int) $_GET['carrera_id']
    : 0;

$periodo_id = isset($_GET['periodo_id'])
    ? (int) $_GET['periodo_id']
    : 0;

/*
|--------------------------------------------------------------------------
| VALIDAR DATOS
|--------------------------------------------------------------------------
*/

if($carrera_id <= 0 || $periodo_id <= 0){

    echo json_encode([
        'success' => false,
        'data' => [],
        'message' => 'Parámetros inválidos'
    ]);

    exit;
}

try{

    /*
    |--------------------------------------------------------------------------
    | OBTENER GRUPOS
    |--------------------------------------------------------------------------
    */

    $stmt = $pdo->prepare("
        SELECT
            id,
            nombre
        FROM grupos
        WHERE carrera_id = ?
        AND periodo_id = ?
        ORDER BY nombre ASC
    ");

    $stmt->execute([
        $carrera_id,
        $periodo_id
    ]);

    $grupos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    /*
    |--------------------------------------------------------------------------
    | RESPUESTA EXITOSA
    |--------------------------------------------------------------------------
    */

    echo json_encode([
        'success' => true,
        'data' => $grupos
    ]);

}catch(Exception $e){

    /*
    |--------------------------------------------------------------------------
    | ERROR
    |--------------------------------------------------------------------------
    */

    echo json_encode([
        'success' => false,
        'data' => [],
        'message' => 'Error al obtener los grupos'
    ]);
}