<?php
require_once __DIR__ . '/../../includes/db.php';

$id = $_GET['id'] ?? null;

if(!$id){
    echo "<script>alert('ID no especificado'); window.location.href='/sistema_academico/dashboards/admin_dashboard.php?modulo=lista_subasignaturas';</script>";
    exit;
}

try {

    /* VALIDOR SI ESTÁ EN USO */
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM materia_subasignatura WHERE subasignatura_id = ?");
    $stmt->execute([$id]);
    $enUso = $stmt->fetchColumn();

    if($enUso > 0){
        echo "<script>
            alert('No se puede eliminar: la subasignatura está asignada a una materia');
            window.location.href='/sistema_academico/dashboards/admin_dashboard.php?modulo=lista_subasignaturas';
        </script>";
        exit;
    }

    /* ELIMINAR */
    $stmt = $pdo->prepare("DELETE FROM subasignaturas WHERE id = ?");
    $stmt->execute([$id]);

    echo "<script>
        alert('Subasignatura eliminada correctamente');
        window.location.href='/sistema_academico/dashboards/admin_dashboard.php?modulo=lista_subasignaturas';
    </script>";
    exit;

} catch(Exception $e){

    echo "<script>
        alert('Error al eliminar la subasignatura');
        window.location.href='/sistema_academico/dashboards/admin_dashboard.php?modulo=lista_subasignaturas';
    </script>";
    exit;
}