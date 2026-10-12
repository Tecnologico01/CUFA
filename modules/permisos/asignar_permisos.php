<?php

require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/permisos.php';

validarPermiso('asignar_permisos');

$rol_id = $_GET['rol_id'] ?? null;

if(!$rol_id){
    die("Rol no especificado.");
}

/* =========================
   OBTENER ROL
========================= */

$stmt = $pdo->prepare("
    SELECT *
    FROM roles
    WHERE id=?
");

$stmt->execute([$rol_id]);

$rol = $stmt->fetch(PDO::FETCH_ASSOC);

if(!$rol){
    die("Rol no encontrado.");
}

/* =========================
   GUARDAR
========================= */

if($_SERVER['REQUEST_METHOD'] === 'POST'){

    $permisos = $_POST['permisos'] ?? [];

    try{

        $pdo->beginTransaction();

        /* ELIMINAR ANTERIORES */

        $stmt = $pdo->prepare("
            DELETE FROM rol_permisos
            WHERE rol_id=?
        ");

        $stmt->execute([$rol_id]);

        /* INSERTAR NUEVOS */

        if(!empty($permisos)){

            $stmtInsert = $pdo->prepare("
                INSERT INTO rol_permisos
                (rol_id, permiso_id)
                VALUES (?,?)
            ");

            foreach($permisos as $permiso_id){

                $stmtInsert->execute([
                    $rol_id,
                    $permiso_id
                ]);
            }
        }

        $pdo->commit();

        echo "
        <script>
            window.location.href='?modulo=asignar_permisos&rol_id=".$rol_id."&success=1';
        </script>
        ";
        exit;

    }catch(Exception $e){

        if($pdo->inTransaction()){
            $pdo->rollBack();
        }

        echo "
        <script>
            alert('Error al guardar permisos');
            window.location.href='?modulo=asignar_permisos&rol_id=".$rol_id."';
        </script>
        ";
        exit;
    }
}

/* =========================
   PERMISOS
========================= */

$stmt = $pdo->query("
    SELECT *
    FROM permisos
    ORDER BY modulo ASC, nombre ASC
");

$permisos = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* =========================
   PERMISOS ACTUALES
========================= */

$stmt = $pdo->prepare("
    SELECT permiso_id
    FROM rol_permisos
    WHERE rol_id=?
");

$stmt->execute([$rol_id]);

$actuales = $stmt->fetchAll(PDO::FETCH_COLUMN);

$agrupados = [];

foreach($permisos as $permiso){

    $agrupados[$permiso['modulo']][] = $permiso;
}

?>

<div class="max-w-7xl mx-auto">

    <!-- HEADER -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-6 mb-10">

        <div>

            <span class="bg-purple-100 text-purple-700 px-4 py-2 rounded-full text-[10px] font-black uppercase tracking-[0.25em]">
                Seguridad
            </span>

            <h1 class="text-4xl font-black text-slate-900 mt-5 uppercase tracking-tight">
                Asignar
                <span class="text-purple-600 italic">
                    Permisos
                </span>
            </h1>

            <p class="text-slate-500 font-semibold mt-3">
                Configuración de accesos para el rol:
            </p>

            <div class="mt-4 inline-flex items-center gap-3 bg-white border border-purple-100 px-5 py-3 rounded-2xl shadow-sm">

                <div class="w-12 h-12 rounded-xl bg-purple-100 flex items-center justify-center text-xl">
                    🔐
                </div>

                <div>

                    <p class="text-[10px] font-black uppercase tracking-[0.2em] text-slate-400">
                        Rol seleccionado
                    </p>

                    <h2 class="text-xl font-black text-purple-700 uppercase">
                        <?= htmlspecialchars($rol['nombre']) ?>
                    </h2>

                </div>

            </div>

        </div>

        <a
            href="?modulo=roles_lista"
            class="bg-slate-900 hover:bg-slate-700 text-white px-6 py-4 rounded-2xl font-black uppercase tracking-[0.2em] text-[10px] transition-all"
        >
            ← Volver
        </a>

    </div>

    <?php if(isset($_GET['success'])): ?>

        <div class="bg-emerald-50 border border-emerald-200 text-emerald-700 rounded-3xl p-6 mb-8">

            <div class="flex items-center gap-4">

                <div class="w-14 h-14 rounded-2xl bg-emerald-100 flex items-center justify-center text-2xl">
                    ✓
                </div>

                <div>

                    <h3 class="font-black uppercase tracking-wide mb-1">
                        Permisos actualizados
                    </h3>

                    <p class="font-semibold">
                        Los permisos del rol fueron guardados correctamente.
                    </p>

                </div>

            </div>

        </div>

    <?php endif; ?>

    <form method="POST">

        <div class="space-y-8">

            <?php foreach($agrupados as $modulo => $lista): ?>

                <div class="bg-white rounded-[2.5rem] border border-slate-100 shadow-sm overflow-hidden">

                    <!-- HEADER -->
                    <div class="bg-gradient-to-r from-slate-50 to-white border-b border-slate-100 px-8 py-6 flex items-center justify-between">

                        <div class="flex items-center gap-4">

                            <div class="w-14 h-14 bg-purple-100 text-purple-700 rounded-2xl flex items-center justify-center text-2xl">
                                📁
                            </div>

                            <div>

                                <h2 class="text-2xl font-black uppercase tracking-tight text-slate-900">
                                    <?= htmlspecialchars($modulo) ?>
                                </h2>

                                <p class="text-slate-400 text-xs font-bold uppercase tracking-widest">
                                    Módulo del sistema
                                </p>

                            </div>

                        </div>

                        <button
                            type="button"
                            onclick="toggleModulo('mod_<?= $modulo ?>')"
                            class="bg-slate-100 hover:bg-purple-100 text-slate-500 hover:text-purple-700 px-5 py-3 rounded-2xl text-[10px] font-black uppercase tracking-widest transition-all"
                        >
                            Mostrar / Ocultar
                        </button>

                    </div>

                    <!-- BODY -->
                    <div
                        id="mod_<?= $modulo ?>"
                        class="p-8 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5"
                    >

                        <?php foreach($lista as $permiso): ?>

                            <label class="group cursor-pointer">

                                <div class="border-2 border-slate-100 hover:border-purple-300 rounded-2xl p-5 transition-all bg-slate-50 hover:bg-purple-50">

                                    <div class="flex items-start gap-4">

                                        <input
                                            type="checkbox"
                                            name="permisos[]"
                                            value="<?= $permiso['id'] ?>"
                                            <?= in_array($permiso['id'], $actuales) ? 'checked' : '' ?>
                                            class="mt-1 w-5 h-5 accent-purple-600"
                                        >

                                        <div>

                                            <h3 class="font-black text-slate-800 uppercase text-sm tracking-tight">
                                                <?= htmlspecialchars($permiso['nombre']) ?>
                                            </h3>

                                            <p class="text-xs text-slate-400 font-semibold mt-1">
                                                <?= htmlspecialchars($permiso['slug']) ?>
                                            </p>

                                        </div>

                                    </div>

                                </div>

                            </label>

                        <?php endforeach; ?>

                    </div>

                </div>

            <?php endforeach; ?>

        </div>

        <!-- BOTONES -->
        <div class="mt-12 flex justify-end gap-5">

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
                Guardar permisos
            </button>

        </div>

    </form>

</div>

<script>

function toggleModulo(id){

    const el = document.getElementById(id);

    if(el.style.display === 'none'){
        el.style.display = 'grid';
    }else{
        el.style.display = 'none';
    }
}

</script>