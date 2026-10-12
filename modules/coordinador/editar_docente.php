<?php

require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/permisos.php';

validarPermiso('editar_docentes');

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

if($id <= 0){
    echo "<div class='p-4 bg-red-100 text-red-700 rounded'>Docente no especificado</div>";
    exit;
}

/*
|--------------------------------------------------------------------------
| OBTENER DOCENTE
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare("
    SELECT 
        d.*,
        u.id AS usuario_id,
        u.nombres,
        u.apellido_paterno,
        u.apellido_materno,
        u.username,
        u.email,
        u.numero_identificador,
        u.activo
    FROM docentes d
    INNER JOIN usuarios u 
        ON u.id = d.usuario_id
    WHERE d.id = ?
");

$stmt->execute([$id]);
$docente = $stmt->fetch(PDO::FETCH_ASSOC);

if(!$docente){
    echo "<div class='p-4 bg-red-100 text-red-700 rounded'>Docente no encontrado</div>";
    exit;
}

/*
|--------------------------------------------------------------------------
| DOCUMENTOS
|--------------------------------------------------------------------------
*/

$stmtDocs = $pdo->prepare("
    SELECT tipo, archivo
    FROM documentos_docente
    WHERE docente_id = ?
");

$stmtDocs->execute([$id]);

$documentos = [];

while($d = $stmtDocs->fetch(PDO::FETCH_ASSOC)){
    $documentos[$d['tipo']] = $d['archivo'];
}

/*
|--------------------------------------------------------------------------
| VER DOCUMENTO
|--------------------------------------------------------------------------
*/

function verDoc($tipo, $documentos){

    if(isset($documentos[$tipo]) && !empty($documentos[$tipo])){

        $archivo = htmlspecialchars($documentos[$tipo]);

        return "
            <a href='/sistema_academico/{$archivo}' target='_blank'
               class='inline-flex items-center gap-1 text-xs font-bold text-blue-600 hover:text-blue-800 transition-colors'>
                VER ACTUAL
            </a>
        ";
    }

    return "
        <span class='text-[10px] font-bold text-gray-400 uppercase tracking-widest'>
            Sin archivo
        </span>
    ";
}

?>

<div class="max-w-6xl mx-auto p-4">

    <div class="flex flex-col md:flex-row md:items-center justify-between mb-8 border-b pb-4 gap-4">

        <div>
            <h1 class="text-3xl font-extrabold text-slate-800">
                Expediente del Docente
            </h1>

            <p class="text-slate-500 font-medium">
                Edición de perfiles y validación de documentos académicos.
            </p>
        </div>

        <a href="?modulo=ver_docente&id=<?= $id ?>"
           class="inline-flex items-center text-sm font-bold text-slate-500 hover:text-slate-800 transition-colors">
            Volver al expediente
        </a>

    </div>

    <form
        method="POST"
        enctype="multipart/form-data"
        action="?modulo=guardar_docente"
        class="space-y-8"
    >

        <input type="hidden" name="id" value="<?= $id ?>">
        <input type="hidden" name="usuario_id" value="<?= $docente['usuario_id'] ?>">

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">

            <div class="lg:col-span-1 space-y-6">

                <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">

                    <div class="bg-slate-50 px-5 py-3 border-b border-slate-200">
                        <h2 class="font-bold text-slate-700">
                            Datos Personales
                        </h2>
                    </div>

                    <div class="p-5 space-y-4">

                        <div>
                            <label class="text-[10px] font-bold text-slate-400 uppercase mb-1 block">
                                Nombre(s)
                            </label>

                            <input
                                type="text"
                                name="nombres"
                                value="<?= htmlspecialchars($docente['nombres']) ?>"
                                required
                                class="w-full p-2.5 bg-slate-50 border border-slate-200 rounded-lg focus:ring-2 focus:ring-indigo-500 outline-none"
                            >
                        </div>

                        <div class="grid grid-cols-2 gap-3">

                            <div>
                                <label class="text-[10px] font-bold text-slate-400 uppercase mb-1 block">
                                    A. Paterno
                                </label>

                                <input
                                    type="text"
                                    name="apellido_paterno"
                                    value="<?= htmlspecialchars($docente['apellido_paterno']) ?>"
                                    required
                                    class="w-full p-2.5 bg-slate-50 border border-slate-200 rounded-lg outline-none focus:ring-2 focus:ring-indigo-500"
                                >
                            </div>

                            <div>
                                <label class="text-[10px] font-bold text-slate-400 uppercase mb-1 block">
                                    A. Materno
                                </label>

                                <input
                                    type="text"
                                    name="apellido_materno"
                                    value="<?= htmlspecialchars($docente['apellido_materno']) ?>"
                                    class="w-full p-2.5 bg-slate-50 border border-slate-200 rounded-lg outline-none focus:ring-2 focus:ring-indigo-500"
                                >
                            </div>

                        </div>

                        <div>
                            <label class="text-[10px] font-bold text-slate-400 uppercase mb-1 block">
                                Usuario
                            </label>

                            <input
                                type="text"
                                name="username"
                                value="<?= htmlspecialchars($docente['username']) ?>"
                                readonly
                                class="w-full p-2.5 border border-slate-200 rounded-lg bg-slate-100 text-slate-600 font-mono text-sm"
                            >
                        </div>

                        <div>
                            <label class="text-[10px] font-bold text-slate-400 uppercase mb-1 block">
                                Correo Electrónico
                            </label>

                            <input
                                type="email"
                                name="email"
                                value="<?= htmlspecialchars($docente['email']) ?>"
                                class="w-full p-2.5 bg-slate-50 border border-slate-200 rounded-lg outline-none focus:ring-2 focus:ring-indigo-500"
                            >
                        </div>

                        <div>
                            <label class="text-[10px] font-bold text-slate-400 uppercase mb-1 block">
                                Estado
                            </label>

                            <select
                                name="activo"
                                class="w-full p-2.5 bg-slate-50 border border-slate-200 rounded-lg outline-none focus:ring-2 focus:ring-indigo-500"
                            >
                                <option value="1" <?= $docente['activo'] == 1 ? 'selected' : '' ?>>
                                    Activo
                                </option>

                                <option value="0" <?= $docente['activo'] == 0 ? 'selected' : '' ?>>
                                    Inactivo
                                </option>
                            </select>
                        </div>

                        <div class="pt-4 border-t border-dashed">

                            <label class="text-[10px] font-bold text-red-400 uppercase mb-1 block">
                                Nueva Contraseña
                            </label>

                            <input
                                type="password"
                                name="password"
                                placeholder="Dejar vacío para no cambiar"
                                class="w-full p-2.5 bg-white border border-slate-200 rounded-lg outline-none focus:ring-2 focus:ring-red-500 text-sm"
                            >

                        </div>

                    </div>

                </div>

                <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">

                    <div class="bg-slate-50 px-5 py-3 border-b border-slate-200">
                        <h2 class="font-bold text-slate-700 text-sm uppercase">
                            Identificación Oficial
                        </h2>
                    </div>

                    <div class="p-5 space-y-4">

                        <div>
                            <label class="text-[10px] font-bold text-slate-400 uppercase mb-1 block">
                                RFC
                            </label>

                            <input
                                type="text"
                                name="rfc"
                                value="<?= htmlspecialchars($docente['rfc'] ?? '') ?>"
                                class="w-full p-2 bg-slate-50 border border-slate-200 rounded uppercase font-bold text-slate-700"
                            >
                        </div>

                        <div>
                            <label class="text-[10px] font-bold text-slate-400 uppercase mb-1 block">
                                CURP
                            </label>

                            <input
                                type="text"
                                name="curp"
                                value="<?= htmlspecialchars($docente['curp'] ?? '') ?>"
                                class="w-full p-2 bg-slate-50 border border-slate-200 rounded uppercase font-bold text-slate-700"
                            >
                        </div>

                        <div>
                            <label class="text-[10px] font-bold text-slate-400 uppercase mb-1 block">
                                Dirección Particular
                            </label>

                            <input
                                type="text"
                                name="direccion"
                                value="<?= htmlspecialchars($docente['direccion'] ?? '') ?>"
                                class="w-full p-2 bg-slate-50 border border-slate-200 rounded text-sm"
                            >
                        </div>

                    </div>

                </div>

            </div>

            <div class="lg:col-span-2 space-y-6">

                <div class="bg-blue-50/50 rounded-2xl border border-blue-100 overflow-hidden">

                    <div class="bg-blue-600 px-6 py-3 flex justify-between items-center text-white">
                        <h2 class="font-bold uppercase tracking-wide text-sm">
                            Formación: Licenciatura
                        </h2>
                    </div>

                    <div class="p-6">

                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">

                            <div class="md:col-span-3">
                                <label class="text-[10px] font-bold text-blue-600 uppercase mb-1 block">
                                    Nombre del Título / Carrera
                                </label>

                                <input
                                    type="text"
                                    name="licenciatura"
                                    value="<?= htmlspecialchars($docente['licenciatura'] ?? '') ?>"
                                    class="w-full p-2.5 border border-blue-200 rounded-lg shadow-sm outline-none focus:ring-2 focus:ring-blue-500"
                                >
                            </div>

                            <div>
                                <label class="text-[10px] font-bold text-blue-600 uppercase mb-1 block">
                                    Folio de Título
                                </label>

                                <input
                                    type="text"
                                    name="licenciatura_titulo"
                                    value="<?= htmlspecialchars($docente['licenciatura_titulo'] ?? '') ?>"
                                    class="w-full p-2.5 border border-blue-200 rounded-lg text-sm"
                                >
                            </div>

                            <div>
                                <label class="text-[10px] font-bold text-blue-600 uppercase mb-1 block">
                                    Número de Cédula
                                </label>

                                <input
                                    type="text"
                                    name="licenciatura_cedula"
                                    value="<?= htmlspecialchars($docente['licenciatura_cedula'] ?? '') ?>"
                                    class="w-full p-2.5 border border-blue-200 rounded-lg text-sm"
                                >
                            </div>

                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 bg-white p-4 rounded-xl border border-blue-100">

                            <div class="flex flex-col gap-2">
                                <div class="flex justify-between items-center">
                                    <label class="text-[11px] font-bold text-slate-500 uppercase">
                                        Certificado
                                    </label>
                                    <?= verDoc('lic_certificado', $documentos) ?>
                                </div>
                                <input type="file" name="lic_certificado" class="text-[10px]">
                            </div>

                            <div class="flex flex-col gap-2">
                                <div class="flex justify-between items-center">
                                    <label class="text-[11px] font-bold text-slate-500 uppercase">
                                        Título
                                    </label>
                                    <?= verDoc('lic_titulo', $documentos) ?>
                                </div>
                                <input type="file" name="lic_titulo" class="text-[10px]">
                            </div>

                            <div class="flex flex-col gap-2">
                                <div class="flex justify-between items-center">
                                    <label class="text-[11px] font-bold text-slate-500 uppercase">
                                        Cédula
                                    </label>
                                    <?= verDoc('lic_cedula', $documentos) ?>
                                </div>
                                <input type="file" name="lic_cedula" class="text-[10px]">
                            </div>

                        </div>

                    </div>

                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

                    <div class="bg-emerald-50/50 rounded-2xl border border-emerald-100 overflow-hidden">

                        <div class="bg-emerald-600 px-4 py-2 text-white text-xs font-bold uppercase tracking-wider">
                            Postgrado: Maestría
                        </div>

                        <div class="p-4 space-y-4">

                            <input
                                type="text"
                                name="maestria"
                                value="<?= htmlspecialchars($docente['maestria'] ?? '') ?>"
                                placeholder="Nombre de maestría"
                                class="w-full p-2 border border-emerald-200 rounded text-sm shadow-sm"
                            >

                            <input
                                type="text"
                                name="maestria_titulo"
                                value="<?= htmlspecialchars($docente['maestria_titulo'] ?? '') ?>"
                                placeholder="Folio de título"
                                class="w-full p-2 border border-emerald-200 rounded text-sm shadow-sm"
                            >

                            <input
                                type="text"
                                name="maestria_cedula"
                                value="<?= htmlspecialchars($docente['maestria_cedula'] ?? '') ?>"
                                placeholder="Cédula profesional"
                                class="w-full p-2 border border-emerald-200 rounded text-sm shadow-sm"
                            >

                            <div class="space-y-3 bg-white/50 p-3 rounded-lg border border-emerald-100">

                                <div class="flex justify-between items-center">
                                    <span class="text-[10px] font-bold text-slate-500">Certificado</span>
                                    <?= verDoc('mae_certificado', $documentos) ?>
                                </div>
                                <input type="file" name="mae_certificado" class="text-[10px]">

                                <div class="flex justify-between items-center">
                                    <span class="text-[10px] font-bold text-slate-500">Título</span>
                                    <?= verDoc('mae_titulo', $documentos) ?>
                                </div>
                                <input type="file" name="mae_titulo" class="text-[10px]">

                                <div class="flex justify-between items-center">
                                    <span class="text-[10px] font-bold text-slate-500">Cédula</span>
                                    <?= verDoc('mae_cedula', $documentos) ?>
                                </div>
                                <input type="file" name="mae_cedula" class="text-[10px]">

                            </div>

                        </div>

                    </div>

                    <div class="bg-purple-50/50 rounded-2xl border border-purple-100 overflow-hidden">

                        <div class="bg-purple-600 px-4 py-2 text-white text-xs font-bold uppercase tracking-wider">
                            Postgrado: Doctorado
                        </div>

                        <div class="p-4 space-y-4">

                            <input
                                type="text"
                                name="doctorado"
                                value="<?= htmlspecialchars($docente['doctorado'] ?? '') ?>"
                                placeholder="Nombre de doctorado"
                                class="w-full p-2 border border-purple-200 rounded text-sm shadow-sm"
                            >

                            <input
                                type="text"
                                name="doctorado_titulo"
                                value="<?= htmlspecialchars($docente['doctorado_titulo'] ?? '') ?>"
                                placeholder="Folio de título"
                                class="w-full p-2 border border-purple-200 rounded text-sm shadow-sm"
                            >

                            <input
                                type="text"
                                name="doctorado_cedula"
                                value="<?= htmlspecialchars($docente['doctorado_cedula'] ?? '') ?>"
                                placeholder="Cédula profesional"
                                class="w-full p-2 border border-purple-200 rounded text-sm shadow-sm"
                            >

                            <div class="space-y-3 bg-white/50 p-3 rounded-lg border border-purple-100">

                                <div class="flex justify-between items-center">
                                    <span class="text-[10px] font-bold text-slate-500">Certificado</span>
                                    <?= verDoc('doc_certificado', $documentos) ?>
                                </div>
                                <input type="file" name="doc_certificado" class="text-[10px]">

                                <div class="flex justify-between items-center">
                                    <span class="text-[10px] font-bold text-slate-500">Título</span>
                                    <?= verDoc('doc_titulo', $documentos) ?>
                                </div>
                                <input type="file" name="doc_titulo" class="text-[10px]">

                                <div class="flex justify-between items-center">
                                    <span class="text-[10px] font-bold text-slate-500">Cédula</span>
                                    <?= verDoc('doc_cedula', $documentos) ?>
                                </div>
                                <input type="file" name="doc_cedula" class="text-[10px]">

                            </div>

                        </div>

                    </div>

                </div>

                <div class="bg-slate-800 rounded-2xl p-6 text-white shadow-xl">

                    <h2 class="text-lg font-bold mb-6 border-b border-slate-700 pb-2">
                        Expediente de Soporte
                    </h2>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-x-12 gap-y-6">

                        <?php
                            $docsExtra = [
                                'ine' => 'INE / Identificación',
                                'domicilio' => 'Comprobante Domicilio',
                                'fiscal' => 'Constancia Fiscal',
                                'cv' => 'Currículum Vitae'
                            ];
                        ?>

                        <?php foreach($docsExtra as $campo => $label): ?>

                            <div class="space-y-2">

                                <div class="flex justify-between">
                                    <label class="text-xs text-slate-400 uppercase font-bold tracking-tighter">
                                        <?= $label ?>
                                    </label>

                                    <?= verDoc($campo, $documentos) ?>
                                </div>

                                <input
                                    type="file"
                                    name="<?= $campo ?>"
                                    class="text-xs text-slate-400 file:bg-slate-700 file:text-white file:border-0 file:rounded file:px-2 file:py-1"
                                >

                            </div>

                        <?php endforeach; ?>

                    </div>

                </div>

                <div class="flex justify-end pt-6">

                    <button
                        type="submit"
                        class="bg-indigo-600 hover:bg-indigo-700 text-white px-10 py-4 rounded-xl font-extrabold shadow-lg shadow-indigo-200 transform hover:scale-105 active:scale-95 transition-all uppercase tracking-wider"
                    >
                        Guardar cambios en el expediente
                    </button>

                </div>

            </div>

        </div>

    </form>

</div>