<?php
require_once __DIR__ . '/../../includes/db.php';

$malla_id = $_POST['malla_id'] ?? null;
$materias = $_POST['materias'] ?? [];

if(!$malla_id){
    die("Malla inválida");
}

try{

$pdo->beginTransaction();

/* BORRAR MATERIAS ACTUALES */

$stmt = $pdo->prepare("DELETE FROM malla_materias WHERE malla_id=?");
$stmt->execute([$malla_id]);

/* INSERTAR NUEVAS MATERIAS */

$stmt = $pdo->prepare("
INSERT INTO malla_materias
(malla_id, materia_id, periodo_numero)
VALUES (?,?,?)
");

foreach($materias as $periodo => $lista){

    foreach($lista as $materia_id){

        $stmt->execute([
            $malla_id,
            $materia_id,
            $periodo
        ]);

    }

}

$pdo->commit();

header("Location: /sistema_academico/dashboards/admin_dashboard.php?modulo=mallas_lista");
exit;

}catch(Exception $e){

$pdo->rollBack();
die("Error al guardar malla: " . $e->getMessage());

}