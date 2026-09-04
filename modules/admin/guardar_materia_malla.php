<?php

require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/permisos.php';

validarPermiso('editar_mallas');

/* =========================
   VALIDAR MÉTODO
========================= */

if($_SERVER['REQUEST_METHOD'] !== 'POST'){

    header("
        Location: /sistema_academico/dashboards/admin_dashboard.php?modulo=ver_mallas
    ");

    exit;
}

/* =========================
   OBTENER DATOS
========================= */

$malla_id = isset($_POST['malla_id'])
    ? (int) $_POST['malla_id']
    : 0;

$materias = $_POST['materias'] ?? [];

/* =========================
   VALIDAR MALLA
========================= */

if($malla_id <= 0){

    header("
        Location: /sistema_academico/dashboards/admin_dashboard.php?modulo=ver_mallas
    ");

    exit;
}

/* =========================
   VALIDAR EXISTENCIA MALLA
========================= */

$stmtMalla = $pdo->prepare("
    SELECT id
    FROM mallas_curriculares
    WHERE id = ?
");

$stmtMalla->execute([$malla_id]);

if(!$stmtMalla->fetch()){

    header("
        Location: /sistema_academico/dashboards/admin_dashboard.php?modulo=ver_mallas
    ");

    exit;
}

try{

    $pdo->beginTransaction();

    /* =========================
       BORRAR RELACIONES ANTERIORES
    ========================= */

    $stmtDelete = $pdo->prepare("
        DELETE FROM malla_materias
        WHERE malla_id = ?
    ");

    $stmtDelete->execute([$malla_id]);

    /* =========================
       INSERTAR NUEVAS MATERIAS
    ========================= */

    $stmtInsert = $pdo->prepare("
        INSERT INTO malla_materias
        (
            malla_id,
            materia_id,
            periodo_numero
        )
        VALUES
        (
            ?, ?, ?
        )
    ");

    foreach($materias as $periodo => $lista){

        $periodo = (int) $periodo;

        if(!is_array($lista)){
            continue;
        }

        foreach($lista as $materia_id){

            $materia_id = (int) $materia_id;

            if($materia_id <= 0){
                continue;
            }

            $stmtInsert->execute([
                $malla_id,
                $materia_id,
                $periodo
            ]);
        }
    }

    $pdo->commit();

}catch(Exception $e){

    $pdo->rollBack();

    die("
        <div style='padding:20px;font-family:sans-serif'>
            <h2 style='color:red'>
                Error al guardar la malla curricular
            </h2>

            <p>
                ".$e->getMessage()."
            </p>
        </div>
    ");
}

/* =========================
   REDIRECCIÓN FINAL
========================= */

header("
    Location: /sistema_academico/dashboards/admin_dashboard.php?modulo=ver_mallas
");

exit;