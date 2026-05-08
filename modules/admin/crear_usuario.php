<?php

require_once __DIR__ . '/../../includes/db.php';

$error = '';
$mensaje = '';

/*
|--------------------------------------------------------------------------
| CREAR USUARIO
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $nombres = trim($_POST['nombres'] ?? '');
    $apellido_paterno = trim($_POST['apellido_paterno'] ?? '');
    $apellido_materno = trim($_POST['apellido_materno'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $rol = trim($_POST['rol'] ?? '');
    $numero_identificador = trim($_POST['numero_identificador'] ?? '');
    $password = $_POST['password'] ?? '';

    /*
    |--------------------------------------------------------------------------
    | VALIDACIONES
    |--------------------------------------------------------------------------
    */

    if (
        empty($nombres) ||
        empty($apellido_paterno) ||
        empty($username) ||
        empty($rol) ||
        empty($password)
    ) {

        $error = "Completa todos los campos obligatorios.";

    } else {

        try {

            /*
            |--------------------------------------------------------------------------
            | VALIDAR USERNAME
            |--------------------------------------------------------------------------
            */

            $stmt = $pdo->prepare("
                SELECT id
                FROM usuarios
                WHERE username = ?
            ");

            $stmt->execute([$username]);

            if ($stmt->fetch()) {

                $error = "El nombre de usuario ya existe.";

            } else {

                /*
                |--------------------------------------------------------------------------
                | VALIDAR EMAIL
                |--------------------------------------------------------------------------
                */

                if (!empty($email)) {

                    $stmt = $pdo->prepare("
                        SELECT id
                        FROM usuarios
                        WHERE email = ?
                    ");

                    $stmt->execute([$email]);

                    if ($stmt->fetch()) {

                        $error = "El correo electrónico ya está registrado.";
                    }
                }
            }

            /*
            |--------------------------------------------------------------------------
            | INSERTAR USUARIO
            |--------------------------------------------------------------------------
            */

            if (!$error) {

                $pdo->beginTransaction();

                $password_hash = password_hash($password, PASSWORD_DEFAULT);

                $stmt = $pdo->prepare("
                    INSERT INTO usuarios
                    (
                        nombres,
                        apellido_paterno,
                        apellido_materno,
                        username,
                        email,
                        password_hash,
                        rol,
                        numero_identificador,
                        fecha_creacion,
                        activo
                    )
                    VALUES
                    (
                        ?, ?, ?, ?, ?, ?, ?, ?, NOW(), 1
                    )
                ");

                $stmt->execute([

                    $nombres,
                    $apellido_paterno,
                    $apellido_materno,
                    $username,
                    $email,
                    $password_hash,
                    $rol,
                    $numero_identificador

                ]);

                $usuario_id = $pdo->lastInsertId();

                /*
                |--------------------------------------------------------------------------
                | DATOS EXTRA DOCENTE
                |--------------------------------------------------------------------------
                */

                if ($rol === 'docente') {

                    $rfc = trim($_POST['rfc'] ?? '');
                    $curp = trim($_POST['curp'] ?? '');
                    $grado_academico = trim($_POST['grado_academico'] ?? '');
                    $especialidad = trim($_POST['especialidad'] ?? '');
                    $fecha_contratacion = $_POST['fecha_contratacion'] ?? null;

                    $foto_path = null;

                    /*
                    |--------------------------------------------------------------------------
                    | SUBIR FOTO
                    |--------------------------------------------------------------------------
                    */

                    if (
                        isset($_FILES['foto']) &&
                        $_FILES['foto']['error'] === 0
                    ) {

                        $upload_dir = __DIR__ . '/../../uploads/docentes/';

                        if (!is_dir($upload_dir)) {

                            mkdir($upload_dir, 0777, true);
                        }

                        $extension = pathinfo(
                            $_FILES['foto']['name'],
                            PATHINFO_EXTENSION
                        );

                        $nombre_foto = uniqid('docente_') . '.' . $extension;

                        move_uploaded_file(
                            $_FILES['foto']['tmp_name'],
                            $upload_dir . $nombre_foto
                        );

                        $foto_path = 'uploads/docentes/' . $nombre_foto;
                    }

                    /*
                    |--------------------------------------------------------------------------
                    | INSERTAR DOCENTE
                    |--------------------------------------------------------------------------
                    */

                    $stmtDocente = $pdo->prepare("
                        INSERT INTO docentes
                        (
                            usuario_id,
                            rfc,
                            curp,
                            foto,
                            grado_academico,
                            especialidad,
                            fecha_contratacion
                        )
                        VALUES
                        (
                            ?, ?, ?, ?, ?, ?, ?
                        )
                    ");

                    $stmtDocente->execute([

                        $usuario_id,
                        $rfc,
                        $curp,
                        $foto_path,
                        $grado_academico,
                        $especialidad,
                        $fecha_contratacion

                    ]);
                }

                $pdo->commit();

                $mensaje = "Usuario creado correctamente.";

            }

        } catch (PDOException $e) {

            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            $error = "Error del sistema: " . $e->getMessage();
        }
    }
}

?>

<!DOCTYPE html>
<html lang="es">
<head>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Crear Usuario</title>

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

        <span class="bg-purple-600 text-white px-4 py-2 rounded-full text-[10px] font-black uppercase tracking-[0.25em]">
            Gestión de Usuarios
        </span>

        <h1 class="text-4xl font-black text-slate-900 mt-5 uppercase tracking-tight">

            Crear
            <span class="text-purple-600 italic">
                Usuario
            </span>

        </h1>

        <p class="text-slate-500 font-semibold mt-3">
            Registro de nuevos usuarios del sistema académico.
        </p>

    </div>

    <!-- ALERTAS -->

    <?php if($error): ?>

        <div class="bg-red-100 border border-red-200 text-red-700 px-6 py-5 rounded-2xl mb-6 font-bold">

            <?= htmlspecialchars($error) ?>

        </div>

    <?php endif; ?>

    <?php if($mensaje): ?>

        <div class="bg-emerald-100 border border-emerald-200 text-emerald-700 px-6 py-5 rounded-2xl mb-6 font-bold">

            <?= htmlspecialchars($mensaje) ?>

        </div>

    <?php endif; ?>

    <!-- FORMULARIO -->

    <form
        method="POST"
        enctype="multipart/form-data"
        class="bg-white rounded-[2rem] shadow-xl border border-slate-100 p-8 space-y-8"
    >

        <!-- DATOS GENERALES -->

        <div>

            <h2 class="text-xl font-black uppercase tracking-tight text-slate-900 mb-6">
                Información General
            </h2>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

                <!-- NOMBRES -->

                <div>

                    <label class="block text-[10px] font-black uppercase tracking-[0.25em] text-slate-400 mb-3">
                        Nombres *
                    </label>

                    <input
                        type="text"
                        name="nombres"
                        required
                        value="<?= htmlspecialchars($_POST['nombres'] ?? '') ?>"
                        class="w-full bg-slate-50 border-2 border-transparent focus:border-purple-600 rounded-2xl p-4 font-bold outline-none transition-all"
                    >

                </div>

                <!-- APELLIDO PATERNO -->

                <div>

                    <label class="block text-[10px] font-black uppercase tracking-[0.25em] text-slate-400 mb-3">
                        Apellido Paterno *
                    </label>

                    <input
                        type="text"
                        name="apellido_paterno"
                        required
                        value="<?= htmlspecialchars($_POST['apellido_paterno'] ?? '') ?>"
                        class="w-full bg-slate-50 border-2 border-transparent focus:border-purple-600 rounded-2xl p-4 font-bold outline-none transition-all"
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
                        value="<?= htmlspecialchars($_POST['apellido_materno'] ?? '') ?>"
                        class="w-full bg-slate-50 border-2 border-transparent focus:border-purple-600 rounded-2xl p-4 font-bold outline-none transition-all"
                    >

                </div>

                <!-- USERNAME -->

                <div>

                    <label class="block text-[10px] font-black uppercase tracking-[0.25em] text-slate-400 mb-3">
                        Usuario *
                    </label>

                    <input
                        type="text"
                        name="username"
                        required
                        value="<?= htmlspecialchars($_POST['username'] ?? '') ?>"
                        class="w-full bg-slate-50 border-2 border-transparent focus:border-purple-600 rounded-2xl p-4 font-bold outline-none transition-all"
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
                        value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                        class="w-full bg-slate-50 border-2 border-transparent focus:border-purple-600 rounded-2xl p-4 font-bold outline-none transition-all"
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
                        value="<?= htmlspecialchars($_POST['numero_identificador'] ?? '') ?>"
                        class="w-full bg-slate-50 border-2 border-transparent focus:border-purple-600 rounded-2xl p-4 font-bold outline-none transition-all"
                    >

                </div>

                <!-- ROL -->

                <div>

                    <label class="block text-[10px] font-black uppercase tracking-[0.25em] text-slate-400 mb-3">
                        Rol *
                    </label>

                    <select
                        name="rol"
                        id="rol"
                        required
                        onchange="toggleDocenteSection()"
                        class="w-full bg-slate-50 border-2 border-transparent focus:border-purple-600 rounded-2xl p-4 font-bold outline-none transition-all"
                    >

                        <option value="">
                            Seleccionar rol
                        </option>

                        <option value="administrador">Administrador</option>
                        <option value="coordinador_academico">Coordinador Académico</option>
                        <option value="control_escolar">Control Escolar</option>
                        <option value="docente">Docente</option>
                        <option value="alumno">Alumno</option>
                        <option value="padre_familia">Padre de Familia</option>

                    </select>

                </div>

                <!-- PASSWORD -->

                <div>

                    <label class="block text-[10px] font-black uppercase tracking-[0.25em] text-slate-400 mb-3">
                        Contraseña *
                    </label>

                    <input
                        type="password"
                        name="password"
                        required
                        class="w-full bg-slate-50 border-2 border-transparent focus:border-purple-600 rounded-2xl p-4 font-bold outline-none transition-all"
                    >

                </div>

            </div>

        </div>

        <!-- DATOS DOCENTE -->

        <div
            id="docente_section"
            style="display:none;"
            class="bg-purple-50 border border-purple-100 rounded-[2rem] p-8"
        >

            <h2 class="text-xl font-black uppercase tracking-tight text-purple-900 mb-6">
                Información del Docente
            </h2>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

                <div>

                    <label class="block text-[10px] font-black uppercase tracking-[0.25em] text-purple-500 mb-3">
                        RFC
                    </label>

                    <input
                        type="text"
                        name="rfc"
                        class="w-full bg-white border-2 border-transparent focus:border-purple-600 rounded-2xl p-4 font-bold outline-none transition-all"
                    >

                </div>

                <div>

                    <label class="block text-[10px] font-black uppercase tracking-[0.25em] text-purple-500 mb-3">
                        CURP
                    </label>

                    <input
                        type="text"
                        name="curp"
                        class="w-full bg-white border-2 border-transparent focus:border-purple-600 rounded-2xl p-4 font-bold outline-none transition-all"
                    >

                </div>

                <div>

                    <label class="block text-[10px] font-black uppercase tracking-[0.25em] text-purple-500 mb-3">
                        Grado Académico
                    </label>

                    <input
                        type="text"
                        name="grado_academico"
                        class="w-full bg-white border-2 border-transparent focus:border-purple-600 rounded-2xl p-4 font-bold outline-none transition-all"
                    >

                </div>

                <div>

                    <label class="block text-[10px] font-black uppercase tracking-[0.25em] text-purple-500 mb-3">
                        Especialidad
                    </label>

                    <input
                        type="text"
                        name="especialidad"
                        class="w-full bg-white border-2 border-transparent focus:border-purple-600 rounded-2xl p-4 font-bold outline-none transition-all"
                    >

                </div>

                <div>

                    <label class="block text-[10px] font-black uppercase tracking-[0.25em] text-purple-500 mb-3">
                        Fecha de Contratación
                    </label>

                    <input
                        type="date"
                        name="fecha_contratacion"
                        class="w-full bg-white border-2 border-transparent focus:border-purple-600 rounded-2xl p-4 font-bold outline-none transition-all"
                    >

                </div>

                <div>

                    <label class="block text-[10px] font-black uppercase tracking-[0.25em] text-purple-500 mb-3">
                        Fotografía
                    </label>

                    <input
                        type="file"
                        name="foto"
                        class="w-full bg-white border-2 border-transparent focus:border-purple-600 rounded-2xl p-4 font-bold outline-none transition-all"
                    >

                </div>

            </div>

        </div>

        <!-- BOTONES -->

        <div class="flex flex-col md:flex-row gap-4 pt-4">

            <button
                type="submit"
                class="flex-1 bg-purple-600 hover:bg-purple-700 text-white rounded-2xl px-6 py-5 font-black uppercase tracking-[0.2em] text-[11px] transition-all shadow-xl shadow-purple-200"
            >

                Crear Usuario

            </button>

            <a
                href="admin_dashboard.php?modulo=usuarios_lista"
                class="flex-1 bg-slate-200 hover:bg-slate-300 text-slate-700 rounded-2xl px-6 py-5 font-black uppercase tracking-[0.2em] text-[11px] text-center transition-all"
            >

                Cancelar

            </a>

        </div>

    </form>

</div>

<script>

function toggleDocenteSection(){

    const rol = document.getElementById('rol').value;
    const section = document.getElementById('docente_section');

    if(rol === 'docente'){

        section.style.display = 'block';

    }else{

        section.style.display = 'none';
    }
}

/*
|--------------------------------------------------------------------------
| EJECUTAR AL CARGAR
|--------------------------------------------------------------------------
*/

toggleDocenteSection();

</script>

</body>
</html>