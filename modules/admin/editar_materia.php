<?php
require_once __DIR__ . '/../../includes/db.php';

$id = $_GET['id'] ?? null;

if(!$id){
    echo "Materia no especificada";
    exit;
}

/* =========================
   OBTENER DATOS
========================= */
$stmt = $pdo->prepare("SELECT * FROM materias WHERE id = ?");
$stmt->execute([$id]);
$materia = $stmt->fetch();

if(!$materia){
    echo "Materia no encontrada";
    exit;
}

/* =========================
   CARRERAS DESDE EL SISTEMA EXTERNO
========================= */
$apiUrl = "https://sistema.cufa.edu.mx/api/carreras";
$apiKey = "H6z0U6FpnMPsgfCAe7ijkiXiL22YEE+ybjRtiZtDKmQ=";

$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => $apiUrl,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => ["X-API-Key: $apiKey"],
]);

$response = curl_exec($ch);
if (curl_errno($ch)) { die("Error en API: " . curl_error($ch)); }
curl_close($ch);

$data = json_decode($response, true);
$carreras = $data['data'] ?? [];
$mapCarreras = [];
foreach ($carreras as $c) { $mapCarreras[$c['id']] = $c['nombre']; }

$todas_materias = $pdo->query("SELECT id, nombre, clave FROM materias ORDER BY nombre ASC")->fetchAll();

