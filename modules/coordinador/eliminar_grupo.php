<?php
require_once __DIR__ . '/../../includes/db.php';

$id = $_GET['id'] ?? null;

if(!$id){
    die("ID inválido");
}

try {

    // Verificar si tiene alumnos
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM grupos_alumnos WHERE grupo_id=?");
    $stmt->execute([$id]);

    if($stmt->fetchColumn() > 0){
        die("No puedes eliminar el grupo porque tiene alumnos asignados");
    }

    $stmt = $pdo->prepare("DELETE FROM grupos WHERE id=?");
    $stmt->execute([$id]);

    header("Location: /sistema_academico/dashboards/coordinador_dashboard.php?modulo=lista_grupos");
    exit;

} catch (Exception $e){
    echo "Error: " . $e->getMessage();
}