<?php
require_once __DIR__ . '/../../includes/db.php';

/* =========================
   CONFIG API (Lógica Original)
========================= */
$apiUrl = "https://sistema.cufa.edu.mx/api/carreras";
$apiKey = "H6z0U6FpnMPsgfCAe7ijkiXiL22YEE+ybjRtiZtDKmQ=";

$resultados = [
    'nuevas' => [],
    'actualizadas' => []
];

/* =========================
   CONSULTAR API
========================= */
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
$apiCarreras = $data['data'] ?? [];

/* =========================
   SINCRONIZAR
========================= */
if (isset($_POST['sincronizar'])) {

    $buscar = $pdo->prepare("SELECT id, nombre FROM carreras WHERE api_id = ?");
    $insertar = $pdo->prepare("INSERT INTO carreras (api_id, nombre) VALUES (?, ?)");
    $actualizar = $pdo->prepare("UPDATE carreras SET nombre=? WHERE api_id=?");

    foreach ($apiCarreras as $carrera) {

        $api_id = $carrera['id'];
        $nombre = trim($carrera['nombre']);

        $buscar->execute([$api_id]);
        $existente = $buscar->fetch(PDO::FETCH_ASSOC);

        if ($existente) {
            if ($existente['nombre'] !== $nombre) {
                $actualizar->execute([$nombre, $api_id]);

                $resultados['actualizadas'][] = [
                    'id' => $api_id,
                    'antes' => $existente['nombre'],
                    'despues' => $nombre
                ];
            }
        } else {
            $insertar->execute([$api_id, $nombre]);

            $resultados['nuevas'][] = [
                'id' => $api_id,
                'nombre' => $nombre
            ];
        }
    }
}

/* =========================
   DATOS LOCALES
========================= */
$stmt = $pdo->query("SELECT * FROM carreras ORDER BY nombre");
$carrerasLocal = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Sincronización de Carreras | CUFA</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;700;800&display=swap');
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
        @keyframes slideUp { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }
        .animate-fade { animation: slideUp 0.6s cubic-bezier(0.16, 1, 0.3, 1); }
    </style>
</head>

<body class="bg-slate-50 p-6 md:p-12 min-h-screen">

