<?php

require_once __DIR__ . '/../../includes/db.php';

$id=$_GET['id'];

$stmt=$pdo->prepare("SELECT * FROM usuarios WHERE id=?");

$stmt->execute([$id]);

$user=$stmt->fetch();

?>

<h1>Usuario</h1>

Nombre: <?= $user['nombre'] ?><br>

Usuario: <?= $user['username'] ?><br>

Rol: <?= $user['rol'] ?><br>