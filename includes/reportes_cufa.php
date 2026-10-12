<?php
require_once __DIR__.'/cufa_fase2.php';
function cufaDocumentoCss(): string {
    return '<style>@page{size:letter;margin:28px 34px}.documento-cufa{font-family:DejaVu Sans,sans-serif;color:#243047;font-size:10px}.documento-cufa h1{font-size:19px;color:#4933a1}.documento-cufa h2{font-size:13px}.documento-cufa table{width:100%;border-collapse:collapse;margin:16px 0}.documento-cufa th{background:#eee8fc;color:#4933a1}.documento-cufa td,.documento-cufa th{border:1px solid #d7dce5;padding:8px;text-align:left}.documento-cufa tr{page-break-inside:avoid}.documento-cufa .institucion{border-bottom:2px solid #7054d8;padding-bottom:12px}.documento-cufa .logo{width:64px;float:left;margin-right:18px}.documento-cufa .subtitulo{color:#667085}.documento-cufa .pendiente{background:#fff4d4;padding:10px}.documento-cufa .firmas{margin-top:50px;page-break-inside:avoid}.documento-cufa .firma{display:inline-block;width:43%;border-top:1px solid #607086;padding-top:8px;margin-right:4%;text-align:center}.documento-cufa .nota{font-size:9px;color:#667085;margin-top:22px}.no-print{margin:12px 0}@media print{body *{visibility:hidden}.documento-cufa,.documento-cufa *{visibility:visible}.documento-cufa{position:absolute;top:0;left:0;width:100%;background:white}.no-print{display:none}body{background:white}}</style>';
}
function cufaEncabezado(string $titulo): string {
    $logo=__DIR__.'/../assets/images/lcufa_pdf.jpg';
    $src=is_file($logo)?'data:image/jpeg;base64,'.base64_encode(file_get_contents($logo)):'';
    return '<div class="institucion">'.($src?'<img class="logo" src="'.$src.'" alt="Logotipo CUFA">':'').'<h1>CUFA · Sistema Académico</h1><p>'.$titulo.'</p><div style="clear:both"></div></div>';
}
function cufaBoletaHtml(array $exp): string {
    $a=$exp['alumno'];$h=cufaDocumentoCss().'<div class="documento-cufa bg-white p-6">'.cufaEncabezado('BOLETA DE CALIFICACIONES');
    if(!$exp['autorizada'])$h.='<p class="pendiente">VISTA PREVIA · Emisión pendiente. Esta consulta no constituye una boleta autorizada: faltan calificaciones aprobadas.</p>';
    $h.='<h2>'.cufaEsc($a['nombre']).'</h2><p>Matrícula: '.cufaEsc($a['numero_control']).'<br>Carrera: '.cufaEsc($a['carrera']).'<br>Fecha de emisión: '.date('d/m/Y').'</p>';
    foreach($exp['inscripciones'] as $g)$h.='<p>Grupo: '.cufaEsc($g['nombre']).' · Periodo: '.cufaEsc($g['periodo']).'</p>';
    $h.='<table><thead><tr><th>Asignatura</th><th>Grupo / periodo</th><th>Calificaciones aprobadas por unidad</th><th>Promedio</th></tr></thead><tbody>';
    foreach($exp['materias'] as $m){$h.='<tr><td>'.cufaEsc($m['clave'].' · '.$m['nombre']).'</td><td>'.cufaEsc($m['grupo'].' / '.$m['periodo']).'</td><td>';
        foreach($m['unidades'] as $u)$h.='U'.$u.': '.cufaNumero($m['notas'][$u]['calificacion_final']??null).'<br>';
        $h.='</td><td>'.cufaNumero($m['promedio']).'</td></tr>';
    }
    if(!$exp['materias'])$h.='<tr><td colspan="4">No hay materias inscritas para este periodo o grupo.</td></tr>';
    $h.='</tbody></table><h2>Promedio general: '.cufaNumero($exp['promedio']).'</h2><div class="firmas"><div class="firma">Control Escolar · firma</div><div class="firma">Sello institucional</div></div><p class="nota">Solo contiene calificaciones aprobadas. La validez institucional está sujeta a autorización, firma y sello de CUFA. No incluye firmas o sellos simulados.</p>';
    return $h.'</div>';
}
function cufaActa(PDO $pdo,int $id): array {
    $s=$pdo->prepare('SELECT ac.*,m.nombre materia,g.nombre grupo,p.nombre periodo,c.nombre carrera FROM actas ac LEFT JOIN materias m ON m.id=ac.materia_id LEFT JOIN grupos g ON g.id=ac.grupo_id LEFT JOIN periodos p ON p.id=ac.periodo_id LEFT JOIN carreras c ON c.id=g.carrera_id WHERE ac.id=?');$s->execute([$id]);$a=$s->fetch();
    if(!$a){http_response_code(404);throw new RuntimeException('Acta no encontrada.');}
    $cab=$pdo->prepare('SELECT materia,grupo,periodo,carrera FROM cufa_actas_encabezado WHERE acta_id=?');$cab->execute([$id]);if($congelado=$cab->fetch())$a=array_merge($a,$congelado);
    $s=$pdo->prepare('SELECT * FROM cufa_actas_detalle WHERE acta_id=? ORDER BY alumno_nombre,unidad');$s->execute([$id]);$a['detalle']=$s->fetchAll();return $a;
}
function cufaActaHtml(array $a): string {
    $h=cufaDocumentoCss().'<div class="documento-cufa bg-white p-6">'.cufaEncabezado('ACTA DE CALIFICACIONES');
    $h.='<h2>'.cufaEsc($a['codigo']?:'ACTA-'.$a['id']).'</h2><p>Asignatura: '.cufaEsc($a['materia']??'Referencia histórica no disponible').'<br>Grupo: '.cufaEsc($a['grupo']??'Sin referencia').' · Periodo: '.cufaEsc($a['periodo']??'Sin referencia').'<br>Carrera: '.cufaEsc($a['carrera']??'Sin referencia').'<br>Generada: '.cufaEsc($a['fecha_generacion']?:$a['created_at']).' · Estado histórico: '.cufaEsc($a['estado']).'</p>';
    $h.='<table><thead><tr><th>Alumno / matrícula</th><th>Unidad</th><th>Actividades</th><th>Examen</th><th>Final congelado</th></tr></thead><tbody>';
    foreach($a['detalle'] as $n)$h.='<tr><td>'.cufaEsc($n['alumno_nombre']).'<br>'.cufaEsc($n['numero_control']).'</td><td>'.(int)$n['unidad'].'</td><td>'.cufaNumero($n['promedio_actividades']).'</td><td>'.cufaNumero($n['calificacion_examen']).'</td><td>'.cufaNumero($n['calificacion_final']).'</td></tr>';
    if(!$a['detalle'])$h.='<tr><td colspan="5">Acta histórica sin detalle congelado disponible. Se conservan sus datos originales; no se reconstruyen notas.</td></tr>';
    $h.='</tbody></table><div class="firmas"><div class="firma">Responsable académico · firma</div><div class="firma">Control Escolar · sello</div></div><p class="nota">Consulta del registro existente. Las notas provienen exclusivamente del detalle congelado, sin recalcularlo. La validez institucional requiere firma y sello.</p>';
    return $h.'</div>';
}
function cufaPdf(string $html,string $nombre,bool $descargar): void {
    require_once __DIR__.'/../vendor/autoload.php';
    $pdf=new \Dompdf\Dompdf(['isRemoteEnabled'=>false,'chroot'=>realpath(__DIR__.'/..')]);
    $pdf->loadHtml('<!doctype html><html lang="es"><meta charset="utf-8"><body>'.$html.'</body></html>');$pdf->setPaper('letter');$pdf->render();
    $canvas=$pdf->getCanvas();$canvas->page_text(34,760,'Página {PAGE_NUM} de {PAGE_COUNT}',null,8,[0.4,0.4,0.4]);
    while(ob_get_level())ob_end_clean();$pdf->stream($nombre,['Attachment'=>$descargar]);
}