/* SUBASIGNATURAS ACTUALES */
$stmt = $pdo->prepare("
SELECT s.id, s.nombre
FROM materia_subasignatura ms
JOIN subasignaturas s ON s.id = ms.subasignatura_id
WHERE ms.materia_id = ?
");
$stmt->execute([$id]);
$subas_asignadas = $stmt->fetchAll();

/* =========================
   ACTUALIZAR (Lógica intacta)
========================= */
if($_SERVER['REQUEST_METHOD'] === 'POST'){
    if(empty($_POST['carrera_id']) || empty($_POST['grado']) || empty($_POST['clave']) || empty($_POST['nombre']) || empty($_POST['total_unidades'])){
        echo "<script>alert('Faltan datos obligatorios');</script>";
        return;
    }

    $pdo->beginTransaction();
    try{
        $stmt = $pdo->prepare("
        UPDATE materias SET
            carrera_id = ?, carrera_nombre = ?, grado = ?, clave = ?, nombre = ?, nombre_corto = ?,
            aula = ?, creditos = ?, tipo = ?, seriacion_id = ?, es_opcional = ?,
            maneja_niveles = ?, area_formacion = ?, horas_docente = ?, horas_independientes = ?,
            total_unidades = ?
        WHERE id = ?
        ");

        $carrera_nombre = $mapCarreras[$_POST['carrera_id']] ?? 'Desconocida';

        $stmt->execute([
            $_POST['carrera_id'], $carrera_nombre, $_POST['grado'], $_POST['clave'],
            $_POST['nombre'], $_POST['nombre_corto'], $_POST['aula'], $_POST['creditos'],
            $_POST['tipo_modalidad'], $_POST['seriacion_id'] ?: null, $_POST['es_opcional'],
            $_POST['maneja_niveles'], $_POST['area_formacion'], $_POST['horas_docente'],
            $_POST['horas_independientes'], $_POST['total_unidades'], $id
        ]);

        $pdo->prepare("DELETE FROM materia_subasignatura WHERE materia_id=?")->execute([$id]);

        if(!empty($_POST['maneja_subas']) && !empty($_POST['subasignaturas_ids'])){
            $stmtSub = $pdo->prepare("INSERT INTO materia_subasignatura (materia_id, subasignatura_id) VALUES (?,?)");
            foreach(array_unique($_POST['subasignaturas_ids']) as $sub){
                $stmtSub->execute([$id, $sub]);
            }
        }

        $pdo->commit();
        echo "<script>alert('Materia actualizada correctamente'); window.location.href='/sistema_academico/dashboards/admin_dashboard.php?modulo=editar_materia&id=$id';</script>";
        exit;
    }catch(Exception $e){
        $pdo->rollBack();
        echo "<script>alert('Error al actualizar');</script>";
    }
}
?>

<div class="max-w-7xl mx-auto p-6 bg-slate-50 min-h-screen font-sans">

    <!-- CABECERA -->
    <div class="flex flex-col md:flex-row justify-between items-start mb-10 gap-4">
        <div class="flex items-center gap-6">
            <a href="/sistema_academico/dashboards/admin_dashboard.php?modulo=lista_asignaturas"
               class="group bg-white p-4 rounded-2xl shadow-sm border border-slate-200 text-slate-400 hover:text-purple-600 hover:border-purple-200 transition-all">
               <svg class="w-6 h-6 transition-transform group-hover:-translate-x-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M10 19l-7-7m0 0l7-7m-7 7h18" stroke-width="3"></path></svg>
            </a>
            <div>
                <h1 class="text-4xl font-black text-slate-800 uppercase italic tracking-tighter leading-none">Editar Asignatura</h1>
                <div class="flex items-center gap-2 mt-2">
                    <span class="px-2 py-0.5 bg-purple-100 text-purple-700 text-[10px] font-black rounded-lg uppercase italic border border-purple-200">Panel de Control</span>
                    <p class="text-xs text-slate-400 font-bold uppercase tracking-widest"><?= $materia['clave'] ?></p>
                </div>
            </div>
        </div>
    </div>

    <form method="POST" class="grid grid-cols-1 lg:grid-cols-3 gap-8">

        <!-- COLUMNA IZQUIERDA -->
        <div class="lg:col-span-2 space-y-8">
            
            <!-- BLOQUE INFORMACIÓN -->
            <div class="bg-white p-8 rounded-[2.5rem] shadow-sm border border-slate-100">
                <div class="flex items-center gap-2 mb-6 border-b border-slate-50 pb-4">
                    <div class="w-2 h-2 bg-purple-500 rounded-full"></div>
                    <h3 class="text-[11px] font-black uppercase text-slate-500 tracking-widest italic">Información Académica Principal</h3>
                </div>
                
                <div class="space-y-6">
                    <div>
                        <label class="text-[10px] font-black text-slate-400 uppercase ml-1">Nombre de la Asignatura</label>
                        <input name="nombre" value="<?= $materia['nombre'] ?>" class="w-full p-4 bg-slate-50 border-2 border-slate-100 rounded-2xl font-bold text-slate-700 focus:border-purple-500 outline-none transition-all">
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="text-[10px] font-black text-slate-400 uppercase ml-1">Clave de Registro</label>
                            <input name="clave" value="<?= $materia['clave'] ?>" class="w-full p-4 bg-slate-50 border-2 border-slate-100 rounded-2xl font-bold text-slate-700 focus:border-purple-500 outline-none">
                        </div>
                        <div>
                            <label class="text-[10px] font-black text-slate-400 uppercase ml-1">Nombre Corto</label>
                            <input name="nombre_corto" value="<?= $materia['nombre_corto'] ?>" class="w-full p-4 bg-slate-50 border-2 border-slate-100 rounded-2xl font-bold text-slate-700 focus:border-purple-500 outline-none">
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 border-t border-slate-50 pt-6">
                        <div>
                            <label class="text-[10px] font-black text-slate-400 uppercase ml-1">Licenciatura</label>
                            <select name="carrera_id" class="w-full p-4 bg-slate-50 border-2 border-slate-100 rounded-2xl font-bold text-slate-700 focus:border-purple-500 outline-none appearance-none cursor-pointer">
                                <?php foreach($carreras as $c): ?>
                                    <option value="<?= $c['id'] ?>" data-nombre="<?= $c['nombre'] ?>" <?= $c['id']==$materia['carrera_id']?'selected':'' ?>>
                                        <?= $c['nombre'] ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <input type="hidden" name="carrera_nombre" id="carreraNombre">
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="text-[10px] font-black text-slate-400 uppercase ml-1">Grado</label>
                                <input type="number" name="grado" value="<?= $materia['grado'] ?>" class="w-full p-4 bg-slate-50 border-2 border-slate-100 rounded-2xl font-bold text-slate-700 outline-none">
                            </div>
                            <div>
                                <label class="text-[10px] font-black text-slate-400 uppercase ml-1">Parciales</label>
                                <input type="number" name="total_unidades" value="<?= $materia['total_unidades'] ?>" class="w-full p-4 bg-yellow-50 border-2 border-yellow-100 rounded-2xl font-bold text-yellow-700 outline-none">
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- BLOQUE SUBASIGNATURAS (REDISEÑADO SIN NEGRO) -->
            <div class="bg-indigo-50/50 p-8 rounded-[2.5rem] border border-indigo-100">
                <div class="flex items-center justify-between mb-8">
                    <div class="flex items-center gap-2">
                        <div class="w-2 h-2 bg-indigo-500 rounded-full"></div>
                        <h3 class="text-[11px] font-black uppercase text-indigo-500 tracking-widest italic">Gestión de Subasignaturas</h3>
                    </div>
                    
                    <div class="flex items-center gap-3 bg-white p-2 rounded-2xl border border-indigo-100 shadow-sm">
                        <span class="text-[9px] font-black text-slate-400 uppercase px-2">¿Maneja subasignaturas?</span>
                        <select id="manejaSubas"
                            class="p-2 bg-indigo-50 border-none rounded-xl text-indigo-700 text-[10px] font-black uppercase outline-none cursor-pointer"
                            onchange="toggleSubSelector(this.value)">
                            <option value="no">No</option>
                            <option value="si">Sí</option>
                        </select>
                        <input type="hidden" name="maneja_subas" id="inputManejaSubas" value="0">
                    </div>
                </div>

                <!-- SELECTOR DINÁMICO -->
                <div id="containerSubasignaturas" class="hidden grid grid-cols-1 md:grid-cols-2 gap-6 mb-8 animate-fade-in">
                    <div class="space-y-2">
                        <span class="text-[9px] font-black text-indigo-400 uppercase ml-2 italic">Subasignaturas disponibles</span>
                        <div id="listaCatalogo" class="h-48 overflow-y-auto custom-scrollbar p-3 rounded-2xl bg-white border border-indigo-100 shadow-inner space-y-1"></div>
                    </div>
                    <div class="space-y-2">
                        <span class="text-[9px] font-black text-emerald-500 uppercase ml-2 italic">Subasignaturas seleccionadas</span>
                        <div id="listaSeleccionadas" class="h-48 overflow-y-auto custom-scrollbar p-3 rounded-2xl bg-white border-2 border-dashed border-emerald-100 shadow-inner space-y-1"></div>
                    </div>
                </div>

                <!-- LISTADO FINAL -->
                <div class="pt-6 border-t border-indigo-100/50">
                    <span class="text-[10px] font-black text-slate-400 uppercase tracking-widest block mb-4 italic">Subasignaturas asignadas:</span>
                    <div id="listaSubas" class="grid grid-cols-1 md:grid-cols-2 gap-3">
                        <!-- Se llena vía JS -->
                    </div>
                </div>
            </div>
        </div>

        <!-- COLUMNA DERECHA -->
        <div class="space-y-8">
            <div class="bg-white p-8 rounded-[2.5rem] shadow-sm border border-slate-100">
                <div class="flex items-center gap-2 mb-6 border-b border-slate-50 pb-4">
                    <div class="w-2 h-2 bg-emerald-500 rounded-full"></div>
                    <h3 class="text-[11px] font-black uppercase text-slate-500 tracking-widest italic">Carga y Configuración</h3>
                </div>

                <div class="space-y-5">
                    <div class="grid grid-cols-2 gap-4">
                        <div class="bg-slate-50 p-4 rounded-2xl text-center border border-slate-100">
                            <label class="text-[8px] font-black text-slate-400 uppercase block mb-1">H. Docente</label>
                            <input type="number" name="horas_docente" value="<?= $materia['horas_docente'] ?>" class="w-full bg-transparent font-black text-center text-lg outline-none text-slate-700">
                        </div>
                        <div class="bg-slate-50 p-4 rounded-2xl text-center border border-slate-100">
                            <label class="text-[8px] font-black text-slate-400 uppercase block mb-1">H. Indep.</label>
                            <input type="number" name="horas_independientes" value="<?= $materia['horas_independientes'] ?>" class="w-full bg-transparent font-black text-center text-lg outline-none text-slate-700">
                        </div>
                    </div>

                    <div>
                        <label class="text-[9px] font-black text-slate-400 uppercase ml-1">Créditos</label>
                        <input type="number" name="creditos" value="<?= $materia['creditos'] ?>" class="w-full p-4 bg-emerald-50 border-2 border-emerald-100 rounded-2xl font-black text-emerald-700 text-3xl text-center outline-none shadow-sm">
                    </div>

                    <div class="space-y-4 pt-4 border-t border-slate-50">
                        <div>
                            <label class="text-[9px] font-black text-slate-400 uppercase ml-1">Modalidad</label>
                            <select name="tipo_modalidad" class="w-full p-3 bg-slate-50 border-2 border-slate-100 rounded-xl font-bold text-xs outline-none">
                                <option <?= $materia['tipo']=='Presencial'?'selected':'' ?>>Presencial</option>
                                <option <?= $materia['tipo']=='Virtual'?'selected':'' ?>>Virtual</option>
                                <option <?= $materia['tipo']=='Mixta'?'selected':'' ?>>Mixta</option>
                            </select>
                        </div>

                        <div>
                            <label class="text-[9px] font-black text-slate-400 uppercase ml-1">Área de Formación</label>
                            <select name="area_formacion" class="w-full p-3 bg-slate-50 border-2 border-slate-100 rounded-xl font-bold text-xs outline-none">
                                <?php $areas = ["Básica","Metodológica","Especializada","Historico Social","Pedagógica","Investigación y Gestión","Lengua Extranjera"];
                                foreach($areas as $area): ?>
                                    <option value="<?= $area ?>" <?= $materia['area_formacion']==$area?'selected':'' ?>><?= $area ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="bg-blue-50/50 p-4 rounded-2xl border border-blue-100">
                            <label class="text-[9px] font-black text-blue-400 uppercase italic mb-1 block text-center">Aula sugerida</label>
                            <input name="aula" value="<?= $materia['aula'] ?>" class="w-full bg-transparent text-center font-black text-blue-700 outline-none uppercase" placeholder="Ej: AULA 10">
                        </div>
                    </div>

                    <div class="pt-4 border-t border-slate-50">
                        <label class="text-[9px] font-black text-slate-400 uppercase ml-1">Asignatura antecesora (Seriación con..)</label>
                        <select name="seriacion_id" class="w-full p-3 bg-slate-50 border-2 border-slate-100 rounded-xl font-bold text-[9px] outline-none">
                            <option value="">Ninguna</option>
                            <?php foreach($todas_materias as $mat): ?>
                                <?php if($mat['id'] != $materia['id']): ?>
                                    <option value="<?= $mat['id'] ?>" <?= $materia['seriacion_id']==$mat['id']?'selected':'' ?>>
                                        [<?= $mat['clave'] ?>] <?= $mat['nombre'] ?>
                                    </option>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="flex flex-col gap-1.5">
                            <label class="text-[9px] font-black text-slate-400 uppercase ml-1 tracking-widest">¿Es opcional?</label>
                            <select name="es_opcional" 
                                class="w-full p-3 bg-slate-50 border-2 border-slate-100 rounded-xl font-black text-[10px] uppercase outline-none text-slate-700 focus:border-purple-200 focus:bg-white transition-all cursor-pointer appearance-none shadow-sm">
                                <option value="1" <?= $materia['es_opcional']?'selected':'' ?>>Sí</option>
                                <option value="0" <?= !$materia['es_opcional']?'selected':'' ?>>No</option>
                            </select>
                        </div>

                        <div class="flex flex-col gap-1.5">
                            <label class="text-[9px] font-black text-slate-400 uppercase ml-1 tracking-widest">¿Maneja niveles?</label>
                            <select name="maneja_niveles" 
                                class="w-full p-3 bg-slate-50 border-2 border-slate-100 rounded-xl font-black text-[10px] uppercase outline-none text-slate-700 focus:border-purple-200 focus:bg-white transition-all cursor-pointer appearance-none shadow-sm">
                                <option value="1" <?= $materia['maneja_niveles']?'selected':'' ?>>Sí</option>
                                <option value="0" <?= !$materia['maneja_niveles']?'selected':'' ?>>No</option>
                            </select>
                        </div>
                    </div>

                    <button class="w-full py-5 bg-purple-600 text-white rounded-[2rem] font-black uppercase tracking-[0.2em] shadow-xl hover:bg-slate-800 transition-all transform hover:-translate-y-1 mt-4">
                        Actualizar Cambios
                    </button>
                </div>
            </div>
        </div>
    </form>
</div>

<style>
.custom-scrollbar::-webkit-scrollbar { width: 4px; }
.custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
.custom-scrollbar::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }
@keyframes fade-in { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
.animate-fade-in { animation: fade-in 0.3s ease-out forwards; }
</style>

<script>
let seleccionadas = <?= json_encode($subas_asignadas) ?>;

function render(){
    let cont = document.getElementById("listaSubas");
    cont.innerHTML = "";

    if(seleccionadas.length === 0){
        cont.innerHTML = "<p class='col-span-2 text-center py-6 text-slate-300 text-[10px] font-black uppercase italic tracking-widest border-2 border-dashed border-slate-100 rounded-3xl'>Sin subasignaturas vinculadas</p>";
        return;
    }

    seleccionadas.forEach(s=>{
        let div = document.createElement('div');
        div.className = "flex justify-between items-center bg-white p-4 rounded-2xl text-[10px] font-black text-indigo-600 uppercase border border-indigo-100 shadow-sm animate-fade-in italic";
        div.innerHTML = `
            <span>${s.nombre}</span>
            <input type="hidden" name="subasignaturas_ids[]" value="${s.id}">
            <button type="button" onclick="quitarSub(${s.id})" class="w-6 h-6 rounded-lg bg-indigo-50 text-red-400 hover:bg-red-500 hover:text-white transition-all flex items-center justify-center">
                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M6 18L18 6M6 6l12 12" stroke-width="3"></path></svg>
            </button>
        `;
        cont.appendChild(div);
    });
}

document.addEventListener("DOMContentLoaded", () => {
    const select = document.querySelector("[name='carrera_id']");
    const hidden = document.getElementById("carreraNombre");
    function actualizarNombre(){
        const selected = select.options[select.selectedIndex];
        hidden.value = selected.getAttribute("data-nombre");
    }
    actualizarNombre();
    select.addEventListener("change", actualizarNombre);
});

function toggleSubSelector(valor){
    let cont = document.getElementById("containerSubasignaturas");
    if(valor === "si"){
        cont.classList.remove("hidden");
        cargarSubasignaturas();
        document.getElementById("inputManejaSubas").value = 1;
    } else {
        cont.classList.add("hidden");
        seleccionadas = [];
        document.getElementById("listaSeleccionadas").innerHTML = "";
        document.getElementById("listaSubas").innerHTML = "";
        document.getElementById("inputManejaSubas").value = 0;
        render();
    }
}

window.addEventListener("DOMContentLoaded", () => {
    let select = document.getElementById("manejaSubas");
    if(seleccionadas.length > 0){
        select.value = "si";
        toggleSubSelector("si");
    } else {
        select.value = "no";
        toggleSubSelector("no");
    }
});

function cargarSubasignaturas(){
    fetch("/sistema_academico/modules/coordinador/get_subasignaturas.php")
    .then(r=>r.json())
    .then(data=>{
        let cat = document.getElementById("listaCatalogo");
        cat.innerHTML="";
        data.forEach(item=>{
            if(!seleccionadas.find(s=>s.id == item.id)){
                let div = document.createElement("div");
                div.className = "p-2 bg-indigo-50/50 border border-indigo-100 rounded-xl text-[9px] font-black text-indigo-500 uppercase hover:bg-indigo-600 hover:text-white transition-all cursor-pointer mb-1";
                div.innerHTML = item.nombre;
                div.onclick = ()=>{
                    seleccionadas.push(item);
                    renderSeleccionadas();
                    div.remove();
                };
                cat.appendChild(div);
            }
        });
    });
}

function renderSeleccionadas(){
    let sel = document.getElementById("listaSeleccionadas");
    sel.innerHTML="";
    seleccionadas.forEach(s=>{
        let div = document.createElement("div");
        div.className = "flex justify-between items-center bg-emerald-500 p-2 rounded-xl text-[9px] font-black text-white uppercase mb-1 shadow-sm";
        div.innerHTML = `<span>${s.nombre}</span><button type="button" onclick="quitarSub(${s.id})" class="bg-emerald-600 hover:bg-white hover:text-emerald-500 w-5 h-5 rounded transition-all">×</button>`;
        sel.appendChild(div);
    });
    render();
}

function quitarSub(id){
    seleccionadas = seleccionadas.filter(s=>s.id != id);
    render();
    renderSeleccionadas();
    cargarSubasignaturas();
}

render();
</script>