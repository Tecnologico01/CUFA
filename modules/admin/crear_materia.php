<?php
require_once __DIR__ . '/../../includes/db.php';

/* =========================
   DATOS LOCALES
========================= */

$carreras = $pdo->query("
    SELECT id, nombre
    FROM carreras
    ORDER BY nombre ASC
")->fetchAll();

$todas_materias = $pdo->query("
    SELECT id, nombre, clave
    FROM materias
    ORDER BY nombre ASC
")->fetchAll();

$todas_materias = $pdo->query("SELECT id, nombre, clave FROM materias ORDER BY nombre ASC")->fetchAll();

/* 🔥 SOLO ÚLTIMAS 5 PARA EL LISTADO DE FONDO */
$materias = $pdo->query("
    SELECT 
        m.id,
        m.clave,
        m.nombre,
        m.nombre_corto,
        c.nombre AS carrera,
        m.tipo
    FROM materias m
    INNER JOIN carreras c ON c.id = m.carrera_id
    ORDER BY m.id DESC
    LIMIT 5
")->fetchAll();

/* =========================
   GUARDAR (Lógica intacta)
========================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (empty($_POST['carrera_id']) || empty($_POST['grado']) || empty($_POST['clave']) || empty($_POST['nombre'])) {
        echo "<script>alert('Faltan datos obligatorios');</script>";
        return;
    }

    $pdo->beginTransaction();
    try {
        $stmt = $pdo->prepare("
            INSERT INTO materias (
                carrera_id,
                grado,
                clave,
                nombre,
                nombre_corto,
                aula,
                creditos,
                tipo,
                seriacion_id,
                es_opcional,
                maneja_niveles,
                area_formacion,
                horas_docente,
                horas_independientes,
                total_unidades
            ) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)
        ");

        $total_unidades = !empty($_POST['total_unidades']) 
        ? (int)$_POST['total_unidades'] 
        : 1;

        $stmt->execute([
            $_POST['carrera_id'],
            $_POST['grado'],
            $_POST['clave'],
            $_POST['nombre'],
            $_POST['nombre_corto'],
            $_POST['aula'],
            $_POST['creditos'],
            $_POST['tipo_modalidad'],
            $_POST['seriacion_id'] ?: null,
            $_POST['es_opcional'],
            $_POST['maneja_niveles'],
            $_POST['area_formacion'],
            $_POST['horas_docente'],
            $_POST['horas_independientes'],
            $total_unidades
        ]);

        $materia_id = $pdo->lastInsertId();

        if (!empty($_POST['subasignaturas_ids'])) {
            $stmtSub = $pdo->prepare("INSERT INTO materia_subasignatura (materia_id, subasignatura_id) VALUES (?,?)");
            foreach (array_unique($_POST['subasignaturas_ids']) as $sub) {
                $stmtSub->execute([$materia_id, $sub]);
            }
        }

        $pdo->commit();
        echo "<script>alert('Materia registrada correctamente'); window.location.href='/sistema_academico/dashboards/admin_dashboard.php?modulo=lista_asignaturas';</script>";
        exit;
    } catch (Exception $e) {
        $pdo->rollBack();
        echo "<script>alert('Error al guardar');</script>";
    }
}
?>

<div class="max-w-7xl mx-auto p-6 bg-slate-50 min-h-screen">
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 mb-8">
        <div>
            <h1 class="text-4xl font-black italic uppercase tracking-tighter text-slate-800">Gestión de Asignaturas</h1>
            <p class="text-purple-600 font-bold text-[10px] uppercase tracking-[0.3em] italic">Panel Administrativo</p>
        </div>

        <button onclick="document.getElementById('modalMateria').classList.remove('hidden')"
            class="bg-slate-900 text-white px-8 py-4 rounded-[1.5rem] font-black uppercase text-[10px] tracking-widest hover:bg-purple-600 transition-all shadow-xl transform hover:-translate-y-1">
            + Nueva Asignatura
        </button>
    </div>

    <!-- LISTADO VISUAL -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <?php foreach($materias as $m): ?>
        <div class="group bg-white p-6 rounded-[2.5rem] border border-slate-100 shadow-sm hover:shadow-xl transition-all">
            <div class="flex justify-between items-start mb-4">
                <span class="px-3 py-1 bg-slate-100 text-slate-400 rounded-full text-[9px] font-black uppercase">
                    <?= $m['clave'] ?>
                </span>
                <span class="text-[9px] font-black text-slate-300 italic uppercase"><?= $m['tipo'] ?></span>
            </div>
            <h3 class="text-lg font-black italic uppercase text-slate-800 leading-tight mb-2 group-hover:text-purple-600 transition-colors">
                <?= $m['nombre'] ?>
            </h3>
            <p class="text-[10px] font-bold text-slate-400 uppercase border-l-2 border-slate-100 pl-3">
                <?= $m['carrera'] ?>
            </p>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<!-- MODAL REDISEÑADO -->
<div id="modalMateria" class="hidden fixed inset-0 bg-slate-900/60 backdrop-blur-md z-50 flex justify-center items-center p-4">
    <div class="bg-white w-full max-w-6xl max-h-[95vh] overflow-y-auto rounded-[3rem] shadow-2xl relative animate-fade-in border border-slate-200">
        
        <!-- Botón Cerrar -->
        <button onclick="document.getElementById('modalMateria').classList.add('hidden')" 
                class="absolute top-8 right-8 text-slate-400 hover:text-red-500 transition-colors z-10">
            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path d="M6 18L18 6M6 6l12 12" stroke-width="3"></path>
            </svg>
        </button>

        <form method="POST" class="p-10">
            <div class="mb-10">
                <h2 class="text-4xl font-black italic uppercase tracking-tighter text-slate-800">Nueva Asignatura</h2>
                <div class="h-1.5 w-24 bg-purple-600 mt-2 rounded-full"></div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-10">
                
                <!-- COLUMNA IZQUIERDA Y CENTRAL: DATOS PRINCIPALES -->
                <div class="lg:col-span-2 space-y-8">
                    
                    <!-- BLOQUE 1: IDENTIFICACIÓN -->
                    <div class="bg-slate-50/50 p-8 rounded-[2.5rem] border border-slate-100 space-y-5">
                        <div class="flex items-center gap-2 mb-2">
                            <div class="w-2 h-2 bg-purple-500 rounded-full"></div>
                            <h3 class="text-[11px] font-black uppercase text-slate-500 tracking-widest">Datos de la Asignatura</h3>
                        </div>
                        
                        <div>
                            <label class="text-[10px] font-black text-slate-400 uppercase ml-1">Nombre</label>
                            <input name="nombre" placeholder="Ej: Introducción al Derecho" required class="w-full p-4 bg-white border-2 border-slate-200 rounded-2xl font-bold outline-none focus:border-purple-500 transition-all shadow-sm">
                        </div>

                        <div class="grid grid-cols-2 gap-6">
                            <div>
                                <label class="text-[10px] font-black text-slate-400 uppercase ml-1">Clave</label>
                                <input name="clave" placeholder="DER-101" required class="w-full p-4 bg-white border-2 border-slate-200 rounded-2xl font-bold outline-none focus:border-purple-500 shadow-sm">
                            </div>
                            <div>
                                <label class="text-[10px] font-black text-slate-400 uppercase ml-1">Nombre Corto</label>
                                <input name="nombre_corto" placeholder="Derecho I" class="w-full p-4 bg-white border-2 border-slate-200 rounded-2xl font-bold outline-none focus:border-purple-500 shadow-sm">
                            </div>
                        </div>
                    </div>

                    <!-- BLOQUE 2: DETALLES ACADÉMICOS -->
                    <div class="bg-slate-50/50 p-8 rounded-[2.5rem] border border-slate-100 space-y-5">
                        <div class="flex items-center gap-2 mb-2">
                            <div class="w-2 h-2 bg-blue-500 rounded-full"></div>
                            <h3 class="text-[11px] font-black uppercase text-slate-500 tracking-widest">Información Académica</h3>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="text-[10px] font-black text-slate-400 uppercase ml-1">Carrera</label>
                                <select name="carrera_id" id="carreraSelect" class="w-full p-4 bg-white border-2 border-slate-200 rounded-2xl font-bold outline-none focus:border-purple-500 appearance-none shadow-sm">
                                    <?php foreach($carreras as $c): ?>
                                        <option value="<?= $c['id'] ?>">
                                            <?= $c['nombre'] ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div>
                                <label class="text-[10px] font-black text-slate-400 uppercase ml-1">Grado</label>
                                <select name="grado" required class="w-full p-4 bg-white border-2 border-slate-200 rounded-2xl font-bold outline-none appearance-none shadow-sm">
                                    <?php for($i=1; $i<=10; $i++): ?>
                                        <option value="<?= $i ?>">Grado <?= $i ?></option>
                                    <?php endfor; ?>
                                </select>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="text-[10px] font-black text-slate-400 uppercase ml-1">Aula Sugerida</label>
                                <input name="aula" placeholder="Ej: Edificio A - Aula 4" class="w-full p-4 bg-blue-50/30 border-2 border-blue-100 rounded-2xl font-bold outline-none shadow-sm">
                            </div>
                            <div>
                                <label class="text-[10px] font-black text-slate-400 uppercase ml-1">Número de Parciales</label>
                                <input type="number" name="total_unidades" min="1" placeholder="5" class="w-full p-4 bg-amber-50/30 border-2 border-amber-100 rounded-2xl font-bold outline-none shadow-sm">
                            </div>
                        </div>
                    </div>

                    <!-- BLOQUE 3: SUBASIGNATURAS (REDISEÑADO SIN NEGRO) -->
                    <div class="bg-indigo-50/50 p-8 rounded-[2.5rem] border border-indigo-100">
                        <div class="flex items-center justify-between mb-6">
                            <div class="flex items-center gap-2">
                                <div class="w-2 h-2 bg-indigo-500 rounded-full"></div>
                                <h3 class="text-[11px] font-black uppercase text-indigo-500 tracking-widest">¿Maneja Subasignaturas?</h3>
                            </div>
                            <select id="manejaSubas" 
                                class="p-2 bg-white border border-indigo-200 rounded-xl text-indigo-600 text-[10px] font-black uppercase outline-none shadow-sm"
                                onchange="toggleSubSelector(this.value)">
                                <option value="no">No</option>
                                <option value="si">Sí</option>
                            </select>
                        </div>

                        <div id="containerSubasignaturas" class="hidden grid grid-cols-1 md:grid-cols-2 gap-6 animate-fade-in">
                            <div class="space-y-2">
                                <p class="text-[9px] font-black text-slate-400 uppercase ml-2 italic">Subasignaturas Disponibles</p>
                                <div id="listaCatalogo" class="h-44 overflow-y-auto bg-white rounded-2xl border border-indigo-100 p-2 shadow-inner"></div>
                            </div>
                            <div class="space-y-2">
                                <p class="text-[9px] font-black text-indigo-400 uppercase ml-2 italic">Subasignaturas Seleccionadas</p>
                                <div id="listaSeleccionadas" class="h-44 overflow-y-auto bg-white rounded-2xl border-2 border-dashed border-indigo-200 p-2 shadow-inner"></div>
                            </div>
                        </div>

                        <div id="listaSubas" class="grid grid-cols-1 md:grid-cols-2 gap-3 mt-4"></div>
                    </div>
                </div>

                <!-- COLUMNA DERECHA: CONFIGURACIÓN TÉCNICA -->
                <div class="space-y-6">
                    <div class="bg-white p-8 rounded-[2.5rem] border border-slate-100 shadow-sm space-y-6">
                        
                        <!-- Créditos e Intensidad -->
                        <div class="space-y-4">
                            <div>
                                <label class="text-[9px] font-black text-slate-400 uppercase ml-1">Total Créditos</label>
                                <input type="number" name="creditos" placeholder="0" class="w-full p-5 bg-emerald-50 text-emerald-700 border-2 border-emerald-100 rounded-3xl font-black text-3xl text-center outline-none shadow-sm">
                            </div>
                            <div class="grid grid-cols-2 gap-3">
                                <div class="bg-slate-50 p-3 rounded-2xl text-center">
                                    <label class="text-[8px] font-black text-slate-400 uppercase block mb-1">H. Docente</label>
                                    <input type="number" name="horas_docente" class="w-full bg-transparent font-black text-center text-sm outline-none">
                                </div>
                                <div class="bg-slate-50 p-3 rounded-2xl text-center">
                                    <label class="text-[8px] font-black text-slate-400 uppercase block mb-1">H. Indep.</label>
                                    <input type="number" name="horas_independientes" class="w-full bg-transparent font-black text-center text-sm outline-none">
                                </div>
                            </div>
                        </div>

                        <!-- Selects de Configuración -->
                        <div class="space-y-4 pt-4 border-t border-slate-50">
                            <div>
                                <label class="text-[9px] font-black text-slate-400 uppercase ml-1 italic">Modalidad</label>
                                <select name="tipo_modalidad" class="w-full p-4 bg-slate-50 rounded-2xl font-bold text-xs uppercase outline-none shadow-sm">
                                    <option>Presencial</option>
                                    <option>Virtual</option>
                                    <option>Mixta</option>
                                </select>
                            </div>

                            <div>
                                <label class="text-[9px] font-black text-slate-400 uppercase ml-1 italic">Área de Formación</label>
                                <select name="area_formacion" class="w-full p-4 bg-slate-50 rounded-2xl font-bold text-xs uppercase outline-none shadow-sm">
                                    <option>Básica</option>
                                    <option>Metodológica</option>
                                    <option>Especializada</option>
                                    <option>Histórico Social</option>
                                    <option>Pedagógica</option>
                                    <option>Investigación y Gestión</option>
                                </select>
                            </div>

                            <div>
                                <label class="text-[9px] font-black text-slate-400 uppercase ml-1 italic">Asignatura Antecesora</label>
                                <select name="seriacion_id" class="w-full p-4 bg-slate-50 rounded-2xl font-bold text-[10px] outline-none shadow-sm">
                                    <option value="">Ninguna</option>
                                    <?php foreach($todas_materias as $m): ?>
                                    <option value="<?= $m['id'] ?>"><?= $m['clave'] ?> - <?= $m['nombre'] ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <!-- Switches de Selección -->
                        <div class="grid grid-cols-2 gap-3">
                            <div class="bg-purple-50/50 p-3 rounded-2xl border border-purple-100">
                                <label class="text-[8px] font-black text-purple-400 uppercase block mb-1 text-center">¿Es Opcional?</label>
                                <select name="es_opcional" class="w-full bg-transparent font-black text-[10px] text-purple-700 text-center outline-none uppercase">
                                    <option value="0">No</option>
                                    <option value="1">Sí</option>
                                </select>
                            </div>
                            <div class="bg-purple-50/50 p-3 rounded-2xl border border-purple-100">
                                <label class="text-[8px] font-black text-purple-400 uppercase block mb-1 text-center">¿Maneja Niveles?</label>
                                <select name="maneja_niveles" class="w-full bg-transparent font-black text-[10px] text-purple-700 text-center outline-none uppercase">
                                    <option value="0">No</option>
                                    <option value="1">Sí</option>
                                </select>
                            </div>
                        </div>

                        <button class="w-full py-5 bg-purple-600 text-white rounded-[2rem] font-black uppercase tracking-[0.2em] shadow-lg shadow-purple-200 hover:bg-slate-800 transition-all transform hover:-translate-y-1">
                            Guardar Asignatura
                        </button>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<style>
@keyframes fade-in { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
.animate-fade-in { animation: fade-in 0.3s ease-out; }
::-webkit-scrollbar { width: 6px; }
::-webkit-scrollbar-thumb { background: #e2e8f0; border-radius: 10px; }
</style>

<script>
let seleccionadas = [];

function toggleSubSelector(valor){
    let contenedor = document.getElementById("containerSubasignaturas");
    if(valor === "si"){
        contenedor.classList.remove("hidden");
        cargarSubasignaturas();
    } else {
        contenedor.classList.add("hidden");
        seleccionadas = [];
        renderSeleccionadas();
    }
}

function cargarSubasignaturas(){
    fetch("/sistema_academico/modules/admin/get_subasignaturas.php")
    .then(res => res.json())
    .then(data => {
        let cat = document.getElementById("listaCatalogo");
        cat.innerHTML = "";
        if(!data || data.length === 0){
            cat.innerHTML = "<p class='text-[9px] text-red-400 p-2'>No hay datos disponibles</p>";
            return;
        }
        data.forEach(item => {
            if(!seleccionadas.find(s => s.id == item.id)){
                let div = document.createElement("div");
                div.className = "p-2 rounded-xl cursor-pointer hover:bg-indigo-500 hover:text-white transition text-[9px] font-bold uppercase mb-1 border border-indigo-50/50 bg-indigo-50/20";
                div.innerText = item.nombre;
                div.onclick = () => {
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
    sel.innerHTML = "";
    seleccionadas.forEach(s => {
        let d = document.createElement("div");
        d.className = "flex justify-between items-center bg-indigo-600 p-2 rounded-xl text-white text-[9px] font-bold uppercase mb-1 shadow-sm";
        d.innerHTML = `<span>${s.nombre}</span><button type="button" onclick="quitarSub(${s.id})" class="bg-indigo-700 w-5 h-5 rounded-lg ml-2 hover:bg-red-500 transition-colors">×</button>`;
        sel.appendChild(d);
    });
    renderInputs();
}

function renderInputs(){
    let cont = document.getElementById("listaSubas");
    cont.innerHTML = "";
    seleccionadas.forEach(s => {
        let d = document.createElement("div");
        d.className = "bg-white p-3 rounded-2xl border border-indigo-100 text-indigo-600 text-[10px] font-black uppercase flex justify-between shadow-sm italic";
        d.innerHTML = `${s.nombre}<input type="hidden" name="subasignaturas_ids[]" value="${s.id}">`;
        cont.appendChild(d);
    });
}

function quitarSub(id){
    seleccionadas = seleccionadas.filter(s => s.id != id);
    renderSeleccionadas();
    cargarSubasignaturas();
}
</script>