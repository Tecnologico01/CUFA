<?php
require_once __DIR__.'/../../includes/db.php';require_once __DIR__.'/../../includes/reportes_cufa.php';validarPermiso('ver_boletas');
try{$f=cufaFiltros();$id=cufaId($_GET,['id','alumno','alumno_id']);$exp=cufaExpediente($pdo,$id,$f,true);cufaPdf(cufaBoletaHtml($exp),'boleta-'.$id.'.pdf',cufaId($_GET,['descargar'])===1);}catch(Throwable $e){if(http_response_code()<400)http_response_code(422);cufaError($e);}
