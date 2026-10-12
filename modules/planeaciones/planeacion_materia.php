<?php
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/planeaciones_service.php';

validarPermiso('ver_planeaciones');

$puedeEditar = ($_SESSION['rol_slug'] ?? '') === 'docente' && (tienePermiso('editar_planeaciones') || tienePermiso('crear_planeaciones'));

$asignacion_id = isset($_GET['asignacion_id'])
    ? (int) $_GET['asignacion_id']
    : (isset($_GET['asignacion']) ? (int) $_GET['asignacion'] : 0);

if($asignacion_id <= 0){ require __DIR__.'/listado_planeaciones.php'; return; }
/* =========================
   OBTENER DATOS DE MATERIA
========================= */
$stmt = $pdo->prepare("
    SELECT 
        ad.id,
        m.nombre AS materia,
        g.nombre AS grupo,
        p.nombre AS periodo,
        CONCAT(u.nombres, ' ', u.apellido_paterno, ' ', u.apellido_materno) AS docente
    FROM asignaciones_docentes ad
    INNER JOIN materias m 
        ON m.id = ad.materia_id
    INNER JOIN grupos g 
        ON g.id = ad.grupo_id
    INNER JOIN periodos p 
        ON p.id = ad.periodo_id
    INNER JOIN docentes d
        ON d.id = ad.docente_id
    INNER JOIN usuarios u
        ON u.id = d.usuario_id
    WHERE ad.id = ?
    LIMIT 1
");

$stmt->execute([$asignacion_id]);
$asignacion = $stmt->fetch(PDO::FETCH_ASSOC);

if(!$asignacion){
    echo "
    <div class='bg-red-50 border border-red-200 text-red-700 p-6 rounded-2xl font-bold'>
        Asignación no encontrada.
    </div>";
    exit;
}

/* =========================
   GUARDAR / ACTUALIZAR PLANEACIÓN
========================= */
if($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['guardar'])){

    if(!$puedeEditar){
        echo "
        <script>
            alert('No tienes permiso para editar planeaciones.');
            window.location.href='?modulo=planeacion_materia&asignacion_id={$asignacion_id}';
        </script>";
        exit;
    }

    $tema_id = isset($_POST['tema_id']) ? (int) $_POST['tema_id'] : 0;
    $subtemas = trim($_POST['subtemas'] ?? '');
    $aprendizaje = trim($_POST['aprendizaje'] ?? '');
    $ensenanza = trim($_POST['ensenanza'] ?? '');
    $competencias = trim($_POST['competencias'] ?? '');
    $horas = trim($_POST['horas'] ?? '');

    if($tema_id <= 0){
        echo "
        <script>
            alert('Tema no válido.');
            window.location.href='?modulo=planeacion_materia&asignacion_id={$asignacion_id}';
        </script>";
        exit;
    }

    try{

        cufaValidarToken();
        $pdo->beginTransaction();
        cufaEditarPlan($pdo,'temas_materia',$tema_id,$asignacion_id);

        /* Validar que el tema pertenezca a la asignación */
        $stmtTema = $pdo->prepare("
            SELECT id
            FROM temas_materia
            WHERE id = ?
            AND asignacion_id = ?
            LIMIT 1
        ");
        $stmtTema->execute([$tema_id, $asignacion_id]);

        if(!$stmtTema->fetch()){
            throw new Exception('El tema no pertenece a esta asignación.');
        }

        $check = $pdo->prepare("
            SELECT id
            FROM planeacion_docente
            WHERE tema_id = ?
            LIMIT 1
        ");

        $check->execute([$tema_id]);
        $planeacionExistente = $check->fetch(PDO::FETCH_ASSOC);

        if($planeacionExistente){
            cufaEditarPlan($pdo,'planeacion_docente',(int)$planeacionExistente['id'],$asignacion_id);

            $stmt = $pdo->prepare("
                UPDATE planeacion_docente
                SET 
                    subtemas = ?,
                    actividades_aprendizaje = ?,
                    actividades_ensenanza = ?,
                    competencias_genericas = ?,
                    horas = ?
                WHERE tema_id = ?
            ");

            $stmt->execute([
                $subtemas,
                $aprendizaje,
                $ensenanza,
                $competencias,
                $horas,
                $tema_id
            ]);

        }else{

            $stmt = $pdo->prepare("
                INSERT INTO planeacion_docente (
                    tema_id,
                    subtemas,
                    actividades_aprendizaje,
                    actividades_ensenanza,
                    competencias_genericas,
                    horas
                ) VALUES (
                    ?,?,?,?,?,?
                )
            ");

            $stmt->execute([
                $tema_id,
                $subtemas,
                $aprendizaje,
                $ensenanza,
                $competencias,
                $horas
            ]);
        }

        $registro_id = $planeacionExistente ? (int)$planeacionExistente['id'] : (int)$pdo->lastInsertId();
        cufaRegistroRevision($pdo,'planeacion_docente',$registro_id,'pendiente','Corrección guardada por el docente.',(int)$_SESSION['user_id']);
        $pdo->commit();

        echo "
        <script>
            alert('Planeación guardada correctamente');
            window.location.href='?modulo=planeacion_materia&asignacion_id={$asignacion_id}';
        </script>";
        exit;

    }catch(Exception $e){

        if($pdo->inTransaction()){
            $pdo->rollBack();
        }

        $msg = htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');

        echo "
        <script>
            alert('Error: {$msg}');
            window.location.href='?modulo=planeacion_materia&asignacion_id={$asignacion_id}';
        </script>";
        exit;
    }
}

/* =========================
   OBTENER TEMAS
========================= */
$stmt = $pdo->prepare("
    SELECT 
        t.id,
        t.parcial,
        t.orden,
        t.tema,
        t.subtemas AS subtemas_originales,
        t.objetivo_unidad,
        t.descripcion,
        t.estado,
        t.nombre_unidad,
        p.id planeacion_id,
        p.subtemas,
        p.actividades_aprendizaje,
        p.actividades_ensenanza,
        p.competencias_genericas,
        p.horas
    FROM temas_materia t
    LEFT JOIN planeacion_docente p 
        ON p.tema_id = t.id
    WHERE t.asignacion_id = ?
    ORDER BY 
        t.parcial ASC,
        t.orden ASC,
        t.id ASC
");

$stmt->execute([$asignacion_id]);
$temas = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="max-w-7xl mx-auto p-6 bg-slate-50 min-h-screen animate-fade-in">

    <div class="mb-10 flex flex-col md:flex-row md:items-end md:justify-between gap-6">
        <div>
            <span class="bg-indigo-100 text-indigo-700 px-4 py-2 rounded-full text-[10px] font-black uppercase tracking-[0.25em]">
                Planeación Docente
            </span>

            <h1 class="text-5xl font-black italic uppercase tracking-tighter text-slate-800 leading-none mt-5">
                Planeación Didáctica
            </h1>

            <p class="text-slate-500 font-semibold mt-3">
                <?= htmlspecialchars($asignacion['materia']) ?>
                · Grupo <?= htmlspecialchars($asignacion['grupo']) ?>
                · <?= htmlspecialchars($asignacion['periodo']) ?>
            </p>

            <p class="text-[10px] text-slate-400 font-black uppercase tracking-widest mt-2">
                Docente: <?= htmlspecialchars($asignacion['docente']) ?>
            </p>
        </div>

        <a href="?modulo=ver_materia&asignacion_id=<?= $asignacion_id ?>"
           class="bg-slate-900 hover:bg-indigo-600 text-white px-6 py-4 rounded-2xl font-black uppercase text-[10px] tracking-widest transition-all">
            ← Volver
        </a>
    </div>

    <?php if(count($temas) > 0): ?>

        <div class="bg-white shadow-xl shadow-slate-200/50 rounded-[2.5rem] border border-slate-100 overflow-hidden">

            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">

                    <thead class="bg-slate-900 text-white">
                        <tr>
                            <th class="p-5 text-left text-[10px] font-black uppercase tracking-widest">Unidad</th>
                            <th class="p-5 text-left text-[10px] font-black uppercase tracking-widest">Tema</th>
                            <th class="p-5 text-left text-[10px] font-black uppercase tracking-widest">Subtemas</th>
                            <th class="p-5 text-left text-[10px] font-black uppercase tracking-widest">Aprendizaje</th>
                            <th class="p-5 text-left text-[10px] font-black uppercase tracking-widest">Enseñanza</th>
                            <th class="p-5 text-left text-[10px] font-black uppercase tracking-widest">Competencias</th>
                            <th class="p-5 text-center text-[10px] font-black uppercase tracking-widest">Horas</th>
                            <th class="p-5 text-center text-[10px] font-black uppercase tracking-widest">Acción</th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-slate-100">
                        <?php foreach($temas as $t): ?>
                            <tr class="hover:bg-slate-50 transition-colors">

                                <td class="p-5 align-top">
                                    <span class="bg-indigo-50 text-indigo-600 px-3 py-1 rounded-full text-[10px] font-black uppercase">
                                        Unidad <?= (int)$t['parcial'] ?>
                                    </span>

                                    <?php if(!empty($t['nombre_unidad'])): ?>
                                        <p class="text-[10px] text-slate-400 font-bold mt-2">
                                            <?= htmlspecialchars($t['nombre_unidad']) ?>
                                        </p>
                                    <?php endif; ?>
                                </td>

                                <td class="p-5 align-top">
                                    <strong class="block text-slate-800 font-black">
                                        <?= htmlspecialchars($t['tema']) ?>
                                    </strong>

                                    <?php if(!empty($t['objetivo_unidad'])): ?>
                                        <span class="block text-[10px] text-slate-400 font-semibold mt-2">
                                            <?= htmlspecialchars($t['objetivo_unidad']) ?>
                                        </span>
                                    <?php endif; ?>
                                </td>

                                <td class="p-5 align-top text-slate-500 font-medium max-w-xs">
                                    <?= nl2br(htmlspecialchars($t['subtemas'] ?: ($t['subtemas_originales'] ?? ''))) ?>
                                </td>

                                <td class="p-5 align-top text-slate-500 font-medium max-w-xs">
                                    <?= nl2br(htmlspecialchars($t['actividades_aprendizaje'] ?? '')) ?>
                                </td>

                                <td class="p-5 align-top text-slate-500 font-medium max-w-xs">
                                    <?= nl2br(htmlspecialchars($t['actividades_ensenanza'] ?? '')) ?>
                                </td>

                                <td class="p-5 align-top text-slate-500 font-medium max-w-xs">
                                    <?= nl2br(htmlspecialchars($t['competencias_genericas'] ?? '')) ?>
                                </td>

                                <td class="p-5 align-top text-center">
                                    <span class="text-slate-700 font-black">
                                        <?= htmlspecialchars($t['horas'] ?? '') ?>
                                    </span>
                                </td>

                                <td class="p-5 align-top text-center">
                                    <?php if($puedeEditar): ?>
                                        <button
                                            type="button"
                                            onclick="abrirModal(<?= (int)$t['id'] ?>)"
                                            class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-3 rounded-2xl text-[10px] font-black uppercase tracking-widest transition-all">
                                            Editar
                                        </button>
                                    <?php else: ?>
                                        <span class="text-[10px] text-slate-300 font-black uppercase">
                                            Solo lectura
                                        </span>
                                    <?php endif; ?>
                                </td>

                            </tr>
                        <?php endforeach; ?>
                    </tbody>

                </table>
            </div>
        </div>

    <?php else: ?>

        <div class="bg-white rounded-[2.5rem] border border-slate-100 p-16 text-center shadow-sm">
            <div class="text-6xl mb-4">📘</div>

            <h2 class="text-2xl font-black text-slate-800 uppercase mb-3">
                Sin temas registrados
            </h2>

            <p class="text-slate-400 font-bold max-w-xl mx-auto">
                Esta asignación todavía no tiene unidades o temas definidos.
            </p>
        </div>

    <?php endif; ?>

</div>

<?php foreach($temas as $t): ?>
    <div
        id="modal<?= (int)$t['id'] ?>"
        class="fixed inset-0 bg-slate-900/70 backdrop-blur-sm hidden items-center justify-center z-50 p-4"
    >
        <div class="bg-white p-8 rounded-[2.5rem] w-full max-w-3xl max-h-[90vh] overflow-y-auto shadow-2xl">

            <div class="mb-6">
                <h2 class="text-3xl font-black text-slate-800 uppercase tracking-tight">
                    Editar Planeación
                </h2>

                <p class="text-purple-600 font-black text-[10px] uppercase tracking-widest mt-2">
                    Unidad <?= (int)$t['parcial'] ?> · <?= htmlspecialchars($t['tema']) ?>
                </p>
            </div>

            <form method="POST" class="space-y-5">

                <?= cufaCampoToken() ?>
<input type="hidden" name="tema_id" value="<?= (int)$t['id'] ?>">

                <div>
                    <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest">
                        Subtemas
                    </label>
                    <textarea name="subtemas" rows="3" class="w-full mt-2 border-2 border-slate-100 bg-slate-50 p-4 rounded-2xl font-semibold outline-none focus:border-purple-500"><?= htmlspecialchars($t['subtemas'] ?: ($t['subtemas_originales'] ?? '')) ?></textarea>
                </div>

                <div>
                    <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest">
                        Actividades de Aprendizaje
                    </label>
                    <textarea name="aprendizaje" rows="3" class="w-full mt-2 border-2 border-slate-100 bg-slate-50 p-4 rounded-2xl font-semibold outline-none focus:border-purple-500"><?= htmlspecialchars($t['actividades_aprendizaje'] ?? '') ?></textarea>
                </div>

                <div>
                    <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest">
                        Actividades de Enseñanza
                    </label>
                    <textarea name="ensenanza" rows="3" class="w-full mt-2 border-2 border-slate-100 bg-slate-50 p-4 rounded-2xl font-semibold outline-none focus:border-purple-500"><?= htmlspecialchars($t['actividades_ensenanza'] ?? '') ?></textarea>
                </div>

                <div>
                    <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest">
                        Competencias Genéricas
                    </label>
                    <textarea name="competencias" rows="3" class="w-full mt-2 border-2 border-slate-100 bg-slate-50 p-4 rounded-2xl font-semibold outline-none focus:border-purple-500"><?= htmlspecialchars($t['competencias_genericas'] ?? '') ?></textarea>
                </div>

                <div>
                    <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest">
                        Horas
                    </label>
                    <input
                        type="text"
                        name="horas"
                        value="<?= htmlspecialchars($t['horas'] ?? '') ?>"
                        class="w-full mt-2 border-2 border-slate-100 bg-slate-50 p-4 rounded-2xl font-black outline-none focus:border-purple-500"
                    >
                </div>

                <div class="flex justify-end gap-3 pt-4">
                    <button
                        type="button"
                        onclick="cerrarModal(<?= (int)$t['id'] ?>)"
                        class="bg-slate-200 hover:bg-slate-300 text-slate-700 px-6 py-3 rounded-2xl font-black uppercase text-[10px] tracking-widest">
                        Cancelar
                    </button>

                    <button
                        type="submit"
                        name="guardar"
                        class="bg-emerald-600 hover:bg-emerald-700 text-white px-6 py-3 rounded-2xl font-black uppercase text-[10px] tracking-widest">
                        Guardar
                    </button>
                </div>

            </form>

        </div>
    </div>
<?php endforeach; ?>

<script>
function abrirModal(id){
    const modal = document.getElementById('modal' + id);
    if(modal){
        modal.classList.remove('hidden');
        modal.classList.add('flex');
    }
}

function cerrarModal(id){
    const modal = document.getElementById('modal' + id);
    if(modal){
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    }
}
</script>

<style>
@keyframes slide-up {
    from {
        opacity: 0;
        transform: translateY(20px);
    }

    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.animate-fade-in {
    animation: slide-up 0.6s cubic-bezier(0.16, 1, 0.3, 1);
}
</style>
<?php foreach(cufaPlanes($pdo,['asignacion_id'=>$asignacion_id]) as $revision): if($revision['origen']==='planeacion_docente'): ?><div class="bg-white rounded-2xl p-4 mt-3"><strong><?= cufaEsc($revision['tema']) ?> — <?= cufaEsc($revision['estado']) ?></strong><p><?= cufaEsc($revision['observaciones']) ?></p><a href="<?= cufaEsc(cufaPanel('detalle_planeacion',['origen'=>$revision['origen'],'id'=>$revision['id']])) ?>">Consultar revisión</a></div><?php endif; endforeach; ?>
