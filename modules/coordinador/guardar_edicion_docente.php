<?php
require_once __DIR__ . '/../../includes/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../../dashboards/coordinador_dashboard.php?modulo=docentes");
    exit;
}

$id = $_POST['id'] ?? null;

if (!$id) {
    die("ID no válido");
}

/* =========================
OBTENER USUARIO_ID
========================= */
$stmt = $pdo->prepare("SELECT usuario_id FROM docentes WHERE id=?");
$stmt->execute([$id]);
$docente = $stmt->fetch();

if (!$docente) {
    die("Docente no encontrado");
}

$usuario_id = $docente['usuario_id'];

/* =========================
DATOS USUARIO
========================= */
$nombres = trim($_POST['nombres']);
$ap_paterno = trim($_POST['apellido_paterno']);
$ap_materno = trim($_POST['apellido_materno']);
$username = trim($_POST['username']);
$email = trim($_POST['email']);
$password = $_POST['password'] ?? '';

/* =========================
DATOS DOCENTE
========================= */
$rfc = trim($_POST['rfc']);
$curp = trim($_POST['curp']);
$direccion = trim($_POST['direccion']);
$licenciatura = trim($_POST['licenciatura']);
$lic_titulo = trim($_POST['licenciatura_titulo']);
$lic_cedula = trim($_POST['licenciatura_cedula']);
$maestria = trim($_POST['maestria']);
$doctorado = trim($_POST['doctorado']);

/* =========================
FUNCION SUBIR ARCHIVO
========================= */
function subirArchivo($file, $ruta) {
    if (isset($file) && $file['error'] === 0) {
        $nombre = time() . "_" . basename($file['name']);
        move_uploaded_file($file['tmp_name'], $ruta . $nombre);
        return 'uploads/docentes/' . $nombre;
    }
    return null;
}

/* =========================
INICIAR TRANSACCION
========================= */
try {

    $pdo->beginTransaction();

    /* =========================
    ACTUALIZAR USUARIO
    ========================= */
    if (!empty($password)) {

        $stmt = $pdo->prepare("
            UPDATE usuarios 
            SET nombres=?, apellido_paterno=?, apellido_materno=?, username=?, email=?, password_hash=? 
            WHERE id=?
        ");

        $stmt->execute([
            $nombres,
            $ap_paterno,
            $ap_materno,
            $username,
            $email,
            password_hash($password, PASSWORD_DEFAULT),
            $usuario_id
        ]);

    } else {

        $stmt = $pdo->prepare("
            UPDATE usuarios 
            SET nombres=?, apellido_paterno=?, apellido_materno=?, username=?, email=? 
            WHERE id=?
        ");

        $stmt->execute([
            $nombres,
            $ap_paterno,
            $ap_materno,
            $username,
            $email,
            $usuario_id
        ]);
    }

    /* =========================
    ACTUALIZAR DOCENTE
    ========================= */
    $stmt = $pdo->prepare("
        UPDATE docentes 
        SET rfc=?, curp=?, direccion=?, licenciatura=?, licenciatura_titulo=?, licenciatura_cedula=?, maestria=?, doctorado=?
        WHERE id=?
    ");

    $stmt->execute([
        $rfc,
        $curp,
        $direccion,
        $licenciatura,
        $lic_titulo,
        $lic_cedula,
        $maestria,
        $doctorado,
        $id
    ]);

    /* =========================
    SUBIR DOCUMENTOS
    ========================= */
    $upload_dir = __DIR__ . '/../../uploads/docentes/';

    if (!is_dir($upload_dir)) {
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

    foreach ($tipos as $tipo) {

        if (!empty($_FILES[$tipo]['name'])) {

            $archivo = subirArchivo($_FILES[$tipo], $upload_dir);

            if ($archivo) {

                // Ver si ya existe
                $stmt = $pdo->prepare("
                    SELECT id FROM documentos_docente 
                    WHERE docente_id=? AND tipo=?
                ");
                $stmt->execute([$id, $tipo]);
                $existe = $stmt->fetch();

                if ($existe) {

                    // UPDATE
                    $stmt = $pdo->prepare("
                        UPDATE documentos_docente 
                        SET archivo=?, fecha_subida=NOW()
                        WHERE docente_id=? AND tipo=?
                    ");
                    $stmt->execute([$archivo, $id, $tipo]);

                } else {

                    // INSERT
                    $stmt = $pdo->prepare("
                        INSERT INTO documentos_docente 
                        (docente_id, tipo, archivo, fecha_subida)
                        VALUES (?,?,?,NOW())
                    ");
                    $stmt->execute([$id, $tipo, $archivo]);
                }
            }
        }
    }

    $pdo->commit();

    header("Location: ../../dashboards/coordinador_dashboard.php?modulo=detalle_docente&id=".$id."&ok=1");
    exit;

} catch (PDOException $e) {

    $pdo->rollBack();
    die("Error: " . $e->getMessage());
}