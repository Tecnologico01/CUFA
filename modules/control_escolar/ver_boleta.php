<?php
require_once __DIR__.'/../../includes/db.php';require_once __DIR__.'/../../includes/reportes_cufa.php';validarPermiso('ver_boletas');
try{$f=cufaFiltros();$id=cufaId($_GET,['id','alumno','alumno_id']);$exp=cufaExpediente($pdo,$id,$f,true);}catch(Throwable $e){cufaError($e);return;}
$params=['id'=>$id,'grupo_id'=>$f['grupo_id'],'periodo_id'=>$f['periodo_id']];
?>
<div class="no-print flex flex-wrap gap-3 mb-6"><a href="<?= cufaEsc(cufaPanel('boletas',$f)) ?>" class="bg-slate-900 text-white p-3 rounded-xl">Volver</a><button type="button" onclick="window.print()" class="bg-purple-600 text-white p-3 rounded-xl">Imprimir vista</button><a target="_blank" href="<?= cufaEsc(cufaUrl('modules/control_escolar/pdf_boleta.php',$params)) ?>" class="bg-purple-600 text-white p-3 rounded-xl">Vista PDF tamaño carta</a><a href="<?= cufaEsc(cufaUrl('modules/control_escolar/pdf_boleta.php',array_merge($params,['descargar'=>1]))) ?>" class="bg-purple-600 text-white p-3 rounded-xl"><?= $exp['autorizada']?'Descargar boleta':'Descargar vista previa' ?></a></div>
<div class="bg-white rounded-3xl p-8 shadow document-cufa"><?= cufaBoletaHtml($exp) ?></div>
<style>@media print{nav,header,footer,.header-section,.no-print{display:none!important}main{padding:0!important}.document-cufa{box-shadow:none}}</style>
