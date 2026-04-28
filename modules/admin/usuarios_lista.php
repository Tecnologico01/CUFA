<?php
require_once __DIR__ . '/../../includes/db.php';

/* FILTROS */

$nombres = $_GET['nombres'] ?? '';
$rol = $_GET['rol'] ?? '';
$activo = $_GET['activo'] ?? '';

/* ROLES DINAMICOS */

$roles = $pdo->query("
SELECT DISTINCT rol
FROM usuarios
")->fetchAll();

/* CONSULTA USUARIOS */

$sql = "SELECT id,nombres,email,rol,activo
        FROM usuarios
        WHERE 1=1";

$params = [];

if($nombres){
$sql .= " AND nombres LIKE ?";
$params[] = "%$nombres%";
}

if($rol){
$sql .= " AND rol = ?";
$params[] = $rol;
}

if($activo !== ''){
$sql .= " AND activo = ?";
$params[] = $activo;
}

$sql .= " ORDER BY nombres";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);

$usuarios = $stmt->fetchAll();
?>

<h1 class="text-3xl font-bold mb-6">
Usuarios del Sistema
</h1>


<!-- FILTROS -->

<div class="bg-white p-6 rounded-xl shadow mb-6">

<form method="GET" action="admin_dashboard.php">

<input type="hidden" name="modulo" value="usuarios_lista">

<div class="grid grid-cols-4 gap-4">

<input
type="text"
name="nombres"
placeholder="Buscar por nombre"
value="<?= htmlspecialchars($nombres) ?>"
class="border p-2 rounded">

<select name="rol" class="border p-2 rounded">

<option value="">Todos los roles</option>

<?php foreach($roles as $r){ ?>

<option
value="<?= $r['rol'] ?>"
<?= $rol==$r['rol'] ? "selected":"" ?>>

<?= ucfirst($r['rol']) ?>

</option>

<?php } ?>

</select>

<select name="activo" class="border p-2 rounded">

<option value="">Estado</option>

<option value="1" <?= $activo==="1"?"selected":"" ?>>
Activo
</option>

<option value="0" <?= $activo==="0"?"selected":"" ?>>
Inactivo
</option>

</select>

<button
class="bg-purple-600 text-white rounded px-4 py-2">

Filtrar

</button>

</div>

</form>

</div>



<!-- TABLA USUARIOS -->

<div class="bg-white p-6 rounded-xl shadow">

<table class="w-full">

<thead>

<tr class="border-b">

<th class="p-2 text-left">Nombre</th>
<th class="p-2 text-left">Email</th>
<th class="p-2 text-left">Rol</th>
<th class="p-2 text-left">Estado</th>
<th class="p-2 text-left">Acciones</th>

</tr>

</thead>

<tbody>

<?php foreach($usuarios as $u){ ?>

<tr class="border-b hover:bg-gray-50">

<td class="p-2">
<?= htmlspecialchars($u['nombres']) ?>
</td>

<td class="p-2">
<?= htmlspecialchars($u['email']) ?>
</td>

<td class="p-2">
<?= ucfirst($u['rol']) ?>
</td>

<td class="p-2">

<?php if($u['activo']){ ?>

<span class="text-green-600 font-semibold">
Activo
</span>

<?php }else{ ?>

<span class="text-red-600 font-semibold">
Inactivo
</span>

<?php } ?>

</td>

<td class="p-2 space-x-2">

<a
href="?modulo=editar_usuario&id=<?= $u['id'] ?>"
class="bg-blue-500 text-white px-3 py-1 rounded">

Editar

</a>

<button
onclick='mostrarEliminar(<?= json_encode($u) ?>)'
class="bg-red-500 text-white px-3 py-1 rounded">

Eliminar

</button>

</td>

</tr>

<?php } ?>

</tbody>

</table>

</div>



<!-- MODAL ELIMINAR -->

<div
id="modalEliminar"
style="display:none"
class="fixed inset-0 bg-black bg-opacity-40 flex items-center justify-center">

<div class="bg-white p-6 rounded-xl w-96">

<h2 class="text-xl font-bold mb-4">
Eliminar Usuario
</h2>

<div id="datosUsuario"></div>

<div class="flex justify-end mt-6 space-x-3">

<button
onclick="cerrarModal()"
class="bg-gray-300 px-4 py-2 rounded">

Cancelar

</button>

<a
id="btnEliminar"
class="bg-red-600 text-white px-4 py-2 rounded">

Eliminar

</a>

</div>

</div>

</div>



<script>

function mostrarEliminar(usuario){

document.getElementById("modalEliminar").style.display="flex";

let estado = usuario.activo == 1 ? "Activo" : "Inactivo";

document.getElementById("datosUsuario").innerHTML=`

<p><b>Nombre:</b> ${usuario.nombres}</p>
<p><b>Email:</b> ${usuario.email}</p>
<p><b>Rol:</b> ${usuario.rol}</p>
<p><b>Estado:</b> ${estado}</p>

<p style="color:red;margin-top:10px">
Esta acción eliminará el usuario permanentemente
</p>

`;

document.getElementById("btnEliminar").href =
"/sistema_academico/modules/admin/eliminar_usuario.php?id="+usuario.id;

}

function cerrarModal(){
document.getElementById("modalEliminar").style.display="none";
}

</script>