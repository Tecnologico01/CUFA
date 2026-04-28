<?php
require_once __DIR__ . '/../../includes/db.php';

/* =========================
   DATOS
========================= */
$carreras = $pdo->query("SELECT id, nombre FROM carreras ORDER BY nombre ASC")->fetchAll();
$todas_materias = $pdo->query("SELECT id, nombre, clave FROM materias ORDER BY nombre ASC")->fetchAll();

/* 🔥 SOLO ÚLTIMAS 5 */
$materias = $pdo->query("
SELECT m.id, m.clave, m.nombre, m.nombre_corto, c.nombre as carrera, m.tipo
FROM materias m
JOIN carreras c ON c.id = m.carrera_id
ORDER BY m.id DESC
LIMIT 5
")->fetchAll();

/* =========================
   GUARDAR
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
            carrera_id, grado, clave, nombre, nombre_corto, aula, creditos, tipo,
            seriacion_id, es_opcional, maneja_niveles, area_formacion,
            horas_docente, horas_independientes
        ) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?)
        ");

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
            $_POST['horas_independientes']
        ]);

        $materia_id = $pdo->lastInsertId();

        if (!empty($_POST['subasignaturas_ids'])) {
            $stmtSub = $pdo->prepare("INSERT INTO materia_subasignatura (materia_id, subasignatura_id) VALUES (?,?)");
            foreach (array_unique($_POST['subasignaturas_ids']) as $sub) {
                $stmtSub->execute([$materia_id, $sub]);
            }
        }

        $pdo->commit();

        echo "<script>
        alert('Materia registrada correctamente');
        window.location.href='/sistema_academico/dashboards/coordinador_dashboard.php?modulo=materias';
        </script>";
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
            <p class="text-purple-600 font-bold text-[10px] uppercase tracking-[0.3em] italic">Creación de Asignaturas</p>
        </div>

        <button onclick="document.getElementById('modalMateria').classList.remove('hidden')"
            class="bg-slate-900 text-white px-8 py-4 rounded-[1.5rem] font-black uppercase text-[10px] tracking-widest hover:bg-purple-600 transition-all shadow-xl transform hover:-translate-y-1">
            + Nueva Asignatura
        </button>
    </div>

    <!-- LISTADO SOLO VISUAL -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <?php foreach($materias as $m): ?>
        <div class="group bg-white p-6 rounded-[2.5rem] border border-slate-100 shadow-sm hover:shadow-xl hover:border-purple-100 transition-all cursor-default transform hover:-translate-y-2"
            data-clave="<?= strtolower($m['clave']) ?>"
            data-nombre="<?= strtolower($m['nombre']) ?>"
            data-corto="<?= strtolower($m['nombre_corto']) ?>">

            <div class="flex justify-between items-start mb-4">
                <span class="px-3 py-1 bg-slate-100 text-slate-400 rounded-full text-[9px] font-black uppercase group-hover:bg-purple-100 group-hover:text-purple-600 transition-colors">
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

            <div class="flex items-center justify-end pt-4 mt-4 border-t border-slate-50">
                <div class="w-8 h-8 rounded-full bg-slate-50 flex items-center justify-center text-slate-300">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path d="M9 5l7 7-7 7" stroke-width="3"></path>
                    </svg>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>


<div id="modalMateria" class="hidden fixed inset-0 bg-slate-900/80 backdrop-blur-sm z-50 flex justify-center items-center p-4">

    <div class="bg-slate-50 w-full max-w-6xl max-h-[90vh] overflow-y-auto rounded-[3rem] shadow-2xl relative animate-fade-in">
        
        <button onclick="document.getElementById('modalMateria').classList.add('hidden')" 
                class="absolute top-8 right-8 text-slate-400 hover:text-red-500 transition-colors z-10">
            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path d="M6 18L18 6M6 6l12 12" stroke-width="3"></path>
            </svg>
        </button>

        <form method="POST" class="p-10 grid grid-cols-1 lg:grid-cols-3 gap-8">
            
    <div class="lg:col-span-3">
        <h2 class="text-3xl font-black italic uppercase tracking-tighter text-slate-800">Nueva Asignatura</h2>
        <div class="h-1 w-20 bg-purple-600 mt-2"></div>
    </div>

    <div class="lg:col-span-2 space-y-6">
        
        <div class="bg-white p-8 rounded-[2rem] shadow-sm space-y-4">
            <div>
                <label class="text-[10px] font-black text-slate-400 uppercase ml-1">Nombre Oficial</label>
                <input name="nombre" placeholder="Nombre completo" required class="w-full p-4 bg-slate-50 border-2 border-slate-100 rounded-2xl font-bold outline-none focus:border-purple-500 transition-all">
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="text-[10px] font-black text-slate-400 uppercase ml-1">Clave</label>
                    <input name="clave" placeholder="Clave" required class="w-full p-4 bg-slate-50 border-2 border-slate-100 rounded-2xl font-bold outline-none">
                </div>
                <div>
                    <label class="text-[10px] font-black text-slate-400 uppercase ml-1">Nombre Corto</label>
                    <input name="nombre_corto" placeholder="Alias" class="w-full p-4 bg-slate-50 border-2 border-slate-100 rounded-2xl font-bold outline-none">
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="text-[10px] font-black text-slate-400 uppercase ml-1">Carrera</label>
                    <select name="carrera_id" class="w-full p-4 bg-slate-50 border-2 border-slate-100 rounded-2xl font-bold outline-none">
                        <?php foreach($carreras as $c): ?>
                        <option value="<?= $c['id'] ?>"><?= $c['nombre'] ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="text-[10px] font-black text-slate-400 uppercase ml-1">Grado / Semestre</label>
                    <select name="grado" required class="w-full p-4 bg-slate-50 border-2 border-slate-100 rounded-2xl font-bold outline-none appearance-none">
                        <?php for($i=1; $i<=9; $i++): ?>
                            <option value="<?= $i ?>">Grado <?= $i ?></option>
                        <?php endfor; ?>
                    </select>
                </div>
            </div>

            <div>
                <label class="text-[10px] font-black text-slate-400 uppercase ml-1">Aula Sugerida</label>
                <input name="aula" placeholder="Ej: Aula 5" class="w-full p-4 bg-blue-50/50 border-2 border-blue-100 rounded-2xl font-bold outline-none italic">
            </div>
        </div>

        <!-- SUBASIGNATURAS -->
        <div class="bg-slate-900 p-8 rounded-[2.5rem] shadow-xl">
            <div class="mb-6 space-y-3">
                <h3 class="text-xs font-black text-slate-400 uppercase italic">¿Maneja subasignaturas?</h3>
                <select id="manejaSubas" 
                    class="w-full p-3 bg-slate-800 border border-slate-700 rounded-xl text-white text-xs font-bold"
                    onchange="toggleSubSelector(this.value)">
                    <option value="">Seleccionar...</option>
                    <option value="no">No</option>
                    <option value="si">Sí</option>
                </select>
            </div>

            <div id="containerSubasignaturas" class="hidden grid grid-cols-2 gap-4 mb-6 p-4 bg-slate-800 rounded-3xl border border-slate-700">
                <div id="listaCatalogo" class="h-40 overflow-y-auto space-y-1 text-[9px] font-bold uppercase text-slate-400 p-1"></div>
                <div id="listaSeleccionadas" class="h-40 overflow-y-auto bg-slate-900 rounded-xl space-y-1 p-2 border border-slate-700"></div>
            </div>

            <div id="listaSubas" class="grid grid-cols-1 md:grid-cols-2 gap-2"></div>
        </div>

    </div>

    <!-- LADO DERECHO -->
    <div class="space-y-6">
        
        <div class="bg-white p-8 rounded-[2.5rem] shadow-sm space-y-4">
            <div>
                <label class="text-[9px] font-black text-slate-400 uppercase ml-1">Créditos</label>
                <input type="number" name="creditos" placeholder="0" class="w-full p-4 bg-emerald-50 border-2 border-emerald-100 rounded-2xl font-black text-emerald-700 text-2xl text-center outline-none">
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="text-[8px] font-black text-slate-400 uppercase">H. Docente</label>
                    <input type="number" name="horas_docente" class="w-full p-3 bg-slate-50 border rounded-xl font-bold text-center text-xs">
                </div>
                <div>
                    <label class="text-[8px] font-black text-slate-400 uppercase">H. Indep.</label>
                    <input type="number" name="horas_independientes" class="w-full p-3 bg-slate-50 border rounded-xl font-bold text-center text-xs">
                </div>
            </div>

            <div class="space-y-4 pt-4 border-t border-slate-50">
                <div>
                    <label class="text-[8px] font-black text-slate-400 uppercase ml-1 italic">Modalidad</label>
                    <select name="tipo_modalidad" class="w-full p-3 bg-slate-50 border rounded-xl font-bold text-xs uppercase italic">
                        <option>Presencial</option>
                        <option>Virtual</option>
                        <option>Mixta</option>
                    </select>
                </div>

                <div>
                    <label class="text-[8px] font-black text-slate-400 uppercase ml-1 italic">Formación</label>
                    <select name="area_formacion" class="w-full p-3 bg-slate-50 border rounded-xl font-bold text-xs uppercase italic">
                        <option>Básica</option>
                        <option>Metodologíca</option>
                        <option>Especializada</option>
                        <option>Historico Social</option>
                        <option>Pedagógica</option>
                        <option>Investigación y Gestión</option>
                    </select>
                </div>

                <div>
                    <label class="text-[8px] font-black text-slate-400 uppercase ml-1 italic">Asignatura Antecesora</label>
                    <select name="seriacion_id" class="w-full p-3 bg-slate-50 border rounded-xl font-bold text-[10px]">
                        <option value="">Ninguna</option>
                        <?php foreach($todas_materias as $m): ?>
                        <option value="<?= $m['id'] ?>"><?= $m['clave'] ?> - <?= $m['nombre'] ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="bg-slate-50 p-3 rounded-xl border border-slate-100">
                    <label class="text-[8px] font-black text-slate-400 uppercase mb-2 block italic text-center tracking-widest">
                        ¿Es una materia opcional?
                    </label>
                    <select name="es_opcional" class="w-full bg-white border-none font-black text-[10px] uppercase text-purple-600 text-center">
                        <option value="0">No</option>
                        <option value="1">Sí</option>
                    </select>
                </div>

                <div class="bg-slate-50 p-3 rounded-xl border border-slate-100">
                    <label class="text-[8px] font-black text-slate-400 uppercase mb-2 block italic text-center tracking-widest">
                        ¿Maneja niveles?
                    </label>
                    <select name="maneja_niveles" class="w-full bg-white border-none font-black text-[10px] uppercase text-purple-600 text-center">
                        <option value="0">No</option>
                        <option value="1">Sí</option>
                    </select>
                </div>
            </div>

            <button class="w-full py-5 bg-purple-600 text-white rounded-[2rem] font-black uppercase tracking-[0.2em] shadow-xl hover:bg-purple-700 transition-all transform hover:-translate-y-1 mt-4">
                Guardar Materia
            </button>
        </div>

    </div>

</form>

    </div>
</div>

<style>
@keyframes fade-in { 
    from { opacity: 0; transform: translateY(10px); } 
    to { opacity: 1; transform: translateY(0); } 
}
.animate-fade-in { animation: fade-in 0.3s ease-out; }
::-webkit-scrollbar { width: 5px; }
::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }
</style>

<script>
let seleccionadas = [];

/* MOSTRAR / OCULTAR */
function toggleSubSelector(valor){
    let contenedor = document.getElementById("containerSubasignaturas");

    if(valor === "si"){
        contenedor.classList.remove("hidden");
        cargarSubasignaturas();
    } else {
        contenedor.classList.add("hidden");

        // limpiar selección si cambia a "NO"
        seleccionadas = [];
        document.getElementById("listaSeleccionadas").innerHTML = "";
        document.getElementById("listaSubas").innerHTML = "";
    }
}

/* CARGAR DESDE BACKEND */
function cargarSubasignaturas(){
    fetch("/sistema_academico/modules/coordinador/get_subasignaturas.php")
    .then(res => res.json())
    .then(data => {

        let cat = document.getElementById("listaCatalogo");
        cat.innerHTML = "";

        if(!data || data.length === 0){
            cat.innerHTML = "<p class='text-xs text-red-400'>No hay subasignaturas</p>";
            return;
        }

        data.forEach(item => {

            if(!seleccionadas.find(s => s.id == item.id)){

                let div = document.createElement("div");

                div.className = "p-2 rounded-lg cursor-pointer hover:bg-purple-600 hover:text-white transition text-[10px]";
                div.innerText = item.nombre;

                div.onclick = () => {
                    seleccionadas.push(item);
                    renderSeleccionadas();
                    div.remove();
                };

                cat.appendChild(div);
            }
        });

    })
    .catch(err => {
        console.error("Error cargando subasignaturas:", err);
        document.getElementById("listaCatalogo").innerHTML =
            "<p class='text-xs text-red-400'>Error al cargar</p>";
    });
}

/* LISTA DERECHA (SELECCIONADAS) */
function renderSeleccionadas(){
    let sel = document.getElementById("listaSeleccionadas");
    sel.innerHTML = "";

    seleccionadas.forEach(s => {

        let d = document.createElement("div");

        d.className = "flex justify-between items-center bg-indigo-900/40 p-2 rounded-lg text-indigo-200 border border-indigo-500/30 text-[9px] font-bold uppercase";

        d.innerHTML = `
            <span>${s.nombre}</span>
            <button type="button" onclick="quitarSub(${s.id})" class="text-red-400 px-2">×</button>
        `;

        sel.appendChild(d);
    });

    renderInputs();
}

/* INPUTS OCULTOS PARA GUARDAR */
function renderInputs(){
    let cont = document.getElementById("listaSubas");
    cont.innerHTML = "";

    seleccionadas.forEach(s => {

        let d = document.createElement("div");

        d.className = "bg-slate-800 p-3 rounded-xl border border-slate-700 text-white text-[10px] font-bold uppercase italic flex justify-between";

        d.innerHTML = `
            ${s.nombre}
            <input type="hidden" name="subasignaturas_ids[]" value="${s.id}">
        `;

        cont.appendChild(d);
    });
}

/* QUITAR */
function quitarSub(id){
    seleccionadas = seleccionadas.filter(s => s.id != id);
    renderSeleccionadas();
    cargarSubasignaturas();
}
</script>