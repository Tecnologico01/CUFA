<?php
require_once __DIR__ . '/config.php';
try {
    $pdo = new PDO('mysql:host='.DB_HOST.';port='.(defined('DB_PORT') ? DB_PORT : '3306').';dbname='.DB_NAME.';charset=utf8mb4', DB_USER, DB_PASS, [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC,PDO::ATTR_EMULATE_PREPARES=>false]);
} catch (PDOException $e) {
    error_log($e->getMessage());
    http_response_code(503);
    exit('No fue posible conectar con la base de datos. Revisa includes/config.php y el servicio MySQL.');
}

require_once __DIR__.'/acceso_directo.php';
