<?php

require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/permisos.php';

validarPermiso('guardar_planeaciones');

if($_SERVER['REQUEST_METHOD'] !== 'POST'){
    header("Location: ../../dashboard.php");
    exit;
}

$asignacion_id = isset($_POST['asignacion_id']) ? (int) $_POST['asignacion_id'] : 0;
$tema_id = isset($_POST['tema_id']) ? (int) $_POST['tema_id'] : 0;
$semana = isset($_POST['semana']) ? (int) $_POST['semana'] : 0;

if($asignacion_id <= 0 || $tema_id <= 0 || $semana <= 0){
    die("Datos inválidos.");
}

try{

    $stmtExiste = $pdo->prepare("
        SELECT id
        FROM planeacion_semanal
        WHERE asignacion_id = ?
        AND tema_id = ?
        AND semana = ?
        LIMIT 1
    ");

    $stmtExiste->execute([
        $asignacion_id,
        $tema_id,
        $semana
    ]);

    if(!$stmtExiste->fetch()){

        $stmt = $pdo->prepare("
            INSERT INTO planeacion_semanal
            (
                asignacion_id,
                tema_id,
                semana
            )
            VALUES
            (
                ?, ?, ?
            )
        ");

        $stmt->execute([
            $asignacion_id,
            $tema_id,
            $semana
        ]);
    }

    header("Location: ../../dashboard.php?modulo=planeacion_semanal&asignacion_id=" . $asignacion_id);
    exit;

}catch(PDOException $e){

    die("Error al guardar planeación: " . $e->getMessage());
}