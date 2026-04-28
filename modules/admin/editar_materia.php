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

$carreras = $pdo->query("SELECT id, nombre FROM carreras")->fetchAll();
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

    if(empty($_POST['carrera_id']) || empty($_POST['grado']) || empty($_POST['clave']) || empty($_POST['nombre'])){
        echo "<script>alert('Faltan datos obligatorios');</script>";
        return;
    }

    $pdo->beginTransaction();

    try{
        $stmt = $pdo->prepare("
        UPDATE materias SET
            carrera_id = ?, grado = ?, clave = ?, nombre = ?, nombre_corto = ?,
            aula = ?, creditos = ?, tipo = ?, seriacion_id = ?, es_opcional = ?,
            maneja_niveles = ?, area_formacion = ?, horas_docente = ?, horas_independientes = ?
        WHERE id = ?
        ");

        $stmt->execute([
            $_POST['carrera_id'], $_POST['grado'], $_POST['clave'], $_POST['nombre'],
            $_POST['nombre_corto'], $_POST['aula'], $_POST['creditos'], $_POST['tipo_modalidad'],
            $_POST['seriacion_id'] ?: null, $_POST['es_opcional'], $_POST['maneja_niveles'],
            $_POST['area_formacion'], $_POST['horas_docente'], $_POST['horas_independientes'],
            $id
        ]);

        /* RESET SUBASIGNATURAS */
        $pdo->prepare("DELETE FROM materia_subasignatura WHERE materia_id=?")->execute([$id]);

        if(!empty($_POST['maneja_subas']) && !empty($_POST['subasignaturas_ids'])){
            $stmtSub = $pdo->prepare("INSERT INTO materia_subasignatura (materia_id, subasignatura_id) VALUES (?,?)");
            foreach(array_unique($_POST['subasignaturas_ids']) as $sub){
                $stmtSub->execute([$id, $sub]);
            }
        }

        $pdo->commit();
        echo "<script>
                alert('Materia actualizada correctamente');
                window.location.href='/sistema_academico/dashboards/admin_dashboard.php?modulo=editar_materia&id=$id';
              </script>";
        exit;

    }catch(Exception $e){
        $pdo->rollBack();
        echo "<script>alert('Error al actualizar');</script>";
    }
}
?>

