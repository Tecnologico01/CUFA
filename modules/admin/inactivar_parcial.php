<?php
require_once __DIR__.'/../../includes/db.php';require_once __DIR__.'/../../includes/academico_service.php';validarPermiso('editar_parciales');
try{$id=cufaId($_POST+$_GET,['id']);if(!$id)throw new RuntimeException('Parcial inválido.');
if($_SERVER['REQUEST_METHOD']!=='POST'){echo '<div class="bg-white p-8 rounded-3xl"><h2>Confirmar cambio de habilitación del parcial</h2><p>Este cambio no modifica notas aprobadas ni actas históricas.</p><form method="POST">'.cufaCampoToken().'<input type="hidden" name="id" value="'.$id.'"><button class="bg-purple-600 text-white p-3 rounded-xl">Confirmar</button></form></div>';return;}
cufaValidarToken();$pdo->prepare('UPDATE parciales SET activo=? WHERE id=?')->execute([0,$id]);header('Location: '.cufaPanel('parciales_anteriores'));exit;}catch(Throwable $e){http_response_code(422);cufaError($e);}
