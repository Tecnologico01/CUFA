<?php

require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/permisos.php';

validarPermiso('eliminar_asignaturas');

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

function redirigirListaMaterias($mensaje = '', $tipo = 'info'){
    $url = "?modulo=lista_asignaturas";

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
    redirigirListaMaterias('Materia no válida', 'error');
}

try{

    $pdo->beginTransaction();

    /* =========================
       VALIDAR EXISTENCIA
    ========================= */
    $stmt = $pdo->prepare("
        SELECT id, nombre, clave
        FROM materias
        WHERE id = ?
        LIMIT 1
    ");

    $stmt->execute([$id]);
    $materia = $stmt->fetch(PDO::FETCH_ASSOC);

    if(!$materia){
        throw new Exception("Materia no encontrada");
    }

    /* =========================
       VALIDAR SERIACIÓN
       Si otra materia depende de esta, no se elimina.
    ========================= */
    $stmt = $pdo->prepare("
        SELECT COUNT(*)
        FROM materias
        WHERE seriacion_id = ?
    ");
    $stmt->execute([$id]);

    if((int)$stmt->fetchColumn() > 0){
        throw new Exception("No puedes eliminar esta materia porque otra asignatura la usa como seriación.");
    }

    /* =========================
       VALIDAR ASIGNACIONES DOCENTES
    ========================= */
    $stmt = $pdo->prepare("
        SELECT COUNT(*)
        FROM asignaciones_docentes
        WHERE materia_id = ?
    ");
    $stmt->execute([$id]);

    if((int)$stmt->fetchColumn() > 0){
        throw new Exception("No puedes eliminar esta materia porque tiene asignaciones docentes activas.");
    }

    /* =========================
       VALIDAR MALLAS CURRICULARES
    ========================= */
    $stmt = $pdo->prepare("
        SELECT COUNT(*)
        FROM malla_materias
        WHERE materia_id = ?
    ");
    $stmt->execute([$id]);

    if((int)$stmt->fetchColumn() > 0){
        throw new Exception("No puedes eliminar esta materia porque está vinculada a una malla curricular.");
    }

    /* =========================
       ELIMINAR RELACIONES DIRECTAS SEGURAS
    ========================= */

    $pdo->prepare("
        DELETE FROM materia_subasignatura
        WHERE materia_id = ?
    ")->execute([$id]);

    /* =========================
       ELIMINAR MATERIA
    ========================= */
    $stmt = $pdo->prepare("
        DELETE FROM materias
        WHERE id = ?
    ");

    $stmt->execute([$id]);

    $pdo->commit();

    echo "
    <script>
        alert('Materia eliminada correctamente');
        window.location.href='?modulo=lista_asignaturas';
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
        window.location.href='?modulo=lista_asignaturas';
    </script>
    ";
    exit;
}
?>