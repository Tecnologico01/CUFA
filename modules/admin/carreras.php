<?php
require_once __DIR__.'/../../includes/db.php';require_once __DIR__.'/../../includes/academico_service.php';validarPermiso('ver_carreras');
try{
 if($_SERVER['REQUEST_METHOD']==='POST'){
  cufaValidarToken();$id=cufaId($_POST,['id']);validarPermiso($id?'editar_carreras':'crear_carreras');$nombre=trim($_POST['nombre']??'');$descripcion=trim($_POST['descripcion']??'');if($nombre===''||mb_strlen($nombre)>100)throw new RuntimeException('El nombre es obligatorio y admite hasta 100 caracteres.');
  if($id){cufaBloquearCambioVinculado($pdo,'carreras',$id,['nombre'=>$nombre]);$pdo->prepare('UPDATE carreras SET nombre=?,descripcion=? WHERE id=?')->execute([$nombre,$descripcion,$id]);}
  else{$s=$pdo->prepare('SELECT id FROM carreras WHERE nombre=?');$s->execute([$nombre]);if($s->fetchColumn())throw new RuntimeException('La carrera ya existe.');$pdo->prepare('INSERT INTO carreras(nombre,descripcion) VALUES(?,?)')->execute([$nombre,$descripcion]);}
  echo '<div class="bg-green-50 p-4 rounded-xl">Carrera guardada.</div>';
 }
 $editar=cufaId($_GET,['id']);$actual=[];if($editar){$s=$pdo->prepare('SELECT * FROM carreras WHERE id=?');$s->execute([$editar]);$actual=$s->fetch();if(!$actual)throw new RuntimeException('Carrera no encontrada.');}
 $carreras=$pdo->query('SELECT * FROM carreras ORDER BY nombre')->fetchAll();
}catch(Throwable $e){cufaError($e);$carreras=$pdo->query('SELECT * FROM carreras ORDER BY nombre')->fetchAll();$actual=[];$editar=0;}
?>
<div class="bg-white rounded-3xl p-8 shadow"><h1 class="text-3xl font-bold mb-6">Carreras</h1><p class="mb-5">Las carreras con historial conservan su nombre. Para un nuevo plan institucional, crea otra carrera.</p>
<?php if(tienePermiso($editar?'editar_carreras':'crear_carreras')): ?><form method="POST" class="space-y-4 mb-6"><?= cufaCampoToken() ?><input type="hidden" name="id" value="<?= (int)$editar ?>"><label>Nombre<input required maxlength="100" name="nombre" class="border rounded-xl p-3 w-full" value="<?= cufaEsc($actual['nombre']??'') ?>"></label><label>Descripción<textarea name="descripcion" class="border rounded-xl p-3 w-full"><?= cufaEsc($actual['descripcion']??'') ?></textarea></label><button class="bg-purple-600 text-white px-6 py-3 rounded-xl">Guardar carrera</button></form><?php endif; ?>
<table class="w-full"><tr class="bg-purple-200"><th>Nombre</th><th>Descripción</th><th>Acciones</th></tr><?php foreach($carreras as $c): ?><tr class="border-t"><td class="p-4"><?= cufaEsc($c['nombre']) ?></td><td><?= cufaEsc($c['descripcion']??'') ?></td><td><?php if(tienePermiso('editar_carreras')): ?><a href="<?= cufaEsc(cufaPanel('carreras',['id'=>$c['id']])) ?>">Editar</a><?php endif; ?> <?php if(tienePermiso('eliminar_carreras')): ?><a href="<?= cufaEsc(cufaPanel('eliminar_carrera',['id'=>$c['id']])) ?>">Eliminar sin historial</a><?php endif; ?></td></tr><?php endforeach; ?></table></div>
