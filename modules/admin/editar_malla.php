<?php
require_once __DIR__ . '/../../includes/db.php';

$malla_id = $_GET['id'] ?? null;

if(!$malla_id){
die("Malla no válida");
}

/* OBTENER MALLA */

$stmt = $pdo->prepare("
SELECT mc.*, c.nombre as carrera
FROM mallas_curriculares mc
JOIN carreras c ON c.id = mc.carrera_id
WHERE mc.id=?
");

$stmt->execute([$malla_id]);
$malla = $stmt->fetch(PDO::FETCH_ASSOC);

if(!$malla){
die("Malla no encontrada");
}

/* TODAS LAS MATERIAS */

$materias = $pdo->query("
SELECT id,nombre,clave,creditos,tipo
FROM materias
ORDER BY nombre
")->fetchAll(PDO::FETCH_ASSOC);

/* MATERIAS DE LA MALLA */

$stmt = $pdo->prepare("
SELECT mm.*, m.nombre, m.creditos, m.clave, m.tipo
FROM malla_materias mm
JOIN materias m ON m.id = mm.materia_id
WHERE mm.malla_id=?
");

$stmt->execute([$malla_id]);

$materias_malla = [];

while($row = $stmt->fetch(PDO::FETCH_ASSOC)){
$materias_malla[$row['periodo_numero']][] = $row;
}
?>

<!DOCTYPE html>
<html>
<head>

<meta charset="UTF-8">

<title>Editar Malla Curricular</title>

<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet"/>

<script src="https://cdn.jsdelivr.net/npm/jquery@3.6.0/dist/jquery.min.js"></script>

<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<style>

body{
font-family:Arial;
background:#f4f6f9;
margin:0;
}

.header{
background:#673ab7;
color:white;
padding:20px;
font-size:22px;
}

.contenedor{
max-width:1200px;
margin:auto;
padding:30px;
}

.periodo{
background:white;
border-radius:8px;
padding:20px;
margin-bottom:25px;
box-shadow:0 2px 8px rgba(0,0,0,0.1);
}

.materia{
display:flex;
gap:10px;
margin-bottom:10px;
align-items:center;
}

.badge{
padding:5px 10px;
border-radius:4px;
font-size:12px;
}

.creditos{
background:#e3f2fd;
}

.clave{
background:#f3e5f5;
}

.tipo{
background:#fff3e0;
}

button{
border:none;
padding:7px 12px;
border-radius:5px;
cursor:pointer;
}

.btn-agregar{
background:#4CAF50;
color:white;
}

.btn-eliminar{
background:#e53935;
color:white;
}

.btn-guardar{
background:#673ab7;
color:white;
font-size:16px;
padding:12px 25px;
margin-top:20px;
}

</style>

</head>

<body>

<div class="header">
Editor de Malla Curricular
</div>

<div class="contenedor">

<h2><?= htmlspecialchars($malla['carrera']) ?></h2>

<form method="POST" action="/sistema_academico/modules/admin/guardar_malla_completa.php">

<input type="hidden" name="malla_id" value="<?= $malla_id ?>">

<?php for($p=1;$p<=$malla['total_periodos'];$p++){ ?>

<div class="periodo">

<h3>Periodo <?= $p ?></h3>

<div id="periodo<?= $p ?>">

<?php
if(isset($materias_malla[$p])){
foreach($materias_malla[$p] as $mat){
?>

<div class="materia">

<select name="materias[<?= $p ?>][]" class="buscar_materia" required>

<?php foreach($materias as $m){ ?>

<option 
value="<?= $m['id'] ?>" 
data-creditos="<?= $m['creditos'] ?>"
data-clave="<?= $m['clave'] ?>"
data-tipo="<?= $m['tipo'] ?>"
<?= $m['id']==$mat['materia_id']?'selected':'' ?>>

<?= $m['clave'] ?> - <?= $m['nombre'] ?> (<?= $m['tipo'] ?>)

</option>

<?php } ?>

</select>

<span class="badge creditos">
<?= $mat['creditos'] ?> créditos
</span>

<span class="badge clave">
<?= $mat['clave'] ?>
</span>

<span class="badge tipo">
<?= $mat['tipo'] ?>
</span>

<button type="button" class="btn-eliminar" onclick="eliminarMateria(this)">
Eliminar
</button>

</div>

<?php
}
}
?>

</div>

<button type="button" class="btn-agregar" onclick="agregarMateria(<?= $p ?>)">
Agregar Materia
</button>

</div>

<?php } ?>

<button class="btn-guardar">
Guardar Malla
</button>

</form>

</div>


<script>

const materias = <?= json_encode($materias) ?>;

/* ACTIVAR BUSCADOR */

$(document).ready(function(){

$('.buscar_materia').select2({
placeholder:"Buscar materia...",
width:'300px'
});

});

/* AGREGAR MATERIA */

function agregarMateria(periodo){

let contenedor=document.getElementById("periodo"+periodo);

let div=document.createElement("div");
div.classList.add("materia");

let select=document.createElement("select");
select.name="materias["+periodo+"][]";
select.classList.add("buscar_materia");

materias.forEach(m=>{

let option=document.createElement("option");

option.value=m.id;
option.textContent=m.clave + " - " + m.nombre + " (" + m.tipo + ")";
option.dataset.creditos=m.creditos;
option.dataset.clave=m.clave;
option.dataset.tipo=m.tipo;

select.appendChild(option);

});

let creditos=document.createElement("span");
creditos.classList.add("badge","creditos");
creditos.innerText="0 créditos";

let clave=document.createElement("span");
clave.classList.add("badge","clave");
clave.innerText="-";

let tipo=document.createElement("span");
tipo.classList.add("badge","tipo");
tipo.innerText="-";

let btn=document.createElement("button");
btn.type="button";
btn.textContent="Eliminar";
btn.classList.add("btn-eliminar");
btn.onclick=()=>div.remove();

div.appendChild(select);
div.appendChild(creditos);
div.appendChild(clave);
div.appendChild(tipo);
div.appendChild(btn);

contenedor.appendChild(div);

$(select).select2({
placeholder:"Buscar materia...",
width:'300px'
});

select.addEventListener("change",function(){

let option=this.options[this.selectedIndex];

creditos.innerText=option.dataset.creditos+" créditos";
clave.innerText=option.dataset.clave;
tipo.innerText=option.dataset.tipo;

});

}

/* ELIMINAR */

function eliminarMateria(btn){
btn.parentElement.remove();
}

/* EVITAR DUPLICADOS */

document.addEventListener("change",function(e){

if(e.target.classList.contains("buscar_materia")){

let seleccionadas=[];

document.querySelectorAll(".buscar_materia").forEach(s=>{
if(s.value){
seleccionadas.push(s.value);
}
});

let duplicadas=seleccionadas.filter((item,index)=>
seleccionadas.indexOf(item)!=index
);

if(duplicadas.length>0){

alert("Esta materia ya fue agregada en la malla");

e.target.value="";
$(e.target).trigger("change");

}

}

});

</script>

</body>
</html>