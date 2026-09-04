<?php

require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/permisos.php';

validarPermiso('ver_detalle_docente');

$puedeEditar = tienePermiso('editar_docentes');

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

if($id <= 0){
    echo "<div class='p-4 bg-red-100 text-red-700 rounded-xl border border-red-200'>Docente no encontrado</div>";
    exit;
}

/*
|--------------------------------------------------------------------------
| DATOS DOCENTE
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare("
    SELECT 
        d.*,
        u.id AS usuario_id,
        u.nombres,
        u.apellido_paterno,
        u.apellido_materno,
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
    echo "<div class='p-4 bg-red-100 text-red-700 rounded-xl border border-red-200'>El registro no existe en la base de datos</div>";
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
    ORDER BY tipo ASC
");

$stmtDocs->execute([$id]);
$documentos = $stmtDocs->fetchAll(PDO::FETCH_KEY_PAIR);

$nombreCompleto = trim(
    $docente['nombres'] . ' ' .
    $docente['apellido_paterno'] . ' ' .
    $docente['apellido_materno']
);

?>

<div class="max-w-6xl mx-auto p-4">

    <div class="flex flex-col md:flex-row md:items-center justify-between mb-8 border-b pb-6 gap-4">

        <div>
            <div class="flex items-center gap-3 mb-1">

                <span class="bg-indigo-100 text-indigo-700 text-[10px] font-extrabold px-2 py-0.5 rounded uppercase tracking-wider border border-indigo-200">
                    Expediente <?= $docente['activo'] ? 'Activo' : 'Inactivo' ?>
                </span>

                <span class="text-slate-400 text-sm font-mono">
                    ID: <?= htmlspecialchars($docente['numero_identificador']) ?>
                </span>

            </div>

            <h1 class="text-3xl font-extrabold text-slate-800 tracking-tight">
                <?= htmlspecialchars($nombreCompleto) ?>
            </h1>

            <p class="text-slate-500 font-medium">
                Visualización completa del perfil docente y repositorio de documentos.
            </p>
        </div>

        <div class="flex gap-3">

            <a
                href="?modulo=lista_docentes"
                class="bg-slate-100 hover:bg-slate-200 text-slate-700 px-5 py-2.5 rounded-xl font-bold text-sm transition-all"
            >
                Volver
            </a>

            <?php if($puedeEditar): ?>
                <a
                    href="?modulo=editar_docente&id=<?= $id ?>"
                    class="bg-amber-500 hover:bg-amber-600 text-white px-5 py-2.5 rounded-xl font-bold text-sm shadow-lg shadow-amber-100 transition-all flex items-center gap-2"
                >
                    Editar Expediente
                </a>
            <?php endif; ?>

        </div>

    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">

        <div class="lg:col-span-1 space-y-6">

            <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">

                <div class="bg-slate-50 px-5 py-3 border-b border-slate-200">
                    <h2 class="font-bold text-slate-700 text-xs uppercase tracking-wider">
                        Información de Contacto
                    </h2>
                </div>

                <div class="p-5 space-y-4">

                    <div>
                        <label class="text-[10px] font-bold text-slate-400 uppercase block">
                            Correo Institucional
                        </label>

                        <p class="text-slate-700 font-semibold break-all">
                            <?= htmlspecialchars($docente['email']) ?>
                        </p>
                    </div>

                    <div>
                        <label class="text-[10px] font-bold text-slate-400 uppercase block">
                            Número Identificador
                        </label>

                        <p class="text-slate-700 font-mono font-bold">
                            <?= htmlspecialchars($docente['numero_identificador']) ?>
                        </p>
                    </div>

                </div>
            </div>

            <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">

                <div class="bg-slate-50 px-5 py-3 border-b border-slate-200">
                    <h2 class="font-bold text-slate-700 text-xs uppercase tracking-wider">
                        Identidad Legal
                    </h2>
                </div>

                <div class="p-5 space-y-4">

                    <div>
                        <label class="text-[10px] font-bold text-slate-400 uppercase block">
                            CURP
                        </label>

                        <p class="text-slate-700 font-bold uppercase">
                            <?= htmlspecialchars($docente['curp'] ?? '—') ?>
                        </p>
                    </div>

                    <div>
                        <label class="text-[10px] font-bold text-slate-400 uppercase block">
                            RFC
                        </label>

                        <p class="text-slate-700 font-bold uppercase">
                            <?= htmlspecialchars($docente['rfc'] ?? '—') ?>
                        </p>
                    </div>

                    <div>
                        <label class="text-[10px] font-bold text-slate-400 uppercase block">
                            Dirección Registrada
                        </label>

                        <p class="text-slate-600 text-sm leading-relaxed">
                            <?= htmlspecialchars($docente['direccion'] ?? '—') ?>
                        </p>
                    </div>

                </div>
            </div>

        </div>

        <div class="lg:col-span-2 space-y-6">

            <div class="bg-blue-50/50 rounded-2xl border border-blue-100 overflow-hidden shadow-sm">

                <div class="bg-blue-600 px-6 py-3 text-white flex justify-between items-center">
                    <h2 class="font-bold uppercase tracking-wide text-xs">
                        Grado Académico Principal
                    </h2>
                </div>

                <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-6">

                    <div class="md:col-span-2">
                        <label class="text-[10px] font-bold text-blue-600 uppercase block tracking-wider mb-1">
                            Licenciatura
                        </label>

                        <p class="text-lg font-extrabold text-blue-900 leading-tight">
                            <?= htmlspecialchars($docente['licenciatura'] ?? '—') ?>
                        </p>
                    </div>

                    <div class="bg-white p-3 rounded-lg border border-blue-100">
                        <label class="text-[10px] font-bold text-slate-400 uppercase block">
                            Título
                        </label>

                        <p class="text-sm font-bold text-slate-700">
                            <?= htmlspecialchars($docente['licenciatura_titulo'] ?? '—') ?>
                        </p>
                    </div>

                    <div class="bg-white p-3 rounded-lg border border-blue-100">
                        <label class="text-[10px] font-bold text-slate-400 uppercase block">
                            Cédula
                        </label>

                        <p class="text-sm font-bold text-slate-700">
                            <?= htmlspecialchars($docente['licenciatura_cedula'] ?? '—') ?>
                        </p>
                    </div>

                    <div class="bg-white p-3 rounded-lg border border-blue-100">
                        <label class="text-[10px] font-bold text-slate-400 uppercase block">
                            Maestría
                        </label>

                        <p class="text-sm font-bold text-slate-700">
                            <?= htmlspecialchars($docente['maestria'] ?? '—') ?>
                        </p>
                    </div>

                    <div class="bg-white p-3 rounded-lg border border-blue-100">
                        <label class="text-[10px] font-bold text-slate-400 uppercase block">
                            Doctorado
                        </label>

                        <p class="text-sm font-bold text-slate-700">
                            <?= htmlspecialchars($docente['doctorado'] ?? '—') ?>
                        </p>
                    </div>

                </div>
            </div>

            <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">

                <div class="bg-slate-50 px-6 py-4 border-b border-slate-200 flex items-center justify-between">

                    <h2 class="font-bold text-slate-700">
                        Repositorio de Documentos Digitalizados
                    </h2>

                    <span class="text-xs font-medium text-slate-400">
                        <?= count($documentos) ?> archivos subidos
                    </span>

                </div>

                <div class="p-6">

                    <?php if(!empty($documentos)): ?>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">

                            <?php foreach($documentos as $tipo => $archivo): ?>

                                <div class="group flex items-center justify-between p-4 rounded-xl border border-slate-100 bg-slate-50 hover:bg-white hover:border-indigo-200 hover:shadow-md transition-all duration-200">

                                    <div>
                                        <p class="font-extrabold text-slate-700 text-xs uppercase tracking-wider">
                                            <?= htmlspecialchars(str_replace('_', ' ', $tipo)) ?>
                                        </p>

                                        <p class="text-[10px] text-slate-400 font-medium italic">
                                            Documento registrado
                                        </p>
                                    </div>

                                    <a
                                        href="/sistema_academico/<?= htmlspecialchars($archivo) ?>"
                                        target="_blank"
                                        class="inline-flex items-center gap-1 text-xs font-bold text-indigo-600 bg-indigo-50 px-3 py-1.5 rounded-lg hover:bg-indigo-600 hover:text-white transition-all"
                                    >
                                        Ver archivo
                                    </a>

                                </div>

                            <?php endforeach; ?>

                        </div>

                    <?php else: ?>

                        <div class="text-center py-12">
                            <p class="text-slate-400 font-medium italic">
                                No se han cargado documentos en este expediente.
                            </p>
                        </div>

                    <?php endif; ?>

                </div>
            </div>

        </div>
    </div>
</div>