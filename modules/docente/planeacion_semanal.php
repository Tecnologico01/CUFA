<?php

require_once __DIR__ . "/../../includes/db.php";

$asignacion_id = $_GET['asignacion_id'] ?? null;

if(!$asignacion_id){
    echo "<p class='text-red-600'>Asignación no válida</p>";
    exit;
}


/* OBTENER INFORMACION DE LA MATERIA */

$stmt = $pdo->prepare("
SELECT 
m.nombre AS materia,
g.nombre AS grupo,
p.nombre AS periodo
FROM asignacion_docente ad
JOIN materias m ON ad.materia_id = m.id
JOIN grupos g ON ad.grupo_id = g.id
JOIN periodos p ON ad.periodo_id = p.id
WHERE ad.id = ?
");

$stmt->execute([$asignacion_id]);

$info = $stmt->fetch(PDO::FETCH_ASSOC);

if(!$info){
    echo "<p class='text-red-600'>Materia no encontrada</p>";
    exit;
}


/* OBTENER PLANEACION */

$stmt = $pdo->prepare("
SELECT * 
FROM planeaciones
WHERE asignacion_id = ?
ORDER BY parcial, semana
");

$stmt->execute([$asignacion_id]);

$planeaciones = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>

<div class="bg-white shadow rounded p-6">

<h2 class="text-2xl font-bold mb-2">
Planeación semanal
</h2>

<p class="text-gray-600 mb-6">

Materia: <b><?php echo $info['materia']; ?></b>  
Grupo: <b><?php echo $info['grupo']; ?></b>  
Periodo: <b><?php echo $info['periodo']; ?></b>

</p>


<table class="w-full border">

<thead class="bg-gray-200">

<tr>

<th class="p-2 border">Parcial</th>
<th class="p-2 border">Semana</th>
<th class="p-2 border">Tema</th>
<th class="p-2 border">Subtemas</th>
<th class="p-2 border">Actividades aprendizaje</th>
<th class="p-2 border">Actividades enseñanza</th>
<th class="p-2 border">Competencias</th>
<th class="p-2 border">Acciones</th>

</tr>

</thead>

<tbody>

<?php foreach($planeaciones as $p): ?>

<tr>

<td class="border p-2"><?php echo $p['parcial']; ?></td>

<td class="border p-2"><?php echo $p['semana']; ?></td>

<td class="border p-2"><?php echo $p['tema']; ?></td>

<td class="border p-2"><?php echo $p['subtemas']; ?></td>

<td class="border p-2"><?php echo $p['act_aprendizaje']; ?></td>

<td class="border p-2"><?php echo $p['act_ensenanza']; ?></td>

<td class="border p-2"><?php echo $p['competencias']; ?></td>

<td class="border p-2">

<a href="docente_dashboard.php?modulo=editar_planeacion&id=<?php echo $p['id']; ?>&asignacion_id=<?php echo $asignacion_id; ?>"
class="bg-blue-600 text-white px-2 py-1 rounded">

Editar

</a>

</td>

</tr>

<?php endforeach; ?>

</tbody>

</table>


<div class="mt-6">

<a href="docente_dashboard.php?modulo=crear_planeacion&asignacion_id=<?php echo $asignacion_id; ?>"
class="bg-green-600 text-white px-4 py-2 rounded">

Agregar planeación

</a>

</div>

</div>