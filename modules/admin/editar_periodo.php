<?php
require_once __DIR__ . '/../../includes/db.php';

$id = $_GET['id'] ?? null;

if (!$id) {
    die("ID no proporcionado");
}

/* Obtener periodo */
$stmt = $pdo->prepare("SELECT * FROM periodos WHERE id = ?");
$stmt->execute([$id]);
$periodo = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$periodo) {
    die("Periodo no encontrado");
}

/* Obtener tipos de periodo */
$tipos = $pdo->query("SELECT * FROM tipos_periodo")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Editar Periodo</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;700;800&display=swap');
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
        @keyframes slideUp { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }
        .animate-fade-in { animation: slideUp 0.6s cubic-bezier(0.16, 1, 0.3, 1); }
    </style>
</head>

<body class="bg-slate-50 flex items-center justify-center min-h-screen p-6">

    <div class="w-full max-w-xl animate-fade-in">
        
        <!-- Encabezado de Identidad -->
        <div class="flex items-center justify-between mb-8 px-4">
            <div>
                <div class="flex items-center gap-2 mb-1">
                    <span class="bg-purple-600 w-2 h-2 rounded-full animate-pulse"></span>
                    <span class="text-[10px] font-black text-slate-400 uppercase tracking-[0.2em]">Configuración</span>
                </div>
                <h1 class="text-4xl font-black text-slate-900 tracking-tighter uppercase leading-none">
                    Editar <span class="text-purple-600 italic">Periodo</span>
                </h1>
            </div>
            <div class="text-right">
                <span class="text-[9px] font-black text-slate-300 uppercase tracking-widest block">ID Registro</span>
                <span class="text-lg font-mono font-bold text-slate-400">#<?= str_pad($periodo['id'], 4, '0', STR_PAD_LEFT) ?></span>
            </div>
        </div>

        <!-- Card Principal -->
        <div class="bg-white p-10 rounded-[2.5rem] shadow-2xl shadow-slate-200/60 border border-white relative overflow-hidden">
            <!-- Decoración sutil -->
            <div class="absolute top-0 right-0 w-32 h-32 bg-purple-50 rounded-full -mr-16 -mt-16 opacity-50"></div>

            <form method="POST" action="/sistema_academico/modules/admin/guardar_periodo.php" class="relative z-10">

                <input type="hidden" name="id" value="<?= $periodo['id'] ?>">

                <!-- Nombre -->
                <div class="mb-6">
                    <label class="text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] ml-2 mb-3 block">Nombre descriptivo</label>
                    <input type="text" name="nombre" value="<?= htmlspecialchars($periodo['nombre']) ?>" required
                        class="w-full bg-slate-50 border-2 border-transparent focus:border-purple-600 focus:bg-white p-4 rounded-2xl text-sm font-bold text-slate-700 outline-none transition-all uppercase tracking-wider">
                </div>

                <!-- Tipo de periodo -->
                <div class="mb-6">
                    <label class="text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] ml-2 mb-3 block">Categoría de Ciclo</label>
                    <div class="relative">
                        <select name="tipo_periodo_id" required
                            class="w-full bg-slate-50 border-2 border-transparent focus:border-purple-600 focus:bg-white p-4 rounded-2xl text-sm font-bold text-slate-700 outline-none transition-all appearance-none cursor-pointer">
                            <?php foreach ($tipos as $t): ?>
                                <option value="<?= $t['id'] ?>" <?= $t['id'] == $periodo['tipo_periodo_id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($t['nombre']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <div class="absolute inset-y-0 right-4 flex items-center pointer-events-none text-slate-400">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M19 9l-7 7-7-7"></path></svg>
                        </div>
                    </div>
                </div>

                <!-- Fechas -->
                <div class="grid grid-cols-2 gap-6 mb-6">
                    <div>
                        <label class="text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] ml-2 mb-3 block">Fecha Inicio</label>
                        <input type="date" name="fecha_inicio" value="<?= $periodo['fecha_inicio'] ?>" required
                            class="w-full bg-slate-50 border-2 border-transparent focus:border-purple-600 focus:bg-white p-4 rounded-2xl text-sm font-bold text-slate-700 outline-none transition-all">
                    </div>

                    <div>
                        <label class="text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] ml-2 mb-3 block">Fecha Fin</label>
                        <input type="date" name="fecha_fin" value="<?= $periodo['fecha_fin'] ?>" required
                            class="w-full bg-slate-50 border-2 border-transparent focus:border-purple-600 focus:bg-white p-4 rounded-2xl text-sm font-bold text-slate-700 outline-none transition-all">
                    </div>
                </div>

                <!-- Estado -->
                <div class="mb-10">
                    <label class="text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] ml-2 mb-3 block">Estatus de Operación</label>
                    <div class="relative">
                        <select name="activo" 
                            class="w-full bg-slate-50 border-2 border-transparent focus:border-purple-600 focus:bg-white p-4 rounded-2xl text-sm font-bold text-slate-700 outline-none transition-all appearance-none cursor-pointer">
                            <option value="1" <?= $periodo['activo'] == 1 ? 'selected' : '' ?>>VIGENTE / ACTIVO</option>
                            <option value="0" <?= $periodo['activo'] == 0 ? 'selected' : '' ?>>CERRADO / INACTIVO</option>
                        </select>
                        <div class="absolute inset-y-0 right-4 flex items-center pointer-events-none text-slate-400">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M19 9l-7 7-7-7"></path></svg>
                        </div>
                    </div>
                </div>

                <!-- Botones -->
                <div class="flex flex-col md:flex-row gap-4">
                    <button type="submit" 
                        class="flex-[2] bg-slate-900 text-white p-5 rounded-3xl font-black uppercase tracking-[0.2em] text-[10px] hover:bg-purple-600 hover:shadow-xl hover:shadow-purple-200 transition-all active:scale-[0.98]">
                        Guardar Cambios
                    </button>

                    <a href="/sistema_academico/dashboards/admin_dashboard.php?modulo=periodos_lista"
                        class="flex-1 bg-white border-2 border-slate-100 text-slate-400 p-5 rounded-3xl font-black uppercase tracking-[0.2em] text-[10px] text-center hover:bg-slate-50 transition-all">
                        Cancelar
                    </a>
                </div>

            </form>
        </div>
        
        <p class="text-center mt-8 text-[10px] font-black text-slate-300 uppercase tracking-[0.4em]">Academic Management Core</p>
    </div>

</body>
</html>