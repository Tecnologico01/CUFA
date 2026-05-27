<?php

require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/permisos.php';

validarPermiso('gestionar_permisos');

$error = null;
$success = null;

/*
|--------------------------------------------------------------------------
| CREAR NUEVO PERMISO
|--------------------------------------------------------------------------
*/

if($_SERVER['REQUEST_METHOD'] === 'POST'){

    $nombre = trim($_POST['nombre'] ?? '');
    $slug = trim($_POST['slug'] ?? '');
    $modulo = trim($_POST['modulo'] ?? '');
    $descripcion = trim($_POST['descripcion'] ?? '');

    if($nombre === '' || $slug === '' || $modulo === ''){

        $error = "Nombre, slug y módulo son obligatorios.";

    }else{

        $slug = strtolower($slug);
        $slug = preg_replace('/[^a-z0-9_]/', '_', $slug);

        $modulo = strtolower($modulo);
        $modulo = preg_replace('/[^a-z0-9_]/', '_', $modulo);

        $stmt = $pdo->prepare("
            SELECT id
            FROM permisos
            WHERE slug = ?
            LIMIT 1
        ");

        $stmt->execute([$slug]);

        if($stmt->fetch()){

            $error = "Ya existe un permiso con ese slug.";

        }else{

            $stmt = $pdo->prepare("
                INSERT INTO permisos
                (
                    nombre,
                    slug,
                    modulo,
                    descripcion
                )
                VALUES
                (
                    ?, ?, ?, ?
                )
            ");

            $stmt->execute([
                $nombre,
                $slug,
                $modulo,
                $descripcion ?: null
            ]);

            $success = "Permiso creado correctamente.";
        }
    }
}

/*
|--------------------------------------------------------------------------
| CONSULTAR PERMISOS
|--------------------------------------------------------------------------
*/

$stmt = $pdo->query("
    SELECT
        id,
        nombre,
        slug,
        modulo,
        descripcion,
        created_at
    FROM permisos
    ORDER BY modulo ASC, nombre ASC
");

$permisos = $stmt->fetchAll(PDO::FETCH_ASSOC);

$agrupados = [];

foreach($permisos as $permiso){
    $agrupados[$permiso['modulo']][] = $permiso;
}

?>

<div class="max-w-7xl mx-auto">

    <!-- HEADER -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-6 mb-10">

        <div>

            <span class="bg-red-100 text-red-700 px-4 py-2 rounded-full text-[10px] font-black uppercase tracking-[0.25em]">
                Seguridad
            </span>

            <h1 class="text-4xl font-black text-slate-900 mt-5 uppercase tracking-tight">
                Gestionar
                <span class="text-red-600 italic">
                    Permisos
                </span>
            </h1>

            <p class="text-slate-500 font-semibold mt-3">
                Administra los permisos generales que controlan el acceso a los módulos del sistema.
            </p>

        </div>

        <a
            href="?modulo=roles_lista"
            class="bg-slate-900 hover:bg-slate-700 text-white px-6 py-4 rounded-2xl font-black uppercase tracking-[0.2em] text-[10px] transition-all"
        >
            ← Volver a roles
        </a>

    </div>

    <?php if($error): ?>

        <div class="bg-red-50 border border-red-200 text-red-700 rounded-3xl p-6 mb-8">

            <div class="flex items-center gap-4">

                <div class="w-14 h-14 rounded-2xl bg-red-100 flex items-center justify-center text-2xl">
                    ⚠️
                </div>

                <div>

                    <h3 class="font-black uppercase tracking-wide mb-1">
                        Error
                    </h3>

                    <p class="font-semibold">
                        <?= htmlspecialchars($error) ?>
                    </p>

                </div>

            </div>

        </div>

    <?php endif; ?>

    <?php if($success): ?>

        <div class="bg-emerald-50 border border-emerald-200 text-emerald-700 rounded-3xl p-6 mb-8">

            <div class="flex items-center gap-4">

                <div class="w-14 h-14 rounded-2xl bg-emerald-100 flex items-center justify-center text-2xl">
                    ✓
                </div>

                <div>

                    <h3 class="font-black uppercase tracking-wide mb-1">
                        Correcto
                    </h3>

                    <p class="font-semibold">
                        <?= htmlspecialchars($success) ?>
                    </p>

                </div>

            </div>

        </div>

    <?php endif; ?>

    <!-- FORMULARIO CREAR PERMISO -->
    <div class="bg-white rounded-[2.5rem] shadow-xl border border-slate-100 overflow-hidden mb-10">

        <div class="bg-gradient-to-r from-red-600 to-rose-600 p-8 text-white">

            <div class="flex items-center gap-5">

                <div class="w-16 h-16 bg-white/20 rounded-[1.5rem] flex items-center justify-center text-3xl">
                    🔑
                </div>

                <div>

                    <h2 class="text-2xl font-black uppercase tracking-tight">
                        Crear Nuevo Permiso
                    </h2>

                    <p class="text-red-100 font-semibold mt-1">
                        Registra permisos adicionales para nuevos módulos o funciones.
                    </p>

                </div>

            </div>

        </div>

        <form method="POST" class="p-8 space-y-6">

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">

                <div>

                    <label class="block text-[10px] font-black uppercase tracking-[0.25em] text-slate-400 mb-3">
                        Nombre
                    </label>

                    <input
                        type="text"
                        name="nombre"
                        required
                        placeholder="Ej. Ver reportes"
                        class="w-full bg-slate-50 border-2 border-slate-100 focus:border-red-600 rounded-2xl px-5 py-4 font-bold outline-none transition-all"
                    >

                </div>

                <div>

                    <label class="block text-[10px] font-black uppercase tracking-[0.25em] text-slate-400 mb-3">
                        Slug
                    </label>

                    <input
                        type="text"
                        name="slug"
                        required
                        placeholder="ver_reportes"
                        class="w-full bg-slate-50 border-2 border-slate-100 focus:border-red-600 rounded-2xl px-5 py-4 font-mono font-bold outline-none transition-all"
                    >

                </div>

                <div>

                    <label class="block text-[10px] font-black uppercase tracking-[0.25em] text-slate-400 mb-3">
                        Módulo
                    </label>

                    <input
                        type="text"
                        name="modulo"
                        required
                        placeholder="reportes"
                        class="w-full bg-slate-50 border-2 border-slate-100 focus:border-red-600 rounded-2xl px-5 py-4 font-bold outline-none transition-all"
                    >

                </div>

            </div>

            <div>

                <label class="block text-[10px] font-black uppercase tracking-[0.25em] text-slate-400 mb-3">
                    Descripción
                </label>

                <textarea
                    name="descripcion"
                    rows="3"
                    placeholder="Describe brevemente para qué sirve este permiso."
                    class="w-full bg-slate-50 border-2 border-slate-100 focus:border-red-600 rounded-2xl px-5 py-4 font-semibold outline-none transition-all resize-none"
                ></textarea>

            </div>

            <div class="flex justify-end">

                <button
                    type="submit"
                    class="bg-red-600 hover:bg-red-700 text-white px-10 py-4 rounded-2xl font-black uppercase tracking-[0.25em] text-[10px] shadow-xl shadow-red-200 transition-all"
                >
                    Crear Permiso
                </button>

            </div>

        </form>

    </div>

    <!-- BUSCADOR -->
    <div class="bg-white rounded-[2rem] shadow-sm border border-slate-100 p-6 mb-8">

        <div class="relative">

            <div class="absolute inset-y-0 left-0 pl-5 flex items-center pointer-events-none text-slate-300">
                🔍
            </div>

            <input
                type="text"
                id="buscadorPermisos"
                placeholder="Buscar permiso por nombre, slug o módulo..."
                class="w-full bg-slate-50 border-2 border-transparent focus:border-red-600 rounded-2xl py-4 pl-14 pr-5 font-bold outline-none transition-all"
            >

        </div>

    </div>

    <!-- LISTADO DE PERMISOS -->
    <div class="space-y-8" id="contenedorPermisos">

        <?php foreach($agrupados as $modulo => $lista): ?>

            <section
                class="modulo-permisos bg-white rounded-[2.5rem] shadow-sm border border-slate-100 overflow-hidden"
                data-modulo="<?= strtolower(htmlspecialchars($modulo)) ?>"
            >

                <div class="bg-slate-50 border-b border-slate-100 px-8 py-6 flex flex-col md:flex-row md:items-center justify-between gap-4">

                    <div class="flex items-center gap-4">

                        <div class="w-14 h-14 bg-red-100 text-red-600 rounded-2xl flex items-center justify-center text-2xl">
                            📁
                        </div>

                        <div>

                            <h2 class="text-2xl font-black text-slate-900 uppercase tracking-tight">
                                <?= htmlspecialchars($modulo) ?>
                            </h2>

                            <p class="text-xs font-black text-slate-400 uppercase tracking-widest">
                                <?= count($lista) ?> permisos registrados
                            </p>

                        </div>

                    </div>

                    <button
                        type="button"
                        onclick="togglePermisos('grupo_<?= htmlspecialchars($modulo) ?>')"
                        class="bg-white hover:bg-red-50 text-slate-500 hover:text-red-600 border border-slate-200 px-5 py-3 rounded-2xl text-[10px] font-black uppercase tracking-widest transition-all"
                    >
                        Mostrar / Ocultar
                    </button>

                </div>

                <div
                    id="grupo_<?= htmlspecialchars($modulo) ?>"
                    class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5 p-8"
                >

                    <?php foreach($lista as $permiso): ?>

                        <div
                            class="permiso-card bg-slate-50 hover:bg-red-50 border border-slate-100 hover:border-red-200 rounded-2xl p-5 transition-all"
                            data-text="<?= strtolower(htmlspecialchars($permiso['nombre'] . ' ' . $permiso['slug'] . ' ' . $permiso['modulo'])) ?>"
                        >

                            <div class="flex items-start justify-between gap-4 mb-4">

                                <div>

                                    <h3 class="font-black text-slate-900 uppercase text-sm tracking-tight">
                                        <?= htmlspecialchars($permiso['nombre']) ?>
                                    </h3>

                                    <p class="text-xs text-red-500 font-mono font-bold mt-1">
                                        <?= htmlspecialchars($permiso['slug']) ?>
                                    </p>

                                </div>

                                <span class="bg-white text-slate-400 px-3 py-1 rounded-full text-[9px] font-black uppercase border border-slate-100">
                                    #<?= (int) $permiso['id'] ?>
                                </span>

                            </div>

                            <p class="text-sm text-slate-500 font-medium leading-relaxed min-h-[40px]">
                                <?= htmlspecialchars($permiso['descripcion'] ?? 'Sin descripción registrada.') ?>
                            </p>

                            <div class="mt-5 pt-4 border-t border-slate-200 flex items-center justify-between">

                                <span class="text-[10px] font-black uppercase tracking-widest text-slate-400">
                                    <?= htmlspecialchars($permiso['modulo']) ?>
                                </span>

                                <span class="text-[10px] font-bold text-slate-300">
                                    <?= htmlspecialchars(date('d/m/Y', strtotime($permiso['created_at']))) ?>
                                </span>

                            </div>

                        </div>

                    <?php endforeach; ?>

                </div>

            </section>

        <?php endforeach; ?>

    </div>

    <div
        id="sinResultadosPermisos"
        class="hidden bg-white rounded-[2rem] shadow-sm border border-slate-100 p-16 text-center mt-8"
    >

        <div class="text-6xl mb-4">
            🔎
        </div>

        <h2 class="text-2xl font-black text-slate-900 uppercase mb-3">
            Sin coincidencias
        </h2>

        <p class="text-slate-500 font-semibold">
            No se encontraron permisos con ese criterio de búsqueda.
        </p>

    </div>

</div>

<script>
function togglePermisos(id){

    const el = document.getElementById(id);

    if(!el){
        return;
    }

    if(el.style.display === 'none'){
        el.style.display = 'grid';
    }else{
        el.style.display = 'none';
    }
}

const buscador = document.getElementById('buscadorPermisos');
const cards = document.querySelectorAll('.permiso-card');
const secciones = document.querySelectorAll('.modulo-permisos');
const sinResultados = document.getElementById('sinResultadosPermisos');

if(buscador){

    buscador.addEventListener('keyup', function(){

        const filtro = this.value.toLowerCase().trim();
        let totalVisibles = 0;

        cards.forEach(card => {

            const texto = card.dataset.text || '';

            if(texto.includes(filtro)){
                card.style.display = 'block';
                totalVisibles++;
            }else{
                card.style.display = 'none';
            }

        });

        secciones.forEach(section => {

            const visibles = section.querySelectorAll('.permiso-card[style="display: block;"]').length;
            const cardsInternas = section.querySelectorAll('.permiso-card');

            if(filtro === ''){
                section.style.display = 'block';
                cardsInternas.forEach(card => card.style.display = 'block');
                totalVisibles = cards.length;
            }else{
                section.style.display = visibles > 0 ? 'block' : 'none';
            }

        });

        if(totalVisibles === 0){
            sinResultados.classList.remove('hidden');
        }else{
            sinResultados.classList.add('hidden');
        }

    });
}
</script>