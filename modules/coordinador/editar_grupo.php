<?php

require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/permisos.php';

validarPermiso('editar_grupos');

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

if($id <= 0){
    echo "<div class='p-6 bg-red-100 text-red-700 rounded-2xl font-bold'>Grupo no válido.</div>";
    exit;
}

$error = '';
$success = '';

/* =========================
   OBTENER GRUPO
========================= */

$stmt = $pdo->prepare("
    SELECT *
    FROM grupos
    WHERE id = ?
");

$stmt->execute([$id]);
$grupo = $stmt->fetch(PDO::FETCH_ASSOC);

if(!$grupo){
    echo "<div class='p-6 bg-red-100 text-red-700 rounded-2xl font-bold'>El grupo no existe.</div>";
    exit;
}

/* =========================
   OBTENER CARRERAS
========================= */

$carreras = $pdo->query("
    SELECT id, nombre
    FROM carreras
    ORDER BY nombre ASC
")->fetchAll(PDO::FETCH_ASSOC);

/* =========================
   OBTENER PERIODOS
========================= */

$periodos = $pdo->query("
    SELECT id, nombre, activo
    FROM periodos
    ORDER BY id DESC
")->fetchAll(PDO::FETCH_ASSOC);

/* =========================
   ACTUALIZAR GRUPO
========================= */

if($_SERVER['REQUEST_METHOD'] === 'POST'){

    $nombre = strtoupper(trim($_POST['nombre'] ?? ''));
    $periodo_id = isset($_POST['periodo_id']) ? (int) $_POST['periodo_id'] : 0;
    $carrera_id = isset($_POST['carrera_id']) ? (int) $_POST['carrera_id'] : 0;
    $grado = isset($_POST['grado']) ? (int) $_POST['grado'] : 0;

    if($nombre === '' || $periodo_id <= 0 || $carrera_id <= 0 || $grado <= 0){

        $error = "Todos los campos son obligatorios.";

    }else{

        try{

            /* VALIDAR CARRERA */

            $stmt = $pdo->prepare("
                SELECT id
                FROM carreras
                WHERE id = ?
            ");

            $stmt->execute([$carrera_id]);

            if(!$stmt->fetch()){
                $error = "La carrera seleccionada no existe.";
            }

            /* VALIDAR PERIODO */

            if(!$error){

                $stmt = $pdo->prepare("
                    SELECT id
                    FROM periodos
                    WHERE id = ?
                ");

                $stmt->execute([$periodo_id]);

                if(!$stmt->fetch()){
                    $error = "El periodo seleccionado no existe.";
                }
            }

            /* VALIDAR DUPLICADO */

            if(!$error){

                $stmt = $pdo->prepare("
                    SELECT id
                    FROM grupos
                    WHERE nombre = ?
                    AND periodo_id = ?
                    AND carrera_id = ?
                    AND grado = ?
                    AND id != ?
                ");

                $stmt->execute([
                    $nombre,
                    $periodo_id,
                    $carrera_id,
                    $grado,
                    $id
                ]);

                if($stmt->fetch()){
                    $error = "Ya existe un grupo con esos datos.";
                }
            }

            /* ACTUALIZAR */

            if(!$error){

                $stmt = $pdo->prepare("
                    UPDATE grupos
                    SET
                        nombre = ?,
                        periodo_id = ?,
                        carrera_id = ?,
                        grado = ?
                    WHERE id = ?
                ");

                $stmt->execute([
                    $nombre,
                    $periodo_id,
                    $carrera_id,
                    $grado,
                    $id
                ]);

                header("Location: ?modulo=lista_grupos&msg=actualizado");
                exit;
            }

        }catch(PDOException $e){

            $error = "Error del sistema: " . $e->getMessage();
        }
    }
}

?>

<div class="max-w-2xl mx-auto p-6 animate-fade-in">

    <div class="flex items-center justify-between mb-8 px-4">

        <div>
            <div class="flex items-center gap-2 mb-1">
                <span class="bg-purple-600 w-2 h-2 rounded-full shadow-[0_0_10px_rgba(147,51,234,0.5)]"></span>

                <span class="text-[10px] font-black text-slate-400 uppercase tracking-[0.2em]">
                    Gestión Escolar
                </span>
            </div>

            <h1 class="text-4xl font-black text-slate-900 tracking-tighter uppercase leading-none">
                Editar
                <span class="text-purple-600 italic text-3xl">
                    Grupo
                </span>
            </h1>
        </div>

        <div class="text-right">
            <span class="text-[9px] font-black text-slate-300 uppercase tracking-widest block">
                Registro Interno
            </span>

            <span class="text-lg font-mono font-bold text-slate-400">
                #GRP-<?= str_pad($grupo['id'], 3, '0', STR_PAD_LEFT) ?>
            </span>
        </div>

    </div>

    <?php if($error): ?>
        <div class="mb-6 p-5 bg-white border-l-4 border-red-500 rounded-2xl shadow-xl shadow-red-100/50 flex items-center gap-4">
            <span class="text-xs font-bold text-slate-700 uppercase tracking-tight">
                <?= htmlspecialchars($error) ?>
            </span>
        </div>
    <?php endif; ?>

    <?php if($success): ?>
        <div class="mb-6 p-5 bg-white border-l-4 border-emerald-500 rounded-2xl shadow-xl shadow-emerald-100/50 flex items-center gap-4">
            <span class="text-xs font-bold text-slate-700 uppercase tracking-tight">
                <?= htmlspecialchars($success) ?>
            </span>
        </div>
    <?php endif; ?>

    <div class="bg-white p-10 rounded-[2.5rem] shadow-2xl shadow-slate-200 border border-white relative overflow-hidden">

        <div class="absolute top-0 right-0 w-32 h-32 bg-slate-50 rounded-full -mr-16 -mt-16 opacity-50"></div>

        <form method="POST" class="relative z-10 space-y-8">

            <div>
                <label class="text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] ml-2 mb-3 block">
                    Nombre del grupo
                </label>

                <input
                    type="text"
                    name="nombre"
                    required
                    value="<?= htmlspecialchars($_POST['nombre'] ?? $grupo['nombre']) ?>"
                    placeholder="Ej. 1A"
                    class="w-full bg-slate-50 border-2 border-transparent focus:border-purple-600 focus:bg-white p-4 rounded-2xl text-sm font-bold text-slate-700 outline-none transition-all uppercase tracking-wider"
                >
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

                <div>
                    <label class="text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] ml-2 mb-3 block">
                        Periodo Escolar
                    </label>

                    <select
                        name="periodo_id"
                        required
                        class="w-full bg-slate-50 border-2 border-transparent focus:border-purple-600 focus:bg-white p-4 rounded-2xl text-sm font-bold text-slate-700 outline-none transition-all appearance-none cursor-pointer uppercase"
                    >
                        <option value="">
                            Seleccionar periodo...
                        </option>

                        <?php foreach($periodos as $p): ?>

                            <option
                                value="<?= $p['id'] ?>"
                                <?= (($_POST['periodo_id'] ?? $grupo['periodo_id']) == $p['id']) ? 'selected' : '' ?>
                            >
                                <?= htmlspecialchars($p['nombre']) ?>
                                <?= $p['activo'] ? ' - ACTIVO' : '' ?>
                            </option>

                        <?php endforeach; ?>

                    </select>
                </div>

                <div>
                    <label class="text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] ml-2 mb-3 block">
                        Carrera
                    </label>

                    <select
                        name="carrera_id"
                        required
                        class="w-full bg-slate-50 border-2 border-transparent focus:border-purple-600 focus:bg-white p-4 rounded-2xl text-sm font-bold text-slate-700 outline-none transition-all appearance-none cursor-pointer uppercase"
                    >
                        <option value="">
                            Seleccionar carrera...
                        </option>

                        <?php foreach($carreras as $c): ?>

                            <option
                                value="<?= $c['id'] ?>"
                                <?= (($_POST['carrera_id'] ?? $grupo['carrera_id']) == $c['id']) ? 'selected' : '' ?>
                            >
                                <?= htmlspecialchars($c['nombre']) ?>
                            </option>

                        <?php endforeach; ?>

                    </select>
                </div>

            </div>

            <div>
                <label class="text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] ml-2 mb-3 block">
                    Grado Académico
                </label>

                <select
                    name="grado"
                    required
                    class="w-full bg-slate-50 border-2 border-transparent focus:border-purple-600 focus:bg-white p-4 rounded-2xl text-sm font-bold text-slate-700 outline-none transition-all appearance-none cursor-pointer uppercase"
                >
                    <option value="">
                        Seleccionar grado...
                    </option>

                    <?php for($i = 1; $i <= 12; $i++): ?>

                        <option
                            value="<?= $i ?>"
                            <?= (($_POST['grado'] ?? $grupo['grado']) == $i) ? 'selected' : '' ?>
                        >
                            <?= $i ?>° Grado
                        </option>

                    <?php endfor; ?>

                </select>
            </div>

            <div class="bg-purple-50 border border-purple-100 rounded-3xl p-6">

                <div class="flex items-center gap-2 mb-3">
                    <div class="w-2 h-2 bg-purple-500 rounded-full"></div>

                    <span class="text-[10px] font-black uppercase tracking-widest text-purple-700 italic">
                        Organización Académica
                    </span>
                </div>

                <p class="text-xs text-slate-600 font-semibold leading-relaxed">
                    Este grupo estará vinculado a una carrera, un grado académico y un periodo escolar.
                    Estos datos serán usados para asignaciones de alumnos, docentes, materias y horarios.
                </p>

            </div>

            <div class="pt-4 flex flex-col md:flex-row gap-4">

                <button
                    type="submit"
                    class="flex-[2] bg-slate-900 text-white p-5 rounded-3xl font-black uppercase tracking-[0.2em] text-[10px] hover:bg-purple-600 hover:shadow-xl hover:shadow-purple-200 transition-all active:scale-[0.97]"
                >
                    Actualizar Grupo
                </button>

                <a
                    href="?modulo=lista_grupos"
                    class="flex-1 bg-white border-2 border-slate-100 text-slate-400 p-5 rounded-3xl font-black uppercase tracking-[0.2em] text-[10px] text-center hover:bg-slate-50 transition-all"
                >
                    Cancelar
                </a>

            </div>

        </form>

    </div>

    <p class="text-center mt-10 text-[10px] font-black text-slate-300 uppercase tracking-[0.5em]">
        Academic Management Core // CUFA
    </p>

</div>

<style>
@keyframes slideUp{
    from{
        opacity:0;
        transform:translateY(15px);
    }

    to{
        opacity:1;
        transform:translateY(0);
    }
}

.animate-fade-in{
    animation:slideUp .5s cubic-bezier(0.16,1,0.3,1);
}
</style>