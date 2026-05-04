<?php
require_once __DIR__ . '/../../includes/db.php';

/* ==============================
   LÓGICA DE DATOS (Preservada)
============================== */
$usuario_id = $_SESSION['user_id'] ?? null;

if (!$usuario_id) {
    echo "<div class='p-8 bg-red-50 border border-red-200 rounded-3xl text-red-700 font-black uppercase text-[10px] tracking-widest italic'>Acceso denegado: Usuario no identificado.</div>";
    exit;
}

$stmt = $pdo->prepare("SELECT id FROM docentes WHERE usuario_id = ?");
$stmt->execute([$usuario_id]);
$docente = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$docente) {
    echo "<div class='p-8 bg-amber-50 border border-amber-200 rounded-3xl text-amber-700 font-black uppercase text-[10px] tracking-widest italic'>Error de perfil: Docente no vinculado.</div>";
    exit;
}

$docente_id = $docente['id'];

$stmt = $pdo->prepare("
    SELECT ad.id, m.nombre AS materia, g.nombre AS grupo, p.nombre AS periodo
    FROM asignaciones_docentes ad
    JOIN materias m ON ad.materia_id = m.id
    JOIN grupos g ON ad.grupo_id = g.id
    JOIN periodos p ON g.periodo_id = p.id
    WHERE ad.docente_id = ?
    ORDER BY p.nombre DESC, m.nombre ASC
");
$stmt->execute([$docente_id]);
$materias = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="max-w-7xl mx-auto p-6 bg-slate-50 min-h-screen font-sans">

    <!-- CABECERA TÉCNICA -->
    <div class="mb-12">
        <h1 class="text-4xl font-black text-slate-800 uppercase italic tracking-tighter leading-none">
            Mis <span class="text-purple-600">Cátedras</span>
        </h1>
        <div class="flex items-center gap-2 mt-2">
            <span class="px-2 py-0.5 bg-slate-200 text-slate-600 text-[9px] font-black rounded-lg uppercase italic border border-slate-300">Portal Docente</span>
            <p class="text-xs text-slate-400 font-bold uppercase tracking-widest italic">Panel de gestión y planeación académica</p>
        </div>
    </div>

    <?php if (!$materias): ?>
        <!-- ESTADO VACÍO -->
        <div class="bg-white p-16 rounded-[3rem] shadow-sm border border-slate-100 text-center animate-fade-in">
            <div class="w-16 h-16 bg-slate-50 rounded-full flex items-center justify-center mx-auto mb-6 border border-slate-100">
                <span class="text-2xl">📚</span>
            </div>
            <h3 class="text-lg font-black text-slate-700 uppercase italic tracking-tighter">Sin asignaciones activas</h3>
            <p class="text-[10px] text-slate-400 font-bold uppercase tracking-widest mt-2">No se encontraron materias vinculadas a tu cuenta en este ciclo.</p>
        </div>
    <?php else: ?>

        <!-- GRID DE MATERIAS -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
            <?php foreach ($materias as $m): ?>
                <div class="group bg-white p-8 rounded-[2.5rem] shadow-sm border border-slate-100 hover:shadow-xl hover:border-purple-100 transition-all duration-500 transform hover:-translate-y-1 flex flex-col justify-between overflow-hidden relative">
                    
                    <!-- Elemento Decorativo de Fondo -->
                    <div class="absolute -top-10 -right-10 w-32 h-32 bg-slate-50 rounded-full group-hover:bg-purple-50 transition-colors duration-500"></div>

                    <div class="relative">
                        <div class="flex justify-between items-start mb-6">
                            <span class="px-3 py-1 bg-indigo-50 text-indigo-600 text-[10px] font-black rounded-xl uppercase tracking-widest border border-indigo-100">
                                <?= htmlspecialchars($m['periodo']) ?>
                            </span>
                            <span class="text-slate-200 font-black text-4xl leading-none italic group-hover:text-purple-100 transition-colors uppercase">
                                <?= htmlspecialchars($m['grupo']) ?>
                            </span>
                        </div>

                        <h2 class="text-xl font-black text-slate-800 uppercase italic leading-tight tracking-tighter mb-2 group-hover:text-purple-700 transition-colors">
                            <?= htmlspecialchars($m['materia']) ?>
                        </h2>
                        <p class="text-[9px] text-slate-400 font-black uppercase tracking-[0.2em] italic mb-8">Unidad Académica Activa</p>
                    </div>

                    <!-- ACCIONES -->
                    <div class="grid grid-cols-2 gap-3 relative mt-auto">
                        <a href="docente_dashboard.php?modulo=ver_materia&asignacion_id=<?= $m['id'] ?>"
                           class="flex items-center justify-center py-4 bg-slate-900 text-white rounded-2xl text-[10px] font-black uppercase tracking-widest hover:bg-purple-600 transition-all shadow-lg shadow-slate-200">
                            Gestionar
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

    <?php endif; ?>

</div>

<style>
@keyframes fade-in { 
    from { opacity: 0; transform: translateY(20px); } 
    to { opacity: 1; transform: translateY(0); } 
}
.animate-fade-in { animation: fade-in 0.6s ease-out forwards; }
</style>