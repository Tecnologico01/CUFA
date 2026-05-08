<?php
require_once __DIR__ . '/../../includes/db.php';

/*
|--------------------------------------------------------------------------
| VALIDAR ID
|--------------------------------------------------------------------------
*/

$id = $_GET['id'] ?? null;

if (!$id || !is_numeric($id)) {

    die("Usuario no válido.");

}

/*
|--------------------------------------------------------------------------
| VERIFICAR USUARIO
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare("
    SELECT id, rol
    FROM usuarios
    WHERE id = ?
");

$stmt->execute([$id]);

$usuario = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$usuario) {

    die("El usuario no existe.");

}

/*
|--------------------------------------------------------------------------
| ELIMINAR DATOS RELACIONADOS
|--------------------------------------------------------------------------
*/

try {

    $pdo->beginTransaction();

    /*
    |--------------------------------------------------------------------------
    | SI ES DOCENTE
    |--------------------------------------------------------------------------
    */

    if ($usuario['rol'] === 'docente') {

        /*
        |--------------------------------------------------------------
        | OBTENER DOCENTE
        |--------------------------------------------------------------
        */

        $stmtDoc = $pdo->prepare("
            SELECT id
            FROM docentes
            WHERE usuario_id = ?
        ");

        $stmtDoc->execute([$id]);

        $docente = $stmtDoc->fetch(PDO::FETCH_ASSOC);

        if ($docente) {

            /*
            |----------------------------------------------------------
            | ELIMINAR ASIGNACIONES
            |----------------------------------------------------------
            */

            $stmtAsignaciones = $pdo->prepare("
                DELETE FROM asignaciones_docentes
                WHERE docente_id = ?
            ");

            $stmtAsignaciones->execute([$docente['id']]);

            /*
            |----------------------------------------------------------
            | ELIMINAR DOCENTE
            |----------------------------------------------------------
            */

            $stmtEliminarDoc = $pdo->prepare("
                DELETE FROM docentes
                WHERE id = ?
            ");

            $stmtEliminarDoc->execute([$docente['id']]);

        }

    }

    /*
    |--------------------------------------------------------------------------
    | ELIMINAR USUARIO
    |--------------------------------------------------------------------------
    */

    $stmtEliminarUsuario = $pdo->prepare("
        DELETE FROM usuarios
        WHERE id = ?
    ");

    $stmtEliminarUsuario->execute([$id]);

    $pdo->commit();

} catch (PDOException $e) {

    $pdo->rollBack();

    die("Error al eliminar usuario: " . $e->getMessage());

}

/*
|--------------------------------------------------------------------------
| REDIRECCIONAR
|--------------------------------------------------------------------------
*/

header("Location: /sistema_academico/dashboards/admin_dashboard.php?modulo=usuarios_lista");
exit;
?>