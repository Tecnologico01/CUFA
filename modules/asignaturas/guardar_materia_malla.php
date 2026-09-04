<?php

require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/permisos.php';

validarPermiso('editar_mallas');

/* =========================
   FUNCIÓN DE REDIRECCIÓN
========================= */
function redirigirMallas($mensaje = '', $tipo = 'info'){

    $url = "?modulo=ver_mallas";

    if($mensaje !== ''){
        $url .= "&{$tipo}=" . urlencode($mensaje);
    }

    echo "
    <script>
        window.location.href='{$url}';
    </script>
    ";
    exit;
}

/* =========================
   VALIDAR MÉTODO
========================= */
if($_SERVER['REQUEST_METHOD'] !== 'POST'){
    redirigirMallas('Método no permitido', 'error');
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
    redirigirMallas('Malla no válida', 'error');
}

/* =========================
   VALIDAR EXISTENCIA MALLA
========================= */
$stmtMalla = $pdo->prepare("
    SELECT
        id,
        carrera_id
    FROM mallas_curriculares
    WHERE id = ?
    LIMIT 1
");

$stmtMalla->execute([$malla_id]);

$malla = $stmtMalla->fetch(PDO::FETCH_ASSOC);

if(!$malla){
    redirigirMallas('La malla curricular no existe', 'error');
}

/* =========================
   VALIDAR DATOS DE MATERIAS
========================= */
if(!is_array($materias)){
    $materias = [];
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
        INSERT INTO malla_materias (
            malla_id,
            materia_id,
            periodo_numero
        ) VALUES (
            ?, ?, ?
        )
    ");

    $insertadas = [];

    foreach($materias as $periodo => $lista){

        $periodo = (int) $periodo;

        if($periodo <= 0){
            continue;
        }

        if(!is_array($lista)){
            continue;
        }

        foreach($lista as $materia_id){

            $materia_id = (int) $materia_id;

            if($materia_id <= 0){
                continue;
            }

            /*
            |--------------------------------------------------------------------------
            | EVITAR DUPLICADOS
            |--------------------------------------------------------------------------
            */
            $claveDuplicado = $periodo . '_' . $materia_id;

            if(isset($insertadas[$claveDuplicado])){
                continue;
            }

            $insertadas[$claveDuplicado] = true;

            /*
            |--------------------------------------------------------------------------
            | VALIDAR QUE LA MATERIA EXISTA
            |--------------------------------------------------------------------------
            */
            $stmtMateria = $pdo->prepare("
                SELECT id
                FROM materias
                WHERE id = ?
                LIMIT 1
            ");

            $stmtMateria->execute([$materia_id]);

            if(!$stmtMateria->fetch()){
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

    redirigirMallas('Malla actualizada correctamente', 'msg');

}catch(Exception $e){

    if($pdo->inTransaction()){
        $pdo->rollBack();
    }

    $msg = htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');

    echo "
    <script>
        alert('Error al guardar la malla curricular: {$msg}');
        window.location.href='?modulo=ver_mallas';
    </script>
    ";
    exit;
}