<?php
require_once __DIR__.'/../../includes/db.php';require_once __DIR__.'/../../includes/academico_service.php';
try { cufaBorrarCatalogo($pdo,'subasignaturas','eliminar_subasignaturas'); } catch(Throwable $e){http_response_code(422);cufaError($e);}
