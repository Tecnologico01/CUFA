<?php
require_once __DIR__ . '/../../includes/db.php';

$id = $_GET['id'] ?? null;

if(!$id){
    echo "Subasignatura no especificada";
    exit;
}

/* =========================
   OBTENER DATOS
========================= */
$stmt = $pdo->prepare("SELECT * FROM subasignaturas WHERE id = ?");
$stmt->execute([$id]);
$sub = $stmt->fetch();

if(!$sub){
    echo "Subasignatura no encontrada";
    exit;
}

/* =========================
   ACTUALIZAR
========================= */
if($_SERVER['REQUEST_METHOD'] === 'POST'){

    if(empty($_POST['clave']) || empty($_POST['nombre'])){
        echo "<script>alert('Faltan datos obligatorios');</script>";
    }else{

        try{
            $stmt = $pdo->prepare("
                UPDATE subasignaturas SET
                    clave = ?,
                    nombre = ?,
                    horas_frente_grupo = ?,
                    horas_independiente = ?,
                    creditos = ?,
                    recurso = ?,
                    descripcion = ?
                WHERE id = ?
            ");

            $stmt->execute([
                $_POST['clave'],
                $_POST['nombre'],
                $_POST['horas_frente_grupo'],
                $_POST['horas_independiente'],
                $_POST['creditos'],
                $_POST['recurso'],
                $_POST['descripcion'],
                $id
            ]);

            echo "<script>
                alert('Subasignatura actualizada correctamente');
                window.location.href='/sistema_academico/dashboards/admin_dashboard.php?modulo=lista_subasignaturas';
            </script>";
            exit;

        }catch(Exception $e){
            echo "<script>alert('Error al actualizar');</script>";
        }
    }
}
?>

<div class="max-w-5xl mx-auto p-6 bg-slate-50 min-h-screen animate-fade-in">

    <div class="flex items-center gap-8 mb-12">
        <a href="/sistema_academico/dashboards/admin_dashboard.php?modulo=subasignaturas"
           class="group bg-white p-5 rounded-[2rem] shadow-sm border border-slate-100 text-slate-400 hover:text-purple-600 transition-all transform hover:-translate-x-2">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path d="M15 19l-7-7 7-7" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"></path>
            </svg>
        </a>

        <div>
            <h1 class="text-5xl font-black italic uppercase tracking-tighter text-slate-800 leading-none">
                Editar Registro
            </h1>
            <p class="text-purple-600 font-bold text-[10px] uppercase tracking-[0.3em] italic mt-2">
                Actualización de subasignatura: <span class="text-slate-400"><?= htmlspecialchars($sub['clave']) ?></span>
            </p>
        </div>
    </div>

    <form method="POST" class="bg-white p-12 rounded-[3.5rem] shadow-sm border border-slate-100 relative overflow-hidden">
        
        <div class="absolute top-0 right-0 p-10 opacity-[0.03] select-none pointer-events-none">
            <span class="text-9xl font-black italic uppercase tracking-tighter">Edit</span>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-6 gap-8 relative z-10">

            <div class="md:col-span-4 space-y-2">
                <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-2">Clave</label>
                <input name="clave" value="<?= htmlspecialchars($sub['clave']) ?>" 
                    class="w-full p-5 bg-slate-50 border-2 border-slate-100 rounded-[1.5rem] font-bold text-slate-700 outline-none focus:border-purple-500 focus:bg-white transition-all shadow-inner">
            </div>

            <div class="md:col-span-2 space-y-2">
                <label class="text-[10px] font-black text-emerald-600 uppercase tracking-widest ml-2">Credítos</label>
                <div class="relative">
                    <input type="number" name="creditos" value="<?= $sub['creditos'] ?>" 
                        class="w-full p-5 bg-emerald-50/50 border-2 border-emerald-100 rounded-[1.5rem] font-black text-emerald-700 text-center text-2xl outline-none shadow-inner">
                    <span class="absolute right-4 top-1/2 -translate-y-1/2 text-[8px] font-black text-emerald-300 uppercase italic">pts</span>
                </div>
            </div>

            <div class="md:col-span-6 space-y-2">
                <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-2">Nombre de la Subasignatura</label>
                <input name="nombre" value="<?= htmlspecialchars($sub['nombre']) ?>" 
                    class="w-full p-5 bg-slate-50 border-2 border-slate-100 rounded-[1.5rem] font-black text-slate-800 text-lg outline-none focus:border-purple-500 focus:bg-white transition-all shadow-inner">
            </div>

            <div class="md:col-span-2 space-y-2">
                <label class="text-[9px] font-black text-slate-400 uppercase tracking-widest ml-2 italic">Horas Frente Grupo</label>
                <div class="relative">
                    <input type="number" name="horas_frente_grupo" value="<?= $sub['horas_frente_grupo'] ?>" 
                        class="w-full p-5 bg-blue-50/30 border-2 border-blue-100 rounded-[1.5rem] font-bold text-slate-600 outline-none shadow-inner">
                    <span class="absolute right-4 top-1/2 -translate-y-1/2 text-[8px] font-black text-blue-300 uppercase italic">Hrs</span>
                </div>
            </div>

            <div class="md:col-span-2 space-y-2">
                <label class="text-[9px] font-black text-slate-400 uppercase tracking-widest ml-2 italic">Horas Independientes</label>
                <div class="relative">
                    <input type="number" name="horas_independiente" value="<?= $sub['horas_independiente'] ?>" 
                        class="w-full p-5 bg-indigo-50/30 border-2 border-indigo-100 rounded-[1.5rem] font-bold text-slate-600 outline-none shadow-inner">
                    <span class="absolute right-4 top-1/2 -translate-y-1/2 text-[8px] font-black text-indigo-300 uppercase italic">Hrs</span>
                </div>
            </div>

            <div class="md:col-span-2 space-y-2">
                <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-2">Modalidad</label>
                <select name="recurso" class="w-full p-5 bg-slate-50 border-2 border-slate-100 rounded-[1.5rem] font-bold text-slate-700 outline-none appearance-none cursor-pointer focus:border-purple-500 transition-all">
                    <option <?= $sub['recurso']=='Presencial'?'selected':'' ?>>Presencial</option>
                    <option <?= $sub['recurso']=='Virtual'?'selected':'' ?>>Virtual</option>
                    <option <?= $sub['recurso']=='Mixto'?'selected':'' ?>>Mixto</option>
                </select>
            </div>

            <div class="md:col-span-6 space-y-2">
                <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-2">Descripción</label>
                <textarea name="descripcion" rows="5"
                    class="w-full p-6 bg-slate-50 border-2 border-slate-100 rounded-[2rem] font-bold text-slate-600 outline-none focus:border-purple-500 focus:bg-white transition-all min-h-[140px] shadow-inner"><?= htmlspecialchars($sub['descripcion']) ?></textarea>
            </div>

            <div class="md:col-span-6 pt-6">
                <button class="group w-full py-6 bg-slate-900 text-white rounded-[2.5rem] font-black uppercase tracking-[0.3em] text-[11px] shadow-2xl hover:bg-purple-600 transition-all transform hover:-translate-y-1 flex items-center justify-center gap-4">
                    <span>Confirmar Cambios</span>
                    <svg class="w-5 h-5 group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path d="M13 7l5 5m0 0l-5 5m5-5H6" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"></path>
                    </svg>
                </button>
            </div>

        </div>
    </form>
</div>

<style>
@keyframes slide-up { 
    from { opacity: 0; transform: translateY(30px); } 
    to { opacity: 1; transform: translateY(0); } 
}
.animate-fade-in { animation: slide-up 0.7s cubic-bezier(0.16, 1, 0.3, 1); }

/* Estilo para los inputs  */
input::-webkit-outer-spin-button,
input::-webkit-inner-spin-button {
  -webkit-appearance: none;
  margin: 0;
}
</style>