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
                window.location.href='/sistema_academico/dashboards/coordinador_dashboard.php?modulo=subasignaturas';
            </script>";
            exit;

        }catch(Exception $e){
            echo "<script>alert('Error al actualizar');</script>";
        }
    }
}
?>

<div class="max-w-5xl mx-auto p-6 bg-slate-50 min-h-screen">

    <div class="flex items-center gap-6 mb-10">
        <a href="/sistema_academico/dashboards/coordinador_dashboard.php?modulo=subasignaturas"
           class="group bg-white p-4 rounded-[1.5rem] shadow-sm border border-slate-100 text-slate-400 hover:text-purple-600 transition-all transform hover:-translate-x-1">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path d="M15 19l-7-7 7-7" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"></path>
            </svg>
        </a>

        <div>
            <h1 class="text-4xl font-black italic uppercase tracking-tighter text-slate-800">
                Editar Registro
            </h1>
            <p class="text-purple-600 font-bold text-[10px] uppercase tracking-[0.3em] italic">
                Modificando: <?= $sub['clave'] ?>
            </p>
        </div>
    </div>

    <form method="POST" class="bg-white p-10 rounded-[3rem] shadow-sm border border-slate-100 grid grid-cols-1 md:grid-cols-2 gap-8 animate-fade-in">

        <div class="space-y-1">
            <label class="text-[10px] font-black text-slate-400 uppercase ml-1">Clave Identificadora</label>
            <input name="clave" value="<?= $sub['clave'] ?>" 
                class="w-full p-4 bg-slate-50 border-2 border-slate-100 rounded-2xl font-bold outline-none focus:border-purple-500 transition-all shadow-inner">
        </div>

        <div class="space-y-1">
            <label class="text-[10px] font-black text-slate-400 uppercase ml-1">Créditos Académicos</label>
            <input type="number" name="creditos" value="<?= $sub['creditos'] ?>" 
                class="w-full p-4 bg-emerald-50 border-2 border-emerald-100 rounded-2xl font-black text-emerald-700 text-center text-xl outline-none shadow-inner">
        </div>

        <div class="md:col-span-2 space-y-1">
            <label class="text-[10px] font-black text-slate-400 uppercase ml-1">Nombre Oficial de la Subasignatura</label>
            <input name="nombre" value="<?= $sub['nombre'] ?>" 
                class="w-full p-4 bg-slate-50 border-2 border-slate-100 rounded-2xl font-bold outline-none focus:border-purple-500 transition-all shadow-inner">
        </div>

        <div class="space-y-1">
            <label class="text-[10px] font-black text-slate-400 uppercase ml-1 italic">Horas Frente a Grupo</label>
            <input type="number" name="horas_frente_grupo" value="<?= $sub['horas_frente_grupo'] ?>" 
                class="w-full p-4 bg-blue-50/50 border-2 border-blue-100 rounded-2xl font-bold outline-none shadow-inner">
        </div>

        <div class="space-y-1">
            <label class="text-[10px] font-black text-slate-400 uppercase ml-1 italic">Horas Independientes</label>
            <input type="number" name="horas_independiente" value="<?= $sub['horas_independiente'] ?>" 
                class="w-full p-4 bg-indigo-50/50 border-2 border-indigo-100 rounded-2xl font-bold outline-none shadow-inner">
        </div>

        <div class="md:col-span-2 space-y-1">
            <label class="text-[10px] font-black text-slate-400 uppercase ml-1">Modalidad de Impartición</label>
            <select name="recurso" class="w-full p-4 bg-slate-50 border-2 border-slate-100 rounded-2xl font-bold outline-none appearance-none">
                <option <?= $sub['recurso']=='Presencial'?'selected':'' ?>>Presencial</option>
                <option <?= $sub['recurso']=='Virtual'?'selected':'' ?>>Virtual</option>
                <option <?= $sub['recurso']=='Mixto'?'selected':'' ?>>Mixto</option>
            </select>
        </div>

        <div class="md:col-span-2 space-y-1">
            <label class="text-[10px] font-black text-slate-400 uppercase ml-1">Descripción del Contenido</label>
            <textarea name="descripcion" rows="4"
                class="w-full p-4 bg-slate-50 border-2 border-slate-100 rounded-2xl font-bold outline-none focus:border-purple-500 transition-all min-h-[120px] shadow-inner"><?= $sub['descripcion'] ?></textarea>
        </div>

        <div class="md:col-span-2 pt-4">
            <button class="w-full py-5 bg-slate-900 text-white rounded-[2rem] font-black uppercase tracking-[0.2em] text-xs shadow-xl hover:bg-purple-600 transition-all transform hover:-translate-y-1">
                Actualizar Información
            </button>
        </div>

    </form>
</div>

<style>
@keyframes fade-in { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
.animate-fade-in { animation: fade-in 0.4s ease-out; }

select { 
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke='%23cbd5e1'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M19 9l-7 7-7-7'%3E%3C/path%3E%3C/svg%3E"); 
    background-repeat: no-repeat; 
    background-position: right 1.5rem center; 
    background-size: 1.2rem; 
}
</style>