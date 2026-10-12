<?php
require_once __DIR__.'/../../includes/db.php';require_once __DIR__.'/../../includes/reportes_cufa.php';validarPermiso('ver_actas');
try{$a=cufaActa($pdo,cufaId($_GET,['id']));cufaPdf(cufaActaHtml($a),'acta-'.$a['id'].'.pdf',cufaId($_GET,['descargar'])===1);}catch(Throwable $e){if(http_response_code()<400)http_response_code(422);cufaError($e);}
