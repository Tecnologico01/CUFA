<?php
require_once __DIR__.'/cufa_fase2.php';
function cufaOrigenPlan(string $o): string {
    if(!in_array($o,['planeaciones','planeacion_semanal','planeacion_docente','temas_materia'],true))throw new RuntimeException('Origen de planeación inválido.');return $o;
}
function cufaPlanes(PDO $pdo,array $f=[]): array {
    $base="ad.id asignacion_id,ad.docente_id,ad.periodo_id,ad.grupo_id,g.carrera_id,m.nombre materia,g.nombre grupo,p.nombre periodo,CONCAT_WS(' ',u.nombres,u.apellido_paterno,u.apellido_materno) docente";
    $joins=' JOIN materias m ON m.id=ad.materia_id JOIN grupos g ON g.id=ad.grupo_id JOIN periodos p ON p.id=ad.periodo_id JOIN docentes d ON d.id=ad.docente_id JOIN usuarios u ON u.id=d.usuario_id';
    $queries=[
      'planeacion_semanal'=>"SELECT ps.id,$base,ps.tema_id,ps.semana,tm.tema,tm.parcial,ps.objetivo,ps.descripcion_tema descripcion,ps.actividad_nombre,ps.actividad_descripcion actividades,ps.material_url material,ps.rubrica_archivo archivo,ps.estado nativo FROM planeacion_semanal ps JOIN asignaciones_docentes ad ON ad.id=ps.asignacion_id $joins LEFT JOIN temas_materia tm ON tm.id=ps.tema_id",
      'planeacion_docente'=>"SELECT pd.id,$base,pd.tema_id,tm.orden semana,tm.tema,tm.parcial,tm.objetivo_unidad objetivo,pd.subtemas descripcion,'' actividad_nombre,CONCAT_WS(' / ',pd.actividades_aprendizaje,pd.actividades_ensenanza) actividades,tm.video_referencia material,tm.presentacion_infografia archivo,tm.estado nativo FROM planeacion_docente pd JOIN temas_materia tm ON tm.id=pd.tema_id JOIN asignaciones_docentes ad ON ad.id=tm.asignacion_id $joins",
      'temas_materia'=>"SELECT tm.id,$base,tm.id tema_id,tm.orden semana,tm.tema,tm.parcial,tm.objetivo_unidad objetivo,tm.subtemas descripcion,'' actividad_nombre,tm.estrategia_didactica actividades,tm.video_referencia material,tm.presentacion_infografia archivo,tm.estado nativo FROM temas_materia tm JOIN asignaciones_docentes ad ON ad.id=tm.asignacion_id $joins",
      'planeaciones'=>"SELECT pl.id,ad.id asignacion_id,pl.docente_id,pl.periodo_id,pl.grupo_id,g.carrera_id,m.nombre materia,g.nombre grupo,p.nombre periodo,CONCAT_WS(' ',u.nombres,u.apellido_paterno,u.apellido_materno) docente,pl.tema_id,pl.semana,tm.tema,tm.parcial,pl.objetivo,pl.descripcion,'' actividad_nombre,pl.actividad actividades,'' material,pl.archivo,pl.estado nativo FROM planeaciones pl JOIN materias m ON m.id=pl.materia_id JOIN grupos g ON g.id=pl.grupo_id JOIN periodos p ON p.id=pl.periodo_id JOIN docentes d ON d.id=pl.docente_id JOIN usuarios u ON u.id=d.usuario_id LEFT JOIN asignaciones_docentes ad ON ad.docente_id=pl.docente_id AND ad.materia_id=pl.materia_id AND ad.grupo_id=pl.grupo_id AND ad.periodo_id=pl.periodo_id LEFT JOIN temas_materia tm ON tm.id=pl.tema_id"
    ];
    $resultado=[];
    foreach($queries as $origen=>$sql){
        $where=[];$args=[];
        $cols=['asignacion_id'=>'ad.id','docente_id'=>$origen==='planeaciones'?'pl.docente_id':'ad.docente_id','periodo_id'=>$origen==='planeaciones'?'pl.periodo_id':'ad.periodo_id','grupo_id'=>$origen==='planeaciones'?'pl.grupo_id':'ad.grupo_id','carrera_id'=>'g.carrera_id'];
        foreach($cols as $k=>$col)if(!empty($f[$k])){$where[]=$col.'=?';$args[]=(int)$f[$k];}
        if(($_SESSION['rol_slug']??'')==='docente'){$where[]='d.usuario_id=?';$args[]=(int)$_SESSION['user_id'];}
        $s=$pdo->prepare($sql.($where?' WHERE '.implode(' AND ',$where):''));$s->execute($args);
        foreach($s->fetchAll() as $row){
            $row['origen']=$origen;$revision=cufaRevisionPlan($pdo,$origen,(int)$row['id'],$row['nativo']);$row=array_merge($row,$revision);
            if(!empty($f['estado']) && $row['estado']!==$f['estado'])continue;
            if(!empty($f['q']) && mb_stripos($row['docente'].' '.$row['materia'].' '.$row['tema'],$f['q'])===false)continue;
            $resultado[$origen.':'.$row['id']]=$row;
        }
    }
    return array_values($resultado);
}
function cufaRevisionPlan(PDO $pdo,string $o,int $id,string $nativo='pendiente'): array {
    $s=$pdo->prepare('SELECT estado,observaciones,fecha_revision,revisado_por FROM cufa_planeaciones_revision WHERE origen=? AND registro_id=?');$s->execute([cufaOrigenPlan($o),$id]);$r=$s->fetch();
    return $r?:['estado'=>$nativo==='enviado'?'revision':($nativo==='borrador'?'pendiente':$nativo),'observaciones'=>'','fecha_revision'=>null,'revisado_por'=>null];
}
function cufaPlan(PDO $pdo,string $o,int $id): array {
    cufaOrigenPlan($o);foreach(cufaPlanes($pdo) as $p)if($p['origen']===$o && (int)$p['id']===$id)return $p;
    http_response_code(404);throw new RuntimeException('Planeación no encontrada o no autorizada.');
}
function cufaRegistroRevision(PDO $pdo,string $o,int $id,string $estado,string $obs,int $usuario): void {
    $s=$pdo->prepare('INSERT INTO cufa_planeaciones_revision(origen,registro_id,estado,observaciones,revisado_por,fecha_revision) VALUES(?,?,?,?,?,NOW()) ON DUPLICATE KEY UPDATE estado=VALUES(estado),observaciones=VALUES(observaciones),revisado_por=VALUES(revisado_por),fecha_revision=NOW()');$s->execute([$o,$id,$estado,$obs,$usuario]);
    $s=$pdo->prepare('INSERT INTO cufa_planeaciones_historial(origen,registro_id,accion,observaciones,usuario_id) VALUES(?,?,?,?,?)');$s->execute([$o,$id,$estado,$obs,$usuario]);
}
function cufaEditarPlan(PDO $pdo,string $o,int $id,int $asignacion): void {
    $s=$pdo->prepare('SELECT ad.id FROM asignaciones_docentes ad JOIN docentes d ON d.id=ad.docente_id WHERE ad.id=? AND d.usuario_id=? FOR UPDATE');$s->execute([$asignacion,(int)($_SESSION['user_id']??0)]);
    if(!$s->fetchColumn())throw new RuntimeException('Asignación no autorizada.');
    if($id){$p=cufaPlan($pdo,$o,$id);if((int)$p['asignacion_id']!==$asignacion)throw new RuntimeException('Planeación de otra asignación.');if($p['estado']==='aprobado')throw new RuntimeException('Planeación aprobada. Debe ser devuelta por revisión antes de editarla.');}
}
function cufaRevisarPlan(PDO $pdo,string $o,int $id,string $accion,string $obs,int $usuario): void {
    cufaOrigenPlan($o);if(!in_array($accion,['aprobado','rechazado','devuelto'],true))throw new RuntimeException('Acción inválida.');
    if($accion!=='aprobado' && trim($obs)==='')throw new RuntimeException('Escribe observaciones al rechazar o devolver.');
    $pdo->beginTransaction();try{
        $p=cufaPlan($pdo,$o,$id);
        if($p['asignacion_id']){$s=$pdo->prepare('SELECT id FROM asignaciones_docentes WHERE id=? FOR UPDATE');$s->execute([$p['asignacion_id']]);}
        $s=$pdo->prepare('SELECT id FROM '.$o.' WHERE id=? FOR UPDATE');$s->execute([$id]);if(!$s->fetchColumn())throw new RuntimeException('Registro no encontrado.');
        cufaRegistroRevision($pdo,$o,$id,$accion,trim($obs),$usuario);
        if($o!=='planeacion_docente'){$s=$pdo->prepare('UPDATE '.$o.' SET estado=? WHERE id=?');$s->execute([$accion==='devuelto'?'rechazado':$accion,$id]);}
        if($p['asignacion_id']){
            $todas=cufaPlanes($pdo,['asignacion_id'=>(int)$p['asignacion_id']]);$estados=array_column($todas,'estado');
            $general=$estados && !array_diff($estados,['aprobado'])?'aprobado':(array_intersect($estados,['rechazado','devuelto'])?'rechazado':'revision');
            $s=$pdo->prepare('UPDATE asignaciones_docentes SET estado_planeacion=?,observaciones_planeacion=? WHERE id=?');$s->execute([$general,trim($obs),$p['asignacion_id']]);
        }
        $pdo->commit();
    }catch(Throwable $e){if($pdo->inTransaction())$pdo->rollBack();throw $e;}
}
function cufaArchivoPlan(array $p,string $campo): string {
    $v=(string)($p[$campo]??'');if($v==='')return '';
    if(preg_match('#^https?://#i',$v) && filter_var($v,FILTER_VALIDATE_URL))return $v;
    return cufaUrl('modules/planeaciones/archivo.php',['origen'=>$p['origen'],'id'=>$p['id'],'campo'=>$campo]);
}
