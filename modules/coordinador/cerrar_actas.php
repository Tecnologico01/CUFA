<?php
require_once __DIR__ . '/../../includes/db.php';

if(isset($_GET['parcial'])){

$id = $_GET['parcial'];

$stmt=$pdo->prepare("UPDATE parciales SET acta_abierta=0 WHERE id=?");
$stmt->execute([$id]);

echo "<div class='bg-red-100 p-3 mb-4'>Acta cerrada</div>";

}

$parciales = $pdo->query("SELECT * FROM parciales")->fetchAll();
?>

<h1 class="text-2xl font-bold mb-6">Cerrar Actas</h1>

<?php foreach($parciales as $p): ?>

<div class="bg-white p-4 mb-3 shadow">

<?= $p['nombre'] ?>

<a href="?modulo=cerrar_actas&parcial=<?= $p['id'] ?>"
class="bg-red-600 text-white px-3 py-1 rounded ml-4">

Cerrar

</a>

</div>

<?php endforeach; ?>