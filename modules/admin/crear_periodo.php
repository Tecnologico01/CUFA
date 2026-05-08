<?php
require_once __DIR__ . '/../../includes/db.php';

$error = '';
$mensaje = '';

/* OBTENER TIPOS */
$tipos = $pdo->query("SELECT id, nombre FROM tipos_periodo")->fetchAll(PDO::FETCH_ASSOC);

if($_SERVER['REQUEST_METHOD'] === 'POST'){
    $nombre = trim($_POST['nombre']);
    $fecha_inicio = $_POST['fecha_inicio'];
    $fecha_fin = $_POST['fecha_fin'];
    $tipo_periodo_id = $_POST['tipo_periodo_id'];

    if(empty($nombre)||empty($fecha_inicio)||empty($fecha_fin)||empty($tipo_periodo_id)){
        $error = "Todos los campos son obligatorios para el registro.";
    } else {
        $inicio = new DateTime($fecha_inicio);
        $fin = new DateTime($fecha_fin);

        if($inicio >= $fin){
            $error = "Cronología inválida: La fecha de cierre debe ser posterior al inicio.";
        } else {
            $intervalo = $inicio->diff($fin);
            $meses = ($intervalo->y * 12) + $intervalo->m;
            $dias = $intervalo->d;

            if($dias > 15) { $meses++; }

            $validacion = false;
            $mensaje_tipo = "";

            switch($tipo_periodo_id){
                case 1: 
                    $validacion = ($meses >= 4 && $meses <= 6);
                    $mensaje_tipo = "Semestral (4-6 meses)";
                    break;
                case 2: 
                    $validacion = ($meses >= 2 && $meses <= 4);
                    $mensaje_tipo = "Cuatrimestral (2-4 meses)";
                    break;
                case 3: 
                    $validacion = ($meses >= 1 && $meses <= 3);
                    $mensaje_tipo = "Trimestral (1-3 meses)";
                    break;
                case 4: 
                    $validacion = ($meses >= 1 && $meses <= 3);
                    $mensaje_tipo = "Bimestral (1-3 meses)";
                    break;
            }

            if(!$validacion){
                $error = "Inconsistencia de duración. El tipo " . $mensaje_tipo . " no coincide con las fechas.";
            } else {
                $stmt = $pdo->prepare("INSERT INTO periodos (nombre, tipo_periodo_id, fecha_inicio, fecha_fin, activo) VALUES (?,?,?,?,0)");
                $stmt->execute([$nombre, $tipo_periodo_id, $fecha_inicio, $fecha_fin]);
                $mensaje = "El periodo académico ha sido configurado exitosamente.";
            }
        }
    }
}
?>

