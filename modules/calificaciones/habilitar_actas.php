<?php
require_once __DIR__.'/../../includes/db.php';require_once __DIR__.'/../../includes/cufa_fase2.php';validarPermiso('habilitar_actas');
$mensaje='';
if($_SERVER['REQUEST_METHOD']==='POST')try{
    cufaValidarToken();$id=cufaId($_POST,['periodo_id']);$accion=$_POST['accion']??'';
    if(!in_array($accion,['habilitar','cerrar_periodo'],true))throw new RuntimeException('Acción inválida.');
    $s=$pdo->prepare('SELECT id FROM periodos WHERE id=?');$s->execute([$id]);if(!$s->fetchColumn())throw new RuntimeException('Periodo no encontrado.');
    $s=$pdo->prepare('INSERT INTO cufa_actas_control(periodo_id,generacion_habilitada,actualizado_por) VALUES(?,?,?) ON DUPLICATE KEY UPDATE generacion_habilitada=VALUES(generacion_habilitada),actualizado_por=VALUES(actualizado_por)');$s->execute([$id,$accion==='habilitar'?1:0,(int)$_SESSION['user_id']]);
    $mensaje=$accion==='habilitar'?'Generación habilitada para el periodo. Las actas existentes no cambian.':'Periodo de generación cerrado. Puedes seguir consultando y descargando las actas existentes.';
}catch(Throwable $e){cufaError($e);}
$periodos=$pdo->query('SELECT p.*,COALESCE(c.generacion_habilitada,0) habilitada FROM periodos p LEFT JOIN cufa_actas_control c ON c.periodo_id=p.id ORDER BY p.id DESC')->fetchAll();
?>
<h1 class="text-3xl font-bold mb-6">Habilitar actas</h1>
<div class="bg-white p-6 rounded-3xl shadow mb-6"><p><strong>Habilitar</strong> permite generar nuevas actas del periodo, siempre con notas completas y aprobadas. <strong>Cerrar periodo de actas</strong> detiene nuevas generaciones. Ninguna acción modifica actas históricas o reabre notas aprobadas.</p><p class="mt-3"><a class="text-purple-700 underline" href="<?= cufaEsc(cufaPanel('generar_actas')) ?>">Generar actas</a> · <a class="text-purple-700 underline" href="<?= cufaEsc(cufaPanel('actas')) ?>">Consultar Actas oficiales</a></p></div>
<?php if($mensaje): ?><p role="status" class="p-5 bg-emerald-50 rounded-2xl mb-6"><?= cufaEsc($mensaje) ?></p><?php endif; ?>
<div class="bg-white rounded-3xl shadow overflow-x-auto"><table class="w-full"><tr class="bg-purple-200"><th class="p-4">Periodo</th><th>Generación</th><th>Acción</th></tr><?php foreach($periodos as $p): ?><tr class="border-t"><td class="p-4"><?= cufaEsc($p['nombre']) ?></td><td><?= $p['habilitada']?'Habilitada':'Cerrada' ?></td><td><form method="POST" onsubmit="return confirm('¿Confirmas cambiar la habilitación de generación? Las actas históricas permanecerán intactas.');"><input type="hidden" name="cufa_csrf" value="<?= cufaEsc(cufaToken()) ?>"><input type="hidden" name="periodo_id" value="<?= (int)$p['id'] ?>"><button name="accion" value="<?= $p['habilitada']?'cerrar_periodo':'habilitar' ?>" class="bg-purple-600 text-white px-4 py-3 rounded-xl"><?= $p['habilitada']?'Cerrar periodo de actas':'Habilitar generación' ?></button></form></td></tr><?php endforeach; ?></table></div>
