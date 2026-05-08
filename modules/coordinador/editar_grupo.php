<?php
require_once __DIR__ . '/../../includes/db.php';

$id = $_GET['id'] ?? null;

if(!$id){
    echo "Grupo no encontrado";
    exit;
}

/* =========================
    OBTENER GRUPO
========================= */
$stmt = $pdo->prepare("
    SELECT * FROM grupos WHERE id = ?
");
$stmt->execute([$id]);
$grupo = $stmt->fetch(PDO::FETCH_ASSOC);

if(!$grupo){
    echo "Grupo no existe";
    exit;
}

/* =========================
    CARRERAS (LOCAL)
========================= */
$carreras = $pdo->query("
    SELECT id, nombre 
    FROM carreras 
    ORDER BY nombre ASC
")->fetchAll(PDO::FETCH_ASSOC);

/* =========================
    PERIODOS
========================= */
$periodos = $pdo->query("
    SELECT id, nombre 
    FROM periodos 
    WHERE activo = 1
    ORDER BY id DESC
")->fetchAll(PDO::FETCH_ASSOC);

/* =========================
    ACTUALIZAR
========================= */
if($_SERVER['REQUEST_METHOD'] === 'POST'){

    $nombre = trim($_POST['nombre'] ?? '');
    $periodo_id = $_POST['periodo_id'] ?? '';
    $carrera_id = $_POST['carrera_id'] ?? '';

    if(!$nombre || !$periodo_id || !$carrera_id){
        $error = "Todos los campos son obligatorios";
    } else {

        /* VALIDAR DUPLICADO */
        $stmt = $pdo->prepare("
            SELECT id FROM grupos 
            WHERE nombre = ? AND periodo_id = ? AND id != ?
        ");
        $stmt->execute([$nombre, $periodo_id, $id]);

        if($stmt->fetch()){
            $error = "Ya existe un grupo con ese nombre en el mismo periodo";
        } else {

            $stmt = $pdo->prepare("
                UPDATE grupos 
                SET nombre = ?, periodo_id = ?, carrera_id = ?
                WHERE id = ?
            ");

            if($stmt->execute([$nombre, $periodo_id, $carrera_id, $id])){
                $success = "Grupo actualizado correctamente";
                
                // Opcional: redirigir
                echo "<script>
                    window.location.href='?modulo=lista_grupos';
                </script>";
                exit;

            } else {
                $error = "Error al actualizar";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;700;800&display=swap');
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
        @keyframes slideUp { from { opacity: 0; transform: translateY(15px); } to { opacity: 1; transform: translateY(0); } }
        .animate-fade-in { animation: slideUp 0.5s cubic-bezier(0.16, 1, 0.3, 1); }
    </style>
</head>

<body class="bg-slate-50 min-h-screen flex items-center justify-center p-6">

    <div class="max-w-2xl w-full animate-fade-in">
        
        <div class="flex items-center justify-between mb-8 px-4">
            <div>
                <div class="flex items-center gap-2 mb-1">
                    <span class="bg-purple-600 w-2 h-2 rounded-full shadow-[0_0_10px_rgba(147,51,234,0.5)]"></span>
                    <span class="text-[10px] font-black text-slate-400 uppercase tracking-[0.2em]">Gestión Escolar</span>
                </div>
                <h1 class="text-4xl font-black text-slate-900 tracking-tighter uppercase leading-none">
                    Editar <span class="text-purple-600 italic text-3xl">Grupo</span>
                </h1>
            </div>
            <div class="text-right">
                <span class="text-[9px] font-black text-slate-300 uppercase tracking-widest block">Registro Interno</span>
                <span class="text-lg font-mono font-bold text-slate-400">#GRP-<?= str_pad($grupo['id'], 3, '0', STR_PAD_LEFT) ?></span>
            </div>
        </div>

        <?php if(!empty($error)): ?>
            <div class="mb-6 p-5 bg-white border-l-4 border-red-500 rounded-2xl shadow-xl shadow-red-100/50 flex items-center gap-4">
                <div class="bg-red-50 p-2 rounded-lg text-red-500">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                </div>
                <span class="text-xs font-bold text-slate-700 uppercase tracking-tight"><?= $error ?></span>
            </div>
        <?php endif; ?>

        <?php if(!empty($success)): ?>
            <div class="mb-6 p-5 bg-white border-l-4 border-emerald-500 rounded-2xl shadow-xl shadow-emerald-100/50 flex items-center gap-4">
                <div class="bg-emerald-50 p-2 rounded-lg text-emerald-500">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                </div>
                <span class="text-xs font-bold text-slate-700 uppercase tracking-tight"><?= $success ?></span>
            </div>
        <?php endif; ?>

        <div class="bg-white p-10 rounded-[2.5rem] shadow-2xl shadow-slate-200 border border-white relative overflow-hidden">
            <div class="absolute top-0 right-0 w-32 h-32 bg-slate-50 rounded-full -mr-16 -mt-16 opacity-50"></div>

            <form method="POST" class="relative z-10 space-y-8">

                <div>
                    <label class="text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] ml-2 mb-3 block">Identificador del Grupo</label>
                    <input type="text" name="nombre" required
                        value="<?= htmlspecialchars($grupo['nombre']) ?>"
                        placeholder="Ej. 2do Cuatrimestre A"
                        class="w-full bg-slate-50 border-2 border-transparent focus:border-purple-600 focus:bg-white p-4 rounded-2xl text-sm font-bold text-slate-700 outline-none transition-all uppercase tracking-wider">
                </div>

                <div>
                    <label class="text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] ml-2 mb-3 block">Ciclo Escolar Vigente</label>
                    <div class="relative">
                        <select name="periodo_id" required 
                            class="w-full bg-slate-50 border-2 border-transparent focus:border-purple-600 focus:bg-white p-4 rounded-2xl text-sm font-bold text-slate-700 outline-none transition-all appearance-none cursor-pointer uppercase">
                            <?php foreach($periodos as $p): ?>
                                <option value="<?= $p['id'] ?>" <?= $grupo['periodo_id'] == $p['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($p['nombre']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <div class="absolute inset-y-0 right-4 flex items-center pointer-events-none text-slate-400">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M19 9l-7 7-7-7"></path></svg>
                        </div>
                    </div>
                </div>

                <div>
                    <label class="text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] ml-2 mb-3 block">Programa Académico</label>
                    <div class="relative">
                        <select name="carrera_id" required 
                            class="w-full bg-slate-50 border-2 border-transparent focus:border-purple-600 focus:bg-white p-4 rounded-2xl text-sm font-bold text-slate-700 outline-none transition-all appearance-none cursor-pointer uppercase">
                            <?php foreach($carreras as $c): ?>
                                <option value="<?= $c['id'] ?>" <?= $grupo['carrera_id'] == $c['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($c['nombre']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <div class="absolute inset-y-0 right-4 flex items-center pointer-events-none text-slate-400">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M19 9l-7 7-7-7"></path></svg>
                        </div>
                    </div>
                </div>

                <div class="pt-4 flex flex-col md:flex-row gap-4">
                    <button type="submit" 
                        class="flex-[2] bg-slate-900 text-white p-5 rounded-3xl font-black uppercase tracking-[0.2em] text-[10px] hover:bg-purple-600 hover:shadow-xl hover:shadow-purple-200 transition-all active:scale-[0.97]">
                        Actualizar Grupo
                    </button>
                    
                    <a href="?modulo=lista_grupos" 
                        class="flex-1 bg-white border-2 border-slate-100 text-slate-400 p-5 rounded-3xl font-black uppercase tracking-[0.2em] text-[10px] text-center hover:bg-slate-50 transition-all">
                        Cancelar
                    </a>
                </div>

            </form>
        </div>

        <p class="text-center mt-10 text-[10px] font-black text-slate-300 uppercase tracking-[0.5em]">Academic Management Core // CUFA</p>
    </div>

</body>
</html>