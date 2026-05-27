<?php

require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/permisos.php';

validarPermiso('eliminar_roles');

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

if($id <= 0){
    die("Rol no válido.");
}

/*
|--------------------------------------------------------------------------
| OBTENER ROL
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare("
    SELECT
        id,
        nombre,
        slug
    FROM roles
    WHERE id = ?
    LIMIT 1
");

$stmt->execute([$id]);

$rol = $stmt->fetch(PDO::FETCH_ASSOC);

if(!$rol){
    die("Rol no encontrado.");
}

/*
|--------------------------------------------------------------------------
| PROTEGER ROLES PRINCIPALES
|--------------------------------------------------------------------------
*/

$rolesSistema = [
    'administrador',
    'admin',
    'coordinador',
    'docente',
    'alumno',
    'estudiante',
    'control_escolar'
];

if(in_array($rol['slug'], $rolesSistema)){
    echo "
    <script>
        alert('No se puede eliminar un rol principal del sistema.');
        window.location.href='?modulo=roles_lista';
    </script>
    ";
    exit;
}

/*
|--------------------------------------------------------------------------
| VALIDAR USUARIOS ASIGNADOS
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare("
    SELECT COUNT(*)
    FROM usuarios
    WHERE rol_id = ?
");

$stmt->execute([$id]);

$totalUsuarios = (int) $stmt->fetchColumn();

if($totalUsuarios > 0){
    echo "
    <script>
        alert('No se puede eliminar este rol porque tiene usuarios asignados.');
        window.location.href='?modulo=roles_lista';
    </script>
    ";
    exit;
}

/*
|--------------------------------------------------------------------------
| ELIMINAR ROL Y PERMISOS ASOCIADOS
|--------------------------------------------------------------------------
*/

try{

    $pdo->beginTransaction();

    $stmt = $pdo->prepare("
        DELETE FROM rol_permisos
        WHERE rol_id = ?
    ");

    $stmt->execute([$id]);

    $stmt = $pdo->prepare("
        DELETE FROM roles
        WHERE id = ?
    ");

    $stmt->execute([$id]);

    $pdo->commit();

    echo "
    <script>
        alert('Rol eliminado correctamente.');
        window.location.href='?modulo=roles_lista';
    </script>
    ";
    exit;

}catch(Exception $e){

    if($pdo->inTransaction()){
        $pdo->rollBack();
    }

    echo "
    <script>
        alert('Error al eliminar el rol.');
        window.location.href='?modulo=roles_lista';
    </script>
    ";
    exit;
}