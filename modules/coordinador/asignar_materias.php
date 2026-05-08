<?php
require_once __DIR__ . '/../../includes/db.php';

$mensaje = "";
$periodo_id = $_GET['periodo_id'] ?? null;

/* =========================
   LÓGICA DE INSERCIÓN
========================= */

if($_SERVER['REQUEST_METHOD']=="POST"){
    $docente_id = $_POST['docente_id'] ?? null;
    $grupo_id   = $_POST['grupo_id'] ?? null;
    $materia_id = $_POST['materia_id'] ?? null;
    $periodo_id = $_POST['periodo_id'] ?? null;

    if(!$docente_id || !$grupo_id || !$materia_id || !$periodo_id){
        $mensaje = ["tipo"=>"error","texto"=>"Faltan datos obligatorios"];
    }else{

        try{

            // 🔒 INICIAR TRANSACCIÓN
            $pdo->beginTransaction();

            /* =========================
               VALIDAR DUPLICADO
            ========================= */
            $stmt = $pdo->prepare("
                SELECT id FROM asignaciones_docentes 
                WHERE docente_id=? AND grupo_id=? AND materia_id=? AND periodo_id=?
            ");
            $stmt->execute([$docente_id,$grupo_id,$materia_id,$periodo_id]);

            if($stmt->fetch()){
                throw new Exception("Esta asignación ya existe");
            }

            /* =========================
               INSERTAR ASIGNACIÓN
            ========================= */
            $stmt=$pdo->prepare("
                INSERT INTO asignaciones_docentes 
                (docente_id,grupo_id,materia_id,periodo_id) 
                VALUES (?,?,?,?)
            ");
            $stmt->execute([$docente_id,$grupo_id,$materia_id,$periodo_id]);

            /* =========================
               CREAR PARCIALES
            ========================= */

            // 1. Obtener materia
            $stmt = $pdo->prepare("
                SELECT total_unidades, carrera_id 
                FROM materias 
                WHERE id = ?
            ");
            $stmt->execute([$materia_id]);
            $materia = $stmt->fetch(PDO::FETCH_ASSOC);

            if(!$materia || $materia['total_unidades'] <= 0){
                throw new Exception("La materia no tiene unidades configuradas");
            }

            $total_parciales = (int)$materia['total_unidades'];
            $carrera_id = $materia['carrera_id'];

            // 2. Obtener periodo
            $stmt = $pdo->prepare("
                SELECT fecha_inicio, fecha_fin 
                FROM periodos 
                WHERE id = ?
            ");
            $stmt->execute([$periodo_id]);
            $periodo = $stmt->fetch(PDO::FETCH_ASSOC);

            if(!$periodo || !$periodo['fecha_inicio'] || !$periodo['fecha_fin']){
                throw new Exception("El periodo no tiene fechas válidas");
            }

            $inicio = new DateTime($periodo['fecha_inicio']);
            $fin    = new DateTime($periodo['fecha_fin']);

            if($inicio >= $fin){
                throw new Exception("Fechas de periodo inválidas");
            }

            // 3. Validar si ya existen parciales
            $stmt = $pdo->prepare("
                SELECT COUNT(*) 
                FROM parciales 
                WHERE grupo_id=? AND materia_id=? AND periodo_id=?
            ");
            $stmt->execute([$grupo_id,$materia_id,$periodo_id]);

            if($stmt->fetchColumn() > 0){
                throw new Exception("Los parciales ya fueron generados");
            }

            // 4. Calcular duración
            $dias_total = $inicio->diff($fin)->days;
            $dias_por_parcial = ceil($dias_total / $total_parciales);

            // 5. Insertar parciales
            for($i = 1; $i <= $total_parciales; $i++){

                $fecha_inicio_parcial = clone $inicio;
                $fecha_inicio_parcial->modify('+' . (($i - 1) * $dias_por_parcial) . ' days');

                $fecha_fin_parcial = clone $fecha_inicio_parcial;
                $fecha_fin_parcial->modify('+' . $dias_por_parcial . ' days');

                // Ajuste final
                if($fecha_fin_parcial > $fin){
                    $fecha_fin_parcial = clone $fin;
                }

                $stmt = $pdo->prepare("
                    INSERT INTO parciales 
                    (periodo_id, carrera_id, materia_id, grupo_id, numero, fecha_inicio, fecha_fin, activo)
                    VALUES (?, ?, ?, ?, ?, ?, ?, 1)
                ");

                $stmt->execute([
                    $periodo_id,
                    $carrera_id,
                    $materia_id,
                    $grupo_id,
                    $i,
                    $fecha_inicio_parcial->format('Y-m-d'),
                    $fecha_fin_parcial->format('Y-m-d')
                ]);
            }

            // ✅ TODO OK
            $pdo->commit();
            $mensaje = ["tipo"=>"success","texto"=>"Cátedra y parciales creados correctamente"];

        }catch(Exception $e){

            // ❌ REVERSIÓN TOTAL
            $pdo->rollBack();
            $mensaje = ["tipo"=>"error","texto"=>$e->getMessage()];

        }catch(PDOException $e){

            $pdo->rollBack();
            $mensaje = ["tipo"=>"error","texto"=>"Error en la base de datos"];

        }
    }
}

/* =========================
   CONSULTAS DE DATOS
========================= */

$periodos = $pdo->query("
    SELECT id,nombre 
    FROM periodos 
    WHERE activo=1 
    ORDER BY nombre DESC
")->fetchAll();

$docentes = $pdo->query("
    SELECT d.id, CONCAT(u.nombres,' ',u.apellido_paterno,' ',u.apellido_materno) AS nombre_completo 
    FROM docentes d 
    JOIN usuarios u ON u.id=d.usuario_id 
    ORDER BY u.nombres ASC
")->fetchAll();

$materias = [];
$asignadas = [];

if($periodo_id){

    $stmt = $pdo->prepare("
        SELECT DISTINCT m.id, m.nombre, m.carrera_id
        FROM materias m
        JOIN grupos g 
            ON g.carrera_id = m.carrera_id
            AND g.periodo_id = ?
        ORDER BY m.nombre
    ");
    $stmt->execute([$periodo_id]);
    $materias = $stmt->fetchAll();

    $stmt = $pdo->prepare("
        SELECT materia_id 
        FROM asignaciones_docentes 
        WHERE periodo_id=?
    ");
    $stmt->execute([$periodo_id]);
    $asignadas = $stmt->fetchAll(PDO::FETCH_COLUMN);
}
?>

<div class="max-w-7xl mx-auto p-6 bg-slate-50 min-h-screen font-sans">

    <!-- CABECERA PROFESIONAL -->
    <div class="flex flex-col md:flex-row justify-between items-end mb-10 gap-6">
        <div>
            <h1 class="text-4xl font-black text-slate-800 uppercase italic tracking-tighter leading-none">
                Control de <span class="text-indigo-600">Asignaciones</span>
            </h1>
            <div class="flex items-center gap-2 mt-2">
                <span class="px-2 py-0.5 bg-indigo-100 text-indigo-700 text-[10px] font-black rounded-lg uppercase italic border border-indigo-200">Admin Académico</span>
                <p class="text-xs text-slate-400 font-bold uppercase tracking-widest italic font-sans">Vincular asignaturas y docentes por periodo</p>
            </div>
        </div>

        <!-- SELECTOR DE PERIODO (Diseño Compacto) -->
        <div class="w-full md:w-72">
            <form method="GET" id="formPeriodo">
                <input type="hidden" name="modulo" value="asignar_materias">
                <label class="text-[9px] font-black text-slate-400 uppercase ml-1 tracking-[0.1em]">Periodo de Gestión</label>
                <select name="periodo_id" onchange="this.form.submit()" 
                    class="w-full p-3 bg-white border-2 border-slate-200 rounded-2xl font-bold text-slate-700 focus:border-indigo-500 outline-none shadow-sm transition-all cursor-pointer">
                    <option value="">Seleccionar periodo activo...</option>
                    <?php foreach($periodos as $p): ?>
                        <option value="<?= $p['id'] ?>" <?= ($periodo_id==$p['id'])?'selected':'' ?>>
                            <?= $p['nombre'] ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </form>
        </div>
    </div>

    <!-- MENSAJES DINÁMICOS -->
    <?php if($mensaje): ?>
        <div class="mb-8 animate-fade-in">
            <div class="<?= $mensaje['tipo'] == 'success' ? 'bg-emerald-50 border-emerald-200 text-emerald-700' : 'bg-red-50 border-red-200 text-red-700' ?> p-4 rounded-[1.5rem] border flex items-center gap-3 shadow-sm">
                <div class="w-2 h-2 rounded-full <?= $mensaje['tipo'] == 'success' ? 'bg-emerald-500' : 'bg-red-500' ?> animate-pulse"></div>
                <span class="text-[11px] font-black uppercase tracking-widest italic"><?= $mensaje['texto'] ?></span>
            </div>
        </div>
    <?php endif; ?>

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
        
        <!-- PANEL DE ACCIÓN (Izquierda) -->
        <div class="lg:col-span-4">
            <?php if($periodo_id): ?>
                <div class="bg-indigo-900 p-8 rounded-[2.5rem] shadow-xl border border-indigo-800 text-white sticky top-6">
                    <div class="flex items-center gap-2 mb-8 border-b border-indigo-800 pb-4">
                        <div class="w-2 h-2 bg-purple-400 rounded-full"></div>
                        <h3 class="text-[11px] font-black uppercase text-indigo-300 tracking-widest italic">Registrar Asignación</h3>
                    </div>
                    
                    <form method="POST" class="space-y-6">
                        <input type="hidden" name="periodo_id" value="<?= $periodo_id ?>">

                        <div class="space-y-1.5">
                            <label class="text-[9px] font-black text-indigo-300 uppercase ml-1 tracking-widest">Docente</label>
                            <select name="docente_id" required class="w-full p-4 bg-indigo-800 border-none rounded-2xl font-bold text-white text-xs outline-none focus:ring-2 focus:ring-purple-400 transition-all cursor-pointer">
                                <option value="">Seleccionar docente...</option>
                                <?php foreach($docentes as $d): ?>
                                    <option value="<?= $d['id'] ?>"><?= $d['nombre_completo'] ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="space-y-1.5">
                            <label class="text-[9px] font-black text-indigo-300 uppercase ml-1 tracking-widest">Asignatura a Asignar</label>
                            <select name="materia_id" id="materiaSelect" required class="w-full p-4 bg-indigo-800 border-none rounded-2xl font-bold text-white text-xs outline-none focus:ring-2 focus:ring-purple-400 transition-all cursor-pointer">
                                <option value="">Seleccionar asignatura...</option>
                                <?php foreach($materias as $m): 
                                    $status = in_array($m['id'],$asignadas) ? "● " : "○ ";
                                ?>
                                    <option value="<?= $m['id'] ?>" data-carrera="<?= $m['carrera_id'] ?>">
                                        <?= $status ?><?= $m['nombre'] ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="space-y-1.5">
                            <label class="text-[9px] font-black text-indigo-300 uppercase ml-1 tracking-widest">Grupo de Asignado</label>
                            <select name="grupo_id" id="grupoSelect" required class="w-full p-4 bg-indigo-800 border-none rounded-2xl font-bold text-white text-xs outline-none focus:ring-2 focus:ring-purple-400 transition-all cursor-pointer">
                                <option value="">Esperando asignatura...</option>
                            </select>
                        </div>

                        <button class="w-full py-5 bg-purple-500 text-white rounded-[2rem] font-black uppercase tracking-[0.2em] shadow-lg hover:bg-white hover:text-indigo-900 transition-all transform hover:-translate-y-1 mt-6 text-[11px]">
                            Asignar Docente
                        </button>
                    </form>
                </div>
            <?php else: ?>
                <div class="bg-white p-12 rounded-[2.5rem] border-2 border-dashed border-slate-200 text-center">
                    <p class="text-[10px] font-black text-slate-400 uppercase italic tracking-widest leading-relaxed">
                        Por favor, selecciona un <span class="text-indigo-500">periodo académico</span> para habilitar el formulario de asignación.
                    </p>
                </div>
            <?php endif; ?>
        </div>

        <!-- LISTADO DE ASIGNACIONES (Derecha) -->
        <div class="lg:col-span-8">
            <div class="bg-white rounded-[2.5rem] shadow-sm border border-slate-100 overflow-hidden">
                <div class="p-8 border-b border-slate-50 flex justify-between items-center bg-slate-50/30">
                    <div>
                        <h2 class="text-xl font-black text-slate-800 uppercase italic leading-none tracking-tighter">Registros de Asignaciones</h2>
                        <p class="text-[10px] text-slate-400 font-bold uppercase tracking-widest mt-1 italic">Historial de Asignaciones actuales</p>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full border-collapse">
                        <thead>
                            <tr class="bg-slate-50">
                                <th class="p-5 text-left text-[10px] font-black text-slate-500 uppercase tracking-widest italic border-b border-slate-100">Docente Asignado</th>
                                <th class="p-5 text-left text-[10px] font-black text-slate-500 uppercase tracking-widest italic border-b border-slate-100">Asignatura</th>
                                <th class="p-5 text-center text-[10px] font-black text-slate-500 uppercase tracking-widest italic border-b border-slate-100">Grupo</th>
                                <th class="p-5 text-right text-[10px] font-black text-slate-500 uppercase tracking-widest italic border-b border-slate-100">Periodo</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-50">
                            <?php
                            $stmt = $pdo->prepare("
                                SELECT
                                    CONCAT(u.nombres,' ',u.apellido_paterno,' ',u.apellido_materno) AS docente,
                                    m.nombre AS materia,
                                    g.nombre AS grupo,
                                    p.nombre AS periodo
                                FROM asignaciones_docentes ad
                                JOIN docentes d ON d.id=ad.docente_id
                                JOIN usuarios u ON u.id=d.usuario_id
                                JOIN materias m ON m.id=ad.materia_id
                                JOIN grupos g ON g.id=ad.grupo_id
                                JOIN periodos p ON p.id=ad.periodo_id
                                WHERE ad.periodo_id = ?
                                ORDER BY g.nombre ASC
                            ");
                            $stmt->execute([$periodo_id]);

                            while($a=$stmt->fetch()): ?>
                                <tr class="hover:bg-indigo-50/30 transition-colors group">
                                    <td class="p-5 text-[11px] font-bold text-slate-700 uppercase italic"><?= $a['docente'] ?></td>
                                    <td class="p-5">
                                        <span class="text-[11px] font-black text-indigo-900 uppercase italic group-hover:text-purple-600 transition-colors"><?= $a['materia'] ?></span>
                                    </td>
                                    <td class="p-5 text-center">
                                        <span class="px-3 py-1 bg-slate-100 text-slate-600 rounded-lg text-[10px] font-black border border-slate-200">
                                            <?= $a['grupo'] ?>
                                        </span>
                                    </td>
                                    <td class="p-5 text-right text-[10px] font-black text-slate-400 italic font-mono"><?= $a['periodo'] ?></td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
@keyframes fade-in { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
.animate-fade-in { animation: fade-in 0.4s ease-out forwards; }
</style>

<script>
document.getElementById("materiaSelect").addEventListener("change", function(){
    let carrera_id = this.options[this.selectedIndex].getAttribute("data-carrera");
    let periodo_id = "<?= $periodo_id ?? '' ?>";
    let select = document.getElementById("grupoSelect");

    if(!carrera_id || !periodo_id){
        select.innerHTML = "<option value=''>Seleccionar grupo</option>";
        return;
    }

    select.innerHTML = "<option>Procesando grupos...</option>";

    fetch(`/sistema_academico/modules/coordinador/get_grupos_por_carrera.php?carrera_id=${carrera_id}&periodo_id=${periodo_id}`)
    .then(res => res.json())
    .then(data => {
        select.innerHTML = "<option value=''>Seleccionar grupo</option>";
        if(data.length === 0){
            select.innerHTML = "<option value=''>No hay grupos compatibles</option>";
            return;
        }
        data.forEach(g => {
            let opt = document.createElement("option");
            opt.value = g.id;
            opt.textContent = g.nombre;
            select.appendChild(opt);
        });
    })
    .catch(err => {
        console.error(err);
        select.innerHTML = "<option>Error en la conexión</option>";
    });
});
</script>