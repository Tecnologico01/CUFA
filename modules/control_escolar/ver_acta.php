<?php
require_once __DIR__.'/../../includes/db.php';require_once __DIR__.'/../../includes/reportes_cufa.php';validarPermiso('ver_actas');
try{$id=cufaId($_GET,['id']);$a=cufaActa($pdo,$id);}catch(Throwable $e){cufaError($e);return;}
?>
<div class="no-print flex flex-wrap gap-3 mb-6"><a href="<?= cufaEsc(cufaPanel('actas')) ?>" class="bg-slate-900 text-white p-3 rounded-xl">Actas oficiales</a><button onclick="window.print()" type="button" class="bg-purple-600 text-white p-3 rounded-xl">Imprimir</button><a target="_blank" href="<?= cufaEsc(cufaUrl('modules/control_escolar/pdf_acta.php',['id'=>$id])) ?>" class="bg-purple-600 text-white p-3 rounded-xl">Abrir PDF</a><a href="<?= cufaEsc(cufaUrl('modules/control_escolar/pdf_acta.php',['id'=>$id,'descargar'=>1])) ?>" class="bg-purple-600 text-white p-3 rounded-xl">Descargar PDF</a></div>
<div class="bg-white rounded-3xl p-8 shadow document-cufa"><?= cufaActaHtml($a) ?></div>
<style>@media print{nav,header,footer,.header-section,.no-print{display:none!important}main{padding:0!important}.document-cufa{box-shadow:none}}</style>
