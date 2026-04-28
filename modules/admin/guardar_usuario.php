<?php
require_once __DIR__ . '/../../includes/db.php';

$id = $_POST['id'];
$nombre = $_POST['nombre'];
$email = $_POST['email'];
$rol = $_POST['rol'];
$activo = $_POST['activo'];
$password = $_POST['password'] ?? '';

if(!empty($password)){

$passwordHash = password_hash($password, PASSWORD_DEFAULT);

$stmt = $pdo->prepare("
UPDATE usuarios
SET nombre=?, email=?, rol=?, activo=?, password=?
WHERE id=?
");

$stmt->execute([$nombre,$email,$rol,$activo,$passwordHash,$id]);

}else{

$stmt = $pdo->prepare("
UPDATE usuarios
SET nombre=?, email=?, rol=?, activo=?
WHERE id=?
");

$stmt->execute([$nombre,$email,$rol,$activo,$id]);

}

header("Location: admin_dashboard.php?modulo=usuarios_lista");
exit;