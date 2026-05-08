<?php
require_once __DIR__ . '/../../includes/db.php';

/*
|--------------------------------------------------------------------------
| FILTROS
|--------------------------------------------------------------------------
*/

$nombres = $_GET['nombres'] ?? '';
$rol = $_GET['rol'] ?? '';
$activo = $_GET['activo'] ?? '';

/*
|--------------------------------------------------------------------------
| ROLES DINÁMICOS
|--------------------------------------------------------------------------
*/

$roles = $pdo->query("
    SELECT DISTINCT rol
    FROM usuarios
    ORDER BY rol ASC
")->fetchAll(PDO::FETCH_ASSOC);

/*
|--------------------------------------------------------------------------
| CONSULTA USUARIOS
|--------------------------------------------------------------------------
*/

$sql = "
    SELECT
        id,
        nombres,
        apellido_paterno,
        apellido_materno,
        username,
        email,
        rol,
        numero_identificador,
        fecha_creacion,
        activo
    FROM usuarios
    WHERE 1=1
";

$params = [];

/* FILTRO NOMBRE */

if($nombres){

    $sql .= "
        AND (
            nombres LIKE ?
            OR apellido_paterno LIKE ?
            OR apellido_materno LIKE ?
            OR username LIKE ?
            OR numero_identificador LIKE ?
        )
    ";

    $params[] = "%$nombres%";
    $params[] = "%$nombres%";
    $params[] = "%$nombres%";
    $params[] = "%$nombres%";
    $params[] = "%$nombres%";
}

/* FILTRO ROL */

if($rol){

    $sql .= " AND rol = ?";
    $params[] = $rol;
}

/* FILTRO ESTADO */

if($activo !== ''){

    $sql .= " AND activo = ?";
    $params[] = $activo;
}

/* ORDEN */

$sql .= "
    ORDER BY
        nombres ASC,
        apellido_paterno ASC
";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);

$usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html lang="es">
<head>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Usuarios del Sistema</title>

<script src="https://cdn.tailwindcss.com"></script>

<style>

@import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap');

body{
    font-family:'Plus Jakarta Sans',sans-serif;
}

.fade-up{
    animation:fadeUp .4s ease;
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

<body class="bg-slate-50 min-h-screen p-6">

<div class="max-w-7xl mx-auto fade-up">

    <!-- HEADER -->

    <div class="mb-8">

        <span class="bg-purple-600 text-white px-4 py-2 rounded-full text-[10px] font-black uppercase tracking-[0.25em]">
            Administración
        </span>

        <h1 class="text-4xl font-black text-slate-900 mt-5 uppercase tracking-tight">

            Usuarios del
            <span class="text-purple-600 italic">
                Sistema
            </span>

        </h1>

        <p class="text-slate-500 font-semibold mt-3">
            Gestión y administración de usuarios registrados.
        </p>

    </div>

    <!-- FILTROS -->

    <div class="bg-white rounded-[2rem] shadow-xl border border-slate-100 p-6 mb-8">

        <form method="GET" action="admin_dashboard.php">

            <input type="hidden" name="modulo" value="usuarios_lista">

            <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4">

                <!-- BUSQUEDA -->

                <div>

                    <label class="block text-[10px] font-black uppercase tracking-[0.2em] text-slate-400 mb-3">
                        Buscar
                    </label>

                    <input
                        type="text"
                        name="nombres"
                        placeholder="Nombre, usuario o matrícula"
                        value="<?= htmlspecialchars($nombres) ?>"
                        class="w-full bg-slate-50 border-2 border-transparent focus:border-purple-600 rounded-2xl p-4 font-bold outline-none transition-all"
                    >

                </div>

                <!-- ROL -->

                <div>

                    <label class="block text-[10px] font-black uppercase tracking-[0.2em] text-slate-400 mb-3">
                        Rol
                    </label>

                    <select
                        name="rol"
                        class="w-full bg-slate-50 border-2 border-transparent focus:border-purple-600 rounded-2xl p-4 font-bold outline-none transition-all"
                    >

                        <option value="">
                            Todos los roles
                        </option>

                        <?php foreach($roles as $r): ?>

                            <option
                                value="<?= $r['rol'] ?>"
                                <?= $rol == $r['rol'] ? 'selected' : '' ?>
                            >

                                <?= ucfirst($r['rol']) ?>

                            </option>

                        <?php endforeach; ?>

                    </select>

                </div>

                <!-- ESTADO -->

                <div>

                    <label class="block text-[10px] font-black uppercase tracking-[0.2em] text-slate-400 mb-3">
                        Estado
                    </label>

                    <select
                        name="activo"
                        class="w-full bg-slate-50 border-2 border-transparent focus:border-purple-600 rounded-2xl p-4 font-bold outline-none transition-all"
                    >

                        <option value="">
                            Todos
                        </option>

                        <option
                            value="1"
                            <?= $activo === "1" ? 'selected' : '' ?>
                        >
                            Activos
                        </option>

                        <option
                            value="0"
                            <?= $activo === "0" ? 'selected' : '' ?>
                        >
                            Inactivos
                        </option>

                    </select>

                </div>

                <!-- BOTON -->

                <div class="flex items-end">

                    <button
                        type="submit"
                        class="w-full bg-slate-900 hover:bg-purple-600 text-white rounded-2xl p-4 font-black uppercase tracking-[0.2em] text-[10px] transition-all"
                    >

                        Filtrar Usuarios

                    </button>

                </div>

            </div>

        </form>

    </div>

    <!-- TABLA -->

    <div class="bg-white rounded-[2rem] shadow-xl border border-slate-100 overflow-hidden">

        <div class="overflow-x-auto">

            <table class="w-full min-w-[1200px]">

                <thead class="bg-slate-900 text-white">

                    <tr>

                        <th class="p-5 text-left text-[10px] uppercase tracking-[0.25em]">
                            Usuario
                        </th>

                        <th class="p-5 text-left text-[10px] uppercase tracking-[0.25em]">
                            Username
                        </th>

                        <th class="p-5 text-left text-[10px] uppercase tracking-[0.25em]">
                            Email
                        </th>

                        <th class="p-5 text-left text-[10px] uppercase tracking-[0.25em]">
                            Identificador
                        </th>

                        <th class="p-5 text-left text-[10px] uppercase tracking-[0.25em]">
                            Rol
                        </th>

                        <th class="p-5 text-left text-[10px] uppercase tracking-[0.25em]">
                            Estado
                        </th>

                        <th class="p-5 text-left text-[10px] uppercase tracking-[0.25em]">
                            Registro
                        </th>

                        <th class="p-5 text-center text-[10px] uppercase tracking-[0.25em]">
                            Acciones
                        </th>

                    </tr>

                </thead>

                <tbody>

                <?php if(count($usuarios) > 0): ?>

                    <?php foreach($usuarios as $u): ?>

                        <?php
                        
                        $nombreCompleto =
                            trim(
                                $u['nombres'] . ' ' .
                                $u['apellido_paterno'] . ' ' .
                                $u['apellido_materno']
                            );

                        ?>

                        <tr class="border-b border-slate-100 hover:bg-slate-50 transition-all">

                            <!-- NOMBRE -->

                            <td class="p-5">

                                <div class="flex flex-col">

                                    <span class="font-black text-slate-900 uppercase text-sm">
                                        <?= htmlspecialchars($nombreCompleto) ?>
                                    </span>

                                    <span class="text-xs text-slate-400 font-bold mt-1">
                                        ID #<?= $u['id'] ?>
                                    </span>

                                </div>

                            </td>

                            <!-- USERNAME -->

                            <td class="p-5">

                                <span class="bg-slate-100 text-slate-700 px-3 py-2 rounded-full text-xs font-black">
                                    @<?= htmlspecialchars($u['username']) ?>
                                </span>

                            </td>

                            <!-- EMAIL -->

                            <td class="p-5">

                                <span class="font-semibold text-slate-700">
                                    <?= htmlspecialchars($u['email']) ?>
                                </span>

                            </td>

                            <!-- IDENTIFICADOR -->

                            <td class="p-5">

                                <span class="font-black text-slate-700">
                                    <?= htmlspecialchars($u['numero_identificador']) ?>
                                </span>

                            </td>

                            <!-- ROL -->

                            <td class="p-5">

                                <span class="bg-purple-100 text-purple-700 px-3 py-2 rounded-full text-xs font-black uppercase">
                                    <?= htmlspecialchars($u['rol']) ?>
                                </span>

                            </td>

                            <!-- ESTADO -->

                            <td class="p-5">

                                <?php if($u['activo']): ?>

                                    <span class="bg-emerald-100 text-emerald-700 px-3 py-2 rounded-full text-xs font-black uppercase">
                                        Activo
                                    </span>

                                <?php else: ?>

                                    <span class="bg-red-100 text-red-700 px-3 py-2 rounded-full text-xs font-black uppercase">
                                        Inactivo
                                    </span>

                                <?php endif; ?>

                            </td>

                            <!-- FECHA -->

                            <td class="p-5">

                                <span class="font-semibold text-slate-600 text-sm">
                                    <?= date('d/m/Y', strtotime($u['fecha_creacion'])) ?>
                                </span>

                            </td>

                            <!-- ACCIONES -->

                            <td class="p-5">

                                <div class="flex items-center justify-center gap-3">

                                    <a
                                        href="?modulo=editar_usuario&id=<?= $u['id'] ?>"
                                        class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-xl text-xs font-black uppercase tracking-wider transition-all"
                                    >

                                        Editar

                                    </a>

                                    <button
                                        onclick='mostrarEliminar(<?= json_encode($u) ?>)'
                                        class="bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded-xl text-xs font-black uppercase tracking-wider transition-all"
                                    >

                                        Eliminar

                                    </button>

                                </div>

                            </td>

                        </tr>

                    <?php endforeach; ?>

                <?php else: ?>

                    <tr>

                        <td colspan="8" class="p-16 text-center">

                            <div class="text-6xl mb-4">
                                👤
                            </div>

                            <h2 class="text-2xl font-black text-slate-900 uppercase mb-3">
                                Sin Usuarios
                            </h2>

                            <p class="text-slate-500 font-semibold">
                                No se encontraron usuarios con los filtros seleccionados.
                            </p>

                        </td>

                    </tr>

                <?php endif; ?>

                </tbody>

            </table>

        </div>

    </div>

</div>

<!-- MODAL ELIMINAR -->

<div
    id="modalEliminar"
    class="fixed inset-0 bg-black/50 hidden items-center justify-center z-50 p-4"
>

    <div class="bg-white rounded-[2rem] shadow-2xl w-full max-w-md p-8">

        <div class="text-center">

            <div class="bg-red-100 text-red-600 w-20 h-20 rounded-3xl flex items-center justify-center mx-auto mb-6 text-3xl">
                ⚠️
            </div>

            <h2 class="text-2xl font-black text-slate-900 uppercase tracking-tight mb-3">
                Eliminar Usuario
            </h2>

            <p class="text-slate-500 font-semibold mb-6">
                Esta acción eliminará permanentemente el usuario seleccionado.
            </p>

        </div>

        <div
            id="datosUsuario"
            class="bg-slate-50 rounded-2xl p-5 border border-slate-100"
        ></div>

        <div class="flex gap-4 mt-8">

            <button
                onclick="cerrarModal()"
                class="flex-1 bg-slate-200 hover:bg-slate-300 text-slate-700 p-4 rounded-2xl font-black uppercase text-[10px] tracking-[0.2em] transition-all"
            >

                Cancelar

            </button>

            <a
                id="btnEliminar"
                class="flex-1 bg-red-500 hover:bg-red-600 text-white p-4 rounded-2xl font-black uppercase text-[10px] tracking-[0.2em] text-center transition-all"
            >

                Eliminar

            </a>

        </div>

    </div>

</div>

<script>

function mostrarEliminar(usuario){

    document
        .getElementById("modalEliminar")
        .classList.remove("hidden");

    document
        .getElementById("modalEliminar")
        .classList.add("flex");

    let estado =
        usuario.activo == 1
        ? "Activo"
        : "Inactivo";

    let nombreCompleto =
        `${usuario.nombres} ${usuario.apellido_paterno ?? ''} ${usuario.apellido_materno ?? ''}`;

    document.getElementById("datosUsuario").innerHTML = `

        <div class="space-y-3 text-sm">

            <p>
                <span class="font-black text-slate-700">
                    Nombre:
                </span>
                ${nombreCompleto}
            </p>

            <p>
                <span class="font-black text-slate-700">
                    Username:
                </span>
                @${usuario.username}
            </p>

            <p>
                <span class="font-black text-slate-700">
                    Email:
                </span>
                ${usuario.email}
            </p>

            <p>
                <span class="font-black text-slate-700">
                    Rol:
                </span>
                ${usuario.rol}
            </p>

            <p>
                <span class="font-black text-slate-700">
                    Estado:
                </span>
                ${estado}
            </p>

        </div>

    `;

    document.getElementById("btnEliminar").href =
        "/sistema_academico/modules/admin/eliminar_usuario.php?id=" + usuario.id;
}

function cerrarModal(){

    document
        .getElementById("modalEliminar")
        .classList.remove("flex");

    document
        .getElementById("modalEliminar")
        .classList.add("hidden");
}

</script>

</body>
</html>