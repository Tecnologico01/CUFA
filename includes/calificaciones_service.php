<?php
/* Identidad académica: alumno_id siempre es alumnos.id. */
function cufaNota($valor): float {
    if (!is_scalar($valor) || !is_numeric($valor) || !is_finite((float)$valor) || (float)$valor < 0 || (float)$valor > 100) {
        throw new RuntimeException('Captura todas las notas con valores numéricos entre 0 y 100.');
    }
    return round((float)$valor, 2);
}
function cufaFinal($actividades, $examen): float {
    return round((cufaNota($actividades) + cufaNota($examen)) / 2, 2);
}
function cufaContexto(PDO $pdo, int $asignacion, bool $bloquear = false): array {
    $s = $pdo->prepare('SELECT * FROM asignaciones_docentes WHERE id = ?' . ($bloquear ? ' FOR UPDATE' : ''));
    $s->execute([$asignacion]); $ad = $s->fetch(PDO::FETCH_ASSOC);
    if (!$ad) throw new RuntimeException('Asignación no válida.');
    $s = $pdo->prepare('SELECT DISTINCT a.id FROM alumnos a JOIN grupos_alumnos ga ON ga.alumno_id=a.id WHERE ga.grupo_id=? AND ga.periodo_id=?');
    $s->execute([$ad['grupo_id'], $ad['periodo_id']]); $ids = array_map('intval', $s->fetchAll(PDO::FETCH_COLUMN));
    $s=$pdo->prepare('SELECT m.total_unidades FROM asignaciones_docentes ad JOIN materias m ON m.id=ad.materia_id WHERE ad.id=?');$s->execute([$asignacion]);$cantidad=(int)$s->fetchColumn();
    if($cantidad>100)throw new RuntimeException('Cantidad de unidades inválida.');
    $unidades=$cantidad>0?range(1,$cantidad):[];
    $s=$pdo->prepare('SELECT DISTINCT parcial FROM temas_materia WHERE asignacion_id=? AND parcial>0');$s->execute([$asignacion]);
    $unidades=array_values(array_unique(array_merge($unidades,array_map('intval',$s->fetchAll(PDO::FETCH_COLUMN)))));sort($unidades);
    $s = $pdo->prepare('SELECT cf.* FROM calificaciones_finales cf WHERE cf.asignacion_id=? ORDER BY cf.id');
    $s->execute([$asignacion]); $filas = [];
    foreach ($s->fetchAll(PDO::FETCH_ASSOC) as $f) {
        if (!in_array((int)$f['alumno_id'], $ids, true) || !in_array((int)$f['unidad'], $unidades, true)) continue;
        $clave = $f['alumno_id'].':'.$f['unidad'];
        if (isset($filas[$clave])) throw new RuntimeException('Hay calificaciones duplicadas; deben conciliarse sin borrar el historial.');
        $filas[$clave] = $f;
    }
    return [$ad, $ids, $unidades, $filas];
}
function cufaEstado(array $filas): string {
    $estados = array_unique(array_column($filas, 'estado'));
    if (!$estados) return 'borrador';
    if (count($estados) === 1) return reset($estados);
    if (!array_diff($estados, ['borrador', 'devuelto'])) return 'devuelto';
    return 'mixto';
}
function cufaGuardar(PDO $pdo, int $asignacion, int $usuario, array $captura, bool $enviar, bool $completa = false): void {
    $pdo->beginTransaction();
    try {
        [$ad, $ids, $unidades, $filas] = cufaContexto($pdo, $asignacion, true);
        cufaProtegerActa($pdo,$ad);
        $s = $pdo->prepare('SELECT id FROM docentes WHERE id=? AND usuario_id=?');
        $s->execute([$ad['docente_id'], $usuario]);
        if (!$s->fetchColumn()) throw new RuntimeException('La asignación no pertenece al docente.');
        if (!in_array(cufaEstado($filas), ['borrador','devuelto'], true)) throw new RuntimeException('Las notas están bloqueadas. Control Escolar debe devolverlas para corregir.');
        if (!$ids || !$unidades || !$captura) throw new RuntimeException('No hay alumnos, unidades o calificaciones para guardar.');
        if ($enviar || $completa) {
            foreach($ids as $id) foreach($unidades as $u) {
                if (!isset($captura[$id][$u]['promedio_actividades'], $captura[$id][$u]['calificacion_examen'])) {
                    throw new RuntimeException('El formulario llegó incompleto. Recarga y revisa max_input_vars en php.ini; no se guardó ningún cambio.');
                }
            }
        }
        foreach ($captura as $alumno => $notas) {
            if (!ctype_digit((string)$alumno) || (string)(int)$alumno !== (string)$alumno || !in_array((int)$alumno, $ids, true) || !is_array($notas)) throw new RuntimeException('Alumno ajeno al grupo o periodo.');
            foreach ($notas as $unidad => $datos) {
                if (!ctype_digit((string)$unidad) || (string)(int)$unidad !== (string)$unidad || !in_array((int)$unidad, $unidades, true) || !is_array($datos)) throw new RuntimeException('Unidad no válida.');
                $a = cufaNota($datos['promedio_actividades'] ?? '');
                $e = cufaNota($datos['calificacion_examen'] ?? '');
                $final = cufaFinal($a, $e);
                $f = $filas[$alumno.':'.$unidad] ?? null;
                if ($f) {
                    $s = $pdo->prepare("UPDATE calificaciones_finales SET promedio_actividades=?,calificacion_examen=?,calificacion_final=?,estado='borrador' WHERE id=?");
                    $s->execute([$a,$e,$final,$f['id']]);
                    if (isset($datos['observaciones']) && is_string($datos['observaciones'])) {
                        $obs=$pdo->prepare('UPDATE calificaciones_finales SET observaciones=? WHERE id=?');
                        $obs->execute([trim($datos['observaciones']),$f['id']]);
                    }
                } else {
                    $s = $pdo->prepare("INSERT INTO calificaciones_finales (asignacion_id,alumno_id,unidad,promedio_actividades,calificacion_examen,calificacion_final,estado) VALUES (?,?,?,?,?,?,'borrador')");
                    $s->execute([$asignacion,(int)$alumno,(int)$unidad,$a,$e,$final]);
                }
            }
        }
        if ($enviar) {
            [,,$us,$actuales] = cufaContexto($pdo,$asignacion);
            cufaCompleta($ids,$us,$actuales);
            $s=$pdo->prepare("UPDATE calificaciones_finales SET estado='cerrado' WHERE id=?");
            foreach ($actuales as $f) $s->execute([$f['id']]);
        }
        $pdo->commit();
    } catch (Throwable $e) { if ($pdo->inTransaction()) $pdo->rollBack(); throw $e; }
}
function cufaCompleta(array $ids, array $us, array $filas): void {
    if (!$ids || !$us) throw new RuntimeException('Se requieren alumnos y unidades.');
    foreach ($ids as $id) foreach ($us as $u) {
        if (!isset($filas[$id.':'.$u])) throw new RuntimeException('Faltan notas de alumnos o unidades.');
        $f=$filas[$id.':'.$u];
        if (abs(cufaFinal($f['promedio_actividades'],$f['calificacion_examen'])-(float)$f['calificacion_final']) > 0.001) throw new RuntimeException('La nota final no coincide con el promedio; devuelve la captura para corregirla.');
    }
}
function cufaRevisar(PDO $pdo, int $asignacion, string $accion): void {
    if (!in_array($accion,['aprobar','devolver'],true)) throw new RuntimeException('Acción no válida.');
    $pdo->beginTransaction();
    try {
        [$ad, $ids, $us, $filas]=cufaContexto($pdo,$asignacion,true);
        cufaProtegerActa($pdo,$ad);
        if (cufaEstado($filas)!=='cerrado') throw new RuntimeException('Las calificaciones no están pendientes de revisión.');
        if ($accion==='aprobar') cufaCompleta($ids,$us,$filas);
        $s=$pdo->prepare('UPDATE calificaciones_finales SET estado=? WHERE id=?');
        foreach($filas as $f) $s->execute([$accion==='aprobar'?'aprobado':'devuelto',$f['id']]);
        $pdo->commit();
    } catch(Throwable $e) { if($pdo->inTransaction()) $pdo->rollBack(); throw $e; }
}
function cufaToken(): string {
    if (empty($_SESSION['cufa_csrf'])) $_SESSION['cufa_csrf']=bin2hex(random_bytes(32));
    return $_SESSION['cufa_csrf'];
}
function cufaValidarToken(): void {
    if (!is_string($_POST['cufa_csrf'] ?? null) || !hash_equals(cufaToken(),$_POST['cufa_csrf'])) throw new RuntimeException('Formulario vencido. Recarga la página.');
}

function cufaProtegerActa(PDO $pdo,array $ad): void {
 $s=$pdo->prepare('SELECT id FROM actas WHERE asignacion_id=? OR (asignacion_id IS NULL AND materia_id=? AND grupo_id=? AND periodo_id=?) LIMIT 1');$s->execute([$ad['id'],$ad['materia_id'],$ad['grupo_id'],$ad['periodo_id']]);if($s->fetchColumn())throw new RuntimeException('La asignación tiene un acta histórica. Sus calificaciones están protegidas y requieren un procedimiento institucional de rectificación.');
}
