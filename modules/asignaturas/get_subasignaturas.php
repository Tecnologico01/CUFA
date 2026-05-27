<?php

require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/permisos.php';

header('Content-Type: application/json; charset=utf-8');

/* =========================
   VALIDAR PERMISO
========================= */

if(!tienePermiso('crear_asignaturas')){
    
    http_response_code(403);

    echo json_encode([
        'error' => true,
        'mensaje' => 'No autorizado'
    ]);

    exit;
}

try {

    $stmt = $pdo->query("
        SELECT 
            id,
            clave,
            nombre
        FROM subasignaturas
        ORDER BY nombre ASC
    ");

    $subasignaturas = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($subasignaturas);

} catch(Exception $e){

    http_response_code(500);

    echo json_encode([
        'error' => true,
        'mensaje' => 'Error al obtener subasignaturas'
    ]);
}