<?php
require_once __DIR__ . '/../../includes/db.php';

/* PERIODOS ACTIVOS */

$stmt = $pdo->query("
SELECT 
p.id,
p.nombre,
p.tipo_periodo_id,
p.fecha_inicio,
tp.nombre AS tipo_nombre
FROM periodos p
JOIN tipos_periodo tp ON tp.id = p.tipo_periodo_id
WHERE p.activo = 1
ORDER BY p.fecha_inicio DESC
");

$periodos = $stmt->fetchAll(PDO::FETCH_ASSOC);


/* GRUPOS */

$grupos = $pdo->query("
SELECT id,nombre
FROM grupos
ORDER BY nombre
")->fetchAll(PDO::FETCH_ASSOC);
?>

<h1 class="text-3xl font-bold mb-6">
Generar Parcial
</h1>

<div class="bg-white p-6 rounded-xl shadow max-w-xl">

<form method="POST" action="/sistema_academico/modules/admin/guardar_parcial.php" class="space-y-4">


<!-- PERIODO ESCOLAR -->

<div>

<label class="font-semibold">
Periodo Escolar
</label>

<select 
id="periodo"
name="periodo_id"
class="w-full border p-2 rounded"
required>

<option value="">
Seleccionar periodo
</option>

<?php foreach($periodos as $p){ ?>

<option 
value="<?= $p['id'] ?>"
data-tipo="<?= $p['tipo_periodo_id'] ?>"
data-nombre="<?= htmlspecialchars($p['tipo_nombre']) ?>"
>

<?= htmlspecialchars($p['nombre']) ?> — <?= htmlspecialchars($p['tipo_nombre']) ?>

</option>

<?php } ?>

</select>

</div>


<!-- CARRERA -->

<div>

<label class="font-semibold">
Carrera
</label>

<select 
id="carrera"
name="carrera_id"
class="w-full border p-2 rounded"
required>

<option value="">
Seleccione un periodo primero
</option>

</select>

</div>


<!-- PERIODO MALLA -->

<div>

<label 
id="label_periodo_malla"
class="font-semibold">

Seleccione periodo

</label>

<select 
id="periodo_malla"
name="periodo_malla"
class="w-full border p-2 rounded"
required>

<option value="">
Seleccione carrera
</option>

</select>

</div>


<!-- MATERIA -->

<div>

<label class="font-semibold">
Materia
</label>

<select 
id="materia"
name="materia_id"
class="w-full border p-2 rounded"
required>

<option value="">
Seleccione periodo
</option>

</select>

</div>


<!-- GRUPO -->

<div>

<label class="font-semibold">
Grupo
</label>

<select 
name="grupo_id"
class="w-full border p-2 rounded"
required>

<option value="">
Seleccionar grupo
</option>

<?php foreach($grupos as $g){ ?>

<option value="<?= $g['id'] ?>">
<?= htmlspecialchars($g['nombre']) ?>
</option>

<?php } ?>

</select>

</div>


<!-- NUMERO PARCIAL -->

<div>

<label class="font-semibold">
Número de Parcial
</label>

<input
type="number"
name="numero"
class="w-full border p-2 rounded"
min="1"
max="10"
required>

</div>


<button class="bg-purple-600 text-white px-6 py-2 rounded hover:bg-purple-700">
Crear Parcial
</button>

</form>

</div>



<script>

/* CUANDO CAMBIA EL PERIODO */

document.getElementById("periodo").addEventListener("change",function(){

let select=this.options[this.selectedIndex];

let tipo=select.dataset.tipo;

let nombreTipo=select.dataset.nombre;

let label=document.getElementById("label_periodo_malla");

/* CAMBIAR TEXTO SEGUN TIPO */

switch(tipo){

case "1":
label.textContent="Seleccione semestre";
break;

case "2":
label.textContent="Seleccione cuatrimestre";
break;

case "3":
label.textContent="Seleccione trimestre";
break;

case "4":
label.textContent="Seleccione bimestre";
break;

default:
label.textContent="Seleccione periodo";

}


/* CARGAR CARRERAS */

fetch("/sistema_academico/modules/admin/carreras_por_tipo.php?tipo="+tipo)

.then(res=>res.json())

.then(data=>{

let carrera=document.getElementById("carrera");

carrera.innerHTML="<option value=''>Seleccionar carrera</option>";

data.forEach(c=>{

let option=document.createElement("option");

option.value=c.id;

option.textContent=c.nombre;

carrera.appendChild(option);

});

});

});



/* CUANDO CAMBIA LA CARRERA */

document.getElementById("carrera").addEventListener("change",function(){

let carrera=this.value;

let periodoSelect=document.getElementById("periodo");

if(!periodoSelect.value) return;

let tipo=periodoSelect.options[periodoSelect.selectedIndex].dataset.tipo;

/* CARGAR PERIODOS MALLA */

fetch("/sistema_academico/modules/admin/periodos_malla.php?carrera_id="+carrera+"&tipo="+tipo)

.then(res=>res.json())

.then(data=>{

let periodo=document.getElementById("periodo_malla");

periodo.innerHTML="<option value=''>Seleccionar</option>";

data.forEach(p=>{

let option=document.createElement("option");

option.value=p.numero;

option.textContent=p.nombre;

periodo.appendChild(option);

});

});

});



/* CUANDO CAMBIA EL PERIODO DE MALLA */

document.getElementById("periodo_malla").addEventListener("change",function(){

let carrera=document.getElementById("carrera").value;

let periodo=this.value;

if(!carrera || !periodo) return;

fetch("/sistema_academico/modules/admin/obtener_materias.php?carrera="+carrera+"&periodo="+periodo)

.then(res=>res.json())

.then(data=>{

let materia=document.getElementById("materia");

materia.innerHTML="<option value=''>Seleccionar materia</option>";

data.forEach(m=>{

let option=document.createElement("option");

option.value=m.id;

option.textContent=m.nombre;

materia.appendChild(option);

});

});

});

</script>