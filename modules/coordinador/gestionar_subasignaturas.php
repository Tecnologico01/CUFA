<?php
require_once __DIR__ . '/../../includes/db.php';

$error = '';
$mensaje = '';

/* =========================
    REGISTRAR (SOLO CREAR)
========================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $clave = trim($_POST['clave']);
    $nombre = trim($_POST['nombre']);
    $horas_fg = $_POST['horas_frente_grupo'] ?? 0;
    $horas_ind = $_POST['horas_independiente'] ?? 0;
    $creditos = $_POST['creditos'] ?? 0;
    $recurso = $_POST['recurso'] ?? '';
    $descripcion = trim($_POST['descripcion']);

    if (!$clave || !$nombre) {
        $error = "La clave y el nombre son campos obligatorios.";
    }

    if (!$error) {
        try {
            $stmt = $pdo->prepare("
                INSERT INTO subasignaturas 
                (clave, nombre, horas_frente_grupo, horas_independiente, creditos, recurso, descripcion) 
                VALUES (?,?,?,?,?,?,?)
            ");
            $stmt->execute([$clave, $nombre, $horas_fg, $horas_ind, $creditos, $recurso, $descripcion]);
            $mensaje = "Subasignatura registrada correctamente en el sistema.";
        } catch (PDOException $e) {
            $error = "Error de base de datos: " . $e->getMessage();
        }
    }
}

/* =========================
    LISTAR SOLO ÚLTIMAS 5
========================= */
$stmt = $pdo->query("
    SELECT * FROM subasignaturas 
    ORDER BY id DESC 
    LIMIT 5
");
$subasignaturas = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="max-w-7xl mx-auto p-6 bg-slate-50 min-h-screen animate-fade-in">

    <div class="mb-10">
        <h1 class="text-5xl font-black italic uppercase tracking-tighter text-slate-800 leading-none">
            Subasignaturas
        </h1>
        <p class="text-purple-600 font-bold text-[10px] uppercase tracking-[0.3em] italic mt-2">
            Panel de Alta de Módulos Académicos
        </p>
    </div>

    <?php if($error): ?>
        <div class="bg-red-50 border-l-4 border-red-500 text-red-700 p-4 rounded-2xl mb-6 font-bold text-sm">
            <?= $error ?>
        </div>
    <?php endif; ?>

    <?php if($mensaje): ?>
        <div class="bg-emerald-50 border-l-4 border-emerald-500 text-emerald-700 p-4 rounded-2xl mb-6 font-bold text-sm">
            <?= $mensaje ?>
        </div>
    <?php endif; ?>

    <div class="bg-white p-10 rounded-[3rem] shadow-sm border border-slate-100 mb-12 relative overflow-hidden">
        
        <div class="absolute top-0 right-0 p-8 select-none pointer-events-none">
            <span class="text-[70px] font-black text-slate-50 italic leading-none uppercase">Nuevo</span>
        </div>

        <form method="POST" class="relative z-10 grid grid-cols-1 md:grid-cols-12 gap-8">

            <div class="md:col-span-3">
                <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest block mb-2">Clave Identificadora</label>
                <input name="clave" required 
                       class="w-full p-4 bg-slate-50 border-none rounded-2xl font-bold text-slate-700 focus:ring-4 focus:ring-purple-500/10 transition-all outline-none">
            </div>

            <div class="md:col-span-9">
                <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest block mb-2">Nombre del Módulo / Subasignatura</label>
                <input name="nombre" required 
                       class="w-full p-4 bg-slate-50 border-none rounded-2xl font-bold text-slate-700 focus:ring-4 focus:ring-purple-500/10 transition-all outline-none">
            </div>

            <div class="md:col-span-3">
                <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest block mb-2">Horas Docente (FG)</label>
                <input type="number" name="horas_frente_grupo" 
                       class="w-full p-4 bg-slate-50 border-none rounded-2xl font-bold text-slate-700 focus:ring-4 focus:ring-purple-500/10 transition-all outline-none">
            </div>

            <div class="md:col-span-3">
                <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest block mb-2">Horas Indep. (IND)</label>
                <input type="number" name="horas_independiente" 
                       class="w-full p-4 bg-slate-50 border-none rounded-2xl font-bold text-slate-700 focus:ring-4 focus:ring-purple-500/10 transition-all outline-none">
            </div>

            <div class="md:col-span-3">
                <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest block mb-2">Créditos Totales</label>
                <input type="number" name="creditos" 
                       class="w-full p-4 bg-slate-50 border-none rounded-2xl font-bold text-emerald-600 focus:ring-4 focus:ring-purple-500/10 transition-all outline-none">
            </div>

            <div class="md:col-span-3">
                <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest block mb-2">Modalidad</label>
                <select name="recurso" 
                        class="w-full p-4 bg-slate-50 border-none rounded-2xl font-black text-slate-700 focus:ring-4 focus:ring-purple-500/10 transition-all outline-none text-xs uppercase italic">
                    <option>Presencial</option>
                    <option>Virtual</option>
                    <option>Mixto</option>
                </select>
            </div>

            <div class="md:col-span-12">
                <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest block mb-2">Descripción del Contenido</label>
                <textarea name="descripcion" rows="3" 
                          class="w-full p-4 bg-slate-50 border-none rounded-[2rem] font-medium text-slate-600 focus:ring-4 focus:ring-purple-500/10 transition-all outline-none"></textarea>
            </div>

            <div class="md:col-span-12 flex justify-end pt-4">
                <button class="bg-slate-900 text-white px-12 py-4 rounded-2xl font-black uppercase text-[10px] tracking-[0.2em] hover:bg-purple-600 transition-all shadow-xl shadow-slate-200">
                    Registrar Módulo
                </button>
            </div>

        </form>

    </div>

    <div class="mt-16">
        <div class="flex items-center gap-4 mb-8">
            <h2 class="text-xl font-black italic uppercase text-slate-800 tracking-tight">Últimos Registros</h2>
            <div class="h-[2px] flex-1 bg-slate-200/50"></div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">

            <?php foreach($subasignaturas as $s): ?>
            <div class="group bg-white p-8 rounded-[2.5rem] border border-slate-100 shadow-sm hover:shadow-xl transition-all relative overflow-hidden">
                
                <div class="absolute top-0 right-0 p-6 opacity-10 font-black italic text-4xl group-hover:text-purple-600 transition-colors">
                    #<?= $s['id'] ?>
                </div>

                <div class="relative z-10">
                    <span class="text-[10px] font-black text-purple-600 italic tracking-widest block mb-2">
                        <?= $s['clave'] ?>
                    </span>

                    <h3 class="text-xl font-black text-slate-800 leading-tight mb-4">
                        <?= $s['nombre'] ?>
                    </h3>

                    <div class="grid grid-cols-2 gap-4 pt-4 border-t border-slate-50">
                        <div>
                            <label class="text-[8px] font-black text-slate-300 uppercase block mb-1 tracking-tighter">Carga Horaria</label>
                            <p class="text-[11px] font-black text-slate-500 italic uppercase">
                                <?= $s['horas_frente_grupo'] ?>h / <?= $s['horas_independiente'] ?>h
                            </p>
                        </div>
                        <div>
                            <label class="text-[8px] font-black text-slate-300 uppercase block mb-1 tracking-tighter">Créditos</label>
                            <p class="text-[11px] font-black text-emerald-600 italic">
                                <?= $s['creditos'] ?> pts
                            </p>
                        </div>
                    </div>

                    <div class="mt-4">
                        <span class="inline-block px-3 py-1 bg-slate-50 rounded-full text-[9px] font-black text-slate-400 uppercase italic group-hover:bg-purple-50 group-hover:text-purple-600 transition-colors border border-slate-100">
                            <?= $s['recurso'] ?>
                        </span>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>

        </div>
    </div>

</div>

<style>
@keyframes slide-up { 
    from { opacity: 0; transform: translateY(15px); } 
    to { opacity: 1; transform: translateY(0); } 
}
.animate-fade-in { animation: slide-up 0.5s ease-out; }

/* Quitar flechas en inputs de número */
input::-webkit-outer-spin-button,
input::-webkit-inner-spin-button {
    -webkit-appearance: none;
    margin: 0;
}
</style>