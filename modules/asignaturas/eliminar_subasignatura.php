<?php

require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/permisos.php';

validarPermiso('eliminar_subasignaturas');

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

function volverListaSubasignaturas($mensaje = '', $tipo = 'info'){

    $url = "?modulo=lista_subasignaturas";

    if($mensaje !== ''){
        $url .= "&{$tipo}=" . urlencode($mensaje);
    }

    echo "
    <script>
        window.location.href='{$url}';
    </script>
    ";
    exit;
}

if($id <= 0){
    volverListaSubasignaturas('Subasignatura no válida', 'error');
}

try{

    $pdo->beginTransaction();

    /* =========================
       VALIDAR EXISTENCIA
    ========================= */
    $stmt = $pdo->prepare("
        SELECT id, clave, nombre
        FROM subasignaturas
        WHERE id = ?
        LIMIT 1
    ");

    $stmt->execute([$id]);
    $subasignatura = $stmt->fetch(PDO::FETCH_ASSOC);

    if(!$subasignatura){
        throw new Exception("Subasignatura no encontrada.");
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
    $enUso = (int) $stmt->fetchColumn();

    if($enUso > 0){
        throw new Exception("No puedes eliminar esta subasignatura porque está vinculada a una o más asignaturas.");
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

    echo "
    <script>
        alert('Subasignatura eliminada correctamente');
        window.location.href='?modulo=lista_subasignaturas';
    </script>
    ";
    exit;

}catch(Exception $e){

    if($pdo->inTransaction()){
        $pdo->rollBack();
    }

    $msg = htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');

    echo "
    <script>
        alert('{$msg}');
        window.location.href='?modulo=lista_subasignaturas';
    </script>
    ";
    exit;
}
?>