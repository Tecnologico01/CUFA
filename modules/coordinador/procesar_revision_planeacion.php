<?php
session_start();
require_once __DIR__ . '/../../includes/db.php';

// 🔒 VALIDAR SESIÓN
if (!isset($_SESSION['user_id']) || $_SESSION['rol'] !== 'coordinador_academico') {
    header("Location: /sistema_academico/login.php");
    exit;
}

// 🚫 SOLO POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: /sistema_academico/dashboards/coordinador_dashboard.php?modulo=revisar_planeaciones_digital");
    exit;
}

// 🧹 DATOS
$asignacion_id = filter_input(INPUT_POST, 'asignacion_id', FILTER_VALIDATE_INT);
$estado = $_POST['accion'] ?? null;
$observaciones = trim($_POST['observaciones'] ?? '');

$estados_validos = ['aprobado', 'rechazado'];

if (!$asignacion_id || !in_array($estado, $estados_validos)) {
    header("Location: /sistema_academico/dashboards/coordinador_dashboard.php?modulo=revisar_planeaciones_digital&status=error");
    exit;
}

try {

    $pdo->beginTransaction();

    // ✅ 1. ESTADO GENERAL (NO LO BORRES)
    $stmt = $pdo->prepare("
        UPDATE asignaciones_docentes 
        SET estado_planeacion = ?, 
            observaciones_planeacion = ?, 
            fecha_revision = NOW() 
        WHERE id = ?
    ");
    $stmt->execute([$estado, $observaciones, $asignacion_id]);

    // 🔥 2. CONTROL REAL DE TEMAS
    if ($estado === 'rechazado') {

        $stmtUnlock = $pdo->prepare("
            UPDATE temas_materia 
            SET estado = 'borrador'
            WHERE asignacion_id = ? AND estado = 'enviado'
        ");
        $stmtUnlock->execute([$asignacion_id]);

        error_log("Desbloqueados: " . $stmtUnlock->rowCount());

    } else {

        $stmtLock = $pdo->prepare("
            UPDATE temas_materia 
            SET estado = 'aprobado'
            WHERE asignacion_id = ?
        ");
        $stmtLock->execute([$asignacion_id]);

        error_log("Bloqueados: " . $stmtLock->rowCount());
    }

    // 📩 NOTIFICACIÓN
    $stmtInfo = $pdo->prepare("
        SELECT d.usuario_id, m.nombre AS materia_nombre 
        FROM asignaciones_docentes ad 
        JOIN docentes d ON ad.docente_id = d.id 
        JOIN materias m ON ad.materia_id = m.id 
        WHERE ad.id = ?
    ");
    $stmtInfo->execute([$asignacion_id]);
    $data = $stmtInfo->fetch(PDO::FETCH_ASSOC);

    if ($data) {

        $mensaje = ($estado === 'aprobado')
            ? "✅ Planeación APROBADA de {$data['materia_nombre']}"
            : "⚠️ Planeación RECHAZADA de {$data['materia_nombre']}. Motivo: " . ($observaciones ?: "Sin observaciones");

        $stmtNotif = $pdo->prepare("
            INSERT INTO notificaciones (usuario_id, mensaje, leido, fecha)
            VALUES (?, ?, 0, NOW())
        ");
        $stmtNotif->execute([$data['usuario_id'], $mensaje]);
    }

    $pdo->commit();

    header("Location: /sistema_academico/dashboards/coordinador_dashboard.php?modulo=revisar_planeaciones_digital&status=ok");
    exit;

} catch (Exception $e) {

    $pdo->rollBack();
    error_log("ERROR: " . $e->getMessage());

    header("Location: /sistema_academico/dashboards/coordinador_dashboard.php?modulo=revisar_planeaciones_digital&status=error_db");
    exit;
}