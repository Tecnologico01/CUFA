<?php

if(session_status() === PHP_SESSION_NONE){
    session_start();
}

/* =========================
   CONSULTAR PERMISO
========================= */
function tienePermiso($permiso){

    global $pdo;

    if(!isset($_SESSION['user_id'])){
        return false;
    }

    $usuario_id = $_SESSION['user_id'];

    $stmt = $pdo->prepare("
        SELECT COUNT(*)

        FROM usuarios u

        INNER JOIN rol_permisos rp
            ON rp.rol_id = u.rol_id

        INNER JOIN permisos p
            ON p.id = rp.permiso_id

        WHERE u.id = ?
        AND p.slug = ?
        AND u.activo = 1
    ");

    $stmt->execute([
        $usuario_id,
        $permiso
    ]);

    return $stmt->fetchColumn() > 0;
}

/* =========================
   VALIDAR PERMISO
========================= */
function validarPermiso($permiso){

    if(!tienePermiso($permiso)){
        http_response_code(403);

        echo "
        <div style='padding:30px;font-family:sans-serif'>
            <h2 style='color:red'>Acceso denegado</h2>
            <p>No tienes permisos para acceder a este módulo.</p>
        </div>
        ";

        exit;
    }
}