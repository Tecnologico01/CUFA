<?php

require_once __DIR__ . '/../../includes/db.php';

$materia = $_GET['m'];
$grupo = $_GET['g'];
$periodo = $_GET['p'];
$estado = $_GET['estado'];

/* verificar si existe */

$stmt = $pdo->prepare("
SELECT id
FROM actas
WHERE materia_id=? AND grupo_id=? AND periodo_id=?
");

$stmt->execute([$materia,$grupo,$periodo]);

$existe = $stmt->fetch();

if($existe){

$stmt = $pdo->prepare("
UPDATE actas
SET estado=?
WHERE materia_id=? AND grupo_id=? AND periodo_id=?
");

$stmt->execute([$estado,$materia,$grupo,$periodo]);

}else{

$stmt = $pdo->prepare("
INSERT INTO actas (materia_id,grupo_id,periodo_id,estado)
VALUES (?,?,?,?)
");

$stmt->execute([$materia,$grupo,$periodo,$estado]);

}

header("Location: /sistema_academico/dashboards/coordinador_dashboard.php?mod=habilitar_actas");
exit;