<?php
require_once __DIR__ . '/../../includes/db.php';

/* OBTENER TIPOS DE PERIODO */
$tipos = $pdo->query("SELECT id, nombre FROM tipos_periodo ORDER BY id ASC")->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="max-w-7xl mx-auto p-6 animate-fade-in font-sans text-slate-900">
    
    <!-- Encabezado Principal -->
    <div class="mb-12">
        <div class="flex items-center gap-3 mb-4">
            <span class="bg-purple-100 text-purple-600 text-[10px] font-black px-3 py-1 rounded-full uppercase tracking-widest">Administración</span>
            <span class="text-slate-300 text-[10px] font-bold">/</span>
            <span class="text-slate-500 text-[10px] font-black uppercase tracking-widest italic">Control de Ciclos</span>
        </div>
        
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-6">
            <div>
                <h1 class="text-6xl font-black tracking-tighter uppercase leading-none mb-2">
                    Periodos <span class="text-purple-600 italic">Académicos</span>
                </h1>
                <p class="text-slate-400 text-[11px] font-black uppercase tracking-[0.3em]">Gestión y activación de ciclos escolares</p>
            </div>
            
            <a href="admin_dashboard.php?modulo=crear_periodo" class="inline-flex items-center gap-3 bg-white border border-slate-100 text-slate-900 px-8 py-4 rounded-2xl font-black uppercase text-[10px] tracking-[0.2em] hover:bg-purple-600 hover:text-white transition-all shadow-xl shadow-slate-200/50 group">
                <svg class="w-4 h-4 text-purple-600 group-hover:text-white transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M12 4v16m8-8H4"></path></svg>
                Nuevo Periodo
            </a>
        </div>
    </div>

    <?php foreach($tipos as $tipo): ?>
        <?php
        /* OBTENER PERIODOS DE ESTE TIPO */
        $stmt = $pdo->prepare("SELECT * FROM periodos WHERE tipo_periodo_id = ? ORDER BY fecha_inicio DESC");
        $stmt->execute([$tipo['id']]);
        $periodos = $stmt->fetchAll(PDO::FETCH_ASSOC);
        ?>

        <section class="mb-16">
            <!-- Etiqueta de Categoría -->
            <div class="flex items-center gap-4 mb-8">
                <h2 class="text-2xl font-black uppercase tracking-tighter text-slate-800">
                    <?= htmlspecialchars($tipo['nombre']) ?>
                </h2>
                <div class="h-[2px] flex-grow bg-gradient-to-r from-slate-100 to-transparent"></div>
            </div>

            <div class="bg-white rounded-[3rem] shadow-2xl shadow-slate-200/60 border border-slate-50 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full border-collapse">
                        <thead>
                            <tr class="border-b border-slate-50">
                                <th class="text-left p-8 text-[10px] font-black text-slate-400 uppercase tracking-widest">Nombre del Periodo</th>
                                <th class="text-left p-8 text-[10px] font-black text-slate-400 uppercase tracking-widest">Cronograma</th>
                                <th class="text-center p-8 text-[10px] font-black text-slate-400 uppercase tracking-widest">Estado</th>
                                <th class="text-right p-8 text-[10px] font-black text-slate-400 uppercase tracking-widest">Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-50">
                            <?php if(!$periodos): ?>
                                <tr>
                                    <td colspan="4" class="p-12 text-center">
                                        <p class="text-slate-300 font-bold italic uppercase tracking-widest text-sm">No se han registrado ciclos en esta categoría</p>
                                    </td>
                                </tr>
                            <?php endif; ?>

                            <?php foreach($periodos as $p): ?>
                                <tr class="group hover:bg-slate-50/50 transition-colors">
                                    <td class="p-8">
                                        <span class="block text-lg font-black text-slate-700 uppercase tracking-tight group-hover:text-purple-600 transition-colors">
                                            <?= htmlspecialchars($p['nombre']) ?>
                                        </span>
                                        <span class="text-[9px] font-mono font-bold text-slate-300 uppercase">UID: #<?= str_pad($p['id'], 4, '0', STR_PAD_LEFT) ?></span>
                                    </td>
                                    
                                    <td class="p-8">
                                        <div class="flex flex-col gap-1">
                                            <div class="flex items-center gap-2">
                                                <span class="text-[10px] font-black text-slate-400 uppercase w-10">Inicia:</span>
                                                <span class="text-xs font-bold text-slate-600 italic"><?= date('d/m/Y', strtotime($p['fecha_inicio'])) ?></span>
                                            </div>
                                            <div class="flex items-center gap-2">
                                                <span class="text-[10px] font-black text-slate-400 uppercase w-10">Cierra:</span>
                                                <span class="text-xs font-bold text-slate-600 italic"><?= date('d/m/Y', strtotime($p['fecha_fin'])) ?></span>
                                            </div>
                                        </div>
                                    </td>

                                    <td class="p-8 text-center">
                                        <?php if($p['activo']): ?>
                                            <span class="inline-flex items-center gap-2 bg-emerald-50 text-emerald-600 px-4 py-2 rounded-full text-[10px] font-black uppercase tracking-widest border border-emerald-100">
                                                <span class="w-1.5 h-1.5 bg-emerald-500 rounded-full animate-pulse"></span>
                                                Activo
                                            </span>
                                        <?php else: ?>
                                            <span class="inline-flex items-center gap-2 bg-slate-100 text-slate-400 px-4 py-2 rounded-full text-[10px] font-black uppercase tracking-widest">
                                                Inactivo
                                            </span>
                                        <?php endif; ?>
                                    </td>

                                    <td class="p-8 text-right">
                                        <div class="flex justify-end gap-3">
                                            <!-- Botón Activar/Desactivar -->
                                            <?php if(!$p['activo']): ?>
                                                <a href="admin_dashboard.php?modulo=periodo_activo&id=<?= $p['id'] ?>&accion=activar" 
                                                   class="p-3 bg-white border border-slate-100 rounded-xl text-emerald-500 hover:bg-emerald-500 hover:text-white transition-all shadow-sm" title="Activar Ciclo">
                                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                                                </a>
                                            <?php else: ?>
                                                <a href="admin_dashboard.php?modulo=periodo_activo&id=<?= $p['id'] ?>&accion=desactivar" 
                                                   class="p-3 bg-white border border-slate-100 rounded-xl text-amber-500 hover:bg-amber-500 hover:text-white transition-all shadow-sm" title="Desactivar Ciclo">
                                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"></path></svg>
                                                </a>
                                            <?php endif; ?>

                                            <!-- Botón Editar -->
                                            <a href="admin_dashboard.php?modulo=editar_periodo&id=<?= $p['id'] ?>" 
                                               class="p-3 bg-white border border-slate-100 rounded-xl text-indigo-500 hover:bg-indigo-500 hover:text-white transition-all shadow-sm" title="Editar Información">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path></svg>
                                            </a>

                                            <!-- Botón Eliminar -->
                                            <a href="admin_dashboard.php?modulo=eliminar_periodo&id=<?= $p['id'] ?>" 
                                               onclick="return confirm('¿Confirmas la eliminación permanente?')"
                                               class="p-3 bg-white border border-slate-100 rounded-xl text-rose-500 hover:bg-rose-500 hover:text-white transition-all shadow-sm" title="Eliminar Registro">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </section>
    <?php endforeach; ?>
</div>

<style>
@keyframes slide-up { from { opacity: 0; transform: translateY(40px); } to { opacity: 1; transform: translateY(0); } }
.animate-fade-in { animation: slide-up 0.8s cubic-bezier(0.16, 1, 0.3, 1); }
</style>