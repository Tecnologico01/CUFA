<?php
require_once __DIR__.'/../../includes/db.php';require_once __DIR__.'/../../includes/academico_service.php';
try { cufaBorrarCatalogo($pdo,'mallas_curriculares','eliminar_mallas'); } catch(Throwable $e){http_response_code(422);cufaError($e);}
