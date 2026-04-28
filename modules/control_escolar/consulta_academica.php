<?php
require_once __DIR__ . '/../../includes/db.php';

$carrera_id = $_GET['carrera'] ?? '';
$grupo_id = $_GET['grupo'] ?? '';
$alumno_id = $_GET['alumno'] ?? '';

/* CARRERAS */

$carreras = $pdo->query("
SELECT id, nombre
FROM carreras
")->fetchAll();

/* GRUPOS */

$grupos = [];

if($carrera_id){

$stmt = $pdo->prepare("
SELECT id, nombre
FROM grupos
WHERE carrera_id = ?
");

$stmt->execute([$carrera_id]);
$grupos = $stmt->fetchAll();

}

/* ALUMNOS */

$alumnos = [];

if($grupo_id){

$stmt = $pdo->prepare("
SELECT a.id, u.nombre, a.numero_control
FROM grupos_alumnos ga
JOIN alumnos a ON ga.alumno_id = a.id
JOIN usuarios u ON a.usuario_id = u.id
WHERE ga.grupo_id = ?
");

$stmt->execute([$grupo_id]);
$alumnos = $stmt->fetchAll();

}

/* CALIFICACIONES */

$calificaciones = [];
$promedio = 0;

if($alumno_id){

$stmt = $pdo->prepare("
SELECT 
m.nombre materia,
AVG(c.calificacion) promedio
FROM calificaciones c
JOIN actividades act ON c.actividad_id = act.id
JOIN asignaciones_docentes ad ON act.asignacion_id = ad.id
JOIN materias m ON ad.materia_id = m.id
WHERE c.alumno_id = ?
GROUP BY m.nombre
");

$stmt->execute([$alumno_id]);

$calificaciones = $stmt->fetchAll();

$total = 0;
$count = 0;

foreach($calificaciones as $c){

$total += $c['promedio'];
$count++;

}

if($count > 0){
$promedio = round($total / $count,2);
}

}
?>

<h1 class="text-3xl font-bold mb-6">Consulta Académica</h1>

<!-- FILTROS -->

<div class="bg-white p-6 rounded shadow mb-6 grid grid-cols-3 gap-4">

<form method="GET">
<input type="hidden" name="modulo" value="consulta_academica">

<label class="font-semibold">Carrera</label>

<select name="carrera" onchange="this.form.submit()" class="w-full border p-2 rounded">

<option value="">Seleccionar</option>

<?php foreach($carreras as $c){ ?>

<option value="<?= $c['id'] ?>" <?= $carrera_id==$c['id']?'selected':'' ?>>

<?= $c['nombre'] ?>

</option>

<?php } ?>

</select>

</form>


<form method="GET">
<input type="hidden" name="modulo" value="consulta_academica">
<input type="hidden" name="carrera" value="<?= $carrera_id ?>">

<label class="font-semibold">Grupo</label>

<select name="grupo" onchange="this.form.submit()" class="w-full border p-2 rounded">

<option value="">Seleccionar</option>

<?php foreach($grupos as $g){ ?>

<option value="<?= $g['id'] ?>" <?= $grupo_id==$g['id']?'selected':'' ?>>

<?= $g['nombre'] ?>

</option>

<?php } ?>

</select>

</form>


<form method="GET">

<input type="hidden" name="modulo" value="consulta_academica">
<input type="hidden" name="carrera" value="<?= $carrera_id ?>">
<input type="hidden" name="grupo" value="<?= $grupo_id ?>">

<label class="font-semibold">Alumno</label>

<select name="alumno" onchange="this.form.submit()" class="w-full border p-2 rounded">

<option value="">Seleccionar</option>

<?php foreach($alumnos as $a){ ?>

<option value="<?= $a['id'] ?>" <?= $alumno_id==$a['id']?'selected':'' ?>>

<?= $a['nombre'] ?> (<?= $a['numero_control'] ?>)

</option>

<?php } ?>

</select>

</form>

</div>


<!-- PERFIL ALUMNO -->

<?php if($alumno_id){ ?>

<div class="bg-white p-6 rounded shadow mb-6">

<h2 class="text-xl font-bold mb-3">Promedio General: <?= $promedio ?></h2>

<a href="control_dashboard.php?modulo=ver_boleta&alumno=<?= $alumno_id ?>" 
class="bg-purple-600 text-white px-4 py-2 rounded">

Generar Boleta

</a>

</div>


<!-- TABLA CALIFICACIONES -->

<table class="w-full bg-white shadow rounded">

<tr class="bg-purple-200">

<th class="p-3">Materia</th>
<th class="p-3">Promedio</th>
<th class="p-3">Estado</th>

</tr>

<?php foreach($calificaciones as $c){ 

$reprobado = $c['promedio'] < 70;

?>

<tr class="border-t">

<td class="p-3"><?= $c['materia'] ?></td>

<td class="p-3"><?= round($c['promedio'],2) ?></td>

<td class="p-3">

<?php if($reprobado){ ?>

<span class="text-red-600 font-bold">Reprobado</span>

<?php } else { ?>

<span class="text-green-600 font-bold">Aprobado</span>

<?php } ?>

</td>

</tr>

<?php } ?>

</table>

<?php } ?>