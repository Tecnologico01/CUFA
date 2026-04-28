<?php

require_once __DIR__ . '/../../includes/db.php';

$id = $_GET['id'] ?? null;

if(!$id){
die("Usuario no válido");
}

$stmt = $pdo->prepare("DELETE FROM usuarios WHERE id=?");

$stmt->execute([$id]);

header("Location: /sistema_academico/dashboards/admin_dashboard.php?modulo=usuarios");

exit;