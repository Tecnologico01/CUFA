<?php
require_once __DIR__.'/../../includes/db.php';require_once __DIR__.'/../../includes/planeaciones_service.php';validarPermiso('revisar_planeaciones');
if($_SERVER['REQUEST_METHOD']!=='POST'){http_response_code(405);exit('Usa el formulario de revisión.');}
try{cufaValidarToken();cufaRevisarPlan($pdo,cufaOrigenPlan((string)($_POST['origen']??'')),cufaId($_POST,['id','registro_id']),(string)($_POST['accion']??''),(string)($_POST['observaciones']??''),(int)$_SESSION['user_id']);header('Location: '.cufaPanel('revisar_planeaciones'));}catch(Throwable $e){cufaError($e);}
