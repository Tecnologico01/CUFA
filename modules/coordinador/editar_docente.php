<?php
require_once __DIR__ . '/../../includes/db.php';

$id = $_GET['id'] ?? null;

if(!$id){
    echo "<div class='p-4 bg-red-100 text-red-700 rounded'>Docente no especificado</div>";
    exit;
}

/* =========================
   OBTENER DATOS (Intacto)
   ========================= */
$stmt = $pdo->prepare("
SELECT 
u.*, d.*
FROM docentes d
JOIN usuarios u ON u.id = d.usuario_id
WHERE d.id = ?
");

$stmt->execute([$id]);
$docente = $stmt->fetch();

if(!$docente){
    echo "<div class='p-4 bg-red-100 text-red-700 rounded'>Docente no encontrado</div>";
    exit;
}

/* =========================
   DOCUMENTOS (Intacto)
   ========================= */
$stmtDocs = $pdo->prepare("
SELECT * FROM documentos_docente WHERE docente_id=?
");
$stmtDocs->execute([$id]);

$documentos = [];
while($d = $stmtDocs->fetch()){
    $documentos[$d['tipo']] = $d['archivo'];
}

/* =========================
   FUNCION VER DOC (Estilo mejorado)
   ========================= */
function verDoc($tipo, $documentos){
    if(isset($documentos[$tipo])){
        return "<a href='/sistema_academico/".$documentos[$tipo]."' target='_blank' 
                class='inline-flex items-center gap-1 text-xs font-bold text-blue-600 hover:text-blue-800 transition-colors'>
                <svg class='w-4 h-4' fill='none' stroke='currentColor' viewBox='0 0 24 24'><path stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M15 12a3 3 0 11-6 0 3 3 0 016 0z'></path><path stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z'></path></svg>
                VER ACTUAL</a>";
    }
    return "<span class='text-[10px] font-bold text-gray-400 uppercase tracking-widest'>Sin archivo</span>";
}
?>

<div class="max-w-6xl mx-auto p-4">
    <div class="flex flex-col md:flex-row md:items-center justify-between mb-8 border-b pb-4 gap-4">
        <div>
            <h1 class="text-3xl font-extrabold text-slate-800">Expediente del Docente</h1>
            <p class="text-slate-500 font-medium">Edición de perfiles y validación de documentos académicos.</p>
        </div>
        <a href="javascript:history.back()" class="inline-flex items-center text-sm font-bold text-slate-500 hover:text-slate-800 transition-colors">
            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
            Volver al listado
        </a>
    </div>

    <form method="POST" enctype="multipart/form-data" 
          action="/sistema_academico/modules/coordinador/guardar_edicion_docente.php" 
          class="space-y-8">

        <input type="hidden" name="id" value="<?= $id ?>">

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            
            <div class="lg:col-span-1 space-y-6">
                <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
                    <div class="bg-slate-50 px-5 py-3 border-b border-slate-200">
                        <h2 class="font-bold text-slate-700 flex items-center gap-2">
                            <svg class="w-5 h-5 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                            Datos Personales
                        </h2>
                    </div>
                    <div class="p-5 space-y-4">
                        <div>
                            <label class="text-[10px] font-bold text-slate-400 uppercase mb-1 block">Nombre(s)</label>
                            <input type="text" name="nombres" value="<?= $docente['nombres'] ?>" required class="w-full p-2.5 bg-slate-50 border border-slate-200 rounded-lg focus:ring-2 focus:ring-indigo-500 outline-none transition-all">
                        </div>
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="text-[10px] font-bold text-slate-400 uppercase mb-1 block">A. Paterno</label>
                                <input type="text" name="apellido_paterno" value="<?= $docente['apellido_paterno'] ?>" required class="w-full p-2.5 bg-slate-50 border border-slate-200 rounded-lg outline-none focus:ring-2 focus:ring-indigo-500">
                            </div>
                            <div>
                                <label class="text-[10px] font-bold text-slate-400 uppercase mb-1 block">A. Materno</label>
                                <input type="text" name="apellido_materno" value="<?= $docente['apellido_materno'] ?>" class="w-full p-2.5 bg-slate-50 border border-slate-200 rounded-lg outline-none focus:ring-2 focus:ring-indigo-500">
                            </div>
                        </div>
                        <div>
                            <label class="text-[10px] font-bold text-slate-400 uppercase mb-1 block">Nombre de Usuario (Login)</label>
                            <input type="text" name="username" value="<?= $docente['username'] ?>" class="w-full p-2.5 border border-slate-200 rounded-lg bg-slate-100 text-slate-600 font-mono text-sm" readonly>
                        </div>
                        <div>
                            <label class="text-[10px] font-bold text-slate-400 uppercase mb-1 block">Correo Electrónico</label>
                            <input type="email" name="email" value="<?= $docente['email'] ?>" class="w-full p-2.5 bg-slate-50 border border-slate-200 rounded-lg outline-none focus:ring-2 focus:ring-indigo-500">
                        </div>
                        <div class="pt-4 border-t border-dashed">
                            <label class="text-[10px] font-bold text-red-400 uppercase mb-1 block">Contraseña</label>
                            <input type="password" name="password" placeholder="Escriba nueva para cambiar..." class="w-full p-2.5 bg-white border border-slate-200 rounded-lg outline-none focus:ring-2 focus:ring-red-500 transition-all text-sm">
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
                    <div class="bg-slate-50 px-5 py-3 border-b border-slate-200">
                        <h2 class="font-bold text-slate-700 flex items-center gap-2 text-sm uppercase">Identificación Oficial</h2>
                    </div>
                    <div class="p-5 space-y-4">
                        <div>
                            <label class="text-[10px] font-bold text-slate-400 uppercase mb-1 block">RFC</label>
                            <input type="text" name="rfc" value="<?= $docente['rfc'] ?>" class="w-full p-2 bg-slate-50 border border-slate-200 rounded uppercase font-bold text-slate-700">
                        </div>
                        <div>
                            <label class="text-[10px] font-bold text-slate-400 uppercase mb-1 block">CURP</label>
                            <input type="text" name="curp" value="<?= $docente['curp'] ?>" class="w-full p-2 bg-slate-50 border border-slate-200 rounded uppercase font-bold text-slate-700">
                        </div>
                        <div>
                            <label class="text-[10px] font-bold text-slate-400 uppercase mb-1 block">Dirección Particular</label>
                            <input type="text" name="direccion" value="<?= $docente['direccion'] ?>" class="w-full p-2 bg-slate-50 border border-slate-200 rounded text-sm">
                        </div>
                    </div>
                </div>
            </div>

            <div class="lg:col-span-2 space-y-6">
                
                <div class="bg-blue-50/50 rounded-2xl border border-blue-100 overflow-hidden">
                    <div class="bg-blue-600 px-6 py-3 flex justify-between items-center text-white">
                        <h2 class="font-bold uppercase tracking-wide text-sm">Formación: Licenciatura</h2>
                        <span class="text-[10px] bg-blue-400/40 px-2 py-1 rounded-full border border-blue-300">Nivel Obligatorio</span>
                    </div>
                    <div class="p-6">
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                            <div class="md:col-span-3">
                                <label class="text-[10px] font-bold text-blue-600 uppercase mb-1 block">Nombre del Título / Carrera</label>
                                <input type="text" name="licenciatura" value="<?= $docente['licenciatura'] ?>" class="w-full p-2.5 border border-blue-200 rounded-lg shadow-sm outline-none focus:ring-2 focus:ring-blue-500">
                            </div>
                            <div>
                                <label class="text-[10px] font-bold text-blue-600 uppercase mb-1 block">Folio de Título</label>
                                <input type="text" name="licenciatura_titulo" value="<?= $docente['licenciatura_titulo'] ?>" class="w-full p-2.5 border border-blue-200 rounded-lg text-sm">
                            </div>
                            <div>
                                <label class="text-[10px] font-bold text-blue-600 uppercase mb-1 block">Número de Cédula</label>
                                <input type="text" name="licenciatura_cedula" value="<?= $docente['licenciatura_cedula'] ?>" class="w-full p-2.5 border border-blue-200 rounded-lg text-sm">
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 bg-white p-4 rounded-xl border border-blue-100 shadow-inner">
                            <div class="flex flex-col gap-2">
                                <div class="flex justify-between items-center">
                                    <label class="text-[11px] font-bold text-slate-500 uppercase">Certificado</label>
                                    <?= verDoc('lic_certificado',$documentos) ?>
                                </div>
                                <input type="file" name="lic_certificado" class="text-[10px] file:mr-2 file:py-1 file:px-2 file:rounded file:border-0 file:text-xs file:font-semibold file:bg-blue-50 file:text-blue-700">
                            </div>
                            <div class="flex flex-col gap-2">
                                <div class="flex justify-between items-center text-xs">
                                    <label class="text-[11px] font-bold text-slate-500 uppercase">Título</label>
                                    <?= verDoc('lic_titulo',$documentos) ?>
                                </div>
                                <input type="file" name="lic_titulo" class="text-[10px] file:mr-2 file:py-1 file:px-2 file:rounded file:border-0 file:text-xs file:font-semibold file:bg-blue-50 file:text-blue-700">
                            </div>
                            <div class="flex flex-col gap-2">
                                <div class="flex justify-between items-center text-xs">
                                    <label class="text-[11px] font-bold text-slate-500 uppercase">Cédula</label>
                                    <?= verDoc('lic_cedula',$documentos) ?>
                                </div>
                                <input type="file" name="lic_cedula" class="text-[10px] file:mr-2 file:py-1 file:px-2 file:rounded file:border-0 file:text-xs file:font-semibold file:bg-blue-50 file:text-blue-700">
                            </div>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="bg-emerald-50/50 rounded-2xl border border-emerald-100 overflow-hidden">
                        <div class="bg-emerald-600 px-4 py-2 text-white text-xs font-bold uppercase tracking-wider">Postgrado: Maestría</div>
                        <div class="p-4 space-y-4">
                            <input type="text" name="maestria" value="<?= $docente['maestria'] ?>" placeholder="Nombre de maestría" class="w-full p-2 border border-emerald-200 rounded text-sm shadow-sm">
                            <div class="space-y-3 pt-2 bg-white/50 p-3 rounded-lg border border-emerald-100">
                                <div class="flex justify-between items-center border-b pb-1 border-emerald-50">
                                    <span class="text-[10px] font-bold text-slate-500">Certificado</span>
                                    <?= verDoc('mae_certificado',$documentos) ?>
                                </div>
                                <input type="file" name="mae_certificado" class="text-[10px]">
                                
                                <div class="flex justify-between items-center border-b pb-1 border-emerald-50 pt-1">
                                    <span class="text-[10px] font-bold text-slate-500">Título</span>
                                    <?= verDoc('mae_titulo',$documentos) ?>
                                </div>
                                <input type="file" name="mae_titulo" class="text-[10px]">

                                <div class="flex justify-between items-center border-b pb-1 border-emerald-50 pt-1">
                                    <span class="text-[10px] font-bold text-slate-500">Cédula</span>
                                    <?= verDoc('mae_cedula',$documentos) ?>
                                </div>
                                <input type="file" name="mae_cedula" class="text-[10px]">
                            </div>
                        </div>
                    </div>

                    <div class="bg-purple-50/50 rounded-2xl border border-purple-100 overflow-hidden">
                        <div class="bg-purple-600 px-4 py-2 text-white text-xs font-bold uppercase tracking-wider">Postgrado: Doctorado</div>
                        <div class="p-4 space-y-4">
                            <input type="text" name="doctorado" value="<?= $docente['doctorado'] ?>" placeholder="Nombre de doctorado" class="w-full p-2 border border-purple-200 rounded text-sm shadow-sm">
                            <div class="space-y-3 pt-2 bg-white/50 p-3 rounded-lg border border-purple-100">
                                <div class="flex justify-between items-center border-b pb-1 border-purple-50">
                                    <span class="text-[10px] font-bold text-slate-500">Certificado</span>
                                    <?= verDoc('doc_certificado',$documentos) ?>
                                </div>
                                <input type="file" name="doc_certificado" class="text-[10px]">
                                
                                <div class="flex justify-between items-center border-b pb-1 border-purple-50 pt-1">
                                    <span class="text-[10px] font-bold text-slate-500">Título</span>
                                    <?= verDoc('doc_titulo',$documentos) ?>
                                </div>
                                <input type="file" name="doc_titulo" class="text-[10px]">

                                <div class="flex justify-between items-center border-b pb-1 border-purple-50 pt-1">
                                    <span class="text-[10px] font-bold text-slate-500">Cédula</span>
                                    <?= verDoc('doc_cedula',$documentos) ?>
                                </div>
                                <input type="file" name="doc_cedula" class="text-[10px]">
                            </div>
                        </div>
                    </div>
                </div>

                <div class="bg-slate-800 rounded-2xl p-6 text-white shadow-xl">
                    <h2 class="text-lg font-bold mb-6 flex items-center gap-2 border-b border-slate-700 pb-2">
                        <svg class="w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                        Expediente de Soporte
                    </h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-x-12 gap-y-6">
                        <div class="space-y-2">
                            <div class="flex justify-between">
                                <label class="text-xs text-slate-400 uppercase font-bold tracking-tighter">INE / Identificación</label>
                                <?= verDoc('ine',$documentos) ?>
                            </div>
                            <input type="file" name="ine" class="text-xs text-slate-400 file:bg-slate-700 file:text-white file:border-0 file:rounded file:px-2 file:py-1 hover:file:bg-slate-600 transition-all">
                        </div>
                        <div class="space-y-2">
                            <div class="flex justify-between">
                                <label class="text-xs text-slate-400 uppercase font-bold tracking-tighter">Comprobante Domicilio</label>
                                <?= verDoc('domicilio',$documentos) ?>
                            </div>
                            <input type="file" name="domicilio" class="text-xs text-slate-400 file:bg-slate-700 file:text-white file:border-0 file:rounded file:px-2 file:py-1">
                        </div>
                        <div class="space-y-2">
                            <div class="flex justify-between">
                                <label class="text-xs text-slate-400 uppercase font-bold tracking-tighter">Constancia Fiscal</label>
                                <?= verDoc('fiscal',$documentos) ?>
                            </div>
                            <input type="file" name="fiscal" class="text-xs text-slate-400 file:bg-slate-700 file:text-white file:border-0 file:rounded file:px-2 file:py-1">
                        </div>
                        <div class="space-y-2">
                            <div class="flex justify-between">
                                <label class="text-xs font-bold text-amber-400 uppercase tracking-tighter">Currículum Vitae (CV)</label>
                                <?= verDoc('cv',$documentos) ?>
                            </div>
                            <input type="file" name="cv" class="text-xs text-slate-400 file:bg-slate-700 file:text-white file:border-0 file:rounded file:px-2 file:py-1">
                        </div>
                    </div>
                </div>

                <div class="flex justify-end pt-6">
                    <button class="bg-indigo-600 hover:bg-indigo-700 text-white px-10 py-4 rounded-xl font-extrabold shadow-lg shadow-indigo-200 transform hover:scale-105 active:scale-95 transition-all flex items-center gap-2 uppercase tracking-wider">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"></path></svg>
                        Guardar cambios en el expediente
                    </button>
                </div>

            </div>
        </div>
    </form>
</div>