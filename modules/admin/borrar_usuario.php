<?php

require_once __DIR__ . '/../../includes/db.php';

if(!isset($pdo)){
die("Error: no hay conexión a la base de datos");
}

$id = $_GET['id'] ?? null;

if(!$id){
die("ID de usuario inválido");
}

$stmt = $pdo->prepare("DELETE FROM usuarios WHERE id=?");

$stmt->execute([$id]);

header("Location: /sistema_academico/dashboards/admin_dashboard.php?modulo=usuarios");

exit;