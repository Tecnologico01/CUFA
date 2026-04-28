<?php

require_once __DIR__ . '/../../includes/db.php';

$asignacion_id = $_GET['asignacion'] ?? null;

if(!$asignacion_id){
echo "<p class='text-red-600'>Asignación no especificada</p>";
exit;
}

/* OBTENER ASIGNACION */

$stmt = $pdo->prepare("
SELECT ad.id,
ad.materia_id,
ad.grupo_id,
m.nombre AS materia,
g.nombre AS grupo
FROM asignaciones_docentes ad
JOIN materias m ON ad.materia_id=m.id
JOIN grupos g ON ad.grupo_id=g.id
WHERE ad.id=?
");

$stmt->execute([$asignacion_id]);
$asignacion = $stmt->fetch();

if(!$asignacion){
echo "Asignación no encontrada";
exit;
}

/* TOTAL PARCIALES */

$stmt = $pdo->prepare("
SELECT numero
FROM parciales
WHERE materia_id = ?
AND grupo_id = ?
AND activo = 1
LIMIT 1
");

$stmt->execute([
$asignacion['materia_id'],
$asignacion['grupo_id']
]);

$total_parciales = $stmt->fetchColumn();

/* ELIMINAR */

if(isset($_GET['eliminar'])){

$id=$_GET['eliminar'];

$stmt=$pdo->prepare("
DELETE FROM temas_materia
WHERE id=?
");

$stmt->execute([$id]);

header("Location: coordinador_dashboard.php?modulo=definir_temas&asignacion=".$asignacion_id);
exit;

}

/* GUARDAR */

if(isset($_POST['guardar'])){

$parcial=$_POST['parcial'];
$tema=trim($_POST['tema']);
$descripcion=trim($_POST['descripcion']);

$stmt=$pdo->prepare("
INSERT INTO temas_materia
(asignacion_id,parcial,tema,descripcion,orden)
VALUES (?,?,?,?,1)
");

$stmt->execute([
$asignacion_id,
$parcial,
$tema,
$descripcion
]);

}

/* EDITAR */

if(isset($_POST['editar'])){

$id=$_POST['id'];
$tema=$_POST['tema'];
$descripcion=$_POST['descripcion'];

$stmt=$pdo->prepare("
UPDATE temas_materia
SET tema=?,descripcion=?
WHERE id=?
");

$stmt->execute([$tema,$descripcion,$id]);

}

/* OBTENER TEMAS */

$stmt=$pdo->prepare("
SELECT *
FROM temas_materia
WHERE asignacion_id=?
");

$stmt->execute([$asignacion_id]);

$temas=[];

foreach($stmt->fetchAll() as $t){
$temas[$t['parcial']]=$t;
}

?>

<div class="bg-white rounded-xl shadow p-6 mb-6">

<h1 class="text-2xl font-bold text-gray-800 mb-2">
Planeación de Temas
</h1>

<p class="text-gray-600">
<strong>Materia:</strong> <?= $asignacion['materia'] ?> |
<strong>Grupo:</strong> <?= $asignacion['grupo'] ?>
</p>

</div>


<div class="grid md:grid-cols-2 gap-6">

<?php for($i=1;$i<=$total_parciales;$i++): ?>

<div class="bg-white rounded-xl shadow-md overflow-hidden">

<div class="bg-purple-600 text-white px-6 py-3 font-semibold">
Parcial <?= $i ?>
</div>

<div class="p-6">

<?php if(isset($temas[$i])): ?>

<p class="text-lg font-semibold text-gray-800 mb-2">
<?= htmlspecialchars($temas[$i]['tema']) ?>
</p>

<p class="text-gray-600 mb-4">
<?= htmlspecialchars($temas[$i]['descripcion']) ?>
</p>

<div class="flex gap-2">

<button
onclick="document.getElementById('edit<?= $i ?>').style.display='block'"
class="bg-blue-500 hover:bg-blue-600 text-white px-3 py-1 rounded flex items-center gap-1"
>
Editar
</button>

<a
href="coordinador_dashboard.php?modulo=definir_temas&asignacion=<?= $asignacion_id ?>&eliminar=<?= $temas[$i]['id'] ?>"
class="bg-red-500 hover:bg-red-600 text-white px-3 py-1 rounded flex items-center gap-1"
onclick="return confirm('¿Eliminar este tema?')"
>
Eliminar
</a>

</div>

<div id="edit<?= $i ?>" style="display:none" class="mt-4">

<form method="POST">

<input type="hidden" name="id" value="<?= $temas[$i]['id'] ?>">

<input
type="text"
name="tema"
value="<?= htmlspecialchars($temas[$i]['tema']) ?>"
class="border p-2 w-full mb-2 rounded"
required
>

<textarea
name="descripcion"
class="border p-2 w-full mb-2 rounded"
><?= htmlspecialchars($temas[$i]['descripcion']) ?></textarea>

<button
name="editar"
class="bg-green-600 hover:bg-green-700 text-white px-3 py-1 rounded"
>
Guardar cambios
</button>

</form>

</div>

<?php else: ?>

<form method="POST">

<input type="hidden" name="parcial" value="<?= $i ?>">

<input
type="text"
name="tema"
placeholder="Tema del parcial"
class="border p-2 w-full mb-2 rounded"
required
>

<textarea
name="descripcion"
placeholder="Descripción"
class="border p-2 w-full mb-2 rounded"
></textarea>

<button
name="guardar"
class="bg-purple-600 hover:bg-purple-700 text-white px-3 py-1 rounded"
>
Agregar tema
</button>

</form>

<?php endif; ?>

</div>

</div>

<?php endfor; ?>

</div>