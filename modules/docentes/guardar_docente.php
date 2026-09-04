<?php

require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/permisos.php';

validarPermiso('editar_docentes');

if($_SERVER['REQUEST_METHOD'] !== 'POST'){

    header("Location: ?modulo=lista_docentes");
    exit;
}

/*
|--------------------------------------------------------------------------
| VALIDAR ID
|--------------------------------------------------------------------------
*/

$id = isset($_POST['id']) ? (int) $_POST['id'] : 0;

if($id <= 0){
    die("ID no válido");
}

/*
|--------------------------------------------------------------------------
| OBTENER DOCENTE
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare("
    SELECT 
        id,
        usuario_id
    FROM docentes
    WHERE id = ?
");

$stmt->execute([$id]);

$docente = $stmt->fetch(PDO::FETCH_ASSOC);

if(!$docente){
    die("Docente no encontrado");
}

$usuario_id = (int) $docente['usuario_id'];

/*
|--------------------------------------------------------------------------
| DATOS USUARIO
|--------------------------------------------------------------------------
*/

$nombres = trim($_POST['nombres'] ?? '');
$ap_paterno = trim($_POST['apellido_paterno'] ?? '');
$ap_materno = trim($_POST['apellido_materno'] ?? '');
$email = trim($_POST['email'] ?? '');
$activo = isset($_POST['activo']) ? (int) $_POST['activo'] : 1;
$password = $_POST['password'] ?? '';

/*
|--------------------------------------------------------------------------
| DATOS DOCENTE
|--------------------------------------------------------------------------
*/

$rfc = strtoupper(trim($_POST['rfc'] ?? ''));
$curp = strtoupper(trim($_POST['curp'] ?? ''));
$direccion = trim($_POST['direccion'] ?? '');

$licenciatura = trim($_POST['licenciatura'] ?? '');
$lic_titulo = trim($_POST['licenciatura_titulo'] ?? '');
$lic_cedula = trim($_POST['licenciatura_cedula'] ?? '');

$maestria = trim($_POST['maestria'] ?? '');
$maestria_titulo = trim($_POST['maestria_titulo'] ?? '');
$maestria_cedula = trim($_POST['maestria_cedula'] ?? '');

$doctorado = trim($_POST['doctorado'] ?? '');
$doctorado_titulo = trim($_POST['doctorado_titulo'] ?? '');
$doctorado_cedula = trim($_POST['doctorado_cedula'] ?? '');

/*
|--------------------------------------------------------------------------
| VALIDACIONES
|--------------------------------------------------------------------------
*/

if($nombres === '' || $ap_paterno === ''){
    die("Nombre y apellido paterno son obligatorios.");
}

if($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)){
    die("Correo electrónico inválido.");
}

/*
|--------------------------------------------------------------------------
| FUNCIÓN SUBIR ARCHIVO
|--------------------------------------------------------------------------
*/

function subirArchivoDocente($file, $ruta, $tipo){

    if(
        !isset($file) ||
        !isset($file['error']) ||
        $file['error'] !== UPLOAD_ERR_OK
    ){
        return null;
    }

    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

    $permitidas = [
        'pdf',
        'jpg',
        'jpeg',
        'png'
    ];

    if(!in_array($extension, $permitidas)){
        throw new Exception("Formato de archivo no permitido para {$tipo}.");
    }

    $nombreArchivo =
        $tipo .
        '_' .
        time() .
        '_' .
        bin2hex(random_bytes(4)) .
        '.' .
        $extension;

    if(!move_uploaded_file($file['tmp_name'], $ruta . $nombreArchivo)){
        throw new Exception("No se pudo subir el archivo {$tipo}.");
    }

    return 'uploads/docentes/' . $nombreArchivo;
}

/*
|--------------------------------------------------------------------------
| GUARDAR
|--------------------------------------------------------------------------
*/

