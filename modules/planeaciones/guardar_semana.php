<?php

require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/permisos.php';

validarPermiso('guardar_planeaciones');

if($_SERVER['REQUEST_METHOD'] !== 'POST'){
    header("Location: ../../dashboard.php");
    exit;
}

$asignacion = isset($_POST['asignacion']) ? (int) $_POST['asignacion'] : 0;
$semana = isset($_POST['semana']) ? (int) $_POST['semana'] : 0;

$objetivo = trim($_POST['objetivo'] ?? '');
$descripcion = trim($_POST['descripcion_tema'] ?? '');
$material = trim($_POST['material_url'] ?? '');

$actividad = trim($_POST['actividad_nombre'] ?? '');
$actividad_desc = trim($_POST['actividad_descripcion'] ?? '');

$fecha_a = $_POST['fecha_apertura'] ?? null;
$fecha_c = $_POST['fecha_cierre'] ?? null;

if($asignacion <= 0 || $semana <= 0){
    die("Datos inválidos.");
}

$rubrica = null;

try{

    $pdo->beginTransaction();

    /*
    |--------------------------------------------------------------------------
    | SUBIR RÚBRICA
    |--------------------------------------------------------------------------
    */

    if(
        isset($_FILES['rubrica']) &&
        $_FILES['rubrica']['error'] === UPLOAD_ERR_OK
    ){

        $extension = strtolower(pathinfo($_FILES['rubrica']['name'], PATHINFO_EXTENSION));

        if($extension !== 'pdf'){
            throw new Exception("La rúbrica debe ser un archivo PDF.");
        }

        $upload_dir = __DIR__ . '/../../uploads/rubricas/';

        if(!is_dir($upload_dir)){
            mkdir($upload_dir, 0777, true);
        }

        $nombre = 'rubrica_semana_' . time() . '_' . bin2hex(random_bytes(4)) . '.pdf';

        if(!move_uploaded_file($_FILES['rubrica']['tmp_name'], $upload_dir . $nombre)){
            throw new Exception("No se pudo subir la rúbrica.");
        }

        $rubrica = 'uploads/rubricas/' . $nombre;
    }

    /*
    |--------------------------------------------------------------------------
    | VERIFICAR SI EXISTE
    |--------------------------------------------------------------------------
    */

    $stmt = $pdo->prepare("
        SELECT id, rubrica
        FROM planeacion_semanal
        WHERE asignacion_id = ?
        AND semana = ?
        LIMIT 1
    ");

    $stmt->execute([
        $asignacion,
        $semana
    ]);

    $existe = $stmt->fetch(PDO::FETCH_ASSOC);

    if($existe){

        if(!$rubrica){
            $rubrica = $existe['rubrica'] ?? null;
        }

        $stmt = $pdo->prepare("
            UPDATE planeacion_semanal
            SET
                objetivo = ?,
                descripcion_tema = ?,
                material_url = ?,
                actividad_nombre = ?,
                actividad_descripcion = ?,
                fecha_apertura = ?,
                fecha_cierre = ?,
                rubrica = ?
            WHERE asignacion_id = ?
            AND semana = ?
        ");

        $stmt->execute([
            $objetivo,
            $descripcion,
            $material,
            $actividad,
            $actividad_desc,
            $fecha_a,
            $fecha_c,
            $rubrica,
            $asignacion,
            $semana
        ]);

    }else{

        $stmt = $pdo->prepare("
            INSERT INTO planeacion_semanal
            (
                asignacion_id,
                semana,
                objetivo,
                descripcion_tema,
                material_url,
                actividad_nombre,
                actividad_descripcion,
                fecha_apertura,
                fecha_cierre,
                rubrica
            )
            VALUES
            (
                ?, ?, ?, ?, ?, ?, ?, ?, ?, ?
            )
        ");

        $stmt->execute([
            $asignacion,
            $semana,
            $objetivo,
            $descripcion,
            $material,
            $actividad,
            $actividad_desc,
            $fecha_a,
            $fecha_c,
            $rubrica
        ]);
    }

    $pdo->commit();

    header("Location: ../../dashboard.php?modulo=ver_materia&asignacion_id=" . $asignacion);
    exit;

}catch(Exception $e){

    if($pdo->inTransaction()){
        $pdo->rollBack();
    }

    die("Error: " . $e->getMessage());
}