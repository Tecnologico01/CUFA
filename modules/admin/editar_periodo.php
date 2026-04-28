<?php
require_once __DIR__ . '/../../includes/db.php';

$id = $_GET['id'] ?? null;

if(!$id){
die("Periodo inválido");
}

$stmt = $pdo->prepare("SELECT * FROM periodos WHERE id=?");
$stmt->execute([$id]);

$periodo = $stmt->fetch(PDO::FETCH_ASSOC);

if(!$periodo){
die("Periodo no encontrado");
}
?>

<!DOCTYPE html>
<html>
<head>

<meta charset="UTF-8">
<title>Editar Periodo</title>

<style>

body{
font-family:Arial;
background:#f4f6f9;
}

.contenedor{
max-width:500px;
margin:auto;
background:white;
padding:30px;
border-radius:8px;
margin-top:50px;
box-shadow:0 2px 10px rgba(0,0,0,0.1);
}

input,select{
width:100%;
padding:10px;
margin-bottom:15px;
}

button{
background:#673ab7;
color:white;
padding:10px;
border:none;
border-radius:5px;
cursor:pointer;
}

</style>

</head>

<body>

<div class="contenedor">

<h2>Editar Periodo</h2>

<form method="POST" action="guardar_periodo.php">

<input type="hidden" name="id" value="<?= $periodo['id'] ?>">

<label>Nombre del periodo</label>
<input type="text" name="nombre" value="<?= $periodo['nombre'] ?>" required>

<label>Fecha inicio</label>
<input type="date" name="fecha_inicio" value="<?= $periodo['fecha_inicio'] ?>" required>

<label>Fecha fin</label>
<input type="date" name="fecha_fin" value="<?= $periodo['fecha_fin'] ?>" required>

<label>Activo</label>
<select name="activo">

<option value="1" <?= $periodo['activo']==1?'selected':'' ?>>Activo</option>
<option value="0" <?= $periodo['activo']==0?'selected':'' ?>>Inactivo</option>

</select>

<button>Guardar cambios</button>

</form>

</div>

</body>
</html>