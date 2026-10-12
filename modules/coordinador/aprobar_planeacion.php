<?php
require_once __DIR__.'/../../includes/db.php';require_once __DIR__.'/../../includes/cufa_fase2.php';validarPermiso('revisar_planeaciones');
// El acceso histórico GET abre detalle; no cambia estados.
header('Location: '.cufaPanel('detalle_planeacion',['origen'=>'planeaciones','id'=>cufaId($_GET,['id'])]));exit;
