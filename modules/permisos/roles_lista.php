<?php

require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/permisos.php';

validarPermiso('ver_roles');

$puedeCrear = tienePermiso('crear_roles');
$puedeEditar = tienePermiso('editar_roles');
$puedeEliminar = tienePermiso('eliminar_roles');
$puedeAsignar = tienePermiso('asignar_permisos');

$stmt = $pdo->query("
    SELECT
        r.id,
        r.nombre,
        r.slug,
        COUNT(rp.permiso_id) AS total_permisos
    FROM roles r
    LEFT JOIN rol_permisos rp
        ON rp.rol_id = r.id
    GROUP BY
        r.id,
        r.nombre,
        r.slug
    ORDER BY r.nombre ASC
");

$roles = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>

<div class="max-w-7xl mx-auto">

    <div class="flex flex-col md:flex-row md:items-center justify-between gap-6 mb-10">

        <div>
            <span class="bg-purple-100 text-purple-700 px-4 py-2 rounded-full text-[10px] font-black uppercase tracking-[0.25em]">
                Seguridad
            </span>

            <h1 class="text-4xl font-black text-slate-900 mt-5 uppercase tracking-tight">
                Roles del
                <span class="text-purple-600 italic">
                    Sistema
                </span>
            </h1>

            <p class="text-slate-500 font-semibold mt-3">
                Administra los perfiles de acceso y permisos del sistema académico.
            </p>
        </div>

        <?php if($puedeCrear): ?>
            <a
                href="?modulo=crear_rol"
                class="bg-purple-600 hover:bg-purple-700 text-white px-7 py-4 rounded-2xl font-black uppercase tracking-[0.2em] text-[11px] shadow-xl shadow-purple-200 transition-all"
            >
                + Crear Rol
            </a>
        <?php endif; ?>

    </div>

    <div class="bg-white rounded-[2rem] shadow-xl border border-slate-100 p-6 mb-8">

        <div class="relative">

            <div class="absolute inset-y-0 left-0 pl-5 flex items-center pointer-events-none text-slate-300">
                🔍
            </div>

            <input
                type="text"
                id="buscadorRoles"
                placeholder="Buscar por nombre o slug del rol..."
                class="w-full bg-slate-50 border-2 border-transparent focus:border-purple-600 rounded-2xl py-4 pl-14 pr-5 font-bold outline-none transition-all"
            >

        </div>

    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6" id="gridRoles">

        <?php if(count($roles) > 0): ?>

            <?php foreach($roles as $rol): ?>

                <?php
                    $esSistema = in_array($rol['slug'], [
                        'administrador',
                        'admin',
                        'coordinador',
                        'docente',
                        'alumno',
                        'control_escolar'
                    ]);
                ?>

                <div
                    class="rol-card bg-white rounded-[2rem] shadow-sm hover:shadow-xl border border-slate-100 p-7 transition-all hover:-translate-y-1"
                    data-text="<?= strtolower(htmlspecialchars($rol['nombre'] . ' ' . $rol['slug'])) ?>"
                >

                    <div class="flex items-start justify-between gap-4 mb-6">

                        <div class="w-14 h-14 bg-purple-100 text-purple-700 rounded-2xl flex items-center justify-center text-2xl">
                            🔐
                        </div>

                        <?php if($esSistema): ?>
                            <span class="bg-slate-100 text-slate-500 px-3 py-1 rounded-full text-[9px] font-black uppercase tracking-widest">
                                Sistema
                            </span>
                        <?php else: ?>
                            <span class="bg-emerald-100 text-emerald-600 px-3 py-1 rounded-full text-[9px] font-black uppercase tracking-widest">
                                Personalizado
                            </span>
                        <?php endif; ?>

                    </div>

                    <h2 class="text-2xl font-black text-slate-900 uppercase tracking-tight mb-2">
                        <?= htmlspecialchars($rol['nombre']) ?>
                    </h2>

                    <p class="text-sm font-bold text-slate-400 mb-6">
                        <?= htmlspecialchars($rol['slug']) ?>
                    </p>

                    <div class="bg-slate-50 rounded-2xl p-5 border border-slate-100 mb-6">

                        <p class="text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] mb-2">
                            Permisos asignados
                        </p>

                        <p class="text-3xl font-black text-purple-600">
                            <?= (int) $rol['total_permisos'] ?>
                        </p>

                    </div>

                    <div class="grid grid-cols-1 gap-3">

                        <?php if($puedeAsignar): ?>
                            <a
                                href="?modulo=asignar_permisos&rol_id=<?= (int) $rol['id'] ?>"
                                class="text-center bg-purple-600 hover:bg-purple-700 text-white py-3 rounded-2xl font-black uppercase tracking-widest text-[10px] transition-all"
                            >
                                Permisos
                            </a>
                        <?php endif; ?>

                        <div class="grid grid-cols-2 gap-3">

                            <?php if($puedeEditar): ?>
                                <a
                                    href="?modulo=editar_rol&id=<?= (int) $rol['id'] ?>"
                                    class="text-center bg-slate-900 hover:bg-slate-700 text-white py-3 rounded-2xl font-black uppercase tracking-widest text-[10px] transition-all"
                                >
                                    Editar
                                </a>
                            <?php endif; ?>

                            <?php if($puedeEliminar && !$esSistema): ?>
                                <a
                                    href="?modulo=eliminar_rol&id=<?= (int) $rol['id'] ?>"
                                    onclick="return confirm('¿Seguro que deseas eliminar este rol? Esta acción no se puede deshacer.')"
                                    class="text-center bg-red-500 hover:bg-red-600 text-white py-3 rounded-2xl font-black uppercase tracking-widest text-[10px] transition-all"
                                >
                                    Eliminar
                                </a>
                            <?php elseif($puedeEliminar && $esSistema): ?>
                                <button
                                    type="button"
                                    disabled
                                    class="bg-slate-100 text-slate-300 py-3 rounded-2xl font-black uppercase tracking-widest text-[10px] cursor-not-allowed"
                                >
                                    Bloqueado
                                </button>
                            <?php endif; ?>

                        </div>

                    </div>

                </div>

            <?php endforeach; ?>

        <?php else: ?>

            <div class="col-span-full bg-white rounded-[2rem] shadow-sm border border-slate-100 p-16 text-center">

                <div class="text-6xl mb-4">
                    🔐
                </div>

                <h2 class="text-2xl font-black text-slate-900 uppercase mb-3">
                    Sin roles registrados
                </h2>

                <p class="text-slate-500 font-semibold">
                    Aún no existen roles disponibles en el sistema.
                </p>

            </div>

        <?php endif; ?>

    </div>

    <div
        id="sinResultados"
        class="hidden bg-white rounded-[2rem] shadow-sm border border-slate-100 p-16 text-center mt-8"
    >
        <div class="text-6xl mb-4">
            🔎
        </div>

        <h2 class="text-2xl font-black text-slate-900 uppercase mb-3">
            Sin coincidencias
        </h2>

        <p class="text-slate-500 font-semibold">
            No se encontraron roles con ese criterio de búsqueda.
        </p>
    </div>

</div>

<script>
const buscadorRoles = document.getElementById('buscadorRoles');
const tarjetasRoles = document.querySelectorAll('.rol-card');
const sinResultados = document.getElementById('sinResultados');

if(buscadorRoles){

    buscadorRoles.addEventListener('keyup', function(){

        const filtro = this.value.toLowerCase().trim();
        let visibles = 0;

        tarjetasRoles.forEach(card => {

            const texto = card.dataset.text || '';

            if(texto.includes(filtro)){
                card.style.display = 'block';
                visibles++;
            }else{
                card.style.display = 'none';
            }

        });

        if(visibles === 0 && tarjetasRoles.length > 0){
            sinResultados.classList.remove('hidden');
        }else{
            sinResultados.classList.add('hidden');
        }

    });
}
</script>