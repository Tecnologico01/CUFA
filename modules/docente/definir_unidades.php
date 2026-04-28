<?php
require_once __DIR__ . '/../../includes/db.php';

$asignacion_id = $_GET['asignacion'] ?? null;

if (!$asignacion_id) {
    echo "Asignación no especificada.";
    exit;
}

function aRomano($num) {
    $map = ['I','II','III','IV','V','VI','VII','VIII','IX'];
    return $map[$num-1] ?? $num;
}

/* INFO DE LA MATERIA */
$stmt = $pdo->prepare("
    SELECT ad.materia_id, ad.grupo_id, m.nombre, g.nombre AS grupo 
    FROM asignaciones_docentes ad 
    JOIN materias m ON ad.materia_id=m.id 
    JOIN grupos g ON ad.grupo_id=g.id 
    WHERE ad.id=?
");
$stmt->execute([$asignacion_id]);
$info = $stmt->fetch();

if (!$info) {
    echo "Asignación no encontrada.";
    exit;
}

/* TOTAL DE UNIDADES (GRADOS) */
$stmtP = $pdo->prepare("SELECT numero FROM parciales WHERE materia_id=? AND grupo_id=? AND activo=1 LIMIT 1");
$stmtP->execute([$info['materia_id'], $info['grupo_id']]);
$total_parciales = $stmtP->fetchColumn() ?: 1;

/* UNIDAD SELECCIONADA */
$num_unidad = $_GET['unidad'] ?? $_POST['num_unidad'] ?? 1;

/* VERIFICAR BLOQUEO */
$stmtEstado = $pdo->prepare("
    SELECT COUNT(*) 
    FROM temas_materia 
    WHERE asignacion_id=? AND parcial=? AND (estado='enviado' OR estado='aprobado')
");
$stmtEstado->execute([$asignacion_id, $num_unidad]);
$total_bloqueados = $stmtEstado->fetchColumn();

$bloqueado = ($total_bloqueados > 0);

/* CARGAR DATOS EXISTENTES */
$stmtTemas = $pdo->prepare("
    SELECT tm.*, a.nombre as act_nombre, a.descripcion as act_desc, a.fecha_cierre, a.valor, a.rubrica
    FROM temas_materia tm
    LEFT JOIN actividades a ON a.tema_id = tm.id
    WHERE tm.asignacion_id=? AND tm.parcial=?
    ORDER BY tm.orden ASC
");
$stmtTemas->execute([$asignacion_id, $num_unidad]);
$temas_db = $stmtTemas->fetchAll(PDO::FETCH_ASSOC);

$stmtEx = $pdo->prepare("SELECT id FROM examenes WHERE asignacion_id=? AND parcial=? LIMIT 1");
$stmtEx->execute([$asignacion_id, $num_unidad]);
$ex_id = $stmtEx->fetchColumn();

$preguntas_db = [];
if($ex_id){
    $stmtR = $pdo->prepare("SELECT * FROM preguntas_examen WHERE examen_id=? ORDER BY id ASC");
    $stmtR->execute([$ex_id]);
    $preguntas_db = $stmtR->fetchAll(PDO::FETCH_ASSOC);
}

/* LÓGICA DE GUARDADO */
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['guardar_unidad']) && !$bloqueado) {
    try {
        $pdo->beginTransaction();
        $num_unidad = $_POST['num_unidad'];
        $objetivo   = $_POST['objetivo_unidad'];

        $pdo->prepare("DELETE FROM actividades WHERE asignacion_id=? AND parcial=?")->execute([$asignacion_id, $num_unidad]);
        $pdo->prepare("DELETE FROM temas_materia WHERE asignacion_id=? AND parcial=?")->execute([$asignacion_id, $num_unidad]);
        $pdo->prepare("DELETE FROM examenes WHERE asignacion_id=? AND parcial=?")->execute([$asignacion_id, $num_unidad]);

        for ($i = 1; $i <= 5; $i++) {
            $tema_txt = $_POST["tema_$i"] ?? '';
            if (trim($tema_txt) == '') continue;

            $stmt = $pdo->prepare("
                INSERT INTO temas_materia 
                (asignacion_id, parcial, tema, subtemas, objetivo_unidad, orden, video_referencia, 
                estilos_aprendizaje, presentacion_infografia, bibliografia_complementaria, estrategia_didactica, estado, nombre_unidad)
                VALUES (?,?,?,?,?,?,?,?,?,?,?, 'enviado', ?)
            ");
            $stmt->execute([
                $asignacion_id, $num_unidad, $tema_txt, $_POST["subtema_$i"] ?? '', $objetivo, $i,
                $_POST["video_$i"] ?? '', $_POST["estilo_$i"] ?? '', $_POST["pres_$i"] ?? '', 
                $_POST["biblio_$i"] ?? '', $_POST["estrategia_$i"] ?? '', $_POST["nombre_unidad"] ?? ''
            ]);

            $tema_id = $pdo->lastInsertId();
            $act_nombre = $_POST["act_nombre_$i"] ?? '';

            if (trim($act_nombre) != '') {
                $stmtAct = $pdo->prepare("
                    INSERT INTO actividades
                    (asignacion_id, semana, nombre, descripcion, fecha_apertura, fecha_cierre, valor, tipo_archivo, rubrica, parcial, tema_id)
                    VALUES (?,?,?,?,?,?,?,?,?,?,?)
                ");
                $stmtAct->execute([
                    $asignacion_id, $i, $act_nombre, $_POST["act_desc_$i"] ?? '', date("Y-m-d"),
                    $_POST["act_fecha_$i"] ?: null, $_POST["act_valor_$i"] ?: 0, 'cualquiera',
                    $_POST["act_rubrica_$i"] ?? '', $num_unidad, $tema_id
                ]);
            }
        }

        $stmtE = $pdo->prepare("INSERT INTO examenes (asignacion_id, parcial, titulo) VALUES (?,?,?)");
        $stmtE->execute([$asignacion_id, $num_unidad, "Examen Unidad $num_unidad"]);
        $nuevo_examen_id = $pdo->lastInsertId();

        for ($r = 1; $r <= 20; $r++) {
            if (!empty($_POST["pregunta_$r"])) {
                $stmtR = $pdo->prepare("
                    INSERT INTO preguntas_examen (examen_id, pregunta, opcion_a, opcion_b, opcion_c, opcion_d, respuesta_correcta)
                    VALUES (?,?,?,?,?,?,?)
                ");
                $stmtR->execute([
                    $nuevo_examen_id, $_POST["pregunta_$r"], $_POST["a_$r"] ?? '', $_POST["b_$r"] ?? '',
                    $_POST["c_$r"] ?? '', $_POST["d_$r"] ?? '', $_POST["correcta_$r"] ?? ''
                ]);
            }
        }

        $pdo->commit();
        header("Location: ?definir_unidades=$asignacion_id&unidad=$num_unidad&success=1");
        exit;
    } catch (Exception $e) {
        $pdo->rollBack();
        $error = "Error al guardar: " . $e->getMessage();
    }
}
?>

<div class="max-w-7xl mx-auto p-6 bg-slate-50 min-h-screen animate-fade-in">
    
    <div class="mb-12 flex flex-col md:flex-row md:items-end justify-between gap-6">
        <div>
            <h1 class="text-6xl font-black italic uppercase tracking-tighter text-slate-900 leading-none">
                Planeación <span class="text-purple-600">Integral</span>
            </h1>
            <p class="text-slate-400 font-bold text-[11px] uppercase tracking-[0.4em] mt-3 flex items-center gap-2">
                <span class="w-8 h-[2px] bg-purple-500"></span>
                <?= htmlspecialchars($info['nombre']) ?> // GRUPO <?= htmlspecialchars($info['grupo']) ?>
            </p>
        </div>
        <div class="bg-white p-5 rounded-3xl shadow-sm border border-slate-100 flex items-center gap-4">
            <div class="text-right">
                <span class="text-[9px] font-black text-slate-300 uppercase block tracking-widest">Estatus de Control</span>
                <span class="text-xs font-black uppercase italic <?= $bloqueado ? 'text-amber-500' : 'text-emerald-500' ?>">
                    <?= $bloqueado ? '🔒 Registro Protegido' : '🔓 Edición Activa' ?>
                </span>
            </div>
            <div class="w-[2px] h-10 bg-slate-100"></div>
            <div class="flex flex-col text-center min-w-[60px]">
                <span class="text-[9px] font-black text-slate-400 uppercase tracking-widest">Unidad</span>
                <span class="text-xl font-black italic text-purple-600"><?= aRomano($num_unidad) ?></span>
            </div>
        </div>
    </div>

    <?php if(isset($_GET['success'])): ?>
        <div class="bg-emerald-600 text-white p-6 rounded-[2rem] mb-10 font-black uppercase text-[10px] tracking-[0.2em] shadow-xl animate-bounce">
            ✓ Expediente de Unidad <?= aRomano($num_unidad) ?> actualizado correctamente.
        </div>
    <?php endif; ?>

    <form method="POST" class="space-y-16">

        <div class="bg-white p-12 rounded-[4rem] shadow-sm border border-slate-100 grid grid-cols-1 md:grid-cols-3 gap-10 relative">
            <div class="space-y-2">
                <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-2 italic">Seleccionar Unidad</label>
                <select name="num_unidad" onchange="location.href='?modulo=definir_unidades&asignacion=<?= $asignacion_id ?>&unidad='+this.value"
                    class="w-full p-5 bg-slate-50 border-2 border-slate-100 rounded-[1.8rem] font-black text-slate-800 outline-none focus:border-purple-500 transition-all cursor-pointer appearance-none">
                    <?php for($i=1;$i<=$total_parciales;$i++): ?>
                        <option value="<?= $i ?>" <?= $i == $num_unidad ? 'selected' : '' ?>>UNIDAD <?= aRomano($i) ?></option>
                    <?php endfor; ?>
                </select>
            </div>
            <div class="md:col-span-2 space-y-2">
                <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-2 italic">Nombre de la Unidad</label>
                <input type="text" name="nombre_unidad" value="<?= htmlspecialchars($temas_db[0]['nombre_unidad'] ?? '') ?>" 
                    class="w-full p-5 bg-slate-50 border-2 border-slate-100 rounded-[1.8rem] font-bold text-slate-800 outline-none focus:border-purple-500" <?= $bloqueado?'disabled':'' ?>>
            </div>
            <div class="md:col-span-3 space-y-2">
                <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-2 italic">Objetivo General de la Unidad</label>
                <textarea name="objetivo_unidad" rows="2" class="w-full p-6 bg-slate-50 border-2 border-slate-100 rounded-[2.5rem] font-bold text-slate-600 outline-none focus:border-purple-500" <?= $bloqueado?'disabled':'' ?>><?= htmlspecialchars($temas_db[0]['objetivo_unidad'] ?? '') ?></textarea>
            </div>
        </div>

        <div class="space-y-12">
            <h2 class="text-3xl font-black italic uppercase tracking-tighter text-slate-800 ml-8 flex items-center gap-4">
                <span class="p-4 bg-purple-600 rounded-2xl text-white text-sm not-italic tracking-normal">01</span>
                Secuencia Temática y Evidencias
            </h2>

            <div class="grid grid-cols-1 gap-10">
                <?php for($i=1;$i<=5;$i++): $t = $temas_db[$i-1] ?? null; ?>
                <div class="bg-white rounded-[4rem] border border-slate-100 shadow-sm overflow-hidden group hover:shadow-xl transition-all duration-500">
                    <div class="bg-slate-900 p-8 flex justify-between items-center group-hover:bg-purple-900 transition-colors">
                        <span class="text-white font-black italic uppercase tracking-[0.3em] text-[10px]">Módulo <?= $i ?></span>
                        <div class="h-[1px] flex-1 mx-8 bg-white/10"></div>
                        <span class="text-purple-400 font-black text-2xl italic opacity-50">#0<?= $i ?></span>
                    </div>

                    <div class="p-12 grid grid-cols-1 lg:grid-cols-2 gap-12">
                        <div class="space-y-4">
                            <h4 class="text-[11px] font-black uppercase text-purple-600 tracking-widest mb-6 flex items-center gap-2">Parámetros Técnicos</h4>
                            <input name="tema_<?= $i ?>" value="<?= htmlspecialchars($t['tema'] ?? '') ?>" placeholder="Tema Principal" class="w-full p-4 bg-slate-50 border border-slate-100 rounded-2xl font-black text-slate-800" <?= $bloqueado?'disabled':'' ?>>
                            <input name="subtema_<?= $i ?>" value="<?= htmlspecialchars($t['subtemas'] ?? '') ?>" placeholder="Subtemas" class="w-full p-4 bg-slate-50 border border-slate-100 rounded-2xl text-sm" <?= $bloqueado?'disabled':'' ?>>
                            <div class="grid grid-cols-2 gap-4">
                                <input name="video_<?= $i ?>" value="<?= htmlspecialchars($t['video_referencia'] ?? '') ?>" placeholder="Video URL" class="p-4 bg-slate-50 border border-slate-100 rounded-2xl text-xs font-bold" <?= $bloqueado?'disabled':'' ?>>
                                <input name="estilo_<?= $i ?>" value="<?= htmlspecialchars($t['estilos_aprendizaje'] ?? '') ?>" placeholder="Estilo" class="p-4 bg-slate-50 border border-slate-100 rounded-2xl text-xs font-bold" <?= $bloqueado?'disabled':'' ?>>
                            </div>
                            <input name="estrategia_<?= $i ?>" value="<?= htmlspecialchars($t['estrategia_didactica'] ?? '') ?>" placeholder="Estrategia" class="w-full p-4 bg-slate-50 border border-slate-100 rounded-2xl text-xs italic" <?= $bloqueado?'disabled':'' ?>>
                            <input name="biblio_<?= $i ?>" value="<?= htmlspecialchars($t['bibliografia_complementaria'] ?? '') ?>" placeholder="Bibliografía" class="w-full p-4 bg-slate-50 border border-slate-100 rounded-2xl text-xs" <?= $bloqueado?'disabled':'' ?>>
                            <input name="pres_<?= $i ?>" value="<?= htmlspecialchars($t['presentacion_infografia'] ?? '') ?>" placeholder="Link Material" class="w-full p-4 bg-slate-50 border border-slate-100 rounded-2xl text-xs" <?= $bloqueado?'disabled':'' ?>>
                        </div>

                        <div class="bg-slate-50/50 p-10 rounded-[3rem] border-2 border-dashed border-slate-200 space-y-4">
                            <h4 class="text-[11px] font-black uppercase text-emerald-600 tracking-widest mb-6 flex items-center gap-2">Actividad</h4>
                            <input name="act_nombre_<?= $i ?>" value="<?= htmlspecialchars($t['act_nombre'] ?? '') ?>" placeholder="Nombre de la Actividad" class="w-full p-4 bg-white border border-slate-100 rounded-2xl font-black text-slate-700 shadow-sm" <?= $bloqueado?'disabled':'' ?>>
                            <textarea name="act_desc_<?= $i ?>" rows="3" placeholder="Instrucciones..." class="w-full p-4 bg-white border border-slate-100 rounded-2xl text-xs font-bold text-slate-500 shadow-sm" <?= $bloqueado?'disabled':'' ?>><?= htmlspecialchars($t['act_desc'] ?? '') ?></textarea>
                            <div class="grid grid-cols-2 gap-4">
                                <input type="date" name="act_fecha_<?= $i ?>" value="<?= htmlspecialchars($t['fecha_cierre'] ?? '') ?>" class="w-full p-3 bg-white border border-slate-100 rounded-xl text-xs font-black text-slate-600 shadow-sm" <?= $bloqueado?'disabled':'' ?>>
                                <input type="number" name="act_valor_<?= $i ?>" value="<?= htmlspecialchars($t['valor'] ?? '') ?>" placeholder="%" class="w-full p-3 bg-white border border-slate-100 rounded-xl text-xs font-black text-center text-purple-600 shadow-sm" <?= $bloqueado?'disabled':'' ?>>
                            </div>
                            <textarea name="act_rubrica_<?= $i ?>" placeholder="Rúbrica..." class="w-full p-4 bg-white border border-slate-100 rounded-2xl text-[10px] text-slate-400 italic font-bold" <?= $bloqueado?'disabled':'' ?>><?= htmlspecialchars($t['rubrica'] ?? '') ?></textarea>
                        </div>
                    </div>
                </div>
                <?php endfor; ?>
            </div>
        </div>

        <div class="space-y-12">
            <h2 class="text-3xl font-black italic uppercase tracking-tighter text-slate-800 ml-8 flex items-center gap-4">
                <span class="p-4 bg-emerald-500 rounded-2xl text-white text-sm not-italic tracking-normal">02</span>
                Examen <span class="text-slate-400 ml-2">(20 Preguntas)</span>
            </h2>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                <?php for($r=1;$r<=20;$r++): $p = $preguntas_db[$r-1] ?? null; ?>
                <div class="bg-white p-8 rounded-[3.5rem] border border-slate-100 shadow-sm space-y-6 hover:shadow-md transition-shadow">
                    <div class="flex items-center gap-4">
                        <span class="w-10 h-10 rounded-2xl bg-slate-100 flex items-center justify-center text-xs font-black text-slate-400 italic"><?= $r ?></span>
                        <input name="pregunta_<?= $r ?>" value="<?= htmlspecialchars($p['pregunta'] ?? '') ?>" placeholder="Planteamiento de la pregunta..." 
                            class="flex-1 bg-transparent border-b border-slate-100 p-2 text-slate-800 text-sm font-bold outline-none focus:border-purple-400 transition-all" <?= $bloqueado?'disabled':'' ?>>
                    </div>

                    <div class="grid grid-cols-1 gap-3 pl-14">
                        <?php foreach(['a','b','c','d'] as $op): ?>
                        <div class="flex items-center gap-3">
                            <span class="text-[9px] font-black text-slate-300 uppercase"><?= $op ?></span>
                            <input name="<?= $op ?>_<?= $r ?>" value="<?= htmlspecialchars($p['opcion_'.$op] ?? '') ?>" placeholder="Opción <?= strtoupper($op) ?>" 
                                class="flex-1 bg-slate-50 p-3 rounded-xl text-xs text-slate-600 outline-none border border-transparent focus:border-slate-200" <?= $bloqueado?'disabled':'' ?>>
                        </div>
                        <?php endforeach; ?>
                    </div>

                    <div class="flex justify-end items-center gap-4 pt-4 border-t border-slate-50 pl-14">
                        <label class="text-[9px] font-black text-emerald-500 uppercase tracking-widest italic">Correcta:</label>
                        <select name="correcta_<?= $r ?>" class="bg-slate-50 text-slate-800 font-black p-2 px-6 rounded-xl border border-slate-100 outline-none focus:ring-2 focus:ring-purple-100" <?= $bloqueado?'disabled':'' ?>>
                            <?php foreach(['A','B','C','D'] as $opt): ?>
                                <option value="<?= $opt ?>" <?= ($p['respuesta_correcta'] ?? '') == $opt ? 'selected' : '' ?>><?= $opt ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <?php endfor; ?>
            </div>
        </div>

        <?php if(!$bloqueado): ?>
        <div class="pt-10 pb-24 text-center">
            <button name="guardar_unidad" class="group relative py-8 px-24 bg-purple-600 text-white rounded-[3rem] font-black uppercase tracking-[0.5em] text-[10px] shadow-2xl hover:scale-105 transition-all">
                <span>Finalizar y Guardar Planeación</span>
            </button>
            <p class="mt-8 text-[9px] font-black text-slate-400 uppercase tracking-widest italic">⚠️ Esta acción enviará los datos para revisión académica.</p>
        </div>
        <?php endif; ?>

    </form>
</div>

<style>
@keyframes slide-up { from { opacity: 0; transform: translateY(40px); } to { opacity: 1; transform: translateY(0); } }
.animate-fade-in { animation: slide-up 0.9s cubic-bezier(0.16, 1, 0.3, 1); }
</style>