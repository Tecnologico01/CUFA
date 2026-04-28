<?php
require_once __DIR__ . '/../../includes/db.php';

$id = $_GET['id'] ?? null;

if(!$id){
    echo "<div class='p-4 bg-red-100 text-red-700 rounded-xl border border-red-200'>Docente no encontrado</div>";
    exit;
}

/* =========================
   DATOS DOCENTE (Intacto)
   ========================= */

$stmt = $pdo->prepare("
    SELECT 
    u.nombres,
    u.apellido_paterno,
    u.apellido_materno,
    u.email,
    u.numero_identificador,
    d.*
    FROM docentes d
    JOIN usuarios u ON u.id = d.usuario_id
    WHERE d.id = ?
");

$stmt->execute([$id]);
$docente = $stmt->fetch();

if(!$docente){
    echo "<div class='p-4 bg-red-100 text-red-700 rounded-xl border border-red-200'>El registro no existe en la base de datos</div>";
    exit;
}

/* =========================
   DOCUMENTOS (Intacto)
   ========================= */

$stmtDocs = $pdo->prepare("
    SELECT tipo, archivo
    FROM documentos_docente
    WHERE docente_id = ?
");

$stmtDocs->execute([$id]);
$documentos = $stmtDocs->fetchAll(PDO::FETCH_KEY_PAIR);
?>

<div class="max-w-6xl mx-auto p-4">
    <div class="flex flex-col md:flex-row md:items-center justify-between mb-8 border-b pb-6 gap-4">
        <div>
            <div class="flex items-center gap-3 mb-1">
                <span class="bg-indigo-100 text-indigo-700 text-[10px] font-extrabold px-2 py-0.5 rounded uppercase tracking-wider border border-indigo-200">Expediente Activo</span>
                <span class="text-slate-400 text-sm font-mono">ID: <?= $docente['numero_identificador'] ?></span>
            </div>
            <h1 class="text-3xl font-extrabold text-slate-800 tracking-tight">
                <?= $docente['nombres'] ?> <?= $docente['apellido_paterno'] ?> <?= $docente['apellido_materno'] ?>
            </h1>
            <p class="text-slate-500 font-medium">Visualización completa del perfil docente y repositorio de documentos.</p>
        </div>
        <div class="flex gap-3">
            <a href="coordinador_dashboard.php?modulo=editar_docente&id=<?= $id ?>" 
               class="bg-amber-500 hover:bg-amber-600 text-white px-5 py-2.5 rounded-xl font-bold text-sm shadow-lg shadow-amber-100 transition-all flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                Editar Expediente
            </a>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        
        <div class="lg:col-span-1 space-y-6">
            
            <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
                <div class="bg-slate-50 px-5 py-3 border-b border-slate-200">
                    <h2 class="font-bold text-slate-700 flex items-center gap-2 text-xs uppercase tracking-wider">
                        <svg class="w-4 h-4 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                        Información de Contacto
                    </h2>
                </div>
                <div class="p-5 space-y-4">
                    <div class="space-y-1">
                        <label class="text-[10px] font-bold text-slate-400 uppercase block tracking-tighter">Correo Institucional</label>
                        <p class="text-slate-700 font-semibold break-all"><?= $docente['email'] ?></p>
                    </div>
                    <div class="space-y-1">
                        <label class="text-[10px] font-bold text-slate-400 uppercase block tracking-tighter">Número Identificador</label>
                        <p class="text-slate-700 font-mono font-bold"><?= $docente['numero_identificador'] ?></p>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
                <div class="bg-slate-50 px-5 py-3 border-b border-slate-200">
                    <h2 class="font-bold text-slate-700 flex items-center gap-2 text-xs uppercase tracking-wider">
                        <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                        Identidad Legal
                    </h2>
                </div>
                <div class="p-5 space-y-4">
                    <div class="space-y-1">
                        <label class="text-[10px] font-bold text-slate-400 uppercase block tracking-tighter">CURP</label>
                        <p class="text-slate-700 font-bold uppercase"><?= $docente['curp'] ?></p>
                    </div>
                    <div class="space-y-1">
                        <label class="text-[10px] font-bold text-slate-400 uppercase block tracking-tighter">RFC</label>
                        <p class="text-slate-700 font-bold uppercase"><?= $docente['rfc'] ?></p>
                    </div>
                    <div class="space-y-1">
                        <label class="text-[10px] font-bold text-slate-400 uppercase block tracking-tighter">Dirección Registrada</label>
                        <p class="text-slate-600 text-sm leading-relaxed"><?= $docente['direccion'] ?></p>
                    </div>
                </div>
            </div>
        </div>

        <div class="lg:col-span-2 space-y-6">
            
            <div class="bg-blue-50/50 rounded-2xl border border-blue-100 overflow-hidden shadow-sm">
                <div class="bg-blue-600 px-6 py-3 text-white flex justify-between items-center">
                    <h2 class="font-bold uppercase tracking-wide text-xs">Grado Académico Principal</h2>
                    <svg class="w-5 h-5 opacity-50" fill="currentColor" viewBox="0 0 20 20"><path d="M10.394 2.827a1 1 0 00-.788 0l-7 3a1 1 0 000 1.848l.498.213a1 1 0 01.597.915v3.138a1 1 0 00.597.915l2 1a1 1 0 00.806 0l2-1a1 1 0 00.597-.915V8.803a1 1 0 01.597-.915l.498-.213a1 1 0 000-1.848l-7-3z"></path></svg>
                </div>
                <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="md:col-span-2">
                        <label class="text-[10px] font-bold text-blue-600 uppercase block tracking-wider mb-1">Nombre de Licenciatura</label>
                        <p class="text-lg font-extrabold text-blue-900 leading-tight"><?= $docente['licenciatura'] ?></p>
                    </div>
                    <div class="bg-white p-3 rounded-lg border border-blue-100">
                        <label class="text-[10px] font-bold text-slate-400 uppercase block">Título</label>
                        <p class="text-sm font-bold text-slate-700"><?= $docente['licenciatura_titulo'] ?></p>
                    </div>
                    <div class="bg-white p-3 rounded-lg border border-blue-100">
                        <label class="text-[10px] font-bold text-slate-400 uppercase block">Cédula</label>
                        <p class="text-sm font-bold text-slate-700"><?= $docente['licenciatura_cedula'] ?></p>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
                <div class="bg-slate-50 px-6 py-4 border-b border-slate-200 flex items-center justify-between">
                    <h2 class="font-bold text-slate-700 flex items-center gap-2">
                        <svg class="w-5 h-5 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 19a2 2 0 01-2-2V7a2 2 0 012-2h4l2 2h4a2 2 0 012 2v1m-6 9a2 2 0 100-4 2 2 0 000 4z"></path></svg>
                        Repositorio de Documentos Digitalizados
                    </h2>
                    <span class="text-xs font-medium text-slate-400"><?= count($documentos) ?> archivos subidos</span>
                </div>
                <div class="p-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <?php foreach($documentos as $tipo => $archivo){ ?>
                            <div class="group flex items-center justify-between p-4 rounded-xl border border-slate-100 bg-slate-50 hover:bg-white hover:border-indigo-200 hover:shadow-md transition-all duration-200">
                                <div class="flex items-center gap-3">
                                    <div class="bg-white p-2 rounded-lg shadow-sm group-hover:bg-indigo-50 transition-colors">
                                        <svg class="w-5 h-5 text-slate-400 group-hover:text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path></svg>
                                    </div>
                                    <div>
                                        <p class="font-extrabold text-slate-700 text-xs uppercase tracking-wider"><?= str_replace('_', ' ', $tipo) ?></p>
                                        <p class="text-[10px] text-slate-400 font-medium italic">Documento verificado</p>
                                    </div>
                                </div>
                                <a href="/sistema_academico/<?= $archivo ?>" target="_blank"
                                   class="inline-flex items-center gap-1 text-xs font-bold text-indigo-600 bg-indigo-50 px-3 py-1.5 rounded-lg hover:bg-indigo-600 hover:text-white transition-all">
                                    VER PDF
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path></svg>
                                </a>
                            </div>
                        <?php } ?>
                    </div>

                    <?php if(empty($documentos)): ?>
                        <div class="text-center py-12">
                            <svg class="w-12 h-12 text-slate-200 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 13h6m-3-3v6m-9 1V7a2 2 0 012-2h6l2 2h6a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2z"></path></svg>
                            <p class="text-slate-400 font-medium italic">No se han cargado documentos en este expediente.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

        </div>
    </div>
</div>