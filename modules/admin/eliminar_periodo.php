<?php
require_once __DIR__ . '/../../includes/db.php';

/* Solo permitir POST por seguridad */
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die("Acceso no permitido");
}

$id = $_POST['id'] ?? null;

if (!$id || !is_numeric($id)) {
    die("ID inválido");
}

try {

    /* =========================
       VALIDACIONES DE RELACIONES
    ========================== */

    // 1. Grupos
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM grupos WHERE periodo_id=?");
    $stmt->execute([$id]);
    if ($stmt->fetchColumn() > 0) {
        die("No se puede eliminar: hay grupos asociados a este periodo.");
    }

    // 2. Parciales
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM parciales WHERE periodo_id=?");
    $stmt->execute([$id]);
    if ($stmt->fetchColumn() > 0) {
        die("No se puede eliminar: hay parciales asociados a este periodo.");
    }

    /* =========================
       ELIMINAR
    ========================== */
    $stmt = $pdo->prepare("DELETE FROM periodos WHERE id=?");
    $stmt->execute([$id]);

    /* Redirección */
    header("Location: /sistema_academico/dashboards/admin_dashboard.php?modulo=periodos_lista");
    exit;

} catch (PDOException $e) {

    // Error controlado
    die("Error al eliminar el periodo: " . $e->getMessage());
}