<div class="max-w-4xl mx-auto p-6 animate-fade-in font-sans text-slate-900">
    
    <!-- Header -->
    <div class="mb-10">
        <div class="flex items-center gap-3 mb-2">
            <span class="bg-indigo-100 text-indigo-600 text-[10px] font-black px-3 py-1 rounded-full uppercase tracking-widest">Configuración</span>
            <div class="w-10 h-[1px] bg-slate-200"></div>
            <span class="text-slate-400 text-[10px] font-black uppercase tracking-widest italic">Sistema CUFA</span>
        </div>
        <h1 class="text-5xl font-black tracking-tighter uppercase leading-none">
            Crear <span class="text-purple-600">Periodo</span>
        </h1>
    </div>

    <!-- Feedback Messages -->
    <?php if($error): ?>
    <div class="mb-8 bg-white border-2 border-red-50 rounded-[2rem] p-6 shadow-xl shadow-red-100/50 flex items-center gap-5 animate-shake">
        <div class="bg-red-50 p-3 rounded-2xl text-red-600">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
        </div>
        <div>
            <p class="text-[10px] font-black text-red-400 uppercase tracking-widest mb-1">Error de Validación</p>
            <p class="text-slate-700 font-bold text-sm uppercase tracking-tight"><?= $error ?></p>
        </div>
    </div>
    <?php endif; ?>

    <?php if($mensaje): ?>
    <div class="mb-8 bg-white border-2 border-emerald-50 rounded-[2rem] p-6 shadow-xl shadow-emerald-100/50 flex items-center gap-5 animate-pop">
        <div class="bg-emerald-50 p-3 rounded-2xl text-emerald-600">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
        </div>
        <div>
            <p class="text-[10px] font-black text-emerald-400 uppercase tracking-widest mb-1">Operación Exitosa</p>
            <p class="text-slate-700 font-bold text-sm uppercase tracking-tight"><?= $mensaje ?></p>
        </div>
    </div>
    <?php endif; ?>

    <!-- Formulario Principal -->
    <form method="POST" class="bg-white rounded-[3rem] p-12 shadow-2xl shadow-slate-200/60 border border-slate-50 relative overflow-hidden">
        
        <!-- Elemento decorativo sutil -->
        <div class="absolute top-0 right-0 w-32 h-32 bg-purple-50 rounded-full -mr-16 -mt-16 opacity-50"></div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-8 relative z-10">
            
            <!-- Nombre del Periodo -->
            <div class="md:col-span-2">
                <label class="block text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] mb-3 ml-2">Identificador del Periodo</label>
                <input type="text" name="nombre" placeholder="EJ: ENERO - JUNIO 2026"
                    class="w-full bg-slate-50 border-none rounded-2xl p-5 text-sm font-bold text-slate-700 placeholder:text-slate-300 focus:ring-4 focus:ring-purple-100 transition-all uppercase tracking-wider" required>
            </div>

            <!-- Tipo de Periodo -->
            <div class="md:col-span-2">
                <label class="block text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] mb-3 ml-2">Tipo de ciclo academico</label>
                <select name="tipo_periodo_id" class="w-full bg-slate-50 border-none rounded-2xl p-5 text-sm font-bold text-slate-700 focus:ring-4 focus:ring-purple-100 transition-all appearance-none cursor-pointer" required>
                    <option value="">Seleccionar tipo de ciclo...</option>
                    <?php foreach($tipos as $t): ?>
                        <option value="<?= $t['id'] ?>"><?= strtoupper($t['nombre']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Fecha Inicio -->
            <div>
                <label class="block text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] mb-3 ml-2">Fecha de Inicio</label>
                <input type="date" name="fecha_inicio"
                    class="w-full bg-slate-50 border-none rounded-2xl p-5 text-sm font-bold text-slate-700 focus:ring-4 focus:ring-purple-100 transition-all" required>
            </div>

            <!-- Fecha Fin -->
            <div>
                <label class="block text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] mb-3 ml-2">Fecha de Cierre</label>
                <input type="date" name="fecha_fin"
                    class="w-full bg-slate-50 border-none rounded-2xl p-5 text-sm font-bold text-slate-700 focus:ring-4 focus:ring-purple-100 transition-all" required>
            </div>

            <!-- Botón Submit -->
            <div class="md:col-span-2 mt-4">
                <button class="w-full bg-slate-900 text-white rounded-2xl py-5 font-black uppercase text-xs tracking-[0.3em] hover:bg-purple-600 hover:-translate-y-1 transition-all shadow-xl shadow-purple-100">
                    Registrar Periodo Académico
                </button>
            </div>
        </div>
    </form>
</div>

<style>
@keyframes slide-up { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }
@keyframes shake { 0%, 100% { transform: translateX(0); } 25% { transform: translateX(-5px); } 75% { transform: translateX(5px); } }
@keyframes pop { 0% { transform: scale(0.95); } 100% { transform: scale(1); } }
.animate-fade-in { animation: slide-up 0.6s cubic-bezier(0.16, 1, 0.3, 1); }
.animate-shake { animation: shake 0.4s ease-in-out; }
.animate-pop { animation: pop 0.3s cubic-bezier(0.34, 1.56, 0.64, 1); }
</style>