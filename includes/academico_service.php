<?php
require_once __DIR__.'/cufa_fase2.php';
function cufaReferencias(PDO $pdo,string $tabla,int $id): array {
 $columnas=['materias'=>['materia_id','seriacion_id'],'grupos'=>['grupo_id'],'periodos'=>['periodo_id'],'carreras'=>['carrera_id'],'mallas_curriculares'=>['malla_id'],'alumnos'=>['alumno_id'],'usuarios'=>['usuario_id','user_id','padre_id'],'docentes'=>['docente_id'],'subasignaturas'=>['subasignatura_id']];
 if(!isset($columnas[$tabla]))throw new RuntimeException('Catálogo no autorizado.');
 $s=$pdo->prepare('SELECT TABLE_NAME,COLUMN_NAME FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND COLUMN_NAME IN ('.implode(',',array_fill(0,count($columnas[$tabla]),'?')).')');$s->execute($columnas[$tabla]);$refs=[];
 foreach($s->fetchAll() as $c){if($c['TABLE_NAME']===$tabla)continue;$q=$pdo->prepare('SELECT COUNT(*) FROM `'.$c['TABLE_NAME'].'` WHERE `'.$c['COLUMN_NAME'].'`=?');$q->execute([$id]);if($q->fetchColumn())$refs[]=$c['TABLE_NAME'];}
 return array_unique($refs);
}
function cufaBorrarCatalogo(PDO $pdo,string $tabla,string $permiso): void {
 validarPermiso($permiso);$id=cufaId($_POST+$_GET,['id']);if(!$id)throw new RuntimeException('Registro inválido.');
 $refs=cufaReferencias($pdo,$tabla,$id);
 if($refs)throw new RuntimeException('No se puede eliminar: hay historial o relaciones en '.implode(', ',$refs).'. Se conservó el registro.');
 if($_SERVER['REQUEST_METHOD']!=='POST'){echo '<div class="bg-white rounded-3xl p-8"><h2>Confirmar eliminación del registro sin relaciones #'.(int)$id.'</h2><form method="POST" onsubmit="return confirm(\'¿Eliminar este registro sin relaciones?\');">'.cufaCampoToken().'<input type="hidden" name="id" value="'.(int)$id.'"><button class="bg-red-600 text-white p-3 rounded-xl">Confirmar eliminación</button></form></div>';return;}
 cufaValidarToken();$pdo->beginTransaction();try{$s=$pdo->prepare('SELECT id FROM `'.$tabla.'` WHERE id=? FOR UPDATE');$s->execute([$id]);if(!$s->fetchColumn())throw new RuntimeException('Registro no encontrado.');if(cufaReferencias($pdo,$tabla,$id))throw new RuntimeException('El registro tiene relaciones.');$pdo->prepare('DELETE FROM `'.$tabla.'` WHERE id=?')->execute([$id]);$pdo->commit();echo '<div class="p-6">Registro sin relaciones eliminado.</div>';}catch(Throwable $e){if($pdo->inTransaction())$pdo->rollBack();throw $e;}
}
function cufaBloquearCambioVinculado(PDO $pdo,string $tabla,int $id,array $valores): void {
 $s=$pdo->prepare('SELECT * FROM `'.$tabla.'` WHERE id=?');$s->execute([$id]);$actual=$s->fetch();if(!$actual)throw new RuntimeException('Registro no encontrado.');
 foreach($valores as $campo=>$nuevo)if((string)$actual[$campo] !== (string)$nuevo && cufaReferencias($pdo,$tabla,$id))throw new RuntimeException('No se puede cambiar '.$campo.' de un registro con historial académico. Crea un nuevo registro para conservar las referencias.');
}
