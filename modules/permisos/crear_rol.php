<?php

require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/permisos.php';

validarPermiso('crear_roles');

$error = null;
$success = null;

if($_SERVER['REQUEST_METHOD'] === 'POST'){

    $nombre = trim($_POST['nombre'] ?? '');
    $slug = trim($_POST['slug'] ?? '');

    if($nombre === '' || $slug === ''){

        $error = "Todos los campos son obligatorios.";

    }else{

        $slug = strtolower($slug);
        $slug = preg_replace('/[^a-z0-9_]/', '_', $slug);

        $stmt = $pdo->prepare("
            SELECT id
            FROM roles
            WHERE slug = ?
            LIMIT 1
        ");

        $stmt->execute([$slug]);

        $existe = $stmt->fetch();

        if($existe){

            $error = "Ya existe un rol con ese slug.";

        }else{

            $stmt = $pdo->prepare("
                INSERT INTO roles (
                    nombre,
                    slug
                ) VALUES (?,?)
            ");

            $stmt->execute([
                $nombre,
                $slug
            ]);

            $rol_id = $pdo->lastInsertId();

            header("Location: ?modulo=asignar_permisos&rol_id=".$rol_id."&nuevo=1");
            exit;
        }
    }
}

?>

<div class="max-w-4xl mx-auto">

    <!-- HEADER -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-6 mb-10">

        <div>

            <span class="bg-purple-100 text-purple-700 px-4 py-2 rounded-full text-[10px] font-black uppercase tracking-[0.25em]">
                Seguridad
            </span>

            <h1 class="text-4xl font-black text-slate-900 mt-5 uppercase tracking-tight">
                Crear
                <span class="text-purple-600 italic">
                    Nuevo Rol
                </span>
            </h1>

            <p class="text-slate-500 font-semibold mt-3">
                Los roles permiten controlar el acceso a módulos y funciones del sistema.
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

    <!-- FORMULARIO -->
    <form
        method="POST"
        class="bg-white rounded-[2.5rem] shadow-xl border border-slate-100 overflow-hidden"
    >

        <!-- TOP -->
        <div class="bg-gradient-to-r from-purple-600 to-violet-600 p-10 text-white">

            <div class="flex items-center gap-5">

                <div class="w-20 h-20 bg-white/20 rounded-[2rem] flex items-center justify-center text-4xl shadow-lg">
                    🔐
                </div>

                <div>

                    <h2 class="text-3xl font-black uppercase tracking-tight">
                        Registro de Rol
                    </h2>

                    <p class="text-purple-100 font-semibold mt-2">
                        Define un nuevo perfil de acceso para usuarios del sistema.
                    </p>

                </div>

            </div>

        </div>

        <!-- BODY -->
        <div class="p-10 space-y-10">

            <!-- NOMBRE -->
            <div>

                <label class="block text-[11px] font-black uppercase tracking-[0.25em] text-slate-400 mb-3">
                    Nombre del Rol
                </label>

                <input
                    type="text"
                    name="nombre"
                    required
                    placeholder="Ejemplo: Coordinador Académico"
                    value="<?= htmlspecialchars($_POST['nombre'] ?? '') ?>"
                    class="w-full bg-slate-50 border-2 border-slate-100 focus:border-purple-600 rounded-2xl px-6 py-5 text-lg font-bold outline-none transition-all"
                >

                <p class="text-xs text-slate-400 mt-3 font-semibold">
                    Nombre visible para identificar el rol dentro del sistema.
                </p>

            </div>

            <!-- SLUG -->
            <div>

                <label class="block text-[11px] font-black uppercase tracking-[0.25em] text-slate-400 mb-3">
                    Identificador del Rol (Slug)
                </label>

                <input
                    type="text"
                    name="slug"
                    required
                    placeholder="Ejemplo: coordinador_academico"
                    value="<?= htmlspecialchars($_POST['slug'] ?? '') ?>"
                    class="w-full bg-slate-50 border-2 border-slate-100 focus:border-purple-600 rounded-2xl px-6 py-5 text-lg font-mono font-bold outline-none transition-all"
                >

                <p class="text-xs text-slate-400 mt-3 font-semibold">
                    Solo letras, números y guiones bajos. Se utiliza internamente para permisos.
                </p>

            </div>

            <!-- INFO -->
            <div class="bg-amber-50 border border-amber-200 rounded-3xl p-6">

                <div class="flex gap-4">

                    <div class="w-14 h-14 bg-amber-100 rounded-2xl flex items-center justify-center text-2xl shrink-0">
                        💡
                    </div>

                    <div>

                        <h3 class="font-black uppercase text-amber-700 tracking-wide mb-2">
                            Recomendaciones
                        </h3>

                        <ul class="space-y-2 text-sm text-amber-700 font-semibold">

                            <li>
                                • Usa nombres descriptivos para identificar fácilmente el rol.
                            </li>

                            <li>
                                • El slug debe ser único dentro del sistema.
                            </li>

                            <li>
                                • Después de crear el rol podrás asignarle permisos específicos.
                            </li>

                        </ul>

                    </div>

                </div>

            </div>

        </div>

        <!-- FOOTER -->
        <div class="border-t border-slate-100 p-8 bg-slate-50 flex justify-end gap-4">

            <a
                href="?modulo=roles_lista"
                class="px-8 py-4 rounded-2xl bg-slate-200 hover:bg-slate-300 text-slate-700 font-black uppercase tracking-[0.2em] text-[10px] transition-all"
            >
                Cancelar
            </a>

            <button
                type="submit"
                class="px-10 py-4 rounded-2xl bg-purple-600 hover:bg-purple-700 text-white font-black uppercase tracking-[0.25em] text-[10px] shadow-xl shadow-purple-200 transition-all"
            >
                Crear Rol
            </button>

        </div>

    </form>

</div>