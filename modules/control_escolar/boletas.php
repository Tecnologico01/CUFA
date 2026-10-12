<?php
require_once __DIR__.'/../../includes/db.php';require_once __DIR__.'/../../includes/cufa_fase2.php';validarPermiso('ver_boletas');
try{$f=cufaFiltros();$alumnos=cufaBuscarAlumnos($pdo,$f);}catch(Throwable $e){cufaError($e);return;}
?>
<h1 class="text-3xl font-bold mb-6">Boletas estudiantiles</h1>
<?php cufaFiltrosHtml($pdo,$f,'boletas'); ?>
<div class="overflow-x-auto bg-white rounded-3xl shadow"><table class="w-full"><tr class="bg-purple-200"><th class="p-4">Alumno</th><th>Matrícula</th><th>Carrera</th><th>Boleta</th></tr>
<?php foreach($alumnos as $a): ?><tr class="border-t"><td class="p-4"><?= cufaEsc($a['nombre']) ?></td><td><?= cufaEsc($a['numero_control']) ?></td><td><?= cufaEsc($a['carrera']) ?></td><td><a class="bg-purple-600 text-white px-4 py-2 rounded-xl" href="<?= cufaEsc(cufaPanel('ver_boleta',array_merge($f,['id'=>$a['id']]))) ?>">Seleccionar / vista previa</a></td></tr><?php endforeach; ?></table></div>
<?php if(!$alumnos): ?><p class="p-6">No se encontraron alumnos con estos filtros.</p><?php endif; ?>
