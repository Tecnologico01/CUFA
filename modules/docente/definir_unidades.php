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
$stmtU = $pdo->prepare("SELECT total_unidades FROM materias WHERE id=?");
$stmtU->execute([$info['materia_id']]);
$total_unidades = $stmtU->fetchColumn() ?: 1;

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
    SELECT tm.*, 
        a.nombre as act_nombre, 
        a.descripcion as act_desc, 
        a.fecha_cierre, 
        a.valor, 
        a.material_archivo
    FROM temas_materia tm
    LEFT JOIN actividades a ON a.tema_id = tm.id
    WHERE tm.asignacion_id = ? AND tm.parcial = ?
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

            $ruta_rubrica = null;

            if(isset($_FILES["rubrica_pdf_$i"]) && $_FILES["rubrica_pdf_$i"]['error'] == 0){
                $archivo = $_FILES["rubrica_pdf_$i"];
                if($archivo['type'] == "application/pdf"){
                    $nombre_archivo = time() . "_rubrica_" . $i . ".pdf";
                    $ruta_destino = "uploads/rubricas/" . $nombre_archivo;
                    if(!is_dir("uploads/rubricas")){
                        mkdir("uploads/rubricas", 0777, true);
                    }
                    move_uploaded_file($archivo['tmp_name'], $ruta_destino);
                    $ruta_rubrica = $ruta_destino;
                }
            }

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
                    (asignacion_id, semana, nombre, descripcion, fecha_apertura, fecha_cierre, valor, tipo_archivo, material_archivo, parcial, tema_id)
                    VALUES (?,?,?,?,?,?,?,?,?,?,?)
                ");

                $stmtAct->execute([
                    $asignacion_id, $i, $act_nombre, $_POST["act_desc_$i"] ?? '', date("Y-m-d"),
                    $_POST["act_fecha_$i"] ?: null, $_POST["act_valor_$i"] ?: 0, 'pdf',
                    $ruta_rubrica, $num_unidad, $tema_id
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

<div class="max-w-7xl mx-auto p-8 bg-slate-50 min-h-screen animate-fade-in font-sans">
    
    <!-- HEADER INSTITUCIONAL -->
    <div class="mb-12 flex flex-col md:flex-row md:items-center justify-between border-b border-slate-200 pb-8 gap-6">
        <div>
            <div class="flex items-center gap-3 mb-2">
                <span class="bg-purple-600 text-white text-[10px] font-black px-3 py-1 rounded-full uppercase tracking-widest">Sistema Académico</span>
                <span class="text-slate-300 text-[10px] font-bold">/</span>
                <span class="text-slate-500 text-[10px] font-black uppercase tracking-widest">Planeación Docente</span>
            </div>
            <h1 class="text-5xl font-black text-slate-900 tracking-tighter uppercase">
                Planeación de la <span class="text-purple-600">Asignatura</span>
            </h1>
            <div class="mt-4 flex items-center gap-4 text-slate-500">
                <div class="flex items-center gap-2 px-4 py-2 bg-white border border-slate-200 rounded-xl shadow-sm">
                    <span class="text-[9px] font-black uppercase tracking-tight text-slate-400">Asignatura:</span>
                    <span class="text-xs font-bold text-slate-700"><?= htmlspecialchars($info['nombre']) ?></span>
                </div>
                <div class="flex items-center gap-2 px-4 py-2 bg-white border border-slate-200 rounded-xl shadow-sm">
                    <span class="text-[9px] font-black uppercase tracking-tight text-slate-400">Grupo:</span>
                    <span class="text-xs font-bold text-slate-700"><?= htmlspecialchars($info['grupo']) ?></span>
                </div>
            </div>
        </div>

        <div class="flex items-center gap-6">
            <div class="text-right hidden md:block">
                <p class="text-[9px] font-black text-slate-400 uppercase tracking-[0.2em] mb-1">Estatus del Expediente</p>
                <p class="text-sm font-black uppercase italic <?= $bloqueado ? 'text-amber-500' : 'text-emerald-500' ?>">
                    <?= $bloqueado ? '🔒 Bloqueado para Evaluación' : '🔓 Edición Autorizada' ?>
                </p>
            </div>
            <div class="bg-white p-5 rounded-[2rem] shadow-xl border border-slate-100 flex flex-col items-center min-w-[120px]">
                <span class="text-[9px] font-black text-slate-400 uppercase tracking-widest">Unidad</span>
                <span class="text-4xl font-black italic text-purple-600 leading-none mt-1"><?= aRomano($num_unidad) ?></span>
            </div>
        </div>
    </div>

    <?php if(isset($_GET['success'])): ?>
        <div class="bg-emerald-500 text-white p-5 rounded-[2rem] mb-10 flex items-center gap-4 shadow-lg shadow-emerald-100 animate-bounce">
            <div class="bg-white/20 p-2 rounded-xl text-lg">✓</div>
            <p class="text-xs font-black uppercase tracking-widest">Los cambios en la unidad han sido registrados correctamente en el servidor.</p>
        </div>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data" class="space-y-24">

        <!-- SECCIÓN 00: DATOS MAESTROS -->
        <section class="relative">
            <div class="flex items-center gap-4 mb-8">
                <div class="h-[1px] flex-1 bg-slate-200"></div>
                <h3 class="text-[11px] font-black text-slate-400 uppercase tracking-[0.4em]">Información General</h3>
                <div class="h-[1px] flex-1 bg-slate-200"></div>
            </div>

            <div class="bg-white p-12 rounded-[4rem] shadow-sm border border-slate-100 grid grid-cols-1 md:grid-cols-4 gap-10">
                <div class="space-y-3">
                    <label class="block text-[10px] font-black text-slate-500 uppercase tracking-widest ml-1">Seleccionar Unidad</label>
                    <select name="num_unidad" onchange="location.href='?modulo=definir_unidades&asignacion=<?= $asignacion_id ?>&unidad='+this.value"
                        class="w-full p-4 bg-slate-50 border border-slate-200 rounded-2xl font-black text-slate-700 focus:ring-4 focus:ring-purple-100 transition-all outline-none appearance-none">
                        <?php for($i=1;$i<=$total_unidades;$i++): ?>
                            <option value="<?= $i ?>" <?= $i == $num_unidad ? 'selected' : '' ?>>UNIDAD <?= aRomano($i) ?></option>
                        <?php endfor; ?>
                    </select>
                </div>
                <div class="md:col-span-3 space-y-3">
                    <label class="block text-[10px] font-black text-slate-500 uppercase tracking-widest ml-1">Nombre de la Unidad</label>
                    <input type="text" name="nombre_unidad" value="<?= htmlspecialchars($temas_db[0]['nombre_unidad'] ?? '') ?>" 
                        placeholder="Ingrese el nombre oficial de la unidad..."
                        class="w-full p-4 bg-slate-50 border border-slate-200 rounded-2xl font-bold text-slate-800 focus:ring-4 focus:ring-purple-100 outline-none transition-all" <?= $bloqueado?'disabled':'' ?>>
                </div>
                <div class="md:col-span-4 space-y-3">
                    <label class="block text-[10px] font-black text-slate-500 uppercase tracking-widest ml-1">Objetivo General de Aprendizaje</label>
                    <textarea name="objetivo_unidad" rows="2" 
                        placeholder="Defina el objetivo pedagógico para esta unidad..."
                        class="w-full p-6 bg-slate-50 border border-slate-200 rounded-[2.5rem] font-medium text-slate-600 focus:ring-4 focus:ring-purple-100 outline-none transition-all" <?= $bloqueado?'disabled':'' ?>><?= htmlspecialchars($temas_db[0]['objetivo_unidad'] ?? '') ?></textarea>
                </div>
            </div>
        </section>

        <!-- SECCIÓN 01: CONTENIDOS -->
        <section class="space-y-12">
            <div class="flex items-center justify-between px-6">
                <div class="flex items-center gap-6">
                    <span class="w-16 h-16 bg-slate-900 text-white rounded-[1.5rem] flex items-center justify-center font-black italic shadow-2xl">01</span>
                    <div>
                        <h2 class="text-3xl font-black text-slate-800 tracking-tight uppercase">Desarrollo de la Unidad</h2>
                        <p class="text-[11px] font-bold text-slate-400 uppercase tracking-widest italic">Planificación de temas y evidencias por sesión de clase</p>
                    </div>
                </div>
            </div>

            <div class="space-y-16">
                <?php for($i=1;$i<=5;$i++): $t = $temas_db[$i-1] ?? null; ?>
                <div class="bg-white rounded-[4rem] border border-slate-200 shadow-sm overflow-hidden group hover:shadow-xl transition-all duration-500">
                    <!-- Banner de Sesión -->
                    <div class="bg-slate-50 px-12 py-5 flex justify-between items-center border-b border-slate-100 group-hover:bg-slate-100 transition-colors">
                        <span class="text-[11px] font-black text-slate-400 uppercase tracking-[0.4em]">Sesión <?= $i ?></span>
                        <div class="flex items-center gap-3">
                            <span class="w-2.5 h-2.5 rounded-full bg-purple-500 animate-pulse"></span>
                            <span class="text-[10px] font-black text-purple-600 uppercase italic tracking-widest">Planeación semanal</span>
                        </div>
                    </div>

                    <div class="p-12 grid grid-cols-1 lg:grid-cols-2 gap-16">
                        <!-- Lado A: Contenido -->
                        <div class="space-y-8">
                            <h4 class="text-[12px] font-black text-slate-800 uppercase tracking-widest border-l-4 border-purple-500 pl-5">I. Contenidos Temáticos</h4>
                            
                            <div class="space-y-5">
                                <div class="group/field">
                                    <label class="text-[9px] font-black text-slate-400 uppercase tracking-widest ml-1">Nombre del Tema</label>
                                    <input name="tema_<?= $i ?>" value="<?= htmlspecialchars($t['tema'] ?? '') ?>" class="w-full p-4 bg-slate-50 border border-slate-200 rounded-2xl font-black text-slate-700 focus:ring-4 focus:ring-purple-50 focus:bg-white transition-all outline-none" <?= $bloqueado?'disabled':'' ?>>
                                </div>
                                <div class="group/field">
                                    <label class="text-[9px] font-black text-slate-400 uppercase tracking-widest ml-1">Subtemas Detallados</label>
                                    <input name="subtema_<?= $i ?>" value="<?= htmlspecialchars($t['subtemas'] ?? '') ?>" class="w-full p-4 bg-slate-50 border border-slate-200 rounded-2xl text-sm font-medium focus:ring-4 focus:ring-purple-50 outline-none" <?= $bloqueado?'disabled':'' ?>>
                                </div>
                                <div class="grid grid-cols-2 gap-5">
                                    <div class="group/field">
                                        <label class="text-[9px] font-black text-slate-400 uppercase tracking-widest ml-1">URL Recurso Multimedia</label>
                                        <input name="video_<?= $i ?>" value="<?= htmlspecialchars($t['video_referencia'] ?? '') ?>" placeholder="https://youtube.com/..." class="w-full p-4 bg-slate-50 border border-slate-200 rounded-2xl text-xs font-bold text-blue-600" <?= $bloqueado?'disabled':'' ?>>
                                    </div>
                                    <div class="group/field">
                                        <label class="text-[9px] font-black text-slate-400 uppercase tracking-widest ml-1"> Estilo de Aprendizaje</label>
                                        <input name="estilo_<?= $i ?>" value="<?= htmlspecialchars($t['estilos_aprendizaje'] ?? '') ?>" placeholder="Visual / Auditivo..." class="w-full p-4 bg-slate-50 border border-slate-200 rounded-2xl text-xs font-bold uppercase" <?= $bloqueado?'disabled':'' ?>>
                                    </div>
                                    <div class="group/field">
                                        <label class="text-[9px] font-black text-slate-400 uppercase ml-1">Presentación o Infografía (URL)</label>
                                        <input name="pres_<?= $i ?>" value="<?= htmlspecialchars($t['presentacion_infografia'] ?? '') ?>" 
                                            placeholder="https://canva.com/..." 
                                            class="w-full p-3 bg-slate-50 border border-slate-200 rounded-xl text-xs font-bold" <?= $bloqueado?'disabled':'' ?>>
                                    </div>
                                </div>
                                <div class="group/field">
                                    <label class="text-[9px] font-black text-slate-400 uppercase tracking-widest ml-1">Estrategia Didáctica Sugerida</label>
                                    <input name="estrategia_<?= $i ?>" value="<?= htmlspecialchars($t['estrategia_didactica'] ?? '') ?>" class="w-full p-4 bg-slate-50 border border-slate-200 rounded-2xl text-xs italic font-medium" <?= $bloqueado?'disabled':'' ?>>
                                </div>
                                <div class="group/field">
                                    <label class="text-[9px] font-black text-slate-400 uppercase tracking-widest ml-1">Bibliografía Complementaria</label>
                                    <input name="biblio_<?= $i ?>" value="<?= htmlspecialchars($t['bibliografia_complementaria'] ?? '') ?>" class="w-full p-4 bg-slate-50 border border-slate-200 rounded-2xl text-xs text-slate-500" <?= $bloqueado?'disabled':'' ?>>
                                </div>
                            </div>
                        </div>

                        <!-- Lado B: Actividad -->
                        <div class="bg-slate-900/5 p-10 rounded-[3.5rem] border border-slate-200 space-y-8">
                            <h4 class="text-[12px] font-black text-emerald-600 uppercase tracking-widest border-l-4 border-emerald-500 pl-5">II. Actividad de la semana</h4>
                            
                            <div class="space-y-5">
                                <div class="group/field">
                                    <label class="text-[9px] font-black text-slate-400 uppercase tracking-widest ml-1">Nombre de la Actividad</label>
                                    <input name="act_nombre_<?= $i ?>" value="<?= htmlspecialchars($t['act_nombre'] ?? '') ?>" class="w-full p-4 bg-white border border-slate-200 rounded-2xl font-black text-slate-700 shadow-sm outline-none focus:ring-4 focus:ring-emerald-50" <?= $bloqueado?'disabled':'' ?>>
                                </div>
                                <div class="group/field">
                                    <label class="text-[9px] font-black text-slate-400 uppercase tracking-widest ml-1">Instrucciones de la Actividad</label>
                                    <textarea name="act_desc_<?= $i ?>" rows="3" class="w-full p-4 bg-white border border-slate-200 rounded-2xl text-xs font-bold text-slate-500 outline-none focus:ring-4 focus:ring-emerald-50" <?= $bloqueado?'disabled':'' ?>><?= htmlspecialchars($t['act_desc'] ?? '') ?></textarea>
                                </div>
                                <div class="grid grid-cols-2 gap-5">
                                    <div class="group/field">
                                        <label class="text-[9px] font-black text-slate-400 uppercase tracking-widest ml-1">Fecha de Entrega</label>
                                        <input type="date" name="act_fecha_<?= $i ?>" value="<?= htmlspecialchars($t['fecha_cierre'] ?? '') ?>" class="w-full p-4 bg-white border border-slate-200 rounded-2xl text-xs font-black text-slate-600 outline-none" <?= $bloqueado?'disabled':'' ?>>
                                    </div>
                                    <div class="group/field">
                                        <label class="text-[9px] font-black text-slate-400 uppercase tracking-widest ml-1">Ponderación (%)</label>
                                        <input type="number" name="act_valor_<?= $i ?>" value="<?= htmlspecialchars($t['valor'] ?? '') ?>" class="w-full p-4 bg-white border border-slate-200 rounded-2xl text-xs font-black text-center text-emerald-600 outline-none" <?= $bloqueado?'disabled':'' ?>>
                                    </div>
                                </div>
                                <div class="group/field">
                                    <label class="text-[9px] font-black text-slate-400 uppercase tracking-widest ml-1">Instrumento de Evaluación para la Actividad (PDF)</label>
                                    <div class="flex flex-col gap-3">
                                        <input type="file" name="rubrica_pdf_<?= $i ?>" accept="application/pdf" class="w-full p-3 bg-white border border-slate-200 rounded-xl text-[10px] file:mr-4 file:py-1 file:px-4 file:rounded-full file:border-0 file:text-[10px] file:font-black file:bg-emerald-50 file:text-emerald-700 hover:file:bg-emerald-100" <?= $bloqueado?'disabled':'' ?>>
                                        <?php if(!empty($t['material_archivo'])): ?>
                                            <a href="<?= $t['material_archivo'] ?>" target="_blank" 
                                            class="inline-flex items-center gap-2 text-blue-600 text-[10px] font-black uppercase tracking-tighter hover:underline">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path></svg>
                                                Consultar Criterios Almacenados
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endfor; ?>
            </div>
        </section>

        <!-- SECCIÓN 02: EXAMEN -->
        <section class="space-y-12">
            <div class="flex items-center justify-between px-6">
                <div class="flex items-center gap-6">
                    <span class="w-16 h-16 bg-emerald-500 text-white rounded-[1.5rem] flex items-center justify-center font-black italic shadow-2xl shadow-emerald-100">02</span>
                    <div>
                        <h2 class="text-3xl font-black text-slate-800 tracking-tight uppercase">Evaluación de la Unidad</h2>
                        <p class="text-[11px] font-bold text-slate-400 uppercase tracking-widest italic">Preguntas de opción múltiple</p>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                <?php for($r=1;$r<=20;$r++): $p = $preguntas_db[$r-1] ?? null; ?>
                <div class="bg-white p-10 rounded-[4rem] border border-slate-200 shadow-sm hover:border-emerald-200 transition-all group/reactivo">
                    <div class="flex items-start gap-5 mb-8">
                        <span class="w-10 h-10 rounded-xl bg-slate-100 flex items-center justify-center text-[11px] font-black text-slate-400 shrink-0 group-hover/reactivo:bg-emerald-500 group-hover/reactivo:text-white transition-colors uppercase italic"><?= $r ?></span>
                        <div class="flex-1 space-y-2">
                            <label class="text-[9px] font-black text-slate-400 uppercase tracking-widest">Planteamiento de la Pregunta</label>
                            <input name="pregunta_<?= $r ?>" value="<?= htmlspecialchars($p['pregunta'] ?? '') ?>" 
                                placeholder="Plantee la pregunta aquí..."
                                class="w-full bg-transparent border-b-2 border-slate-100 pb-3 text-sm font-bold text-slate-800 outline-none focus:border-emerald-500 transition-colors" <?= $bloqueado?'disabled':'' ?>>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 gap-4 ml-14">
                        <?php foreach(['a','b','c','d'] as $op): ?>
                        <div class="flex items-center gap-4 bg-slate-50 p-3 rounded-2xl border border-transparent group-hover/reactivo:bg-white group-hover/reactivo:border-slate-100 transition-all">
                            <span class="text-[11px] font-black text-slate-300 uppercase w-5"><?= $op ?>)</span>
                            <input name="<?= $op ?>_<?= $r ?>" value="<?= htmlspecialchars($p['opcion_'.$op] ?? '') ?>" 
                                class="flex-1 bg-transparent text-xs font-bold text-slate-600 outline-none" <?= $bloqueado?'disabled':'' ?>>
                        </div>
                        <?php endforeach; ?>
                    </div>

                    <div class="mt-8 flex justify-end items-center gap-5 pt-6 border-t border-slate-50 ml-14">
                        <label class="text-[10px] font-black text-emerald-600 uppercase italic tracking-widest">Respuesta Correcta:</label>
                        <select name="correcta_<?= $r ?>" class="bg-emerald-50 text-emerald-700 font-black text-xs p-3 px-6 rounded-2xl border border-emerald-100 outline-none hover:bg-emerald-100 transition-colors" <?= $bloqueado?'disabled':'' ?>>
                            <?php foreach(['A','B','C','D'] as $opt): ?>
                                <option value="<?= $opt ?>" <?= ($p['respuesta_correcta'] ?? '') == $opt ? 'selected' : '' ?>><?= $opt ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <?php endfor; ?>
            </div>
        </section>

        <!-- ACCIONES FINALES -->
        <?php if(!$bloqueado): ?>
        <div class="py-20 text-center bg-white rounded-[5rem] border-2 border-dashed border-slate-200 shadow-inner group">
            <div class="max-w-md mx-auto space-y-8">
                <div class="w-24 h-24 bg-purple-100 text-purple-600 rounded-full flex items-center justify-center mx-auto mb-6 group-hover:scale-110 transition-transform duration-500 shadow-xl shadow-purple-50">
                    <svg class="w-12 h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"></path></svg>
                </div>
                <h3 class="text-2xl font-black text-slate-800 uppercase tracking-tight">¿Confirmar Planeación?</h3>
                <p class="text-[11px] font-bold text-slate-400 uppercase tracking-[0.2em] leading-loose px-12">
                    Al registrar, la información será enviada al módulo de revisión académica para su aprobación definitiva.
                </p>
                <button name="guardar_unidad" class="w-full py-7 bg-purple-600 text-white rounded-[2.5rem] font-black uppercase tracking-[0.5em] text-xs shadow-2xl hover:bg-purple-700 hover:scale-[1.03] active:scale-95 transition-all duration-300">
                    Registrar Planeación Oficial
                </button>
            </div>
        </div>
        <?php endif; ?>

    </form>

    <footer class="mt-24 pb-12 text-center border-t border-slate-200 pt-12">
        <p class="text-[10px] font-black text-slate-300 uppercase tracking-[0.6em]">Secretaría Académica // Dirección de Gestión de Calidad Universitaria</p>
    </footer>
</div>

<style>
@keyframes slide-up { 
    from { opacity: 0; transform: translateY(40px); } 
    to { opacity: 1; transform: translateY(0); } 
}
.animate-fade-in { animation: slide-up 1s cubic-bezier(0.2, 0.8, 0.2, 1); }
input[disabled], select[disabled], textarea[disabled] { 
    cursor: not-allowed; 
    opacity: 0.65; 
    background-color: #f8fafc !important; 
    border-color: #e2e8f0 !important;
}
input:focus, select:focus, textarea:focus {
    transform: translateY(-2px);
}
</style>