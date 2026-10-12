<?php
require_once __DIR__.'/../../includes/db.php';
require_once __DIR__.'/../../includes/planeaciones_service.php';
validarPermiso('guardar_planeaciones');
try {
 if($_SERVER['REQUEST_METHOD']!=='POST')throw new RuntimeException('Utiliza el formulario para guardar.');
 cufaValidarToken();$asig=cufaId($_POST,['asignacion','asignacion_id']);$id=cufaId($_POST,['id']);$semana=cufaId($_POST,['semana']);$tema=cufaId($_POST,['tema_id']);
 if(!$asig || !$semana || !$tema)throw new RuntimeException('Selecciona una asignación, semana y tema válidos.');
 $inicio=cufaFecha($_POST['fecha_apertura']??'');$fin=cufaFecha($_POST['fecha_cierre']??'');if($inicio && $fin && $fin<$inicio)throw new RuntimeException('El cierre no puede ser anterior a la apertura.');
 $pdo->beginTransaction();cufaEditarPlan($pdo,'planeacion_semanal',$id,$asig);
 $s=$pdo->prepare('SELECT id FROM temas_materia WHERE id=? AND asignacion_id=?');$s->execute([$tema,$asig]);if(!$s->fetchColumn())throw new RuntimeException('El tema no pertenece a la asignación.');
 if(!$id){$duplicada=$pdo->prepare('SELECT id FROM planeacion_semanal WHERE asignacion_id=? AND tema_id=? AND semana=?');$duplicada->execute([$asig,$tema,$semana]);if($duplicada->fetchColumn())throw new RuntimeException('La semana y tema ya tienen planeación. Abre el registro existente para corregirlo.');}
 $anterior=[];if($id){$s=$pdo->prepare('SELECT * FROM planeacion_semanal WHERE id=? AND asignacion_id=? FOR UPDATE');$s->execute([$id,$asig]);$anterior=$s->fetch();if(!$anterior)throw new RuntimeException('Planeación no encontrada.');}
 $rubrica=$anterior['rubrica_archivo']??null;
 if(isset($_FILES['rubrica']) && $_FILES['rubrica']['error']!==UPLOAD_ERR_NO_FILE){
  $f=$_FILES['rubrica'];if($f['error']!==UPLOAD_ERR_OK || $f['size']>10*1024*1024 || (new finfo(FILEINFO_MIME_TYPE))->file($f['tmp_name'])!=='application/pdf')throw new RuntimeException('La rúbrica debe ser PDF y menor de 10 MB.');
  $dir=__DIR__.'/../../uploads/rubricas/';if(!is_dir($dir))mkdir($dir,0755,true);$nombre=bin2hex(random_bytes(16)).'.pdf';if(!move_uploaded_file($f['tmp_name'],$dir.$nombre))throw new RuntimeException('No se pudo guardar el archivo.');$rubrica='uploads/rubricas/'.$nombre;
 }
 $v=[$tema,$semana,trim($_POST['objetivo']??''),trim($_POST['descripcion_tema']??''),trim($_POST['material_url']??''),trim($_POST['actividad_nombre']??''),trim($_POST['actividad_descripcion']??''),$inicio,$fin,$rubrica];
 if($id){$s=$pdo->prepare("UPDATE planeacion_semanal SET tema_id=?,semana=?,objetivo=?,descripcion_tema=?,material_url=?,actividad_nombre=?,actividad_descripcion=?,fecha_apertura=?,fecha_cierre=?,rubrica_archivo=?,estado='pendiente' WHERE id=? AND asignacion_id=?");$s->execute(array_merge($v,[$id,$asig]));}
 else{$s=$pdo->prepare('INSERT INTO planeacion_semanal(tema_id,semana,objetivo,descripcion_tema,material_url,actividad_nombre,actividad_descripcion,fecha_apertura,fecha_cierre,rubrica_archivo,asignacion_id) VALUES(?,?,?,?,?,?,?,?,?,?,?)');$s->execute(array_merge($v,[$asig]));$id=(int)$pdo->lastInsertId();}
 cufaRegistroRevision($pdo,'planeacion_semanal',$id,'pendiente','Guardada por el docente.',(int)$_SESSION['user_id']);$pdo->commit();header('Location: '.cufaPanel('detalle_planeacion',['origen'=>'planeacion_semanal','id'=>$id]));exit;
}catch(Throwable $e){if($pdo->inTransaction())$pdo->rollBack();http_response_code(422);cufaError($e);}
