<?php
require_once __DIR__ . '/../../includes/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: /sistema_academico/");
    exit;
}

/* =========================
   DATOS
========================= */
$asignacion_id = $_POST['asignacion_id'] ?? null;
$accion        = $_POST['accion'] ?? null;
$observaciones = trim($_POST['observaciones'] ?? '');

/* =========================
   VALIDAR DATOS
========================= */
if (!$asignacion_id || !$accion) {
    die("Datos incompletos");
}

/* =========================
   VALIDAR ACCIÓN
========================= */
if (!in_array($accion, ['aprobado', 'rechazado'])) {
    die("Acción inválida");
}

try {

    $pdo->beginTransaction();

    /* =========================
       VERIFICAR ASIGNACIÓN
    ========================= */
    $stmtAsignacion = $pdo->prepare("
        SELECT id
        FROM asignaciones_docentes
        WHERE id = ?
        LIMIT 1
    ");

    $stmtAsignacion->execute([$asignacion_id]);

    $asignacion = $stmtAsignacion->fetch(PDO::FETCH_ASSOC);

    if (!$asignacion) {
        throw new Exception("La asignación no existe");
    }

    /* =========================
       DEFINIR BLOQUEO
    ========================= */

    // Si se APRUEBA:
    // - queda bloqueada
    // - el docente ya no puede editar

    // Si se RECHAZA:
    // - se desbloquea
    // - el docente puede corregir

    $planeacion_bloqueada = ($accion === 'aprobado') ? 1 : 0;

    /* =========================
       ACTUALIZAR ASIGNACIÓN
    ========================= */
    $stmtActualizar = $pdo->prepare("
        UPDATE asignaciones_docentes
        SET
            estado_planeacion = ?,
            observaciones_planeacion = ?,
            planeacion_bloqueada = ?
        WHERE id = ?
    ");

    $stmtActualizar->execute([
        $accion,
        $observaciones,
        $planeacion_bloqueada,
        $asignacion_id
    ]);

    /* =========================
       ACTUALIZAR TEMAS
    ========================= */

    // También actualizamos los registros
    // de temas_materia para mantener
    // coherencia visual del sistema.

    $estado_tema = ($accion === 'aprobado')
        ? 'aprobado'
        : 'rechazado';

    $stmtTemas = $pdo->prepare("
        UPDATE temas_materia
        SET estado = ?
        WHERE asignacion_id = ?
    ");

    $stmtTemas->execute([
        $estado_tema,
        $asignacion_id
    ]);

    $pdo->commit();

    /* =========================
       REDIRECCIÓN
    ========================= */
    header("Location: /sistema_academico/dashboards/coordinador_dashboard.php?modulo=ver_planeaciones&success=1");
    exit;

} catch (Exception $e) {

    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    die("Error: " . $e->getMessage());
}
?>