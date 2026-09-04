<?php
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/permisos.php';

validarPermiso('editar_subasignaturas');

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

if(!$id){
    echo "
    <div class='p-6'>
        <div class='bg-red-50 border border-red-200 text-red-600 p-4 rounded-2xl font-bold'>
            Subasignatura no especificada.
        </div>
    </div>";
    exit;
}

/* =========================
   OBTENER DATOS
========================= */
$stmt = $pdo->prepare("
    SELECT *
    FROM subasignaturas
    WHERE id = ?
");
$stmt->execute([$id]);
$sub = $stmt->fetch(PDO::FETCH_ASSOC);

if(!$sub){
    echo "
    <div class='p-6'>
        <div class='bg-red-50 border border-red-200 text-red-600 p-4 rounded-2xl font-bold'>
            Subasignatura no encontrada.
        </div>
    </div>";
    exit;
}

$error = '';

/* =========================
   ACTUALIZAR
========================= */
if($_SERVER['REQUEST_METHOD'] === 'POST'){

    $clave = strtoupper(trim($_POST['clave'] ?? ''));
    $nombre = trim($_POST['nombre'] ?? '');

    $horas_fg = is_numeric($_POST['horas_frente_grupo'] ?? null)
        ? (int) $_POST['horas_frente_grupo']
        : 0;

    $horas_ind = is_numeric($_POST['horas_independiente'] ?? null)
        ? (int) $_POST['horas_independiente']
        : 0;

    $creditos = is_numeric($_POST['creditos'] ?? null)
        ? (int) $_POST['creditos']
        : 0;

    $recurso = trim($_POST['recurso'] ?? 'Presencial');
    $descripcion = trim($_POST['descripcion'] ?? '');

    if(!$clave || !$nombre){
        $error = "La clave y el nombre son obligatorios.";
    }elseif(strlen($clave) > 30){
        $error = "La clave es demasiado larga.";
    }elseif(strlen($nombre) > 255){
        $error = "El nombre es demasiado largo.";
    }

    if(!$error){

        try{

            $pdo->beginTransaction();

            /* =========================
               VALIDAR DUPLICADO CLAVE
            ========================= */
            $stmtDupClave = $pdo->prepare("
                SELECT COUNT(*)
                FROM subasignaturas
                WHERE clave = ?
                AND id != ?
            ");
            $stmtDupClave->execute([$clave, $id]);

            if($stmtDupClave->fetchColumn() > 0){
                throw new Exception("Ya existe otra subasignatura con esa clave.");
            }

            /* =========================
               VALIDAR DUPLICADO NOMBRE
            ========================= */
            $stmtDupNombre = $pdo->prepare("
                SELECT COUNT(*)
                FROM subasignaturas
                WHERE nombre = ?
                AND id != ?
            ");
            $stmtDupNombre->execute([$nombre, $id]);

            if($stmtDupNombre->fetchColumn() > 0){
                throw new Exception("Ya existe otra subasignatura con ese nombre.");
            }

            /* =========================
               ACTUALIZAR REGISTRO
            ========================= */
            $stmtUpdate = $pdo->prepare("
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

            $stmtUpdate->execute([
                $clave,
                $nombre,
                $horas_fg,
                $horas_ind,
                $creditos,
                $recurso,
                $descripcion,
                $id
            ]);

            $pdo->commit();

            echo "
            <script>
                alert('Subasignatura actualizada correctamente');
                window.location.href='?modulo=lista_subasignaturas';
            </script>";
            exit;

        }catch(Exception $e){

            if($pdo->inTransaction()){
                $pdo->rollBack();
            }

            $error = $e->getMessage();
        }
    }

    $stmt = $pdo->prepare("
        SELECT *
        FROM subasignaturas
        WHERE id = ?
    ");
    $stmt->execute([$id]);
    $sub = $stmt->fetch(PDO::FETCH_ASSOC);
}
?>

<div class="max-w-5xl mx-auto p-6 bg-slate-50 min-h-screen animate-fade-in">

    <div class="flex items-center gap-8 mb-12">
        <a href="?modulo=lista_subasignaturas"
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
                Actualización de subasignatura:
                <span class="text-slate-400">
                    <?= htmlspecialchars($sub['clave']) ?>
                </span>
            </p>
        </div>
    </div>

    <?php if($error): ?>
        <div class="bg-red-50 border border-red-200 text-red-700 p-5 rounded-2xl mb-8 font-bold">
            <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>

    <form method="POST" class="bg-white p-12 rounded-[3.5rem] shadow-sm border border-slate-100 relative overflow-hidden">
        
        <div class="absolute top-0 right-0 p-10 opacity-[0.03] select-none pointer-events-none">
            <span class="text-9xl font-black italic uppercase tracking-tighter">Edit</span>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-6 gap-8 relative z-10">

            <div class="md:col-span-4 space-y-2">
                <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-2">
                    Clave
                </label>

                <input 
                    name="clave" 
                    value="<?= htmlspecialchars($sub['clave']) ?>" 
                    required
                    class="w-full p-5 bg-slate-50 border-2 border-slate-100 rounded-[1.5rem] font-bold text-slate-700 outline-none focus:border-purple-500 focus:bg-white transition-all shadow-inner uppercase">
            </div>

            <div class="md:col-span-2 space-y-2">
                <label class="text-[10px] font-black text-emerald-600 uppercase tracking-widest ml-2">
                    Créditos
                </label>

                <div class="relative">
                    <input 
                        type="number" 
                        min="0" 
                        name="creditos" 
                        value="<?= htmlspecialchars($sub['creditos']) ?>" 
                        class="w-full p-5 bg-emerald-50/50 border-2 border-emerald-100 rounded-[1.5rem] font-black text-emerald-700 text-center text-2xl outline-none shadow-inner">

                    <span class="absolute right-4 top-1/2 -translate-y-1/2 text-[8px] font-black text-emerald-300 uppercase italic">
                        pts
                    </span>
                </div>
            </div>

            <div class="md:col-span-6 space-y-2">
                <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-2">
                    Nombre de la Subasignatura
                </label>

                <input 
                    name="nombre" 
                    value="<?= htmlspecialchars($sub['nombre']) ?>" 
                    required
                    class="w-full p-5 bg-slate-50 border-2 border-slate-100 rounded-[1.5rem] font-black text-slate-800 text-lg outline-none focus:border-purple-500 focus:bg-white transition-all shadow-inner">
            </div>

            <div class="md:col-span-2 space-y-2">
                <label class="text-[9px] font-black text-slate-400 uppercase tracking-widest ml-2 italic">
                    Horas Frente Grupo
                </label>

                <div class="relative">
                    <input 
                        type="number" 
                        min="0" 
                        name="horas_frente_grupo" 
                        value="<?= htmlspecialchars($sub['horas_frente_grupo']) ?>" 
                        class="w-full p-5 bg-blue-50/30 border-2 border-blue-100 rounded-[1.5rem] font-bold text-slate-600 outline-none shadow-inner">

                    <span class="absolute right-4 top-1/2 -translate-y-1/2 text-[8px] font-black text-blue-300 uppercase italic">
                        Hrs
                    </span>
                </div>
            </div>

            <div class="md:col-span-2 space-y-2">
                <label class="text-[9px] font-black text-slate-400 uppercase tracking-widest ml-2 italic">
                    Horas Independientes
                </label>

                <div class="relative">
                    <input 
                        type="number" 
                        min="0" 
                        name="horas_independiente" 
                        value="<?= htmlspecialchars($sub['horas_independiente']) ?>" 
                        class="w-full p-5 bg-indigo-50/30 border-2 border-indigo-100 rounded-[1.5rem] font-bold text-slate-600 outline-none shadow-inner">

                    <span class="absolute right-4 top-1/2 -translate-y-1/2 text-[8px] font-black text-indigo-300 uppercase italic">
                        Hrs
                    </span>
                </div>
            </div>

            <div class="md:col-span-2 space-y-2">
                <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-2">
                    Modalidad
                </label>

                <select 
                    name="recurso" 
                    class="w-full p-5 bg-slate-50 border-2 border-slate-100 rounded-[1.5rem] font-bold text-slate-700 outline-none appearance-none cursor-pointer focus:border-purple-500 transition-all">
                    <option value="Presencial" <?= $sub['recurso'] === 'Presencial' ? 'selected' : '' ?>>Presencial</option>
                    <option value="Virtual" <?= $sub['recurso'] === 'Virtual' ? 'selected' : '' ?>>Virtual</option>
                    <option value="Mixto" <?= $sub['recurso'] === 'Mixto' ? 'selected' : '' ?>>Mixto</option>
                    <option value="Mixta" <?= $sub['recurso'] === 'Mixta' ? 'selected' : '' ?>>Mixta</option>
                </select>
            </div>

            <div class="md:col-span-6 space-y-2">
                <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-2">
                    Descripción
                </label>

                <textarea 
                    name="descripcion" 
                    rows="5"
                    class="w-full p-6 bg-slate-50 border-2 border-slate-100 rounded-[2rem] font-bold text-slate-600 outline-none focus:border-purple-500 focus:bg-white transition-all min-h-[140px] shadow-inner"><?= htmlspecialchars($sub['descripcion'] ?? '') ?></textarea>
            </div>

            <div class="md:col-span-6 pt-6">
                <button 
                    type="submit"
                    class="group w-full py-6 bg-slate-900 text-white rounded-[2.5rem] font-black uppercase tracking-[0.3em] text-[11px] shadow-2xl hover:bg-purple-600 transition-all transform hover:-translate-y-1 flex items-center justify-center gap-4">
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

.animate-fade-in { 
    animation: slide-up 0.7s cubic-bezier(0.16, 1, 0.3, 1); 
}

input::-webkit-outer-spin-button,
input::-webkit-inner-spin-button {
    -webkit-appearance: none;
    margin: 0;
}
</style>