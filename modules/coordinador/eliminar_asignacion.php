<?php
require_once __DIR__ . '/../../includes/db.php';

if(!isset($_GET['id']) || empty($_GET['id'])){
    header("Location: /sistema_academico/dashboards/coordinador_dashboard.php?modulo=ver_asignaciones");
    exit;
}

$id = (int)$_GET['id'];

try{

    /* =========================
       INICIAR TRANSACCIÓN
    ========================= */
    $pdo->beginTransaction();

    /* =========================
       OBTENER DATOS DE LA ASIGNACIÓN
    ========================= */
    $stmt = $pdo->prepare("
        SELECT 
            grupo_id,
            materia_id,
            periodo_id
        FROM asignaciones_docentes
        WHERE id = ?
    ");

    $stmt->execute([$id]);

    $asignacion = $stmt->fetch(PDO::FETCH_ASSOC);

    if(!$asignacion){
        throw new Exception("La asignación no existe");
    }

    $grupo_id   = $asignacion['grupo_id'];
    $materia_id = $asignacion['materia_id'];
    $periodo_id = $asignacion['periodo_id'];

    /* =========================
       ELIMINAR PARCIALES RELACIONADOS
    ========================= */
    $stmt = $pdo->prepare("
        DELETE FROM parciales
        WHERE grupo_id = ?
        AND materia_id = ?
        AND periodo_id = ?
    ");

    $stmt->execute([
        $grupo_id,
        $materia_id,
        $periodo_id
    ]);

    /* =========================
       ELIMINAR ASIGNACIÓN
    ========================= */
    $stmt = $pdo->prepare("
        DELETE FROM asignaciones_docentes
        WHERE id = ?
    ");

    $stmt->execute([$id]);

    /* =========================
       CONFIRMAR CAMBIOS
    ========================= */
    $pdo->commit();

}catch(Exception $e){

    $pdo->rollBack();

}

header("Location: /sistema_academico/dashboards/coordinador_dashboard.php?modulo=ver_asignaciones");

exit;
?>