<div class="max-w-7xl mx-auto p-6 bg-slate-50 min-h-screen font-sans">

    <div class="flex flex-col md:flex-row justify-between items-center mb-10 gap-4">
        <div class="flex items-center gap-6">
            <a href="/sistema_academico/dashboards/admin_dashboard.php?modulo=lista_asignaturas"
               class="group bg-white p-4 rounded-2xl shadow-sm border border-slate-200 text-slate-400 hover:text-purple-600 hover:border-purple-200 transition-all">
               <svg class="w-6 h-6 transition-transform group-hover:-translate-x-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M10 19l-7-7m0 0l7-7m-7 7h18" stroke-width="3"></path></svg>
            </a>
            <div>
                <h1 class="text-3xl font-black text-slate-800 uppercase italic tracking-tighter">Modificar Asignatura</h1>
                <div class="flex items-center gap-2">
                    <span class="px-2 py-0.5 bg-purple-100 text-purple-700 text-[10px] font-black rounded-lg uppercase italic border border-purple-200">Editando Registro</span>
                    <p class="text-xs text-slate-400 font-bold uppercase tracking-widest"><?= $materia['clave'] ?></p>
                </div>
            </div>
        </div>
    </div>

    <form method="POST" class="grid grid-cols-1 lg:grid-cols-3 gap-8">

        <div class="lg:col-span-2 space-y-8">
            
            <div class="bg-white p-8 rounded-[2.5rem] shadow-sm border border-slate-100">
                <h3 class="text-xs font-black text-purple-600 uppercase tracking-widest border-b border-slate-50 pb-4 mb-6 italic">Información General</h3>
                
                <div class="space-y-6">
                    <div>
                        <label class="text-[10px] font-black text-slate-400 uppercase ml-1">Nombre Completo de la Asignatura</label>
                        <input name="nombre" value="<?= $materia['nombre'] ?>" class="w-full p-4 bg-slate-50 border-2 border-slate-100 rounded-2xl font-bold text-slate-700 focus:border-purple-500 outline-none transition-all placeholder:text-slate-300">
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="text-[10px] font-black text-slate-400 uppercase ml-1">Clave de Control</label>
                            <input name="clave" value="<?= $materia['clave'] ?>" class="w-full p-4 bg-slate-50 border-2 border-slate-100 rounded-2xl font-bold text-slate-700 focus:border-purple-500 outline-none">
                        </div>
                        <div>
                            <label class="text-[10px] font-black text-slate-400 uppercase ml-1">Nombre Corto / Identificador</label>
                            <input name="nombre_corto" value="<?= $materia['nombre_corto'] ?>" class="w-full p-4 bg-slate-50 border-2 border-slate-100 rounded-2xl font-bold text-slate-700 focus:border-purple-500 outline-none">
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 border-t border-slate-50 pt-6">
                        <div>
                            <label class="text-[10px] font-black text-slate-400 uppercase ml-1">Carrera Vinculada</label>
                            <select name="carrera_id" class="w-full p-4 bg-slate-50 border-2 border-slate-100 rounded-2xl font-bold text-slate-700 focus:border-purple-500 outline-none appearance-none cursor-pointer">
                                <?php foreach($carreras as $c): ?>
                                    <option value="<?= $c['id'] ?>" <?= $c['id']==$materia['carrera_id']?'selected':'' ?>>
                                        <?= $c['nombre'] ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label class="text-[10px] font-black text-slate-400 uppercase ml-1">Grado / Semestre Sugerido</label>
                            <input type="number" name="grado" value="<?= $materia['grado'] ?>" class="w-full p-4 bg-slate-50 border-2 border-slate-100 rounded-2xl font-bold text-slate-700 focus:border-purple-500 outline-none">
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-slate-900 p-8 rounded-[2.5rem] shadow-xl overflow-hidden relative">
                <div class="flex justify-between items-center mb-6">
                    <div class="mb-6 space-y-3">
                        <h3 class="text-xs font-black text-slate-400 uppercase italic">¿Maneja subasignaturas?</h3>
                        <select id="manejaSubas"
                            class="w-full p-3 bg-slate-800 border border-slate-700 rounded-xl text-white text-xs font-bold"
                            onchange="toggleSubSelector(this.value)">
                            <option value="no">No</option>
                            <option value="si">Sí</option>
                            <input type="hidden" name="maneja_subas" id="inputManejaSubas" value="0">
                        </select>
                    </div>
                </div>

                <div id="containerSubasignaturas" class="hidden grid grid-cols-1 md:grid-cols-2 gap-6 mb-8 animate-fade-in">
                    <div class="space-y-2">
                        <span class="text-[9px] font-black text-indigo-400 uppercase tracking-widest block mb-2 text-center">Sub-asignaturas Disponibles</span>
                        <div id="listaCatalogo" class="h-48 overflow-y-auto custom-scrollbar p-2 rounded-2xl bg-slate-800/50 border border-slate-800 space-y-1"></div>
                    </div>
                    <div class="space-y-2">
                        <span class="text-[9px] font-black text-emerald-400 uppercase tracking-widest block mb-2 text-center">Sub-asignaturas a agregar</span>
                        <div id="listaSeleccionadas" class="h-48 overflow-y-auto custom-scrollbar p-2 rounded-2xl bg-slate-800/50 border border-slate-800 space-y-1"></div>
                    </div>
                </div>

                <div class="border-t border-slate-800 pt-6">
                    <span class="text-[10px] font-black text-slate-500 uppercase tracking-widest block mb-4 italic">Asignaciones Actuales:</span>
                    <div id="listaSubas" class="grid grid-cols-1 md:grid-cols-2 gap-3">
                        </div>
                </div>
            </div>
        </div>

        <div class="space-y-8">
            <div class="bg-white p-8 rounded-[2.5rem] shadow-sm border border-slate-100">
                <h3 class="text-xs font-black text-emerald-600 uppercase tracking-widest border-b border-slate-50 pb-4 mb-6 italic">Carga y Modalidad</h3>

                <div class="space-y-5">
                    <div class="grid grid-cols-2 gap-4">
                        <div class="space-y-1 text-center">
                            <label class="text-[9px] font-black text-slate-400 uppercase">Horas Docente</label>
                            <input type="number" name="horas_docente" value="<?= $materia['horas_docente'] ?>" class="w-full p-3 bg-slate-50 border-2 border-slate-100 rounded-xl font-bold text-center outline-none focus:border-emerald-500">
                        </div>
                        <div class="space-y-1 text-center">
                            <label class="text-[9px] font-black text-slate-400 uppercase">Horas Independiente</label>
                            <input type="number" name="horas_independientes" value="<?= $materia['horas_independientes'] ?>" class="w-full p-3 bg-slate-50 border-2 border-slate-100 rounded-xl font-bold text-center outline-none focus:border-emerald-500">
                        </div>
                    </div>

                    <div>
                        <label class="text-[10px] font-black text-slate-400 uppercase ml-1">Créditos Totales</label>
                        <input type="number" name="creditos" value="<?= $materia['creditos'] ?>" class="w-full p-4 bg-emerald-50 border-2 border-emerald-100 rounded-2xl font-black text-emerald-700 text-xl text-center outline-none focus:border-emerald-500">
                    </div>

                    <div class="grid grid-cols-1 gap-4 pt-4 border-t border-slate-50">
                        <div>
                            <label class="text-[10px] font-black text-slate-400 uppercase ml-1">Modalidad</label>
                            <select name="tipo_modalidad" class="w-full p-3 bg-slate-50 border-2 border-slate-100 rounded-xl font-bold text-slate-700 outline-none">
                                <option <?= $materia['tipo']=='Presencial'?'selected':'' ?>>Presencial</option>
                                <option <?= $materia['tipo']=='Virtual'?'selected':'' ?>>Virtual</option>
                                <option <?= $materia['tipo']=='Mixta'?'selected':'' ?>>Mixta</option>
                            </select>
                        </div>

                        <div>
                            <label class="text-[10px] font-black text-slate-400 uppercase ml-1">Área de Formación</label>
                            <select name="area_formacion" class="w-full p-3 bg-slate-50 border-2 border-slate-100 rounded-xl font-bold text-slate-700 outline-none">
                                <?php
                                $areas = ["Básica","Metodológica","Especializada","Historico Social","Pedagógica","Investigación y Gestión","Lengua Extranjera"];
                                foreach($areas as $area): ?>
                                    <option value="<?= $area ?>" <?= $materia['area_formacion']==$area?'selected':'' ?>>
                                        <?= $area ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div>
                            <label class="text-[10px] font-black text-blue-500 uppercase ml-1 italic">Aula / Laboratorio</label>
                            <input name="aula" value="<?= $materia['aula'] ?>" class="w-full p-3 bg-blue-50 border-2 border-blue-100 rounded-xl font-bold text-slate-700 outline-none focus:border-blue-500">
                        </div>
                    </div>

                    <div class="pt-4 border-t border-slate-50">
                        <label class="text-[10px] font-black text-slate-400 uppercase ml-1">Pre-asignatura (Seriación)</label>
                        <select name="seriacion_id" class="w-full p-3 bg-slate-50 border-2 border-slate-100 rounded-xl font-bold text-[10px] outline-none">
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

                    <div class="grid grid-cols-2 gap-2">
                        <select name="es_opcional" class="p-3 bg-slate-50 border-2 border-slate-100 rounded-xl font-black text-[10px] uppercase outline-none">
                            <option value="1" <?= $materia['es_opcional']?'selected':'' ?>>Opcional</option>
                            <option value="0" <?= !$materia['es_opcional']?'selected':'' ?>>Obligatoria</option>
                        </select>
                        <select name="maneja_niveles" class="p-3 bg-slate-50 border-2 border-slate-100 rounded-xl font-black text-[10px] uppercase outline-none">
                            <option value="1" <?= $materia['maneja_niveles']?'selected':'' ?>>Con niveles</option>
                            <option value="0" <?= !$materia['maneja_niveles']?'selected':'' ?>>Sin niveles</option>
                        </select>
                    </div>

                    <button class="w-full py-5 bg-purple-600 text-white rounded-[2rem] font-black uppercase tracking-[0.2em] shadow-xl hover:bg-purple-700 transition-all transform hover:-translate-y-1 mt-4">
                        Guardar Cambios
                    </button>
                </div>
            </div>
        </div>
    </form>
