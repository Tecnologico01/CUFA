<?php
function cufaAutorizarRecurso(PDO $pdo,string $ruta): void {
    if(($_SESSION['rol_slug']??'')!=='docente')return;
    if(!preg_match('#modules/(docente|planeaciones|actividades|calificaciones)/#',$ruta))return;
    $asignacion=(int)($_POST['asignacion_id']??$_GET['asignacion_id']??$_POST['asignacion']??$_GET['asignacion']??0);
    $usuario=(int)($_SESSION['user_id']??0);
    if($asignacion>0){
        $s=$pdo->prepare('SELECT ad.id FROM asignaciones_docentes ad JOIN docentes d ON d.id=ad.docente_id WHERE ad.id=? AND d.usuario_id=?');$s->execute([$asignacion,$usuario]);
        if(!$s->fetchColumn()){http_response_code(403);exit('La asignación no pertenece al docente.');}
    }
    $id=(int)($_POST['id']??$_GET['id']??0);
    $tabla=basename($ruta)==='editar_calificacion.php'?'calificaciones_finales':(basename($ruta)==='editar_planeacion.php' && strpos($ruta,'/planeaciones/')!==false?'planeacion_semanal':null);
    if($tabla && $id>0){
        $s=$pdo->prepare('SELECT recurso.id FROM '.$tabla.' recurso JOIN asignaciones_docentes ad ON ad.id=recurso.asignacion_id JOIN docentes d ON d.id=ad.docente_id WHERE recurso.id=? AND d.usuario_id=?');$s->execute([$id,$usuario]);
        if(!$s->fetchColumn()){http_response_code(403);exit('El registro no pertenece al docente.');}
    }
}
