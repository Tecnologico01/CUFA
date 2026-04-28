<?php
require_once __DIR__ . '/../../includes/db.php';

$id = $_GET['id'] ?? null;

if(!$id){
    echo "<script>
        alert('ID no especificado');
        window.location.href='/sistema_academico/dashboards/admin_dashboard.php?modulo=lista_asignaturas';
    </script>";
    exit;
}

try{
    $pdo->beginTransaction();

    /* 🔥 IMPORTANTE: eliminar relaciones primero */
    $stmt = $pdo->prepare("DELETE FROM materia_subasignatura WHERE materia_id = ?");
    $stmt->execute([$id]);

    /* 🔥 eliminar materia */
    $stmt = $pdo->prepare("DELETE FROM materias WHERE id = ?");
    $stmt->execute([$id]);

    $pdo->commit();

    echo "<script>
        alert('Materia eliminada correctamente');
        window.location.href='/sistema_academico/dashboards/admin_dashboard.php?modulo=lista_asignaturas';
    </script>";

}catch(Exception $e){
    $pdo->rollBack();

    echo "<script>
        alert('Error al eliminar la materia');
        window.location.href='/sistema_academico/dashboards/admin_dashboard.php?modulo=lista_asignaturas';
    </script>";
}
?>