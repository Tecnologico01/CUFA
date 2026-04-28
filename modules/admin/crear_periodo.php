<?php
require_once __DIR__ . '/../../includes/db.php';

$error = '';
$mensaje = '';

/* OBTENER TIPOS */

$tipos = $pdo->query("
SELECT id,nombre
FROM tipos_periodo
")->fetchAll(PDO::FETCH_ASSOC);

if($_SERVER['REQUEST_METHOD'] === 'POST'){

$nombre = trim($_POST['nombre']);
$fecha_inicio = $_POST['fecha_inicio'];
$fecha_fin = $_POST['fecha_fin'];
$tipo_periodo_id = $_POST['tipo_periodo_id'];

if(empty($nombre)||empty($fecha_inicio)||empty($fecha_fin)||empty($tipo_periodo_id)){
    $error = "Todos los campos son obligatorios.";
}else{

    $inicio = new DateTime($fecha_inicio);
    $fin = new DateTime($fecha_fin);

    if($inicio >= $fin){
        $error = "La fecha final debe ser mayor a la inicial.";
    }else{

        /* CALCULAR DIFERENCIA REAL EN MESES (CON DÍAS) */

        $intervalo = $inicio->diff($fin);

        $meses = ($intervalo->y * 12) + $intervalo->m;
        $dias = $intervalo->d;

        /* SI PASA DE 15 DÍAS, CUENTA COMO MES EXTRA */
        if($dias > 15){
            $meses++;
        }

        /* VALIDACIÓN POR TIPO */

        $validacion = false;
        $mensaje_tipo = "";

        switch($tipo_periodo_id){

            case 1: // Semestral (6 meses aprox)
                $validacion = ($meses >= 5 && $meses <= 7);
                $mensaje_tipo = "Semestral (entre 5 y 7 meses)";
            break;

            case 2: // Cuatrimestral (4 meses)
                $validacion = ($meses >= 3 && $meses <= 5);
                $mensaje_tipo = "Cuatrimestral (entre 3 y 5 meses)";
            break;

            case 3: // Trimestral (3 meses)
                $validacion = ($meses >= 2 && $meses <= 4);
                $mensaje_tipo = "Trimestral (entre 2 y 4 meses)";
            break;

            case 4: // Bimestral (2 meses)
                $validacion = ($meses >= 1 && $meses <= 3);
                $mensaje_tipo = "Bimestral (entre 1 y 3 meses)";
            break;

        }

        if(!$validacion){
            $error = "La duración no corresponde al tipo seleccionado. Debe ser: ".$mensaje_tipo;
        }else{

            $stmt = $pdo->prepare("
            INSERT INTO periodos
            (nombre,tipo_periodo_id,fecha_inicio,fecha_fin,activo)
            VALUES (?,?,?,?,0)
            ");

            $stmt->execute([
                $nombre,
                $tipo_periodo_id,
                $fecha_inicio,
                $fecha_fin
            ]);

            $mensaje = "Periodo creado correctamente.";
        }

    }
}
}
?>

<h1 class="text-2xl font-bold mb-4">Crear Periodo</h1>

<?php if($error): ?>
<div class="bg-red-100 text-red-700 p-4 mb-4 rounded">
<?= $error ?>
</div>
<?php endif; ?>

<?php if($mensaje): ?>
<div class="bg-green-100 text-green-700 p-4 mb-4 rounded">
<?= $mensaje ?>
</div>
<?php endif; ?>

<form method="POST" class="bg-white p-6 rounded shadow space-y-4">

<input type="text" name="nombre"
placeholder="Ej: Enero - Abril 2026"
class="border p-2 w-full rounded" required>

<input type="date" name="fecha_inicio"
class="border p-2 w-full rounded" required>

<input type="date" name="fecha_fin"
class="border p-2 w-full rounded" required>

<select name="tipo_periodo_id" class="border p-2 w-full rounded" required>
<option value="">Seleccionar tipo</option>

<?php foreach($tipos as $t){ ?>
<option value="<?= $t['id'] ?>">
<?= $t['nombre'] ?>
</option>
<?php } ?>

</select>

<button class="bg-purple-600 text-white px-4 py-2 rounded w-full">
Crear Periodo
</button>

</form>