<div class="max-w-7xl mx-auto animate-fade">

    <!-- CABECERA -->
    <div class="mb-12 flex flex-col md:flex-row md:items-end justify-between gap-6">
        <div>
            <div class="flex items-center gap-3 mb-2">
                <span class="bg-purple-600 text-white text-[10px] font-black px-3 py-1 rounded-full uppercase tracking-widest">Database Sync</span>
                <span class="text-slate-300 text-[10px] font-bold">/</span>
                <span class="text-slate-400 text-[10px] font-black uppercase tracking-widest italic">External API</span>
            </div>
            <h1 class="text-5xl font-black text-slate-900 tracking-tighter uppercase leading-none">
                Sincronización de <span class="text-purple-600">Carreras</span>
            </h1>
        </div>

        <form method="POST">
            <button name="sincronizar"
                class="inline-flex items-center gap-3 bg-slate-900 text-white px-10 py-5 rounded-3xl font-black uppercase text-[10px] tracking-[0.2em] hover:bg-purple-600 transition-all shadow-2xl shadow-slate-200 active:scale-95">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
                Ejecutar Sincronización
            </button>
        </form>
    </div>

    <!-- SECCIÓN DE RESULTADOS -->
    <?php if (!empty($resultados['nuevas']) || !empty($resultados['actualizadas'])): ?>
    <div class="bg-white p-10 rounded-[3rem] shadow-2xl shadow-slate-200 border border-white mb-12 relative overflow-hidden">
        <div class="absolute top-0 right-0 p-8">
            <span class="text-[4rem] font-black text-slate-50 leading-none select-none uppercase">Report</span>
        </div>
        
        <h2 class="text-xl font-black text-slate-900 uppercase tracking-tighter mb-8 flex items-center gap-3 relative z-10">
            <span class="w-3 h-3 bg-emerald-500 rounded-full animate-pulse"></span>
            Cambios realizados en esta sesión
        </h2>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-10 relative z-10">
            <?php if (!empty($resultados['nuevas'])): ?>
                <div>
                    <h3 class="text-[10px] font-black text-emerald-600 uppercase tracking-widest mb-4 ml-2">Nuevos Registros</h3>
                    <div class="space-y-2">
                        <?php foreach ($resultados['nuevas'] as $n): ?>
                            <div class="bg-emerald-50 border border-emerald-100 p-4 rounded-2xl flex items-center justify-between">
                                <span class="text-xs font-bold text-emerald-700 uppercase"><?= $n['nombre'] ?></span>
                                <span class="text-[10px] font-mono font-bold text-emerald-400 bg-white px-2 py-1 rounded-lg">API: <?= $n['id'] ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

            <?php if (!empty($resultados['actualizadas'])): ?>
                <div>
                    <h3 class="text-[10px] font-black text-amber-600 uppercase tracking-widest mb-4 ml-2">Registros Actualizados</h3>
                    <div class="space-y-2">
                        <?php foreach ($resultados['actualizadas'] as $a): ?>
                            <div class="bg-amber-50 border border-amber-100 p-4 rounded-2xl">
                                <div class="text-[9px] font-black text-amber-400 uppercase mb-1">ID <?= $a['id'] ?></div>
                                <div class="flex items-center gap-2 text-xs font-bold">
                                    <span class="text-slate-400 line-through opacity-50"><?= $a['antes'] ?></span>
                                    <span class="text-amber-700">→</span>
                                    <span class="text-amber-800 uppercase"><?= $a['despues'] ?></span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- PANELES DE COMPARATIVA -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-10">

        <!-- PANEL API -->
        <div class="bg-white rounded-[3rem] shadow-xl shadow-slate-200/60 border border-slate-100 overflow-hidden">
            <div class="bg-slate-50 p-8 border-b border-slate-100 flex justify-between items-center">
                <h2 class="text-lg font-black text-slate-800 uppercase tracking-tighter italic">Source: Carreras API</h2>
                <div class="flex items-center gap-2">
                    <span class="w-2 h-2 bg-blue-500 rounded-full"></span>
                    <span class="text-[9px] font-black text-blue-600 uppercase tracking-widest">Servidor Externo</span>
                </div>
            </div>
            <div class="p-6">
                <table class="w-full text-left">
                    <thead>
                        <tr>
                            <th class="p-4 text-[10px] font-black text-slate-400 uppercase tracking-[0.2em]">API ID</th>
                            <th class="p-4 text-[10px] font-black text-slate-400 uppercase tracking-[0.2em]">Nombre Oficial</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-50">
                        <?php foreach ($apiCarreras as $c): ?>
                            <tr class="hover:bg-slate-50 transition-all group">
                                <td class="p-4">
                                    <span class="text-xs font-mono font-bold text-slate-400">#<?= str_pad($c['id'], 3, '0', STR_PAD_LEFT) ?></span>
                                </td>
                                <td class="p-4">
                                    <span class="text-xs font-bold text-slate-700 uppercase group-hover:text-purple-600 transition-colors"><?= $c['nombre'] ?></span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- PANEL LOCAL -->
        <div class="bg-white rounded-[3rem] shadow-xl shadow-slate-200/60 border border-slate-100 overflow-hidden">
            <div class="bg-slate-50 p-8 border-b border-slate-100 flex justify-between items-center">
                <h2 class="text-lg font-black text-slate-800 uppercase tracking-tighter italic">Local: Carreras CUFA</h2>
                <div class="flex items-center gap-2">
                    <span class="w-2 h-2 bg-emerald-500 rounded-full"></span>
                    <span class="text-[9px] font-black text-emerald-600 uppercase tracking-widest">Base de Datos</span>
                </div>
            </div>
            <div class="p-6">
                <table class="w-full text-left">
                    <thead>
                        <tr>
                            <th class="p-4 text-[10px] font-black text-slate-400 uppercase tracking-[0.2em]">ID</th>
                            <th class="p-4 text-[10px] font-black text-slate-400 uppercase tracking-[0.2em]">API ID</th>
                            <th class="p-4 text-[10px] font-black text-slate-400 uppercase tracking-[0.2em]">Nombre en Sistema</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-50">
                        <?php foreach ($carrerasLocal as $c): ?>
                            <tr class="hover:bg-slate-50 transition-all group">
                                <td class="p-4 text-xs font-mono font-bold text-slate-300"><?= $c['id'] ?></td>
                                <td class="p-4 text-xs font-mono font-bold text-purple-400">#<?= str_pad($c['api_id'], 3, '0', STR_PAD_LEFT) ?></td>
                                <td class="p-4 text-xs font-bold text-slate-700 uppercase group-hover:text-purple-600 transition-colors"><?= $c['nombre'] ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div>

    <!-- FOOTER -->
    <div class="mt-16 text-center">
        <p class="text-[10px] font-black text-slate-300 uppercase tracking-[0.5em]">Academic Core System // Synchronization Module</p>
    </div>

</div>

</body>
</html>