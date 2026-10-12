<?php
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../vendor/autoload.php';

use Dompdf\Dompdf;
use Dompdf\Options;

$carrera_id = isset($_GET['carrera_id']) ? (int) $_GET['carrera_id'] : 0;

if($carrera_id <= 0){
    die("Carrera no especificada.");
}

/* =========================
   OBTENER CARRERA
========================= */
$stmtCarrera = $pdo->prepare("
    SELECT id, nombre
    FROM carreras
    WHERE id = ?
    LIMIT 1
");
$stmtCarrera->execute([$carrera_id]);
$carrera = $stmtCarrera->fetch(PDO::FETCH_ASSOC);

if(!$carrera){
    die("Carrera no encontrada.");
}

/* =========================
   OBTENER MATERIAS
========================= */
$stmt = $pdo->prepare("
    SELECT
        m.id,
        m.grado,
        m.clave,
        m.nombre,
        m.nombre_corto,
        m.creditos,
        m.tipo,
        m.area_formacion,
        m.es_opcional,
        m.seriacion_id,
        s.nombre AS seriacion_nombre,
        s.clave AS seriacion_clave
    FROM materias m
    LEFT JOIN materias s
        ON s.id = m.seriacion_id
    WHERE m.carrera_id = ?
    ORDER BY
        m.grado ASC,
        m.nombre ASC
");
$stmt->execute([$carrera_id]);
$materias = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* =========================
   AGRUPAR POR GRADO
========================= */
$grados = [];

foreach($materias as $m){
    $grado = (int) $m['grado'];

    if(!isset($grados[$grado])){
        $grados[$grado] = [];
    }

    $grados[$grado][] = $m;
}

$totalGrados = 10;

/* =========================
   COLORES
========================= */
function colorMateria($area, $opcional)
{
    if($opcional){
        return '#fff7ed';
    }

    $area = mb_strtolower((string)$area, 'UTF-8');

    if(str_contains($area, 'básica') || str_contains($area, 'basica')){
        return '#eff6ff';
    }

    if(str_contains($area, 'metodológica') || str_contains($area, 'metodologica')){
        return '#f0fdf4';
    }

    if(str_contains($area, 'especializada')){
        return '#f5f3ff';
    }

    if(str_contains($area, 'pedagógica') || str_contains($area, 'pedagogica')){
        return '#fdf2f8';
    }

    return '#ffffff';
}

function colorBordeMateria($area, $opcional)
{
    if($opcional){
        return '#f97316';
    }

    $area = mb_strtolower((string)$area, 'UTF-8');

    if(str_contains($area, 'básica') || str_contains($area, 'basica')){
        return '#2563eb';
    }

    if(str_contains($area, 'metodológica') || str_contains($area, 'metodologica')){
        return '#16a34a';
    }

    if(str_contains($area, 'especializada')){
        return '#7c3aed';
    }

    if(str_contains($area, 'pedagógica') || str_contains($area, 'pedagogica')){
        return '#db2777';
    }

    return '#9333ea';
}

function nombreArchivoSeguro($texto)
{
    $texto = strtolower(trim($texto));
    $texto = preg_replace('/[^a-z0-9áéíóúñü_\s-]/iu', '', $texto);
    $texto = preg_replace('/[\s-]+/', '_', $texto);

    return $texto ?: 'malla_curricular';
}

/* =========================
   LIMPIAR BUFFER
========================= */
if(ob_get_length()){
    ob_end_clean();
}

/* =========================
   HTML PDF
========================= */
ob_start();
?>

<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">

<style>
    @page {
        margin: 20px 22px;
    }

    body {
        font-family: Helvetica, Arial, sans-serif;
        color: #0f172a;
        background: #ffffff;
        font-size: 10px;
    }

    .header {
        border-bottom: 2px solid #e2e8f0;
        padding-bottom: 12px;
        margin-bottom: 18px;
    }

    .tag {
        font-size: 8px;
        font-weight: bold;
        color: #9333ea;
        text-transform: uppercase;
        letter-spacing: 2px;
    }

    h1 {
        margin: 4px 0;
        font-size: 28px;
        line-height: 1;
        text-transform: uppercase;
        color: #0f172a;
    }

    .accent {
        color: #9333ea;
        font-style: italic;
    }

    .career {
        margin-top: 4px;
        font-size: 12px;
        font-weight: bold;
        color: #64748b;
        text-transform: uppercase;
        letter-spacing: 1px;
    }

    .summary {
        margin-top: 10px;
        font-size: 9px;
        color: #475569;
    }

    .malla-table {
        width: 100%;
        border-collapse: separate;
        border-spacing: 6px 0;
        table-layout: fixed;
    }

    .grado-col {
        vertical-align: top;
        width: <?= round(100 / $totalGrados, 2) ?>%;
    }

    .grado-header {
        background: #0f172a;
        color: #ffffff;
        text-align: center;
        padding: 9px 4px;
        border-radius: 7px 7px 0 0;
        font-weight: bold;
        font-size: 9px;
        text-transform: uppercase;
        margin-bottom: 8px;
    }

    .materia {
        border: 1px solid #e2e8f0;
        border-left-width: 4px;
        border-radius: 8px;
        padding: 7px;
        margin-bottom: 8px;
        page-break-inside: avoid;
        overflow: hidden;
    }

    .top {
        margin-bottom: 5px;
    }

    .clave {
        background: #f1f5f9;
        color: #475569;
        font-size: 7px;
        font-weight: bold;
        padding: 2px 4px;
        border-radius: 3px;
        display: inline-block;
    }

    .creditos {
        float: right;
        color: #64748b;
        font-size: 7px;
        font-weight: bold;
        margin-top: 2px;
    }

    .nombre {
        font-size: 8px;
        font-weight: bold;
        color: #1e293b;
        line-height: 1.25;
        text-transform: uppercase;
        margin-top: 6px;
        word-wrap: break-word;
        word-break: break-word;
    }

    .meta {
        margin-top: 5px;
        font-size: 6.5px;
        color: #64748b;
        text-transform: uppercase;
        font-weight: bold;
        line-height: 1.2;
    }

    .opcional {
        margin-top: 5px;
        display: inline-block;
        background: #ffedd5;
        color: #c2410c;
        font-size: 6.5px;
        font-weight: bold;
        padding: 2px 4px;
        border-radius: 4px;
        text-transform: uppercase;
    }

    .seriacion {
        margin-top: 6px;
        padding-top: 5px;
        border-top: 1px dashed #cbd5e1;
    }

    .seriacion-label {
        display: block;
        font-size: 6.5px;
        font-weight: bold;
        color: #9333ea;
        text-transform: uppercase;
    }

    .seriacion-clave {
        font-size: 7px;
        font-weight: bold;
        color: #0f172a;
    }

    .empty {
        border: 1px dashed #e2e8f0;
        color: #cbd5e1;
        text-align: center;
        padding: 18px 4px;
        border-radius: 8px;
        font-size: 7px;
        font-weight: bold;
        text-transform: uppercase;
    }

    .footer {
        margin-top: 18px;
        border-top: 1px solid #e2e8f0;
        padding-top: 8px;
        font-size: 8px;
        color: #64748b;
        text-transform: uppercase;
        font-weight: bold;
    }

    .footer-right {
        float: right;
    }
</style>
</head>

<body>

<div class="header">
    <div class="tag">Sistema Académico CUFA</div>
    <h1>Malla <span class="accent">Curricular</span></h1>
    <div class="career"><?= htmlspecialchars($carrera['nombre']) ?></div>
    <div class="summary">
        Total de asignaturas: <?= count($materias) ?> |
        Grados visualizados: <?= $totalGrados ?> |
        Fecha de emisión: <?= date('d/m/Y') ?>
    </div>
</div>

<table class="malla-table">
    <tr>
        <?php for($g = 1; $g <= $totalGrados; $g++): ?>
            <td class="grado-col">

                <div class="grado-header">
                    Grado <?= $g ?>
                </div>

                <?php if(!empty($grados[$g])): ?>

                    <?php foreach($grados[$g] as $m): ?>

                        <?php
                            $bgColor = colorMateria($m['area_formacion'] ?? '', !empty($m['es_opcional']));
                            $borderColor = colorBordeMateria($m['area_formacion'] ?? '', !empty($m['es_opcional']));
                        ?>

                        <div class="materia" style="background-color: <?= $bgColor ?>; border-left-color: <?= $borderColor ?>;">

                            <div class="top">
                                <span class="clave"><?= htmlspecialchars($m['clave']) ?></span>
                                <span class="creditos"><?= (int)$m['creditos'] ?> CR</span>
                            </div>

                            <div class="nombre">
                                <?= htmlspecialchars($m['nombre_corto'] ?: $m['nombre']) ?>
                            </div>

                            <div class="meta">
                                <?= htmlspecialchars($m['tipo'] ?: 'Presencial') ?>
                                <?php if(!empty($m['area_formacion'])): ?>
                                    <br><?= htmlspecialchars($m['area_formacion']) ?>
                                <?php endif; ?>
                            </div>

                            <?php if(!empty($m['es_opcional'])): ?>
                                <span class="opcional">Opcional</span>
                            <?php endif; ?>

                            <?php if(!empty($m['seriacion_id'])): ?>
                                <div class="seriacion">
                                    <span class="seriacion-label">Requiere:</span>
                                    <span class="seriacion-clave">
                                        <?= htmlspecialchars($m['seriacion_clave'] ?: 'N/A') ?>
                                    </span>
                                </div>
                            <?php endif; ?>

                        </div>

                    <?php endforeach; ?>

                <?php else: ?>

                    <div class="empty">
                        Sin asignaturas
                    </div>

                <?php endif; ?>

            </td>
        <?php endfor; ?>
    </tr>
</table>

<div class="footer">
    <span>CUFA - Sistema Académico</span>
    <span class="footer-right">Documento generado automáticamente</span>
</div>

</body>
</html>

<?php
$html = ob_get_clean();

/* =========================
   DOMPDF
========================= */
$options = new Options();
$options->set('isRemoteEnabled', true);
$options->set('defaultFont', 'Helvetica');
$options->set('isHtml5ParserEnabled', true);
$options->set('isPhpEnabled', false);

$dompdf = new Dompdf($options);
$dompdf->loadHtml($html, 'UTF-8');
$dompdf->setPaper('A3', 'landscape');
$dompdf->render();

$filename = "malla_curricular_" . nombreArchivoSeguro($carrera['nombre']) . ".pdf";

$dompdf->stream($filename, [
    "Attachment" => true
]);

exit;