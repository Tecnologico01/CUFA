<?php

require_once __DIR__ . '/../../includes/db.php';

$id = $_GET['id'];
$estado = $_GET['estado'];
$docente = $_GET['docente'];

/* actualizar estado */

$stmt = $pdo->prepare("
UPDATE parciales
SET estado_revision=?
WHERE id=?
");

$stmt->execute([$estado,$id]);

/* notificar docente */

if($estado=='rechazado'){

$stmt = $pdo->prepare("
INSERT INTO notificaciones (usuario_id,mensaje)
VALUES (?,?)
");

$mensaje = "Las calificaciones de un parcial fueron rechazadas. Revisar captura.";

$stmt->execute([$docente,$mensaje]);

}

/* regresar al modulo */

header("Location: /sistema_academico/dashboards/coordinador_dashboard.php?mod=revisar_calificaciones");

exit;