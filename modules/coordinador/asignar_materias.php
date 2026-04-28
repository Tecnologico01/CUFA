<?php

require_once __DIR__ . '/../../includes/db.php';

$mensaje = "";
$periodo_id = $_GET['periodo_id'] ?? null;

/* =========================
INSERTAR ASIGNACION
========================= */

if($_SERVER['REQUEST_METHOD']=="POST"){

    $docente_id = $_POST['docente_id'];
    $grupo_id = $_POST['grupo_id'];
    $materia_id = $_POST['materia_id'];
    $periodo_id = $_POST['periodo_id'];

    try{

        $stmt=$pdo->prepare("
        INSERT INTO asignaciones_docentes
        (docente_id,grupo_id,materia_id,periodo_id)
        VALUES (?,?,?,?)
        ");

        $stmt->execute([$docente_id,$grupo_id,$materia_id,$periodo_id]);

        $mensaje="Materia asignada correctamente";

    }catch(PDOException $e){

        if($e->getCode()==23000){
            $mensaje="Esta materia ya está asignada a este docente en este grupo y periodo.";
        }else{
            throw $e;
        }

    }

}

/* =========================
PERIODOS ACTIVOS
========================= */

$periodos=$pdo->query("
SELECT id,nombre
FROM periodos
WHERE activo=1
ORDER BY nombre DESC
")->fetchAll();

/* =========================
DOCENTES (CORREGIDO)
========================= */

$docentes=$pdo->query("
SELECT 
    d.id,
    CONCAT(u.nombres,' ',u.apellido_paterno,' ',u.apellido_materno) AS nombre_completo
FROM docentes d
JOIN usuarios u ON u.id=d.usuario_id
ORDER BY u.nombres ASC
")->fetchAll();

/* =========================
MATERIAS Y GRUPOS SEGUN PERIODO
========================= */

$materias=[];
$grupos=[];

if($periodo_id){

    $stmt=$pdo->prepare("
    SELECT DISTINCT m.id,m.nombre
    FROM parciales p
    JOIN materias m ON m.id=p.materia_id
    WHERE p.periodo_id=?
    AND p.activo=1
    ORDER BY m.nombre
    ");

    $stmt->execute([$periodo_id]);
    $materias=$stmt->fetchAll();


    $stmt=$pdo->prepare("
    SELECT DISTINCT g.id,g.nombre
    FROM parciales p
    JOIN grupos g ON g.id=p.grupo_id
    WHERE p.periodo_id=?
    AND p.activo=1
    ORDER BY g.nombre
    ");

    $stmt->execute([$periodo_id]);
    $grupos=$stmt->fetchAll();

}

/* =========================
MATERIAS YA ASIGNADAS
========================= */

$asignadas=[];

if($periodo_id){

    $stmt=$pdo->prepare("
    SELECT materia_id
    FROM asignaciones_docentes
    WHERE periodo_id=?
    ");

    $stmt->execute([$periodo_id]);
    $asignadas=$stmt->fetchAll(PDO::FETCH_COLUMN);

}

?>

<h1 class="text-3xl font-bold mb-6">
Asignar Materias a Docentes
</h1>

<?php if($mensaje!=""){ ?>

<div class="bg-yellow-200 text-yellow-800 p-3 rounded mb-6">
<?= $mensaje ?>
</div>

<?php } ?>

<!-- =========================
SELECCIONAR PERIODO
========================= -->

<div class="bg-white p-6 rounded-xl shadow mb-6">

<form method="GET">

<input type="hidden" name="modulo" value="asignar_materias">

<label class="block mb-2 font-bold">
Seleccionar periodo
</label>

<select name="periodo_id"
class="border p-2 rounded w-full"
onchange="this.form.submit()">

<option value="">Seleccionar periodo</option>

<?php foreach($periodos as $p){ ?>

<option value="<?= $p['id'] ?>"
<?= ($periodo_id==$p['id'])?'selected':'' ?>>

<?= $p['nombre'] ?>

</option>

<?php } ?>

</select>

</form>

</div>

<?php if($periodo_id){ ?>

<!-- =========================
FORMULARIO DE ASIGNACION
========================= -->

<div class="bg-white p-6 rounded-xl shadow mb-6">

<form method="POST" class="grid grid-cols-4 gap-4">

<input type="hidden" name="periodo_id" value="<?= $periodo_id ?>">

<select name="docente_id" class="border p-2 rounded" required>

<option value="">Seleccionar docente</option>

<?php foreach($docentes as $d){ ?>

<option value="<?= $d['id'] ?>">
<?= $d['nombre_completo'] ?>
</option>

<?php } ?>

</select>


<select name="grupo_id" class="border p-2 rounded" required>

<option value="">Seleccionar grupo</option>

<?php foreach($grupos as $g){ ?>

<option value="<?= $g['id'] ?>">
<?= $g['nombre'] ?>
</option>

<?php } ?>

</select>


<select name="materia_id" class="border p-2 rounded" required>

<option value="">Seleccionar materia</option>

<?php foreach($materias as $m){

$icono=in_array($m['id'],$asignadas) ? "🟢" : "⚪";

?>

<option value="<?= $m['id'] ?>">
<?= $icono ?> <?= $m['nombre'] ?>
</option>

<?php } ?>

</select>


<button class="bg-purple-600 text-white px-4 py-2 rounded hover:bg-purple-700">
Asignar
</button>

</form>

</div>

<?php } ?>

<!-- =========================
TABLA DE ASIGNACIONES
========================= -->

<h2 class="text-2xl font-bold mb-4">
Materias ya asignadas
</h2>

<div class="bg-white rounded-xl shadow overflow-hidden">

<table class="w-full">

<thead class="bg-purple-200 text-purple-900">

<tr>
<th class="p-4 text-left">Docente</th>
<th class="p-4 text-left">Materia</th>
<th class="p-4 text-left">Grupo</th>
<th class="p-4 text-left">Periodo</th>
</tr>

</thead>

<tbody>

<?php

$stmt=$pdo->query("

SELECT
CONCAT(u.nombres,' ',u.apellido_paterno,' ',u.apellido_materno) AS docente,
m.nombre AS materia,
g.nombre AS grupo,
p.nombre AS periodo

FROM asignaciones_docentes ad

JOIN docentes d ON d.id=ad.docente_id
JOIN usuarios u ON u.id=d.usuario_id
JOIN materias m ON m.id=ad.materia_id
JOIN grupos g ON g.id=ad.grupo_id
JOIN periodos p ON p.id=ad.periodo_id

ORDER BY p.nombre,g.nombre

");

while($a=$stmt->fetch()){

?>

<tr class="border-b hover:bg-gray-50">

<td class="p-4"><?= $a['docente'] ?></td>
<td class="p-4"><?= $a['materia'] ?></td>
<td class="p-4"><?= $a['grupo'] ?></td>
<td class="p-4"><?= $a['periodo'] ?></td>

</tr>

<?php } ?>

</tbody>

</table>

</div>