</div>

<style>
.custom-scrollbar::-webkit-scrollbar { width: 4px; }
.custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
.custom-scrollbar::-webkit-scrollbar-thumb { background: #334155; border-radius: 10px; }
@keyframes fade-in { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
.animate-fade-in { animation: fade-in 0.3s ease-out forwards; }
input::-webkit-outer-spin-button, input::-webkit-inner-spin-button { -webkit-appearance: none; margin: 0; }
</style>

<script>
let seleccionadas = <?= json_encode($subas_asignadas) ?>;

/* RENDER PRINCIPAL (Listado de abajo) */
function render(){
    let cont = document.getElementById("listaSubas");
    cont.innerHTML = "";

    if(seleccionadas.length === 0){
        cont.innerHTML = "<p class='col-span-2 text-center py-4 text-slate-600 text-[10px] font-black uppercase italic tracking-widest opacity-40 italic'>Sin subasignaturas vinculadas</p>";
        return;
    }

    seleccionadas.forEach(s=>{
        let div = document.createElement('div');
        div.className = "flex justify-between items-center bg-slate-800 p-3 rounded-2xl text-[10px] font-bold text-white uppercase border border-slate-700 pl-5 animate-fade-in";
        div.innerHTML = `
            <span>${s.nombre}</span>
            <input type="hidden" name="subasignaturas_ids[]" value="${s.id}">
            <button type="button" onclick="quitarSub(${s.id})" class="w-6 h-6 rounded-lg bg-slate-700 text-red-400 hover:bg-red-500 hover:text-white transition-colors flex items-center justify-center font-black">
                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M6 18L18 6M6 6l12 12" stroke-width="3"></path></svg>
            </button>
        `;
        cont.appendChild(div);
    });
}

/* TOGGLE SELECTOR */
function toggleSubSelector(valor){
    let cont = document.getElementById("containerSubasignaturas");

    if(valor === "si"){
        cont.classList.remove("hidden");
        cargarSubasignaturas();
        document.getElementById("inputManejaSubas").value = 1;
    } else {
        cont.classList.add("hidden");

        // limpiar todo si cambia a NO
        seleccionadas = [];
        document.getElementById("listaSeleccionadas").innerHTML = "";
        document.getElementById("listaSubas").innerHTML = "";

        document.getElementById("inputManejaSubas").value = 0;
    }
}

// AUTO-DETECCIÓN AL CARGAR
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

/* CARGAR CATALOGO (AJAX) */
function cargarSubasignaturas(){
    fetch("/sistema_academico/modules/coordinador/get_subasignaturas.php")
    .then(r=>r.json())
    .then(data=>{
        let cat = document.getElementById("listaCatalogo");
        cat.innerHTML="";
        data.forEach(item=>{
            if(!seleccionadas.find(s=>s.id == item.id)){
                let div = document.createElement("div");
                div.className = "p-2 bg-slate-700/50 border border-slate-700 rounded-xl text-[9px] font-black text-slate-300 uppercase hover:bg-indigo-600 hover:text-white transition-all cursor-pointer";
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

/* PANEL DINÁMICO (Derecho en el selector) */
function renderSeleccionadas(){
    let sel = document.getElementById("listaSeleccionadas");
    sel.innerHTML="";
    seleccionadas.forEach(s=>{
        let div = document.createElement("div");
        div.className = "flex justify-between items-center bg-indigo-900/40 p-2 rounded-xl text-[9px] font-black text-indigo-200 uppercase border border-indigo-500/30 pl-3";
        div.innerHTML = `
            <span>${s.nombre}</span>
            <button type="button" onclick="quitarSub(${s.id})" class="text-indigo-400 hover:text-white p-1">×</button>
        `;
        sel.appendChild(div);
    });
    render();
}

/* QUITAR */
function quitarSub(id){
    seleccionadas = seleccionadas.filter(s=>s.id != id);
    render();
    renderSeleccionadas();
    cargarSubasignaturas();
}

render();
</script>