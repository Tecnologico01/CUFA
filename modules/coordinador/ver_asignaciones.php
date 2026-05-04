<?php
require_once __DIR__ . '/../../includes/db.php';

// Consulta optimizada para traer los datos necesarios del docente y la materia
$stmt = $pdo->query("
    SELECT 
        ad.id,
        CONCAT(u.nombres, ' ', u.apellido_paterno, ' ', u.apellido_materno) AS docente,
        m.nombre AS materia,
        g.nombre AS grupo,
        p.nombre AS periodo
    FROM asignaciones_docentes ad
    JOIN docentes d ON d.id = ad.docente_id
    JOIN usuarios u ON u.id = d.usuario_id
    JOIN materias m ON m.id = ad.materia_id
    JOIN grupos g ON g.id = ad.grupo_id
    JOIN periodos p ON p.id = ad.periodo_id
    ORDER BY p.nombre DESC, g.nombre ASC, m.nombre ASC
");
?>

<div class="max-w-7xl mx-auto p-6 animate-fade-in">
    <!-- Encabezado con Estilo Institucional -->
    <div class="mb-8 flex justify-between items-end">
        <div>
            <div class="flex items-center gap-2 mb-1">
                <span class="bg-purple-600 text-white text-[10px] font-black px-2 py-0.5 rounded-full uppercase tracking-tighter">Gestión Académica</span>
            </div>
            <h1 class="text-4xl font-black text-slate-900 tracking-tighter uppercase">
                Asignaciones <span class="text-purple-600 italic">Docentes</span>
            </h1>
        </div>
        <div class="text-right">
            <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Registros Totales</p>
            <p class="text-2xl font-black text-slate-700"><?= $stmt->rowCount() ?></p>
        </div>
    </div>

    <div class="bg-white rounded-[2rem] shadow-xl shadow-slate-200/50 border border-slate-100 overflow-hidden">
        <table class="w-full border-collapse">
            <thead>
                <tr class="bg-slate-900 text-white">
                    <th class="p-5 text-left text-[10px] font-black uppercase tracking-[0.2em]">Catedrático</th>
                    <th class="p-5 text-left text-[10px] font-black uppercase tracking-[0.2em]">Asignatura</th>
                    <th class="p-5 text-left text-[10px] font-black uppercase tracking-[0.2em]">Grupo</th>
                    <th class="p-5 text-left text-[10px] font-black uppercase tracking-[0.2em]">Periodo</th>
                    <th class="p-5 text-center text-[10px] font-black uppercase tracking-[0.2em]">Gestión</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                <?php while($a = $stmt->fetch()): ?>
                <tr class="hover:bg-slate-50/80 transition-colors group">
                    <td class="p-5">
                        <div class="flex flex-col">
                            <span class="text-sm font-bold text-slate-800"><?= htmlspecialchars($a['docente']) ?></span>
                            <span class="text-[10px] font-medium text-slate-400">ID Asignación: #<?= $a['id'] ?></span>
                        </div>
                    </td>
                    <td class="p-5 text-sm font-semibold text-slate-600 italic"><?= htmlspecialchars($a['materia']) ?></td>
                    <td class="p-5">
                        <span class="px-3 py-1 bg-purple-50 text-purple-700 rounded-lg text-xs font-black uppercase">
                            <?= htmlspecialchars($a['grupo']) ?>
                        </span>
                    </td>
                    <td class="p-5 text-xs font-bold text-slate-500"><?= htmlspecialchars($a['periodo']) ?></td>
                    <td class="p-5">
                        <div class="flex items-center justify-center gap-2">
                            <a href="/sistema_academico/modules/coordinador/eliminar_asignacion.php?id=<?= $a['id'] ?>" 
                               onclick="return confirm('¿Confirma la eliminación permanente de esta asignación?')"
                               class="p-2 text-slate-300 hover:text-red-500 transition-colors"
                               title="Eliminar Asignación">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                            </a>
                        </div>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<style>
@keyframes slide-up { from { opacity: 0; transform: translateY(15px); } to { opacity: 1; transform: translateY(0); } }
.animate-fade-in { animation: slide-up 0.5s ease-out; }
</style>