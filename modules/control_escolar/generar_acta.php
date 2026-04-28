<?php
require_once __DIR__ . '/../../includes/db.php';

if($_SERVER['REQUEST_METHOD']=="POST"){

$materia=$_POST['materia'];
$grupo=$_POST['grupo'];

$stmt=$pdo->prepare("
INSERT INTO actas(materia_id,grupo_id)
VALUES(?,?)
");

$stmt->execute([$materia,$grupo]);

echo "<p class='text-green-600 mb-4'>Acta generada correctamente</p>";

}

$materias=$pdo->query("SELECT id,nombre FROM materias")->fetchAll();
$grupos=$pdo->query("SELECT id,nombre FROM grupos")->fetchAll();
?>

<h1 class="text-2xl font-bold mb-6">Generar Acta</h1>

<form method="POST" class="bg-white p-6 rounded shadow">

<label class="block mb-2 font-semibold">Materia</label>

<select name="materia" class="w-full border p-2 mb-4">

<?php foreach($materias as $m){ ?>

<option value="<?= $m['id'] ?>">
<?= $m['nombre'] ?>
</option>

<?php } ?>

</select>

<label class="block mb-2 font-semibold">Grupo</label>

<select name="grupo" class="w-full border p-2 mb-4">

<?php foreach($grupos as $g){ ?>

<option value="<?= $g['id'] ?>">
<?= $g['nombre'] ?>
</option>

<?php } ?>

</select>

<button class="bg-purple-600 text-white px-4 py-2 rounded">
Generar Acta
</button>

</form>