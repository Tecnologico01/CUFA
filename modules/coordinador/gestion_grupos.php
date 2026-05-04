<?php
require_once __DIR__ . '/../../includes/db.php';

/* =========================
   OBTENER CARRERAS (API)
========================= */
$apiUrl = "https://sistema.cufa.edu.mx/api/carreras";
$apiKey = "H6z0U6FpnMPsgfCAe7ijkiXiL22YEE+ybjRtiZtDKmQ=";

$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => $apiUrl,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => ["X-API-Key: $apiKey"],
]);

$response = curl_exec($ch);

if (curl_errno($ch)) {
    die("Error en la conexión: " . curl_error($ch));
}

$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode !== 200) {
    die("Error HTTP: $httpCode");
}

$data = json_decode($response, true);
$carreras = $data['data'] ?? [];

/* =========================
   OBTENER PERIODOS ACTIVOS
========================= */
$stmt = $pdo->query("
    SELECT id, nombre 
    FROM periodos 
    WHERE activo = 1
    ORDER BY id DESC
");
$periodos = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* =========================
   GUARDAR GRUPO
========================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $nombre = trim($_POST['nombre'] ?? '');
    $periodo_id = $_POST['periodo_id'] ?? '';
    $carrera_nombre = $_POST['carrera_nombre'] ?? '';

    if (!$nombre || !$periodo_id || !$carrera_nombre) {
        $error = "Todos los campos son obligatorios";
    } else {

        // Validar duplicado
        $stmt = $pdo->prepare("
            SELECT id FROM grupos 
            WHERE nombre = ? AND periodo_id = ?
        ");
        $stmt->execute([$nombre, $periodo_id]);

        if ($stmt->fetch()) {
            $error = "El grupo ya existe en este periodo";
        } else {

            $stmt = $pdo->prepare("
                INSERT INTO grupos (nombre, periodo_id, carrera_nombre)
                VALUES (?, ?, ?)
            ");

            if ($stmt->execute([$nombre, $periodo_id, $carrera_nombre])) {
                $success = "Grupo creado correctamente";
            } else {
                $error = "Error al crear el grupo";
            }
        }
    }
}
?>

<div class="max-w-2xl mx-auto p-6 animate-fade-in font-sans">
    
    <!-- Encabezado Institucional -->
    <div class="mb-10 flex justify-between items-end">
        <div>
            <div class="flex items-center gap-2 mb-2">
                <span class="bg-purple-600 text-white text-[10px] font-black px-2 py-0.5 rounded-full uppercase tracking-tighter">Control Escolar</span>
                <span class="text-slate-300 text-[10px] font-bold">/</span>
                <span class="text-slate-500 text-[10px] font-black uppercase tracking-widest">Infraestructura</span>
            </div>
            <h1 class="text-4xl font-black text-slate-900 tracking-tighter uppercase leading-none">
                Apertura de <span class="text-purple-600 italic">Grupos</span>
            </h1>
        </div>
        <div class="text-right">
            <span class="text-[9px] font-black text-slate-400 uppercase tracking-widest block mb-1">Estado de Red</span>
            <div class="flex items-center justify-end gap-2">
                <span class="text-[10px] font-bold text-emerald-500 italic">API Conectada</span>
                <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
            </div>
        </div>
    </div>

    <!-- Contenedor del Formulario -->
    <div class="bg-white rounded-[2.5rem] shadow-2xl shadow-slate-200/60 border border-slate-100 overflow-hidden">
        <div class="p-10">
            
            <?php if (!empty($error)): ?>
                <div class="mb-6 flex items-center gap-3 bg-red-50 border border-red-100 text-red-600 p-4 rounded-2xl">
                    <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    <span class="text-xs font-black uppercase tracking-tight"><?= $error ?></span>
                </div>
            <?php endif; ?>

            <?php if (!empty($success)): ?>
                <div class="mb-6 flex items-center gap-3 bg-emerald-50 border border-emerald-100 text-emerald-600 p-4 rounded-2xl">
                    <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    <span class="text-xs font-black uppercase tracking-tight"><?= $success ?></span>
                </div>
            <?php endif; ?>

            <form method="POST" class="space-y-6">
                
                <!-- Nombre -->
                <div class="group/field">
                    <label class="text-[10px] font-black text-slate-400 uppercase ml-2 tracking-[0.2em] mb-2 block">Nombre del grupo</label>
                    <input type="text" name="nombre" placeholder="Ej: 1A" required
                        class="w-full p-4 bg-slate-50 border-2 border-slate-50 rounded-2xl text-sm font-bold text-slate-700 focus:bg-white focus:border-purple-600 focus:outline-none transition-all">
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Periodo -->
                    <div class="group/field">
                        <label class="text-[10px] font-black text-slate-400 uppercase ml-2 tracking-[0.2em] mb-2 block">Periodo Activo</label>
                        <select name="periodo_id" required 
                            class="w-full p-4 bg-slate-50 border-2 border-slate-50 rounded-2xl text-sm font-bold text-slate-700 focus:bg-white focus:border-purple-600 focus:outline-none transition-all cursor-pointer appearance-none">
                            <option value="">Seleccionar...</option>
                            <?php foreach ($periodos as $p): ?>
                                <option value="<?= $p['id'] ?>"><?= $p['nombre'] ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Carrera -->
                    <div class="group/field">
                        <label class="text-[10px] font-black text-slate-400 uppercase ml-2 tracking-[0.2em] mb-2 block">Carrera (Sincronizada)</label>
                        <select name="carrera_nombre" required 
                            class="w-full p-4 bg-slate-50 border-2 border-slate-50 rounded-2xl text-sm font-bold text-slate-700 focus:bg-white focus:border-purple-600 focus:outline-none transition-all cursor-pointer appearance-none">
                            <option value="">Seleccionar...</option>
                            <?php foreach ($carreras as $c): ?>
                                <option value="<?= $c['nombre'] ?>">
                                    <?= $c['nombre'] ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <button class="w-full bg-slate-900 text-white p-5 rounded-3xl font-black uppercase tracking-[0.2em] text-xs hover:bg-purple-600 hover:shadow-xl transition-all active:scale-[0.98] mt-4 flex items-center justify-center gap-2">
                    Crear Nuevo Grupo
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M12 4v16m8-8H4"></path></svg>
                </button>

            </form>
        </div>
    </div>
</div>

<style>
@keyframes slide-up { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }
.animate-fade-in { animation: slide-up 0.6s cubic-bezier(0.16, 1, 0.3, 1); }
</style>