try{

    $pdo->beginTransaction();

    /*
    |--------------------------------------------------------------------------
    | VALIDAR EMAIL DUPLICADO
    |--------------------------------------------------------------------------
    */

    if($email !== ''){

        $stmt = $pdo->prepare("
            SELECT id
            FROM usuarios
            WHERE email = ?
            AND id != ?
        ");

        $stmt->execute([
            $email,
            $usuario_id
        ]);

        if($stmt->fetch()){
            throw new Exception("El correo electrónico ya está registrado.");
        }
    }

    /*
    |--------------------------------------------------------------------------
    | ACTUALIZAR USUARIO
    |--------------------------------------------------------------------------
    */

    if(!empty($password)){

        $stmt = $pdo->prepare("
            UPDATE usuarios
            SET
                nombres = ?,
                apellido_paterno = ?,
                apellido_materno = ?,
                email = ?,
                activo = ?,
                password_hash = ?
            WHERE id = ?
        ");

        $stmt->execute([
            $nombres,
            $ap_paterno,
            $ap_materno,
            $email,
            $activo,
            password_hash($password, PASSWORD_DEFAULT),
            $usuario_id
        ]);

    }else{

        $stmt = $pdo->prepare("
            UPDATE usuarios
            SET
                nombres = ?,
                apellido_paterno = ?,
                apellido_materno = ?,
                email = ?,
                activo = ?
            WHERE id = ?
        ");

        $stmt->execute([
            $nombres,
            $ap_paterno,
            $ap_materno,
            $email,
            $activo,
            $usuario_id
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | ACTUALIZAR DOCENTE
    |--------------------------------------------------------------------------
    */

    $stmt = $pdo->prepare("
        UPDATE docentes
        SET
            rfc = ?,
            curp = ?,
            direccion = ?,
            licenciatura = ?,
            licenciatura_titulo = ?,
            licenciatura_cedula = ?,
            maestria = ?,
            maestria_titulo = ?,
            maestria_cedula = ?,
            doctorado = ?,
            doctorado_titulo = ?,
            doctorado_cedula = ?
        WHERE id = ?
    ");

    $stmt->execute([
        $rfc,
        $curp,
        $direccion,
        $licenciatura,
        $lic_titulo,
        $lic_cedula,
        $maestria,
        $maestria_titulo,
        $maestria_cedula,
        $doctorado,
        $doctorado_titulo,
        $doctorado_cedula,
        $id
    ]);

    /*
    |--------------------------------------------------------------------------
    | SUBIR DOCUMENTOS
    |--------------------------------------------------------------------------
    */

    $upload_dir = __DIR__ . '/../../uploads/docentes/';

    if(!is_dir($upload_dir)){
        mkdir($upload_dir, 0777, true);
    }

    $tipos = [
        'lic_certificado',
        'lic_titulo',
        'lic_cedula',
        'mae_certificado',
        'mae_titulo',
        'mae_cedula',
        'doc_certificado',
        'doc_titulo',
        'doc_cedula',
        'ine',
        'domicilio',
        'fiscal',
        'cv'
    ];

    foreach($tipos as $tipo){

        if(
            isset($_FILES[$tipo]) &&
            !empty($_FILES[$tipo]['name'])
        ){

            $archivo = subirArchivoDocente(
                $_FILES[$tipo],
                $upload_dir,
                $tipo
            );

            if($archivo){

                $stmt = $pdo->prepare("
                    SELECT id
                    FROM documentos_docente
                    WHERE docente_id = ?
                    AND tipo = ?
                ");

                $stmt->execute([
                    $id,
                    $tipo
                ]);

                $existe = $stmt->fetch(PDO::FETCH_ASSOC);

                if($existe){

                    $stmt = $pdo->prepare("
                        UPDATE documentos_docente
                        SET
                            archivo = ?,
                            fecha_subida = NOW()
                        WHERE docente_id = ?
                        AND tipo = ?
                    ");

                    $stmt->execute([
                        $archivo,
                        $id,
                        $tipo
                    ]);

                }else{

                    $stmt = $pdo->prepare("
                        INSERT INTO documentos_docente
                        (
                            docente_id,
                            tipo,
                            archivo,
                            fecha_subida
                        )
                        VALUES
                        (
                            ?, ?, ?, NOW()
                        )
                    ");

                    $stmt->execute([
                        $id,
                        $tipo,
                        $archivo
                    ]);
                }
            }
        }
    }

    $pdo->commit();

    header("Location: ?modulo=ver_docente&id=" . $id . "&ok=1");
    exit;

}catch(Exception $e){

    if($pdo->inTransaction()){
        $pdo->rollBack();
    }

    die("Error: " . $e->getMessage());
}