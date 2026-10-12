<?php

require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/permisos.php';

validarPermiso('eliminar_grupos');

/*
|--------------------------------------------------------------------------
| VALIDAR ID
|--------------------------------------------------------------------------
*/

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

if($id <= 0){

    header("Location: ?modulo=lista_grupos&error=grupo_invalido");
    exit;
}

/*
|--------------------------------------------------------------------------
| ELIMINAR GRUPO
|--------------------------------------------------------------------------
*/

try{

    $pdo->beginTransaction();

    /*
    |--------------------------------------------------------------------------
    | VALIDAR EXISTENCIA
    |--------------------------------------------------------------------------
    */

    $stmt = $pdo->prepare("
        SELECT id, nombre
        FROM grupos
        WHERE id = ?
    ");

    $stmt->execute([$id]);

    $grupo = $stmt->fetch(PDO::FETCH_ASSOC);

    if(!$grupo){

        throw new Exception("El grupo no existe.");
    }

    /*
    |--------------------------------------------------------------------------
    | VALIDAR ALUMNOS ASIGNADOS
    |--------------------------------------------------------------------------
    */

    $stmt = $pdo->prepare("
        SELECT COUNT(*)
        FROM grupos_alumnos
        WHERE grupo_id = ?
    ");

    $stmt->execute([$id]);

    $total_alumnos = (int) $stmt->fetchColumn();

    if($total_alumnos > 0){

        throw new Exception("No puedes eliminar este grupo porque tiene alumnos asignados.");
    }

    /*
    |--------------------------------------------------------------------------
    | VALIDAR ASIGNACIONES DOCENTES
    |--------------------------------------------------------------------------
    */

    $stmt = $pdo->prepare("
        SELECT COUNT(*)
        FROM asignaciones_docentes
        WHERE grupo_id = ?
    ");

    $stmt->execute([$id]);

    $total_asignaciones = (int) $stmt->fetchColumn();

    if($total_asignaciones > 0){

        throw new Exception("No puedes eliminar este grupo porque tiene asignaciones docentes activas.");
    }

    /*
    |--------------------------------------------------------------------------
    | VALIDAR PARCIALES
    |--------------------------------------------------------------------------
    | Si quieres permitir eliminar parciales automáticamente, cambia esta
    | validación por DELETE FROM parciales WHERE grupo_id = ?
    |--------------------------------------------------------------------------
    */

    $stmt = $pdo->prepare("
        SELECT COUNT(*)
        FROM parciales
        WHERE grupo_id = ?
    ");

    $stmt->execute([$id]);

    $total_parciales = (int) $stmt->fetchColumn();

    if($total_parciales > 0){

        throw new Exception("No puedes eliminar este grupo porque tiene parciales registrados.");
    }

    /*
    |--------------------------------------------------------------------------
    | ELIMINAR GRUPO
    |--------------------------------------------------------------------------
    */

    $stmt = $pdo->prepare("
        DELETE FROM grupos
        WHERE id = ?
    ");

    $stmt->execute([$id]);

    $pdo->commit();

    header("Location: ?modulo=lista_grupos&msg=grupo_eliminado");
    exit;

}catch(Exception $e){

    if($pdo->inTransaction()){
        $pdo->rollBack();
    }

    $mensaje = urlencode($e->getMessage());

    header("Location: ?modulo=lista_grupos&error={$mensaje}");
    exit;
}