<?php

require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/permisos.php';

header('Content-Type: application/json; charset=utf-8');

/* =========================
   VALIDAR PERMISO
========================= */

if (!tienePermiso('ver_subasignaturas')) {

    http_response_code(403);

    echo json_encode([
        'success' => false,
        'mensaje' => 'No tiene permisos para consultar subasignaturas.'
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

    echo json_encode([
        'success' => true,
        'total' => count($subasignaturas),
        'data' => $subasignaturas
    ]);

} catch (PDOException $e) {

    http_response_code(500);

    echo json_encode([
        'success' => false,
        'mensaje' => 'Error al obtener las subasignaturas.',
        'error' => $e->getMessage()
    ]);
}