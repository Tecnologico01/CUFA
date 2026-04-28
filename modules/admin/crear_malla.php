<?php
require_once __DIR__ . '/../../includes/db.php';

if($_SERVER["REQUEST_METHOD"] === "POST"){

$carrera_id = $_POST['carrera_id'];
$tipo_periodo_id = $_POST['tipo_periodo_id'];
$total_periodos = $_POST['total_periodos'];

$stmt = $pdo->prepare("
INSERT INTO mallas_curriculares
(carrera_id,tipo_periodo_id,total_periodos)
VALUES (?,?,?)
");

$stmt->execute([
$carrera_id,
$tipo_periodo_id,
$total_periodos
]);

$malla_id = $pdo->lastInsertId();

header("Location: /sistema_academico/dashboards/admin_dashboard.php?modulo=editar_malla&id=".$malla_id);
exit;

}

$carreras = $pdo->query("
SELECT id,nombre FROM carreras ORDER BY nombre
")->fetchAll(PDO::FETCH_ASSOC);

$tipos = $pdo->query("
SELECT id,nombre FROM tipos_periodo
")->fetchAll(PDO::FETCH_ASSOC);
?>

<h1 class="text-3xl font-bold mb-6">
Crear Malla Curricular
</h1>

<div class="bg-white p-6 rounded-xl shadow max-w-xl">

<form method="POST" class="space-y-4">

<div>
<label class="font-semibold">Carrera</label>

<select name="carrera_id" class="w-full border p-2 rounded" required>

<option value="">Seleccionar carrera</option>

<?php foreach($carreras as $c){ ?>

<option value="<?= $c['id'] ?>">
<?= $c['nombre'] ?>
</option>

<?php } ?>

</select>
</div>


<div>
<label class="font-semibold">Tipo</label>

<select id="tipo_periodo" name="tipo_periodo_id" class="w-full border p-2 rounded" required>

<option value="">Seleccionar tipo</option>

<?php foreach($tipos as $t){ ?>

<option value="<?= $t['id'] ?>">
<?= $t['nombre'] ?>
</option>

<?php } ?>

</select>

</div>


<div>

<label id="label_periodos" class="font-semibold">
Número total de periodos
</label>

<input
type="number"
name="total_periodos"
class="w-full border p-2 rounded"
required>

</div>


<button class="bg-purple-600 text-white px-6 py-2 rounded hover:bg-purple-700">
Crear Malla
</button>

</form>


<script>

const tipoSelect = document.getElementById("tipo_periodo");
const label = document.getElementById("label_periodos");

tipoSelect.addEventListener("change", function(){

let tipo = this.value;

switch(tipo){

case "1":
label.textContent = "Número total de semestres";
break;

case "2":
label.textContent = "Número total de cuatrimestres";
break;

case "3":
label.textContent = "Número total de trimestres";
break;

case "4":
label.textContent = "Número total de bimestres";
break;

default:
label.textContent = "Número total de periodos";

}

});

</script>

</div>