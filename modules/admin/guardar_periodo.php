<?php
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__.'/../../includes/permisos.php';validarPermiso('editar_periodos');
require_once __DIR__.'/../../includes/academico_service.php';
if(($_SERVER['REQUEST_METHOD']??'GET')==='POST'){try{cufaValidarToken();}catch(Throwable $e){http_response_code(422);cufaError($e);return;}}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die("Acceso no permitido");
}

$id = $_POST['id'] ?? null;
$nombre = $_POST['nombre'] ?? '';
try{$fecha_inicio=cufaFecha($_POST['fecha_inicio']??'');$fecha_fin=cufaFecha($_POST['fecha_fin']??'');}catch(Throwable $e){http_response_code(422);cufaError($e);return;}
$activo = $_POST['activo'] ?? 0;
$tipo_periodo_id = $_POST['tipo_periodo_id'] ?? null;

/* Validaciones */
if (!$id || !$nombre || !$fecha_inicio || !$fecha_fin || !$tipo_periodo_id) {
    die("Datos incompletos");
}

if ($fecha_inicio > $fecha_fin) {
    die("La fecha de inicio no puede ser mayor a la fecha fin");
}

try { cufaBloquearCambioVinculado($pdo,'periodos',(int)$id,['nombre'=>$nombre,'fecha_inicio'=>$fecha_inicio,'fecha_fin'=>$fecha_fin,'tipo_periodo_id'=>$tipo_periodo_id]); } catch(Throwable $e){http_response_code(422);cufaError($e);return;}
/* Actualizar */
$stmt = $pdo->prepare("
    UPDATE periodos
    SET nombre=?, fecha_inicio=?, fecha_fin=?, activo=?, tipo_periodo_id=?
    WHERE id=?
");

$stmt->execute([
    $nombre,
    $fecha_inicio,
    $fecha_fin,
    $activo,
    $tipo_periodo_id,
    $id
]);

/* Redirección */
header("Location: /sistema_academico/dashboards/admin_dashboard.php?modulo=periodos_lista");
exit;