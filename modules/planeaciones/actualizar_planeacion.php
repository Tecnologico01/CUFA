<?php

require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/permisos.php';

validarPermiso('actualizar_planeaciones');

/*
|--------------------------------------------------------------------------
| VALIDAR MÉTODO
|--------------------------------------------------------------------------
*/

if($_SERVER['REQUEST_METHOD'] !== 'POST'){

    header("Location: ../../index.php");
    exit;
}

/*
|--------------------------------------------------------------------------
| OBTENER DATOS
|--------------------------------------------------------------------------
*/

$id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
$tema_id = isset($_POST['tema_id']) ? (int) $_POST['tema_id'] : 0;

$objetivo = trim($_POST['objetivo'] ?? '');
$actividad = trim($_POST['actividad'] ?? '');
$descripcion = trim($_POST['descripcion'] ?? '');

/*
|--------------------------------------------------------------------------
| VALIDACIONES
|--------------------------------------------------------------------------
*/

if($id <= 0 || $tema_id <= 0){

    echo "
    <script>
        alert('Datos inválidos para actualizar la planeación');
        window.history.back();
    </script>
    ";

    exit;
}

/*
|--------------------------------------------------------------------------
| VALIDAR PLANEACIÓN
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare("
    SELECT id
    FROM planeaciones
    WHERE id = ?
");

$stmt->execute([$id]);

$planeacion = $stmt->fetch(PDO::FETCH_ASSOC);

if(!$planeacion){

    echo "
    <script>
        alert('La planeación no existe');
        window.history.back();
    </script>
    ";

    exit;
}

/*
|--------------------------------------------------------------------------
| ACTUALIZAR
|--------------------------------------------------------------------------
*/

try{

    $stmt = $pdo->prepare("
        UPDATE planeaciones
        SET
            objetivo = ?,
            actividad = ?,
            descripcion = ?
        WHERE id = ?
    ");

    $stmt->execute([
        $objetivo,
        $actividad,
        $descripcion,
        $id
    ]);

    echo "
    <script>
        alert('Planeación actualizada correctamente');
        window.location.href='../../dashboard.php?modulo=planeacion_semanal&tema=".$tema_id."';
    </script>
    ";

    exit;

}catch(PDOException $e){

    echo "
    <script>
        alert('Error al actualizar la planeación');
        window.history.back();
    </script>
    ";

    exit;
}
?>