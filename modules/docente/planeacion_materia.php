<?php
require_once __DIR__ . '/../../includes/db.php';

/* =========================
   VALIDAR ASIGNACIÓN
========================= */

$asignacion_id = $_GET['asignacion_id'] ?? $_GET['asignacion'] ?? null;

if (!$asignacion_id) {
    echo "<p class='text-red-600'>Asignación no especificada.</p>";
    exit;
}

/* =========================
   OBTENER DATOS DE MATERIA
========================= */

$stmt = $pdo->prepare("
SELECT ad.id,
       m.nombre AS materia,
       g.nombre AS grupo
FROM asignaciones_docentes ad
JOIN materias m ON ad.materia_id = m.id
JOIN grupos g ON ad.grupo_id = g.id
WHERE ad.id = ?
");

$stmt->execute([$asignacion_id]);
$asignacion = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$asignacion) {
    echo "<p class='text-red-600'>Asignación no encontrada.</p>";
    exit;
}

/* =========================
   GUARDAR / ACTUALIZAR PLANEACIÓN
========================= */

if (isset($_POST['guardar'])) {

    $tema_id = $_POST['tema_id'] ?? null;
    $subtemas = $_POST['subtemas'] ?? '';
    $aprendizaje = $_POST['aprendizaje'] ?? '';
    $ensenanza = $_POST['ensenanza'] ?? '';
    $competencias = $_POST['competencias'] ?? '';
    $horas = $_POST['horas'] ?? '';

    if ($tema_id) {

        /* Verificar si ya existe planeación */

        $check = $pdo->prepare("SELECT id FROM planeacion_docente WHERE tema_id=?");
        $check->execute([$tema_id]);

        if ($check->rowCount() > 0) {

            /* ACTUALIZAR */

            $stmt = $pdo->prepare("
            UPDATE planeacion_docente
            SET subtemas=?,
                actividades_aprendizaje=?,
                actividades_ensenanza=?,
                competencias_genericas=?,
                horas=?
            WHERE tema_id=?
            ");

            $stmt->execute([
                $subtemas,
                $aprendizaje,
                $ensenanza,
                $competencias,
                $horas,
                $tema_id
            ]);

        } else {

            /* INSERTAR */

            $stmt = $pdo->prepare("
            INSERT INTO planeacion_docente
            (tema_id, subtemas, actividades_aprendizaje, actividades_ensenanza, competencias_genericas, horas)
            VALUES (?,?,?,?,?,?)
            ");

            $stmt->execute([
                $tema_id,
                $subtemas,
                $aprendizaje,
                $ensenanza,
                $competencias,
                $horas
            ]);

        }

        echo "<script>
        alert('Planeación guardada correctamente');
        window.location.href='docente_dashboard.php?modulo=planeacion_materia&asignacion=$asignacion_id';
        </script>";
    }
}

/* =========================
   OBTENER TEMAS
========================= */

$stmt = $pdo->prepare("
SELECT t.id,
       t.parcial,
       t.tema,
       t.descripcion,
       p.subtemas,
       p.actividades_aprendizaje,
       p.actividades_ensenanza,
       p.competencias_genericas,
       p.horas
FROM temas_materia t
LEFT JOIN planeacion_docente p ON p.tema_id = t.id
WHERE t.asignacion_id = ?
ORDER BY t.parcial
");

$stmt->execute([$asignacion_id]);
$temas = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>

<div class="bg-white shadow rounded-xl p-6 mb-6">

<h1 class="text-2xl font-bold text-gray-800">
Planeación Didáctica
</h1>

<p class="text-gray-600 mt-2">
<strong>Materia:</strong> <?= htmlspecialchars($asignacion['materia']) ?> |
<strong>Grupo:</strong> <?= htmlspecialchars($asignacion['grupo']) ?>
</p>

</div>



<div class="bg-white shadow rounded-xl p-6 overflow-x-auto">

<table class="min-w-full border text-sm">

<thead class="bg-gray-100">

<tr>

<th class="border p-3">Parcial</th>
<th class="border p-3">Tema</th>
<th class="border p-3">Subtemas</th>
<th class="border p-3">Actividades de Aprendizaje</th>
<th class="border p-3">Actividades de Enseñanza</th>
<th class="border p-3">Competencias</th>
<th class="border p-3">Horas</th>
<th class="border p-3">Acción</th>

</tr>

</thead>

<tbody>

<?php foreach ($temas as $t): ?>

<tr>

<td class="border p-3 text-center">
<?= $t['parcial'] ?>
</td>

<td class="border p-3">

<strong><?= htmlspecialchars($t['tema']) ?></strong>

<?php if(!empty($t['descripcion'])): ?>
<br>
<span class="text-xs text-gray-500">
<?= htmlspecialchars($t['descripcion']) ?>
</span>
<?php endif; ?>

</td>

<td class="border p-3">
<?= nl2br(htmlspecialchars($t['subtemas'] ?? '')) ?>
</td>

<td class="border p-3">
<?= nl2br(htmlspecialchars($t['actividades_aprendizaje'] ?? '')) ?>
</td>

<td class="border p-3">
<?= nl2br(htmlspecialchars($t['actividades_ensenanza'] ?? '')) ?>
</td>

<td class="border p-3">
<?= nl2br(htmlspecialchars($t['competencias_genericas'] ?? '')) ?>
</td>

<td class="border p-3 text-center">
<?= htmlspecialchars($t['horas'] ?? '') ?>
</td>

<td class="border p-3 text-center">

<button onclick="abrirModal(<?= $t['id'] ?>)"
class="bg-indigo-600 text-white px-3 py-1 rounded hover:bg-indigo-700">
Editar
</button>

</td>

</tr>

<?php endforeach; ?>

</tbody>

</table>

</div>



<?php foreach ($temas as $t): ?>

<div id="modal<?= $t['id'] ?>" class="fixed inset-0 bg-black bg-opacity-40 hidden items-center justify-center">

<div class="bg-white p-6 rounded-xl w-[700px]">

<h2 class="text-xl font-bold mb-4">
Editar Planeación - Parcial <?= $t['parcial'] ?>
</h2>

<form method="POST">

<input type="hidden" name="tema_id" value="<?= $t['id'] ?>">

<label class="font-semibold">Subtemas</label>
<textarea name="subtemas" class="border w-full p-2 mb-3 rounded"><?= $t['subtemas'] ?></textarea>

<label class="font-semibold">Actividades de Aprendizaje</label>
<textarea name="aprendizaje" class="border w-full p-2 mb-3 rounded"><?= $t['actividades_aprendizaje'] ?></textarea>

<label class="font-semibold">Actividades de Enseñanza</label>
<textarea name="ensenanza" class="border w-full p-2 mb-3 rounded"><?= $t['actividades_ensenanza'] ?></textarea>

<label class="font-semibold">Competencias Genéricas</label>
<textarea name="competencias" class="border w-full p-2 mb-3 rounded"><?= $t['competencias_genericas'] ?></textarea>

<label class="font-semibold">Horas</label>
<input type="text" name="horas" value="<?= $t['horas'] ?>" class="border w-full p-2 mb-4 rounded">

<div class="flex justify-end gap-2">

<button type="button"
onclick="cerrarModal(<?= $t['id'] ?>)"
class="bg-gray-400 text-white px-3 py-1 rounded">
Cancelar
</button>

<button name="guardar"
class="bg-green-600 text-white px-3 py-1 rounded">
Guardar
</button>

</div>

</form>

</div>

</div>

<?php endforeach; ?>



<script>

function abrirModal(id){
document.getElementById('modal'+id).style.display='flex';
}

function cerrarModal(id){
document.getElementById('modal'+id).style.display='none';
}

</script>