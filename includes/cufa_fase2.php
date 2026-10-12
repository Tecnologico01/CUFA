<?php
require_once __DIR__.'/permisos.php';
require_once __DIR__.'/calificaciones_service.php';
function cufaEsc($v): string { return htmlspecialchars((string)($v ?? ''),ENT_QUOTES,'UTF-8'); }
function cufaId(array $entrada,array $nombres,int $default=0): int {
    foreach($nombres as $nombre) if(isset($entrada[$nombre]) && $entrada[$nombre]!=='') {
        if(!is_scalar($entrada[$nombre]) || !ctype_digit((string)$entrada[$nombre])) throw new RuntimeException('Parámetro inválido: '.$nombre);
        return (int)$entrada[$nombre];
    }
    return $default;
}
function cufaUrl(string $path,array $params=[]): string { return rtrim(BASE_URL,'/').'/'.ltrim($path,'/').($params?'?'.http_build_query($params):''); }
function cufaPanel(string $modulo,array $params=[]): string { return cufaUrl('dashboards/dashboard.php',array_merge(['modulo'=>$modulo],$params)); }
function cufaFecha($v): ?string {
    if($v===null || $v==='') return null;
    if(!is_string($v))throw new RuntimeException('Fecha inválida.');
    $d=DateTimeImmutable::createFromFormat('!Y-m-d',$v);
    if(!$d || $d->format('Y-m-d')!==$v)throw new RuntimeException('Fecha inválida.');
    return $v;
}
function cufaEsPublico(): bool { return in_array($_SESSION['rol_slug']??'',['alumno','estudiante','padre'],true); }
function cufaAlumno(PDO $pdo,int $id): array {
    $s=$pdo->prepare("SELECT a.*,CONCAT_WS(' ',u.nombres,u.apellido_paterno,u.apellido_materno) nombre,c.nombre carrera FROM alumnos a JOIN usuarios u ON u.id=a.usuario_id LEFT JOIN carreras c ON c.id=a.carrera_id WHERE a.id=?");
    $s->execute([$id]);$a=$s->fetch();
    if(!$a){http_response_code(404);throw new RuntimeException('Alumno no encontrado.');}
    $rol=$_SESSION['rol_slug']??'';$uid=(int)($_SESSION['user_id']??0);
    if(in_array($rol,['alumno','estudiante'],true) && (int)$a['usuario_id']!==$uid){http_response_code(403);throw new RuntimeException('Alumno no autorizado.');}
    if($rol==='padre'){
        $s=$pdo->prepare('SELECT id FROM padres_familia WHERE usuario_id=? AND alumno_id=?');$s->execute([$uid,$id]);
        if(!$s->fetchColumn()){http_response_code(403);throw new RuntimeException('Alumno no autorizado.');}
    }
    return $a;
}
function cufaFiltros(): array {
    return ['q'=>is_string($_GET['q']??null)?trim($_GET['q']):'', 'carrera_id'=>cufaId($_GET,['carrera_id','carrera']), 'grupo_id'=>cufaId($_GET,['grupo_id','grupo']), 'periodo_id'=>cufaId($_GET,['periodo_id','periodo'])];
}
function cufaBuscarAlumnos(PDO $pdo,array $f): array {
    $where=[];$args=[];
    if($f['q']!==''){$where[]="(CONCAT_WS(' ',u.nombres,u.apellido_paterno,u.apellido_materno) LIKE ? OR a.numero_control LIKE ?)";$args[]='%'.$f['q'].'%';$args[]='%'.$f['q'].'%';}
    if($f['carrera_id']){$where[]='a.carrera_id=?';$args[]=$f['carrera_id'];}
    if($f['grupo_id'] || $f['periodo_id']){
        $sub='ga.alumno_id=a.id';
        if($f['grupo_id']){$sub.=' AND ga.grupo_id=?';$args[]=$f['grupo_id'];}
        if($f['periodo_id']){$sub.=' AND ga.periodo_id=?';$args[]=$f['periodo_id'];}
        $where[]='EXISTS (SELECT 1 FROM grupos_alumnos ga WHERE '.$sub.')';
    }
    $rol=$_SESSION['rol_slug']??'';$uid=(int)($_SESSION['user_id']??0);
    if(in_array($rol,['alumno','estudiante'],true)){$where[]='a.usuario_id=?';$args[]=$uid;}
    if($rol==='padre'){$where[]='EXISTS (SELECT 1 FROM padres_familia pf WHERE pf.alumno_id=a.id AND pf.usuario_id=?)';$args[]=$uid;}
    $sql="SELECT a.id,a.numero_control,CONCAT_WS(' ',u.nombres,u.apellido_paterno,u.apellido_materno) nombre,c.nombre carrera FROM alumnos a JOIN usuarios u ON u.id=a.usuario_id LEFT JOIN carreras c ON c.id=a.carrera_id".($where?' WHERE '.implode(' AND ',$where):'').' ORDER BY nombre LIMIT 200';
    $s=$pdo->prepare($sql);$s->execute($args);return $s->fetchAll();
}
function cufaUnidades(PDO $pdo,int $asignacion): array {
    $s=$pdo->prepare('SELECT m.total_unidades FROM asignaciones_docentes ad JOIN materias m ON m.id=ad.materia_id WHERE ad.id=?');$s->execute([$asignacion]);$n=(int)$s->fetchColumn();
    if($n>100)throw new RuntimeException('Cantidad de unidades inválida.');
    $us=$n>0?range(1,$n):[];
    $s=$pdo->prepare('SELECT DISTINCT parcial FROM temas_materia WHERE asignacion_id=? AND parcial>0');$s->execute([$asignacion]);
    $us=array_values(array_unique(array_merge($us,array_map('intval',$s->fetchAll(PDO::FETCH_COLUMN)))));sort($us);return $us;
}
function cufaExpediente(PDO $pdo,int $alumno,array $f,bool $soloAprobadas): array {
    $perfil=cufaAlumno($pdo,$alumno);$where=['ga.alumno_id=?'];$args=[$alumno];
    foreach(['grupo_id','periodo_id'] as $campo)if(!empty($f[$campo])){$where[]='ga.'.$campo.'=?';$args[]=$f[$campo];}
    $s=$pdo->prepare('SELECT DISTINCT g.id,g.nombre,p.id periodo_id,p.nombre periodo,c.nombre carrera FROM grupos_alumnos ga JOIN grupos g ON g.id=ga.grupo_id JOIN periodos p ON p.id=ga.periodo_id LEFT JOIN carreras c ON c.id=g.carrera_id WHERE '.implode(' AND ',$where).' ORDER BY p.id DESC,g.nombre');$s->execute($args);$inscripciones=$s->fetchAll();
    $s=$pdo->prepare('SELECT DISTINCT ad.id,ad.materia_id,m.nombre,m.clave,m.total_unidades,g.nombre grupo,p.nombre periodo,p.id periodo_id FROM grupos_alumnos ga JOIN asignaciones_docentes ad ON ad.grupo_id=ga.grupo_id AND ad.periodo_id=ga.periodo_id JOIN materias m ON m.id=ad.materia_id JOIN grupos g ON g.id=ad.grupo_id JOIN periodos p ON p.id=ad.periodo_id WHERE '.implode(' AND ',$where).' ORDER BY p.id DESC,m.nombre');$s->execute($args);$materias=$s->fetchAll();
    $completo=(bool)$materias;$medias=[];
    foreach($materias as &$m){
        $m['unidades']=cufaUnidades($pdo,(int)$m['id']);$m['notas']=[];
        $s=$pdo->prepare('SELECT * FROM calificaciones_finales WHERE asignacion_id=? AND alumno_id=? ORDER BY unidad,id');$s->execute([$m['id'],$alumno]);
        foreach($s->fetchAll() as $n){
            if(!in_array((int)$n['unidad'],$m['unidades'],true))continue;
            if(isset($m['notas'][(int)$n['unidad']]))throw new RuntimeException('Notas duplicadas: se requiere conciliación, sin modificar el historial.');
            $m['notas'][(int)$n['unidad']]=$soloAprobadas && $n['estado']!=='aprobado'?['unidad'=>$n['unidad'],'estado'=>'pendiente','calificacion_final'=>null]:$n;
        }
        $vals=[];$aprobada=(bool)$m['unidades'];$registrada=(bool)$m['unidades'];
        foreach($m['unidades'] as $u){$n=$m['notas'][$u]??null;if(!$n || $n['calificacion_final']===null)$registrada=false;else $vals[]=(float)$n['calificacion_final'];if(!$n || $n['estado']!=='aprobado')$aprobada=false;}
        $m['promedio']=$registrada?round(array_sum($vals)/count($m['unidades']),2):null;
        $m['autorizada']=$aprobada;
        $m['estado']=$aprobada?'Calificaciones autorizadas':'Pendiente de captura o autorización';
        if(!$aprobada)$completo=false;
        if($m['promedio']!==null)$medias[]=$m['promedio'];
    }unset($m);
    return ['alumno'=>$perfil,'inscripciones'=>$inscripciones,'materias'=>$materias,'promedio'=>count($medias)===count($materias) && $materias?round(array_sum($medias)/count($medias),2):null,'autorizada'=>$completo];
}
function cufaNumero($v): string { return $v===null?'Pendiente':number_format((float)$v,2); }
function cufaFiltrosHtml(PDO $pdo,array $f,string $modulo): void {
    $carreras=$pdo->query('SELECT id,nombre FROM carreras ORDER BY nombre')->fetchAll();
    $periodos=$pdo->query('SELECT id,nombre FROM periodos ORDER BY id DESC')->fetchAll();
    $s=$pdo->prepare('SELECT id,nombre FROM grupos WHERE (?=0 OR carrera_id=?) AND (?=0 OR periodo_id=?) ORDER BY nombre');$s->execute([$f['carrera_id'],$f['carrera_id'],$f['periodo_id'],$f['periodo_id']]);$grupos=$s->fetchAll();
    echo '<form method="GET" class="bg-white p-6 rounded-3xl shadow mb-6 grid grid-cols-1 md:grid-cols-4 gap-4"><input type="hidden" name="modulo" value="'.cufaEsc($modulo).'">';
    echo '<label>Nombre completo o matrícula<input name="q" value="'.cufaEsc($f['q']).'" class="border p-3 rounded-xl w-full" placeholder="22510027 o nombre"></label>';
    foreach(['carrera_id'=>['Carrera',$carreras],'grupo_id'=>['Grupo',$grupos],'periodo_id'=>['Periodo',$periodos]] as $name=>[$label,$rows]){
        echo '<label>'.$label.'<select name="'.$name.'" class="border p-3 rounded-xl w-full"><option value="">Todos</option>';
        foreach($rows as $row)echo '<option value="'.(int)$row['id'].'" '.($f[$name]==$row['id']?'selected':'').'>'.cufaEsc($row['nombre']).'</option>';
        echo '</select></label>';
    }
    echo '<button class="bg-purple-600 text-white px-6 py-3 rounded-xl">Buscar / aplicar filtros</button></form>';
}
function cufaError(Throwable $e): void { $mensaje=$e->getMessage();if($e instanceof PDOException){error_log($mensaje);$mensaje='No fue posible completar la operación en la base de datos. Revisa el registro de errores y la instalación de la migración.';} echo '<div role="alert" class="p-6 rounded-2xl bg-red-50 text-red-700">'.cufaEsc($mensaje).'</div>'; }

function cufaCampoToken(): string { return '<input type="hidden" name="cufa_csrf" value="'.cufaEsc(cufaToken()).'">'; }
