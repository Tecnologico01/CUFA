<?php

require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/permisos.php';

if(!tienePermiso('editar_usuarios')){
    die("Sin permisos");
}

/*
|--------------------------------------------------------------------------
| VALIDAR ID
|--------------------------------------------------------------------------
*/

$id = $_POST['id'] ?? null;

if(!$id){

    die("Usuario inválido");
}

/*
|--------------------------------------------------------------------------
| OBTENER DATOS
|--------------------------------------------------------------------------
*/

$nombres = trim($_POST['nombres'] ?? '');
$apellido_paterno = trim($_POST['apellido_paterno'] ?? '');
$apellido_materno = trim($_POST['apellido_materno'] ?? '');
$username = trim($_POST['username'] ?? '');
$email = trim($_POST['email'] ?? '');
$numero_identificador = trim($_POST['numero_identificador'] ?? '');
$rol_id = $_POST['rol_id'] ?? null;
$activo = $_POST['activo'] ?? 1;
$password = $_POST['password'] ?? '';

/*
|--------------------------------------------------------------------------
| VALIDACIONES
|--------------------------------------------------------------------------
*/

if(
    empty($nombres) ||
    empty($apellido_paterno) ||
    empty($username) ||
    empty($rol_id)
){

    die("Completa los campos obligatorios.");
}

try{

    /*
    |--------------------------------------------------------------------------
    | VALIDAR USERNAME REPETIDO
    |--------------------------------------------------------------------------
    */

    $stmt = $pdo->prepare("
        SELECT id
        FROM usuarios
        WHERE username = ?
        AND id != ?
    ");

    $stmt->execute([$username, $id]);

    if($stmt->fetch()){

        die("El username ya está registrado.");
    }

    /*
    |--------------------------------------------------------------------------
    | VALIDAR EMAIL REPETIDO
    |--------------------------------------------------------------------------
    */

    if(!empty($email)){

        $stmt = $pdo->prepare("
            SELECT id
            FROM usuarios
            WHERE email = ?
            AND id != ?
        ");

        $stmt->execute([$email, $id]);

        if($stmt->fetch()){

            die("El correo ya está registrado.");
        }
    }

    /*
    |--------------------------------------------------------------------------
    | ACTUALIZAR USUARIO
    |--------------------------------------------------------------------------
    */

    if(!empty($password)){

        $password_hash = password_hash($password, PASSWORD_DEFAULT);

        $stmt = $pdo->prepare("
            UPDATE usuarios
            SET
                nombres = ?,
                apellido_paterno = ?,
                apellido_materno = ?,
                username = ?,
                email = ?,
                rol_id = ?,
                numero_identificador = ?,
                activo = ?,
                password_hash = ?
            WHERE id = ?
        ");

        $stmt->execute([

            $nombres,
            $apellido_paterno,
            $apellido_materno,
            $username,
            $email,
            $rol_id,
            $numero_identificador,
            $activo,
            $password_hash,
            $id

        ]);

    }else{

        $stmt = $pdo->prepare("
            UPDATE usuarios
            SET
                nombres = ?,
                apellido_paterno = ?,
                apellido_materno = ?,
                username = ?,
                email = ?,
                rol_id = ?,
                numero_identificador = ?,
                activo = ?
            WHERE id = ?
        ");

        $stmt->execute([

            $nombres,
            $apellido_paterno,
            $apellido_materno,
            $username,
            $email,
            $rol_id,
            $numero_identificador,
            $activo,
            $id

        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | REDIRECCIÓN
    |--------------------------------------------------------------------------
    */

    header("Location: ../dashboards/admin_dashboard.php?modulo=usuarios_lista");

    exit;

}catch(PDOException $e){

    die("Error del sistema: " . $e->getMessage());
}