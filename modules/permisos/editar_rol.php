<?php

require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/permisos.php';

validarPermiso('editar_roles');

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

if($id <= 0){
    die("Rol no válido.");
}

/*
|--------------------------------------------------------------------------
| OBTENER ROL
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare("
    SELECT
        id,
        nombre,
        slug
    FROM roles
    WHERE id = ?
    LIMIT 1
");

$stmt->execute([$id]);

$rol = $stmt->fetch(PDO::FETCH_ASSOC);

if(!$rol){
    die("Rol no encontrado.");
}

$rolesSistema = [
    'administrador',
    'admin',
    'coordinador',
    'docente',
    'alumno',
    'estudiante',
    'control_escolar'
];

$esSistema = in_array($rol['slug'], $rolesSistema);

$error = null;
$success = null;

/*
|--------------------------------------------------------------------------
| ACTUALIZAR ROL
|--------------------------------------------------------------------------
*/

if($_SERVER['REQUEST_METHOD'] === 'POST'){

    $nombre = trim($_POST['nombre'] ?? '');
    $slug = trim($_POST['slug'] ?? '');

    if($nombre === '' || $slug === ''){

        $error = "Todos los campos son obligatorios.";

    }else{

        $slug = strtolower($slug);
        $slug = preg_replace('/[^a-z0-9_]/', '_', $slug);

        /*
        |--------------------------------------------------------------------------
        | EVITAR CAMBIAR SLUG DE ROLES DEL SISTEMA
        |--------------------------------------------------------------------------
        */

        if($esSistema && $slug !== $rol['slug']){

            $error = "No puedes cambiar el slug de un rol principal del sistema.";

        }else{

            /*
            |--------------------------------------------------------------------------
            | VALIDAR SLUG REPETIDO
            |--------------------------------------------------------------------------
            */

            $stmt = $pdo->prepare("
                SELECT id
                FROM roles
                WHERE slug = ?
                AND id != ?
                LIMIT 1
            ");

            $stmt->execute([
                $slug,
                $id
            ]);

            if($stmt->fetch()){

                $error = "Ya existe otro rol con ese slug.";

            }else{

                $stmt = $pdo->prepare("
                    UPDATE roles
                    SET
                        nombre = ?,
                        slug = ?
                    WHERE id = ?
                ");

                $stmt->execute([
                    $nombre,
                    $slug,
                    $id
                ]);

                $success = "Rol actualizado correctamente.";

                $rol['nombre'] = $nombre;
                $rol['slug'] = $slug;
            }
        }
    }
}

?>

<div class="max-w-4xl mx-auto">

    <!-- HEADER -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-6 mb-10">

        <div>

            <span class="bg-blue-100 text-blue-700 px-4 py-2 rounded-full text-[10px] font-black uppercase tracking-[0.25em]">
                Seguridad
            </span>

            <h1 class="text-4xl font-black text-slate-900 mt-5 uppercase tracking-tight">
                Editar
                <span class="text-blue-600 italic">
                    Rol
                </span>
            </h1>

            <p class="text-slate-500 font-semibold mt-3">
                Modifica los datos principales del rol seleccionado.
            </p>

        </div>

        <a
            href="?modulo=roles_lista"
            class="bg-slate-900 hover:bg-slate-700 text-white px-6 py-4 rounded-2xl font-black uppercase tracking-[0.2em] text-[10px] transition-all"
        >
            ← Volver
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

    <!-- FORMULARIO -->
    <form
        method="POST"
        class="bg-white rounded-[2.5rem] shadow-xl border border-slate-100 overflow-hidden"
    >

        <!-- TOP -->
        <div class="bg-gradient-to-r from-blue-600 to-indigo-600 p-10 text-white">

            <div class="flex items-center gap-5">

                <div class="w-20 h-20 bg-white/20 rounded-[2rem] flex items-center justify-center text-4xl shadow-lg">
                    🔐
                </div>

                <div>

                    <h2 class="text-3xl font-black uppercase tracking-tight">
                        <?= htmlspecialchars($rol['nombre']) ?>
                    </h2>

                    <p class="text-blue-100 font-semibold mt-2">
                        Slug actual:
                        <span class="font-mono">
                            <?= htmlspecialchars($rol['slug']) ?>
                        </span>
                    </p>

                </div>

            </div>

        </div>

        <!-- BODY -->
        <div class="p-10 space-y-10">

            <?php if($esSistema): ?>

                <div class="bg-amber-50 border border-amber-200 rounded-3xl p-6">

                    <div class="flex gap-4">

                        <div class="w-14 h-14 bg-amber-100 rounded-2xl flex items-center justify-center text-2xl shrink-0">
                            ⚠️
                        </div>

                        <div>

                            <h3 class="font-black uppercase text-amber-700 tracking-wide mb-2">
                                Rol principal del sistema
                            </h3>

                            <p class="text-sm text-amber-700 font-semibold leading-relaxed">
                                Este rol forma parte de la estructura principal del sistema.
                                Puedes editar su nombre visible, pero no se recomienda cambiar su identificador interno.
                            </p>

                        </div>

                    </div>

                </div>

            <?php endif; ?>

            <!-- NOMBRE -->
            <div>

                <label class="block text-[11px] font-black uppercase tracking-[0.25em] text-slate-400 mb-3">
                    Nombre del Rol
                </label>

                <input
                    type="text"
                    name="nombre"
                    required
                    value="<?= htmlspecialchars($rol['nombre']) ?>"
                    class="w-full bg-slate-50 border-2 border-slate-100 focus:border-blue-600 rounded-2xl px-6 py-5 text-lg font-bold outline-none transition-all"
                >

                <p class="text-xs text-slate-400 mt-3 font-semibold">
                    Nombre visible para identificar el rol dentro del sistema.
                </p>

            </div>

            <!-- SLUG -->
            <div>

                <label class="block text-[11px] font-black uppercase tracking-[0.25em] text-slate-400 mb-3">
                    Identificador del Rol
                </label>

                <input
                    type="text"
                    name="slug"
                    required
                    value="<?= htmlspecialchars($rol['slug']) ?>"
                    <?= $esSistema ? 'readonly' : '' ?>
                    class="w-full bg-slate-50 border-2 border-slate-100 focus:border-blue-600 rounded-2xl px-6 py-5 text-lg font-mono font-bold outline-none transition-all <?= $esSistema ? 'opacity-70 cursor-not-allowed' : '' ?>"
                >

                <p class="text-xs text-slate-400 mt-3 font-semibold">
                    Solo letras, números y guiones bajos. Se utiliza internamente para permisos.
                </p>

            </div>

        </div>

        <!-- FOOTER -->
        <div class="border-t border-slate-100 p-8 bg-slate-50 flex flex-col md:flex-row justify-between gap-4">

            <?php if(tienePermiso('asignar_permisos')): ?>

                <a
                    href="?modulo=asignar_permisos&rol_id=<?= (int) $rol['id'] ?>"
                    class="px-8 py-4 rounded-2xl bg-purple-600 hover:bg-purple-700 text-white font-black uppercase tracking-[0.2em] text-[10px] text-center transition-all"
                >
                    Administrar permisos
                </a>

            <?php endif; ?>

            <div class="flex gap-4 justify-end">

                <a
                    href="?modulo=roles_lista"
                    class="px-8 py-4 rounded-2xl bg-slate-200 hover:bg-slate-300 text-slate-700 font-black uppercase tracking-[0.2em] text-[10px] transition-all"
                >
                    Cancelar
                </a>

                <button
                    type="submit"
                    class="px-10 py-4 rounded-2xl bg-blue-600 hover:bg-blue-700 text-white font-black uppercase tracking-[0.25em] text-[10px] shadow-xl shadow-blue-200 transition-all"
                >
                    Guardar cambios
                </button>

            </div>

        </div>

    </form>

</div>