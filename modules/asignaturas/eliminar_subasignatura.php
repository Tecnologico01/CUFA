<?php

require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/permisos.php';

if(!tienePermiso('eliminar_asignaturas')){
    echo "<div class='p-6 text-red-600 font-bold'>Acceso denegado</div>";
    exit;
}

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

if($id <= 0){

    header("Location: /sistema_academico/dashboards/dashboard.php?modulo=lista_subasignaturas");
    exit;
}

try {

    $pdo->beginTransaction();

    /* =========================
       VALIDAR EXISTENCIA
    ========================= */

    $stmt = $pdo->prepare("
        SELECT id 
        FROM subasignaturas
        WHERE id = ?
    ");

    $stmt->execute([$id]);

    if(!$stmt->fetch()){

        throw new Exception("Subasignatura no encontrada");
    }

    /* =========================
       VALIDAR SI ESTÁ EN USO
    ========================= */

    $stmt = $pdo->prepare("
        SELECT COUNT(*)
        FROM materia_subasignatura
        WHERE subasignatura_id = ?
    ");

    $stmt->execute([$id]);

    $enUso = $stmt->fetchColumn();

    if($enUso > 0){

        $pdo->rollBack();

        header("Location: /sistema_academico/dashboards/dashboard.php?modulo=lista_subasignaturas&error=en_uso");
        exit;
    }

    /* =========================
       ELIMINAR
    ========================= */

    $stmt = $pdo->prepare("
        DELETE FROM subasignaturas
        WHERE id = ?
    ");

    $stmt->execute([$id]);

    $pdo->commit();

    header("Location: /sistema_academico/dashboards/dashboard.php?modulo=lista_subasignaturas&msg=eliminado");
    exit;

} catch(Exception $e){

    if($pdo->inTransaction()){
        $pdo->rollBack();
    }

    header("Location: /sistema_academico/dashboards/dashboard.php?modulo=lista_subasignaturas&error=1");
    exit;
}
?>