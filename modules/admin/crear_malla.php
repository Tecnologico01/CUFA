<?php
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__.'/../../includes/permisos.php';validarPermiso('crear_mallas');
require_once __DIR__.'/../../includes/academico_service.php';
if(($_SERVER['REQUEST_METHOD']??'GET')==='POST'){try{cufaValidarToken();}catch(Throwable $e){http_response_code(422);cufaError($e);return;}}

if($_SERVER["REQUEST_METHOD"] === "POST"){

$carrera_id = $_POST['carrera_id'];
$tipo_periodo_id = $_POST['tipo_periodo_id'];
$total_periodos = $_POST['total_periodos'];

try{
if(!ctype_digit((string)$total_periodos)||(int)$total_periodos<1||(int)$total_periodos>30)throw new RuntimeException('La malla requiere de 1 a 30 periodos.');
$check=$pdo->prepare('SELECT id FROM carreras WHERE id=?');$check->execute([(int)$carrera_id]);if(!$check->fetchColumn())throw new RuntimeException('Carrera inválida.');
$check=$pdo->prepare('SELECT id FROM tipos_periodo WHERE id=?');$check->execute([(int)$tipo_periodo_id]);if(!$check->fetchColumn())throw new RuntimeException('Tipo de periodo inválido.');
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
}catch(Throwable $e){http_response_code(422);cufaError($e);}

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

<form method="POST" class="space-y-4"><?= cufaCampoToken() ?>

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