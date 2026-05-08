<?php
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../vendor/autoload.php';

use Dompdf\Dompdf;
use Dompdf\Options;

/*
|--------------------------------------------------------------------------
| VALIDAR CARRERA
|--------------------------------------------------------------------------
*/

$carrera_id = $_GET['carrera_id'] ?? null;

if (!$carrera_id) {
    die("Carrera no especificada.");
}

/*
|--------------------------------------------------------------------------
| OBTENER CARRERA
|--------------------------------------------------------------------------
*/

$stmtCarrera = $pdo->prepare("
    SELECT *
    FROM carreras
    WHERE id = ?
");

$stmtCarrera->execute([$carrera_id]);

$carrera = $stmtCarrera->fetch(PDO::FETCH_ASSOC);

if (!$carrera) {
    die("Carrera no encontrada.");
}

/*
|--------------------------------------------------------------------------
| OBTENER MATERIAS
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare("
    SELECT
        m.*,
        s.nombre AS seriacion_nombre,
        s.clave AS seriacion_clave
    FROM materias m
    LEFT JOIN materias s ON s.id = m.seriacion_id
    WHERE m.carrera_id = ?
    ORDER BY
        m.grado ASC,
        m.nombre ASC
");

$stmt->execute([$carrera_id]);

$materias = $stmt->fetchAll(PDO::FETCH_ASSOC);

/*
|--------------------------------------------------------------------------
| AGRUPAR POR GRADO
|--------------------------------------------------------------------------
*/

$grados = [];

foreach ($materias as $m) {
    $grado = (int)$m['grado'];
    if (!isset($grados[$grado])) {
        $grados[$grado] = [];
    }
    $grados[$grado][] = $m;
}

/*
|--------------------------------------------------------------------------
| COLORES
|--------------------------------------------------------------------------
*/

function colorMateria($tipo)
{
    switch (strtolower($tipo)) {
        case 'especialidad': return '#eff6ff';
        case 'optativa': return '#fdf2f8';
        case 'tronco comun': return '#fffbeb';
        default: return '#ffffff';
    }
}

function colorBordeMateria($tipo)
{
    switch (strtolower($tipo)) {
        case 'especialidad': return '#3b82f6';
        case 'optativa': return '#ec4899';
        case 'tronco comun': return '#f59e0b';
        default: return '#9333ea';
    }
}

/*
|--------------------------------------------------------------------------
| GENERAR HTML
|--------------------------------------------------------------------------
*/

ob_start();
?>

<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<style>
    body {
        font-family: 'Helvetica', sans-serif;
        margin: 15px 20px;
        color: #0f172a;
        background-color: #f8fafc;
    }

    .header-container {
        border-bottom: 2px solid #e2e8f0;
        padding-bottom: 10px;
        margin-bottom: 20px;
    }

    .cufa-tag {
        font-size: 8px;
        font-weight: bold;
        color: #9333ea;
        text-transform: uppercase;
        letter-spacing: 2px;
        display: block;
    }

    h1 {
        margin: 5px 0;
        font-size: 28px;
        font-weight: 900;
        text-transform: uppercase;
        color: #0f172a;
    }

    h1 span { color: #9333ea; font-style: italic; }

    h2 {
        margin: 0;
        color: #64748b;
        font-size: 12px;
        text-transform: uppercase;
        letter-spacing: 1.5px;
    }

    .malla-table {
        width: 100%;
        border-collapse: separate;
        border-spacing: 8px 0;
        table-layout: fixed; /* Mantiene las columnas del mismo tamaño */
    }

    .grado-columna {
        vertical-align: top;
        width: 11.11%;
    }

    .grado-header {
        background-color: #0f172a;
        color: #ffffff;
        text-align: center;
        padding: 10px 5px;
        border-radius: 8px 8px 0 0;
        font-weight: bold;
        font-size: 10px;
        text-transform: uppercase;
        margin-bottom: 12px;
    }

    .materia {
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        padding: 8px;
        margin-bottom: 12px;
        background-color: #ffffff;
        overflow: hidden;
    }

    .materia-top {
        width: 100%;
        margin-bottom: 5px;
    }

    .clave {
        background-color: #f1f5f9;
        color: #475569;
        font-size: 8px;
        font-weight: bold;
        padding: 2px 4px;
        border-radius: 3px;
        display: inline-block;
    }

    .creditos {
        color: #64748b;
        font-size: 8px;
        font-weight: bold;
        float: right;
    }

    /* CORRECCIÓN PARA EL NOMBRE: */
    .nombre {
        font-size: 9px;
        font-weight: bold;
        color: #1e293b;
        line-height: 1.2;
        text-transform: uppercase;
        margin: 6px 0;
        word-wrap: break-word; /* Rompe palabras largas */
        word-break: break-all; /* Forzado extra para Dompdf */
        overflow: visible;     /* Permite que crezca hacia abajo */
    }

    .seriacion-box {
        margin-top: 8px;
        padding-top: 6px;
        border-top: 1px dashed #cbd5e1;
    }

    .seriacion-label {
        font-size: 7px;
        font-weight: bold;
        color: #9333ea;
        display: block;
    }

    .seriacion-clave {
        font-size: 8px;
        font-weight: bold;
        color: #0f172a;
    }

    .footer {
        margin-top: 30px;
        border-top: 1px solid #e2e8f0;
        padding-top: 10px;
        font-size: 9px;
        color: #64748b;
        text-transform: uppercase;
    }

    .footer-right { float: right; }
</style>
</head>
<body>

<div class="header-container">
    <span class="cufa-tag">Sistema Académico CUFA</span>
    <h1>Malla <span>Curricular</span></h1>
    <h2><?= htmlspecialchars($carrera['nombre']) ?></h2>
</div>

<table class="malla-table">
    <tr>
        <?php for($g=1; $g<=9; $g++): ?>
            <td class="grado-columna">
                <div class="grado-header">Grado <?= $g ?></div>
                <?php if(isset($grados[$g])): ?>
                    <?php foreach($grados[$g] as $m): ?>
                        <?php 
                            $bgColor = colorMateria($m['tipo']); 
                            $borderColor = colorBordeMateria($m['tipo']);
                        ?>
                        <div class="materia" style="background-color: <?= $bgColor ?>; border-left: 3px solid <?= $borderColor ?>;">
                            <div class="materia-top">
                                <span class="clave"><?= htmlspecialchars($m['clave']) ?></span>
                                <span class="creditos"><?= $m['creditos'] ?> CR</span>
                            </div>
                            <div class="nombre">
                                <?= htmlspecialchars($m['nombre_corto'] ?: $m['nombre']) ?>
                            </div>
                            <?php if($m['seriacion_id']): ?>
                                <div class="seriacion-box">
                                    <span class="seriacion-label">Asignatura Requerida:</span>
                                    <span class="seriacion-clave"><?= htmlspecialchars($m['seriacion_clave']) ?></span>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </td>
        <?php endfor; ?>
    </tr>
</table>

<div class="footer">
    <span>Asignaturas Totales: <?= count($materias) ?></span>
    <span class="footer-right">Fecha de Emisión: <?= date('d/m/Y') ?></span>
</div>

</body>
</html>

<?php
$html = ob_get_clean();

$options = new Options();
$options->set('isRemoteEnabled', true);
$options->set('defaultFont', 'Helvetica'); 

$dompdf = new Dompdf($options);
$dompdf->loadHtml($html);
$dompdf->setPaper('A3', 'landscape');
$dompdf->render();

$dompdf->stream(
    "malla_curricular_" . str_replace(' ', '_', strtolower($carrera['nombre'])) . ".pdf",
    ["Attachment" => true]
);
exit;