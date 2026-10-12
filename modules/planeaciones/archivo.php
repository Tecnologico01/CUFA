<?php
require_once __DIR__.'/../../includes/db.php';require_once __DIR__.'/../../includes/planeaciones_service.php';validarPermiso('ver_planeaciones');
try{$p=cufaPlan($pdo,cufaOrigenPlan((string)($_GET['origen']??'')),cufaId($_GET,['id']));$campo=$_GET['campo']??'';if(!in_array($campo,['material','archivo'],true))throw new RuntimeException('Archivo inválido.');
$ruta=str_replace('\\','/',(string)($p[$campo]??''));$raiz=realpath(__DIR__.'/../..');
$ruta=preg_replace('#^/?sistema_academico/#','',$ruta);if($p['origen']==='planeaciones' && strpos($ruta,'/')===false)$ruta='uploads/planeaciones/'.$ruta;
$real=realpath($raiz.'/'.ltrim($ruta,'/'));
if(!$real || !is_file($real) || !(str_starts_with($real,$raiz.'/uploads/') || str_starts_with($real,$raiz.'/dashboards/uploads/')))throw new RuntimeException('Archivo adjunto no disponible. Se conserva su referencia.');
$ext=strtolower(pathinfo($real,PATHINFO_EXTENSION));if(!in_array($ext,['pdf','png','jpg','jpeg','doc','docx','ppt','pptx','xls','xlsx','zip'],true))throw new RuntimeException('Tipo de archivo no permitido.');
header('Content-Type: '.(new finfo(FILEINFO_MIME_TYPE))->file($real));header('X-Content-Type-Options: nosniff');header('Content-Disposition: '.($ext==='pdf'?'inline':'attachment').'; filename="'.basename($real).'"');readfile($real);
}catch(Throwable $e){http_response_code(404);cufaError($e);}
