<?php
require_once __DIR__ . '/../../includes/db.php';

$id = $_GET['id'] ?? null;

if(!$id){
    header("Location: /sistema_academico/dashboards/admin_dashboard.php?modulo=lista_asignaturas");
    exit;
}

try{
    $pdo->beginTransaction();

    /* =========================
       VALIDAR EXISTENCIA
    ========================= */
    $stmt = $pdo->prepare("SELECT id FROM materias WHERE id = ?");
    $stmt->execute([$id]);

    if(!$stmt->fetch()){
        throw new Exception("Materia no encontrada");
    }

    /* =========================
       ELIMINAR RELACIONES
    ========================= */

    // Subasignaturas
    $pdo->prepare("DELETE FROM materia_subasignatura WHERE materia_id = ?")->execute([$id]);

    /*
    // Temas / unidades
    $pdo->prepare("DELETE FROM temas_materia WHERE materia_id = ?")->execute([$id]);

    // Calificaciones
    $pdo->prepare("DELETE FROM calificaciones WHERE materia_id = ?")->execute([$id]);

    // Asignaciones docente-grupo
    $pdo->prepare("DELETE FROM asignaciones WHERE materia_id = ?")->execute([$id]);
    */

    /* =========================
       ELIMINAR MATERIA
    ========================= */
    $pdo->prepare("DELETE FROM materias WHERE id = ?")->execute([$id]);

    $pdo->commit();

    header("Location: /sistema_academico/dashboards/admin_dashboard.php?modulo=lista_asignaturas&msg=eliminado");
    exit;

}catch(Exception $e){

    $pdo->rollBack();

    header("Location: /sistema_academico/dashboards/admin_dashboard.php?modulo=lista_asignaturas&error=1");
    exit;
}
?>