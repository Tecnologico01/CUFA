<?php

require_once __DIR__ . '/config.php';

try {

    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS
    );

    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

} catch (PDOException $e) {

    if (APP_DEBUG) {
        die("Error de conexión: " . $e->getMessage());
    }

    die("Error de conexión a la base de datos.");

}

?>
