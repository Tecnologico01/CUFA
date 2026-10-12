<?php
require_once __DIR__.'/../../includes/db.php';require_once __DIR__.'/../../includes/cufa_fase2.php';
validarPermiso('ver_consulta_academica');
try{$f=cufaFiltros();$id=cufaId($_GET,['alumno_id','alumno','id']);$alumnos=cufaBuscarAlumnos($pdo,$f);}catch(Throwable $e){cufaError($e);return;}
?>
<h1 class="text-3xl font-bold mb-6">Consulta Académica</h1>
<?php cufaFiltrosHtml($pdo,$f,'consulta_academica'); ?>
<div class="bg-white rounded-3xl shadow p-6 mb-6"><p class="mb-3">Selecciona un alumno. La búsqueda muestra hasta 200 coincidencias; añade filtros si necesitas acotarla.</p>
<?php foreach($alumnos as $a): ?><a class="block p-3 border-b hover:bg-purple-50" href="<?= cufaEsc(cufaPanel('consulta_academica',array_merge($f,['alumno_id'=>$a['id']]))) ?>"><?= cufaEsc($a['nombre'].' · '.$a['numero_control'].' · '.$a['carrera']) ?></a><?php endforeach; ?>
<?php if(!$alumnos): ?><p>No se encontraron alumnos.</p><?php endif; ?></div>
<?php if($id): try{$exp=cufaExpediente($pdo,$id,$f,cufaEsPublico());$a=$exp['alumno']; ?>
<div class="bg-white p-6 rounded-3xl shadow mb-6"><h2 class="text-xl font-bold"><?= cufaEsc($a['nombre']) ?></h2><p>Matrícula <?= cufaEsc($a['numero_control']) ?> · <?= cufaEsc($a['carrera']) ?> · Grado <?= (int)$a['grado'] ?></p>
<?php foreach($exp['inscripciones'] as $g): ?><p>Grupo <?= cufaEsc($g['nombre']) ?> · Periodo <?= cufaEsc($g['periodo']) ?></p><?php endforeach; ?>
<p class="mt-4 font-bold">Promedio general: <?= cufaNumero($exp['promedio']) ?></p><p><?= $exp['autorizada']?'Evaluación autorizada':'Evaluación pendiente de completar o autorizar' ?></p>
<?php if(tienePermiso('ver_boletas')): ?><a class="inline-block bg-purple-600 text-white px-5 py-3 rounded-xl mt-4" href="<?= cufaEsc(cufaPanel('ver_boleta',array_merge($f,['id'=>$id]))) ?>">Vista previa de boleta</a><?php endif; ?></div>
<div class="overflow-x-auto bg-white rounded-3xl shadow"><table class="w-full"><thead class="bg-purple-200"><tr><th class="p-4">Materia / grupo / periodo</th><th>Calificaciones por unidad</th><th>Promedio</th><th>Estado académico</th></tr></thead><tbody>
<?php foreach($exp['materias'] as $m): ?><tr class="border-t"><td class="p-4"><?= cufaEsc($m['nombre']) ?><br><small><?= cufaEsc($m['grupo'].' / '.$m['periodo']) ?></small></td><td class="p-4"><?php foreach($m['unidades'] as $u): $n=$m['notas'][$u]??null; ?><p>Unidad <?= $u ?>: <?= cufaNumero($n['calificacion_final']??null) ?> <span class="text-xs bg-purple-50 rounded px-2"><?= cufaEsc($n['estado']??'sin captura') ?></span></p><?php endforeach; ?></td><td class="p-4"><?= cufaNumero($m['promedio']) ?><?= !$m['autorizada'] && $m['promedio']!==null?' (provisional)':'' ?></td><td class="p-4"><?= cufaEsc($m['estado']) ?></td></tr><?php endforeach; ?></tbody></table></div>
<?php if(!$exp['materias']): ?><p class="p-6">No hay materias inscritas para los filtros seleccionados. No se asigna promedio cero.</p><?php endif; ?>
<?php }catch(Throwable $e){cufaError($e);} endif; ?>
