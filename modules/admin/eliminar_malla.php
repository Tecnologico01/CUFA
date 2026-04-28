<?php
require_once __DIR__ . '/../../includes/db.php';

$id = $_GET['id'];

$stmt = $pdo->prepare("
DELETE FROM mallas_curriculares
WHERE id=?
");

$stmt->execute([$id]);

header("Location: ../../dashboards/admin_dashboard.php?modulo=ver_mallas");
exit;