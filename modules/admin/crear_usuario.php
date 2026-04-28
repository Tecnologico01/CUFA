<?php

require_once __DIR__ . '/../../includes/db.php';

$error = '';
$mensaje = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $nombre = trim($_POST['nombre'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $rol = $_POST['rol'] ?? '';
    $numero_identificador = trim($_POST['numero_identificador'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($nombre) || empty($username) || empty($password) || empty($rol)) {
        $error = "Faltan campos obligatorios.";
    } else {

        try {

            $pdo->beginTransaction();

            $password_hash = password_hash($password, PASSWORD_DEFAULT);

            $stmt = $pdo->prepare("
                INSERT INTO usuarios
                (nombre, username, email, password_hash, rol, numero_identificador)
                VALUES (?,?,?,?,?,?)
            ");

            $stmt->execute([
                $nombre,
                $username,
                $email,
                $password_hash,
                $rol,
                $numero_identificador
            ]);

            $usuario_id = $pdo->lastInsertId();

            // SI ES DOCENTE GUARDAMOS DATOS EXTRA
            if ($rol === 'docente') {

                $rfc = $_POST['rfc'] ?? '';
                $curp = $_POST['curp'] ?? '';
                $grado = $_POST['grado_academico'] ?? '';
                $especialidad = $_POST['especialidad'] ?? '';
                $fecha_contratacion = $_POST['fecha_contratacion'] ?? '';

                $foto_path = '';

                if (isset($_FILES['foto']) && $_FILES['foto']['error'] === 0) {

                    $upload_dir = __DIR__ . '/../../uploads/';

                    if (!is_dir($upload_dir)) {
                        mkdir($upload_dir,0777,true);
                    }

                    $nombre_foto = time() . "_" . basename($_FILES['foto']['name']);

                    move_uploaded_file(
                        $_FILES['foto']['tmp_name'],
                        $upload_dir . $nombre_foto
                    );

                    $foto_path = 'uploads/' . $nombre_foto;
                }

                $stmt_doc = $pdo->prepare("
                    INSERT INTO docentes
                    (usuario_id,rfc,curp,foto,grado_academico,especialidad,fecha_contratacion)
                    VALUES (?,?,?,?,?,?,?)
                ");

                $stmt_doc->execute([
                    $usuario_id,
                    $rfc,
                    $curp,
                    $foto_path,
                    $grado,
                    $especialidad,
                    $fecha_contratacion
                ]);

            }

            $pdo->commit();

            $mensaje = "Usuario creado correctamente";

        } catch(PDOException $e){

            $pdo->rollBack();

            $error = "Error: " . $e->getMessage();

        }

    }

}
?>

<h1 class="text-2xl font-bold mb-8">Crear Nuevo Usuario</h1>

<?php if ($error): ?>
<div class="bg-red-100 text-red-700 p-4 rounded-xl mb-6"><?= $error ?></div>
<?php endif; ?>

<?php if ($mensaje): ?>
<div class="bg-green-100 text-green-700 p-4 rounded-xl mb-6"><?= $mensaje ?></div>
<?php endif; ?>

<form method="POST" enctype="multipart/form-data" class="space-y-6">

<div>
<label class="block text-gray-700 mb-2">Nombre completo</label>
<input type="text" name="nombre" required class="w-full p-4 bg-gray-50 border border-gray-300 rounded-xl">
</div>

<div>
<label class="block text-gray-700 mb-2">Nombre de usuario</label>
<input type="text" name="username" required class="w-full p-4 bg-gray-50 border border-gray-300 rounded-xl">
</div>

<div>
<label class="block text-gray-700 mb-2">Correo</label>
<input type="email" name="email" class="w-full p-4 bg-gray-50 border border-gray-300 rounded-xl">
</div>

<div>
<label class="block text-gray-700 mb-2">Número identificador</label>
<input type="text" name="numero_identificador" class="w-full p-4 bg-gray-50 border border-gray-300 rounded-xl">
</div>

<div>
<label class="block text-gray-700 mb-2">Rol</label>
<select name="rol" id="rol" onchange="toggleDocenteSection()" class="w-full p-4 bg-gray-50 border border-gray-300 rounded-xl">

<option value="">Seleccione</option>
<option value="administrador">Administrador</option>
<option value="coordinador_academico">Coordinador Académico</option>
<option value="control_escolar">Control Escolar</option>
<option value="docente">Docente</option>
<option value="alumno">Alumno</option>
<option value="padre_familia">Padre de Familia</option>

</select>
</div>

<div>
<label class="block text-gray-700 mb-2">Contraseña</label>
<input type="password" name="password" required class="w-full p-4 bg-gray-50 border border-gray-300 rounded-xl">
</div>

<!-- DATOS DOCENTE -->

<div id="docente_section" style="display:none;" class="bg-purple-50 p-6 rounded-xl space-y-4">

<h3 class="text-lg font-bold">Datos del Docente</h3>

<div>
<label class="block text-gray-700 mb-2">RFC</label>
<input type="text" name="rfc" class="w-full p-4 bg-gray-50 border border-gray-300 rounded-xl">
</div>

<div>
<label class="block text-gray-700 mb-2">CURP</label>
<input type="text" name="curp" class="w-full p-4 bg-gray-50 border border-gray-300 rounded-xl">
</div>

<div>
<label class="block text-gray-700 mb-2">Grado Académico</label>
<input type="text" name="grado_academico" class="w-full p-4 bg-gray-50 border border-gray-300 rounded-xl">
</div>

<div>
<label class="block text-gray-700 mb-2">Especialidad</label>
<input type="text" name="especialidad" class="w-full p-4 bg-gray-50 border border-gray-300 rounded-xl">
</div>

<div>
<label class="block text-gray-700 mb-2">Fecha de contratación</label>
<input type="date" name="fecha_contratacion" class="w-full p-4 bg-gray-50 border border-gray-300 rounded-xl">
</div>

<div>
<label class="block text-gray-700 mb-2">Foto</label>
<input type="file" name="foto" class="w-full p-4 bg-gray-50 border border-gray-300 rounded-xl">
</div>

</div>

<button type="submit"
class="w-full bg-blue-600 text-white py-4 rounded-xl font-bold text-lg hover:bg-blue-700">
Crear Usuario
</button>

</form>

<script>

function toggleDocenteSection(){

var rol = document.getElementById('rol').value;
var seccion = document.getElementById('docente_section');

if(rol === 'docente'){
seccion.style.display = 'block';
}else{
seccion.style.display = 'none';
}

}

</script>