<?php
require_once __DIR__.'/cufa_fase2.php';
function cufaGenerarActa(PDO $pdo,int $asignacion,int $usuario): int {
    $pdo->beginTransaction();
    try {
        [$ad,$ids,$us,$filas]=cufaContexto($pdo,$asignacion,true);
        $s=$pdo->prepare('SELECT generacion_habilitada FROM cufa_actas_control WHERE periodo_id=? FOR UPDATE');$s->execute([$ad['periodo_id']]);
        if((int)$s->fetchColumn()!==1)throw new RuntimeException('La generación está cerrada para este periodo. Control Escolar debe habilitarla.');
        $s=$pdo->prepare('SELECT id FROM actas WHERE asignacion_id=? OR (asignacion_id IS NULL AND materia_id=? AND grupo_id=? AND periodo_id=?) LIMIT 1');$s->execute([$asignacion,$ad['materia_id'],$ad['grupo_id'],$ad['periodo_id']]);
        $existe=$s->fetchColumn();if($existe)throw new RuntimeException('Ya existe el acta '.$existe.'. Consúltala en Actas oficiales; no se modifica ni se duplica.');
        cufaCompleta($ids,$us,$filas);
        if(cufaEstado($filas)!=='aprobado')throw new RuntimeException('Todas las unidades de todos los alumnos deben estar aprobadas por Control Escolar.');
        $s=$pdo->prepare("INSERT INTO actas(codigo,asignacion_id,materia_id,grupo_id,periodo_id,estado,fecha_generacion,generado_por) VALUES(?,?,?,?,?,'generada',NOW(),?)");
        $s->execute(['ACTA-'.date('Ymd-His').'-'.$asignacion,$asignacion,$ad['materia_id'],$ad['grupo_id'],$ad['periodo_id'],$usuario]);$id=(int)$pdo->lastInsertId();
        $s=$pdo->prepare("INSERT INTO cufa_actas_detalle(acta_id,alumno_id,unidad,alumno_nombre,numero_control,promedio_actividades,calificacion_examen,calificacion_final) SELECT ?,cf.alumno_id,cf.unidad,CONCAT_WS(' ',u.nombres,u.apellido_paterno,u.apellido_materno),a.numero_control,cf.promedio_actividades,cf.calificacion_examen,cf.calificacion_final FROM calificaciones_finales cf JOIN alumnos a ON a.id=cf.alumno_id JOIN usuarios u ON u.id=a.usuario_id WHERE cf.id=?");
        foreach($filas as $fila)$s->execute([$id,$fila['id']]);
        $s=$pdo->prepare('INSERT INTO cufa_actas_encabezado(acta_id,materia,grupo,periodo,carrera) SELECT ?,m.nombre,g.nombre,p.nombre,c.nombre FROM asignaciones_docentes ad JOIN materias m ON m.id=ad.materia_id JOIN grupos g ON g.id=ad.grupo_id JOIN periodos p ON p.id=ad.periodo_id JOIN carreras c ON c.id=g.carrera_id WHERE ad.id=?');$s->execute([$id,$asignacion]);
        $pdo->commit();return $id;
    }catch(Throwable $e){if($pdo->inTransaction())$pdo->rollBack();throw $e;}
}
