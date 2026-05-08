<?php

require_once __DIR__ . '/../../includes/db.php';

/*
|--------------------------------------------------------------------------
| OBTENER ID
|--------------------------------------------------------------------------
*/

$id = $_GET['id'] ?? null;

if (!$id) {

    echo "
    <div class='bg-red-100 border border-red-200 text-red-700 p-6 rounded-2xl font-bold'>
        Usuario no especificado.
    </div>
    ";

    exit;
}

/*
|--------------------------------------------------------------------------
| OBTENER USUARIO
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare("
    SELECT
        id,
        nombres,
        apellido_paterno,
        apellido_materno,
        username,
        email,
        rol,
        numero_identificador,
        activo
    FROM usuarios
    WHERE id = ?
");

$stmt->execute([$id]);

$usuario = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$usuario) {

    echo "
    <div class='bg-red-100 border border-red-200 text-red-700 p-6 rounded-2xl font-bold'>
        Usuario no encontrado.
    </div>
    ";

    exit;
}

/*
|--------------------------------------------------------------------------
| ROLES DISPONIBLES
|--------------------------------------------------------------------------
*/

$roles = $pdo->query("
    SELECT DISTINCT rol
    FROM usuarios
    ORDER BY rol ASC
")->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html lang="es">
<head>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Editar Usuario</title>

<script src="https://cdn.tailwindcss.com"></script>

<style>

@import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap');

body{
    font-family:'Plus Jakarta Sans',sans-serif;
}

.fade-up{
    animation:fadeUp .5s ease;
}

@keyframes fadeUp{
    from{
        opacity:0;
        transform:translateY(15px);
    }
    to{
        opacity:1;
        transform:translateY(0);
    }
}

</style>

</head>

<body class="bg-slate-50 min-h-screen p-8">

<div class="max-w-5xl mx-auto fade-up">

    <!-- HEADER -->

    <div class="mb-8">

        <span class="bg-blue-600 text-white px-4 py-2 rounded-full text-[10px] font-black uppercase tracking-[0.25em]">
            Gestión de Usuarios
        </span>

        <h1 class="text-4xl font-black text-slate-900 mt-5 uppercase tracking-tight">

            Editar
            <span class="text-blue-600 italic">
                Usuario
            </span>

        </h1>

        <p class="text-slate-500 font-semibold mt-3">
            Modifica la información y configuración del usuario.
        </p>

    </div>

    <!-- FORMULARIO -->

    <form
        method="POST"
        action="admin_dashboard.php?modulo=guardar_usuario"
        class="bg-white rounded-[2rem] shadow-xl border border-slate-100 p-8 space-y-8"
    >

        <input
            type="hidden"
            name="id"
            value="<?= $usuario['id'] ?>"
        >

        <!-- INFORMACIÓN GENERAL -->

        <div>

            <h2 class="text-xl font-black uppercase tracking-tight text-slate-900 mb-6">
                Información General
            </h2>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

                <!-- NOMBRES -->

                <div>

                    <label class="block text-[10px] font-black uppercase tracking-[0.25em] text-slate-400 mb-3">
                        Nombres
                    </label>

                    <input
                        type="text"
                        name="nombres"
                        required
                        value="<?= htmlspecialchars($usuario['nombres']) ?>"
                        class="w-full bg-slate-50 border-2 border-transparent focus:border-blue-600 rounded-2xl p-4 font-bold outline-none transition-all"
                    >

                </div>

                <!-- APELLIDO PATERNO -->

                <div>

                    <label class="block text-[10px] font-black uppercase tracking-[0.25em] text-slate-400 mb-3">
                        Apellido Paterno
                    </label>

                    <input
                        type="text"
                        name="apellido_paterno"
                        required
                        value="<?= htmlspecialchars($usuario['apellido_paterno']) ?>"
                        class="w-full bg-slate-50 border-2 border-transparent focus:border-blue-600 rounded-2xl p-4 font-bold outline-none transition-all"
                    >

                </div>

                <!-- APELLIDO MATERNO -->

                <div>

                    <label class="block text-[10px] font-black uppercase tracking-[0.25em] text-slate-400 mb-3">
                        Apellido Materno
                    </label>

                    <input
                        type="text"
                        name="apellido_materno"
                        value="<?= htmlspecialchars($usuario['apellido_materno']) ?>"
                        class="w-full bg-slate-50 border-2 border-transparent focus:border-blue-600 rounded-2xl p-4 font-bold outline-none transition-all"
                    >

                </div>

                <!-- USERNAME -->

                <div>

                    <label class="block text-[10px] font-black uppercase tracking-[0.25em] text-slate-400 mb-3">
                        Nombre de Usuario
                    </label>

                    <input
                        type="text"
                        name="username"
                        required
                        value="<?= htmlspecialchars($usuario['username']) ?>"
                        class="w-full bg-slate-50 border-2 border-transparent focus:border-blue-600 rounded-2xl p-4 font-bold outline-none transition-all"
                    >

                </div>

                <!-- EMAIL -->

                <div>

                    <label class="block text-[10px] font-black uppercase tracking-[0.25em] text-slate-400 mb-3">
                        Correo Electrónico
                    </label>

                    <input
                        type="email"
                        name="email"
                        value="<?= htmlspecialchars($usuario['email']) ?>"
                        class="w-full bg-slate-50 border-2 border-transparent focus:border-blue-600 rounded-2xl p-4 font-bold outline-none transition-all"
                    >

                </div>

                <!-- IDENTIFICADOR -->

                <div>

                    <label class="block text-[10px] font-black uppercase tracking-[0.25em] text-slate-400 mb-3">
                        Número Identificador
                    </label>

                    <input
                        type="text"
                        name="numero_identificador"
                        value="<?= htmlspecialchars($usuario['numero_identificador']) ?>"
                        class="w-full bg-slate-50 border-2 border-transparent focus:border-blue-600 rounded-2xl p-4 font-bold outline-none transition-all"
                    >

                </div>

            </div>

        </div>

        <!-- CONFIGURACIÓN -->

        <div>

            <h2 class="text-xl font-black uppercase tracking-tight text-slate-900 mb-6">
                Configuración del Sistema
            </h2>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

                <!-- ROL -->

                <div>

                    <label class="block text-[10px] font-black uppercase tracking-[0.25em] text-slate-400 mb-3">
                        Rol
                    </label>

                    <select
                        name="rol"
                        class="w-full bg-slate-50 border-2 border-transparent focus:border-blue-600 rounded-2xl p-4 font-bold outline-none transition-all"
                    >

                        <?php foreach($roles as $r): ?>

                            <option
                                value="<?= $r['rol'] ?>"
                                <?= $usuario['rol'] === $r['rol'] ? 'selected' : '' ?>
                            >

                                <?= ucfirst(str_replace('_', ' ', $r['rol'])) ?>

                            </option>

                        <?php endforeach; ?>

                    </select>

                </div>

                <!-- ESTADO -->

                <div>

                    <label class="block text-[10px] font-black uppercase tracking-[0.25em] text-slate-400 mb-3">
                        Estado
                    </label>

                    <select
                        name="activo"
                        class="w-full bg-slate-50 border-2 border-transparent focus:border-blue-600 rounded-2xl p-4 font-bold outline-none transition-all"
                    >

                        <option
                            value="1"
                            <?= $usuario['activo'] == 1 ? 'selected' : '' ?>
                        >
                            Activo
                        </option>

                        <option
                            value="0"
                            <?= $usuario['activo'] == 0 ? 'selected' : '' ?>
                        >
                            Inactivo
                        </option>

                    </select>

                </div>

            </div>

        </div>

        <!-- CONTRASEÑA -->

        <div>

            <h2 class="text-xl font-black uppercase tracking-tight text-slate-900 mb-6">
                Seguridad
            </h2>

            <div>

                <label class="block text-[10px] font-black uppercase tracking-[0.25em] text-slate-400 mb-3">
                    Nueva Contraseña
                </label>

                <input
                    type="password"
                    name="password"
                    placeholder="Dejar vacío para conservar la contraseña actual"
                    class="w-full bg-slate-50 border-2 border-transparent focus:border-blue-600 rounded-2xl p-4 font-bold outline-none transition-all"
                >

                <p class="text-xs text-slate-400 font-bold mt-3 uppercase tracking-wide">
                    Solo llena este campo si deseas cambiar la contraseña.
                </p>

            </div>

        </div>

        <!-- BOTONES -->

        <div class="flex flex-col md:flex-row gap-4 pt-4">

            <button
                type="submit"
                class="flex-1 bg-blue-600 hover:bg-blue-700 text-white rounded-2xl px-6 py-5 font-black uppercase tracking-[0.2em] text-[11px] transition-all shadow-xl shadow-blue-200"
            >

                Guardar Cambios

            </button>

            <a
                href="admin_dashboard.php?modulo=usuarios_lista"
                class="flex-1 bg-slate-200 hover:bg-slate-300 text-slate-700 rounded-2xl px-6 py-5 font-black uppercase tracking-[0.2em] text-[11px] text-center transition-all">
                Cancelar
            </a>
        </div>

    </form>

</div>

</body>
</html>