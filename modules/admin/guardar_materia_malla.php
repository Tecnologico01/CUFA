<?php
require_once __DIR__ . '/../../includes/db.php';

$malla_id = $_POST['malla_id'];
$materias = $_POST['materias'] ?? [];

if(!$materias){
    header("Location: /sistema_academico/modules/admin/editar_malla.php?id=".$malla_id);
    exit;
}

/* BORRAR MALLA ANTERIOR */

$stmt = $pdo->prepare("
DELETE FROM malla_materias
WHERE malla_id = ?
");

$stmt->execute([$malla_id]);

/* INSERTAR MATERIAS */

$stmt = $pdo->prepare("
INSERT INTO malla_materias
(malla_id,materia_id,periodo_numero)
VALUES (?,?,?)
");

foreach($materias as $periodo => $lista){

    foreach($lista as $materia){

        if($materia=="") continue;

        $stmt->execute([
            $malla_id,
            $materia,
            $periodo
        ]);

    }

}

header("Location: /sistema_academico/dashboards/admin_dashboard.php?modulo=ver_mallas");
exit;
