<?php
require_once __DIR__ . '/../../includes/db.php';

$id = $_GET['id'] ?? null;

if(!$id){
echo "<p class='text-red-600'>Usuario no especificado</p>";
exit;
}

/* OBTENER USUARIO */

$stmt = $pdo->prepare("
SELECT id,nombre,email,rol,activo
FROM usuarios
WHERE id=?
");

$stmt->execute([$id]);

$usuario = $stmt->fetch(PDO::FETCH_ASSOC);

if(!$usuario){
echo "<p class='text-red-600'>Usuario no encontrado</p>";
exit;
}

/* OBTENER ROLES DINAMICOS */

$roles = $pdo->query("
SELECT DISTINCT rol
FROM usuarios
ORDER BY rol
")->fetchAll(PDO::FETCH_ASSOC);

?>

<h1 class="text-3xl font-bold mb-6">
Editar Usuario
</h1>

<div class="bg-white p-6 rounded-xl shadow max-w-xl">

<form method="POST" action="admin_dashboard.php?modulo=guardar_usuario">

<input type="hidden" name="id" value="<?= $usuario['id'] ?>">

<!-- NOMBRE -->

<div class="mb-4">

<label class="font-semibold">
Nombre
</label>

<input
type="text"
name="nombre"
required
value="<?= htmlspecialchars($usuario['nombre']) ?>"
class="w-full border p-2 rounded">

</div>


<!-- EMAIL -->

<div class="mb-4">

<label class="font-semibold">
Email
</label>

<input
type="email"
name="email"
required
value="<?= htmlspecialchars($usuario['email']) ?>"
class="w-full border p-2 rounded">

</div>


<!-- ROL -->

<div class="mb-4">

<label class="font-semibold">
Rol
</label>

<select
name="rol"
class="w-full border p-2 rounded">

<?php foreach($roles as $r){ ?>

<option
value="<?= $r['rol'] ?>"
<?= $usuario['rol'] == $r['rol'] ? "selected" : "" ?>>

<?= ucfirst($r['rol']) ?>

</option>

<?php } ?>

</select>

</div>


<!-- ESTADO -->

<div class="mb-4">

<label class="font-semibold">
Estado del Usuario
</label>

<select
name="activo"
class="w-full border p-2 rounded">

<option value="1" <?= $usuario['activo']==1 ? "selected" : "" ?>>
Activo
</option>

<option value="0" <?= $usuario['activo']==0 ? "selected" : "" ?>>
Inactivo
</option>

</select>

</div>


<!-- PASSWORD OPCIONAL -->

<div class="mb-4">

<label class="font-semibold">
Nueva contraseña (opcional)
</label>

<input
type="password"
name="password"
placeholder="Dejar vacío para no cambiar"
class="w-full border p-2 rounded">

</div>


<button
class="bg-purple-600 text-white px-6 py-2 rounded hover:bg-purple-700">

Guardar Cambios

</button>

</form>

</div>