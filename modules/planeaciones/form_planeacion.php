<?php
require_once __DIR__.'/../../includes/db.php';
require_once __DIR__.'/../../includes/permisos.php';
require_once __DIR__.'/../../includes/planeaciones_service.php';
$editar=basename($_GET['modulo']??'')==='editar_planeacion';
validarPermiso($editar?'editar_planeaciones':'crear_planeaciones');
$asignacion_id=(int)($_GET['asignacion_id']??0);$id=(int)($_GET['id']??0);
$s=$pdo->prepare('SELECT ad.* FROM asignaciones_docentes ad JOIN docentes d ON d.id=ad.docente_id WHERE ad.id=? AND d.usuario_id=?');
$s->execute([$asignacion_id,(int)($_SESSION['user_id']??0)]);$ad=$s->fetch();
if(!$ad){http_response_code(403);exit('Asignación no autorizada.');}
$s=$pdo->prepare('SELECT id,tema,parcial FROM temas_materia WHERE asignacion_id=? ORDER BY parcial,orden');$s->execute([$asignacion_id]);$temas=$s->fetchAll();
$plan=[];
if($editar){$s=$pdo->prepare('SELECT * FROM planeacion_semanal WHERE id=? AND asignacion_id=?');$s->execute([$id,$asignacion_id]);$plan=$s->fetch();if(!$plan)exit('Planeación no encontrada.');}
if($_SERVER['REQUEST_METHOD']==='POST'){
 try{
  cufaValidarToken();$pdo->beginTransaction();cufaEditarPlan($pdo,'planeacion_semanal',$editar?$id:0,$asignacion_id);$tema=(int)($_POST['tema_id']??0);$semana=(int)($_POST['semana']??0);
  if($semana<1 || !in_array($tema,array_map('intval',array_column($temas,'id')),true))throw new RuntimeException('Tema o semana inválidos.');
  if(!$editar){$dup=$pdo->prepare('SELECT id FROM planeacion_semanal WHERE asignacion_id=? AND tema_id=? AND semana=?');$dup->execute([$asignacion_id,$tema,$semana]);if($dup->fetchColumn())throw new RuntimeException('Planeación ya existente. Abre su edición.');}
  $valores=[$tema,$semana,trim($_POST['objetivo']??''),trim($_POST['descripcion_tema']??''),trim($_POST['actividad_nombre']??''),trim($_POST['actividad_descripcion']??'')];
  if($editar){$s=$pdo->prepare('UPDATE planeacion_semanal SET tema_id=?,semana=?,objetivo=?,descripcion_tema=?,actividad_nombre=?,actividad_descripcion=? WHERE id=? AND asignacion_id=?');$s->execute(array_merge($valores,[$id,$asignacion_id]));}
  else{$s=$pdo->prepare('INSERT INTO planeacion_semanal (tema_id,semana,objetivo,descripcion_tema,actividad_nombre,actividad_descripcion,asignacion_id) VALUES (?,?,?,?,?,?,?)');$s->execute(array_merge($valores,[$asignacion_id]));}
  if(!$editar)$id=(int)$pdo->lastInsertId();
  $pdo->prepare("UPDATE planeacion_semanal SET estado='pendiente' WHERE id=?")->execute([$id]);
  cufaRegistroRevision($pdo,'planeacion_semanal',$id,'pendiente','Guardada por el docente.',(int)$_SESSION['user_id']);$pdo->commit();
  echo '<script>window.location.href="?modulo=planeacion_semanal&asignacion_id='.$asignacion_id.'";</script>';return;
 }catch(Throwable $e){if($pdo->inTransaction())$pdo->rollBack();echo '<p class="p-6 text-red-700">'.htmlspecialchars($e->getMessage()).'</p>';}
}
?>
<div class="bg-white rounded-3xl p-8 shadow"><h1 class="text-2xl font-bold mb-6"><?= $editar?'Editar':'Crear' ?> planeación semanal</h1>
<form method="POST" class="space-y-6"><input type="hidden" name="cufa_csrf" value="<?= htmlspecialchars(cufaToken()) ?>">
<label class="block">Tema<select name="tema_id" required class="border p-3 rounded-xl w-full"><?php foreach($temas as $t): ?><option value="<?= (int)$t['id'] ?>" <?= (int)($plan['tema_id']??0)===(int)$t['id']?'selected':'' ?>>Unidad <?= (int)$t['parcial'] ?> · <?= htmlspecialchars($t['tema']) ?></option><?php endforeach; ?></select></label>
<label class="block">Semana<input name="semana" type="number" min="1" required class="border p-3 rounded-xl w-full" value="<?= (int)($plan['semana']??1) ?>"></label>
<?php foreach(['objetivo'=>'Objetivo','descripcion_tema'=>'Descripción del tema','actividad_nombre'=>'Nombre de actividad','actividad_descripcion'=>'Descripción de actividad'] as $campo=>$texto): ?><label class="block"><?= $texto ?><textarea name="<?= $campo ?>" class="border p-3 rounded-xl w-full"><?= htmlspecialchars($plan[$campo]??'') ?></textarea></label><?php endforeach; ?>
<button class="bg-purple-600 text-white px-6 py-3 rounded-xl">Guardar</button></form></div>
