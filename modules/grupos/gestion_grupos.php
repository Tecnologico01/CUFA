<?php

require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/permisos.php';

validarPermiso('crear_grupos');

$error = '';
$success = '';

/* =========================
   OBTENER CARRERAS
========================= */

$stmt = $pdo->query("
    SELECT id, nombre
    FROM carreras
    ORDER BY nombre ASC
");

$carreras = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* =========================
   OBTENER PERIODOS ACTIVOS
========================= */

$stmt = $pdo->query("
    SELECT id, nombre
    FROM periodos
    WHERE activo = 1
    ORDER BY id DESC
");

$periodos = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* =========================
   GUARDAR GRUPO
========================= */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $nombre = strtoupper(trim($_POST['nombre'] ?? ''));
    $periodo_id = isset($_POST['periodo_id']) ? (int) $_POST['periodo_id'] : 0;
    $carrera_id = isset($_POST['carrera_id']) ? (int) $_POST['carrera_id'] : 0;
    $grado = isset($_POST['grado']) ? (int) $_POST['grado'] : 0;

    if ($nombre === '' || $periodo_id <= 0 || $carrera_id <= 0 || $grado <= 0) {

        $error = "Todos los campos son obligatorios.";

    } else {

        try {

            /* VALIDAR PERIODO */
            $stmt = $pdo->prepare("
                SELECT id
                FROM periodos
                WHERE id = ?
                AND activo = 1
            ");
            $stmt->execute([$periodo_id]);

            if (!$stmt->fetch()) {
                $error = "El periodo seleccionado no existe o no está activo.";
            }

            /* VALIDAR CARRERA */
            if (!$error) {
                $stmt = $pdo->prepare("
                    SELECT id
                    FROM carreras
                    WHERE id = ?
                ");
                $stmt->execute([$carrera_id]);

                if (!$stmt->fetch()) {
                    $error = "La carrera seleccionada no existe.";
                }
            }

            /* VALIDAR DUPLICADO */
            if (!$error) {

                $stmt = $pdo->prepare("
                    SELECT id
                    FROM grupos
                    WHERE nombre = ?
                    AND periodo_id = ?
                    AND carrera_id = ?
                    AND grado = ?
                ");

                $stmt->execute([
                    $nombre,
                    $periodo_id,
                    $carrera_id,
                    $grado
                ]);

                if ($stmt->fetch()) {
                    $error = "El grupo ya existe para esa carrera, grado y periodo.";
                }
            }

            /* INSERTAR */
            if (!$error) {

                $stmt = $pdo->prepare("
                    INSERT INTO grupos
                    (
                        nombre,
                        periodo_id,
                        carrera_id,
                        grado
                    )
                    VALUES
                    (
                        ?, ?, ?, ?
                    )
                ");

                $stmt->execute([
                    $nombre,
                    $periodo_id,
                    $carrera_id,
                    $grado
                ]);

                $success = "Grupo creado correctamente.";
            }

        } catch (PDOException $e) {

            $error = "Error del sistema: " . $e->getMessage();
        }
    }
}

?>

<div class="max-w-2xl mx-auto p-6 animate-fade-in font-sans">

    <div class="mb-10 flex justify-between items-end">

        <div>

            <div class="flex items-center gap-2 mb-2">

                <span class="bg-purple-600 text-white text-[10px] font-black px-2 py-0.5 rounded-full uppercase tracking-tighter">
                    Grupos
                </span>

                <span class="text-slate-300 text-[10px] font-bold">/</span>

                <span class="text-slate-500 text-[10px] font-black uppercase tracking-widest">
                    Gestión Académica
                </span>

            </div>

            <h1 class="text-4xl font-black text-slate-900 tracking-tighter uppercase leading-none">
                Apertura de
                <span class="text-purple-600 italic">Grupos</span>
            </h1>

        </div>

    </div>

    <div class="bg-white rounded-[2.5rem] shadow-2xl shadow-slate-200/60 border border-slate-100 overflow-hidden">

        <div class="p-10">

            <?php if (!empty($error)): ?>

                <div class="mb-6 flex items-center gap-3 bg-red-50 border border-red-100 text-red-600 p-4 rounded-2xl">
                    <span class="text-xs font-black uppercase tracking-tight">
                        <?= htmlspecialchars($error) ?>
                    </span>
                </div>

            <?php endif; ?>

            <?php if (!empty($success)): ?>

                <div class="mb-6 flex items-center gap-3 bg-emerald-50 border border-emerald-100 text-emerald-600 p-4 rounded-2xl">
                    <span class="text-xs font-black uppercase tracking-tight">
                        <?= htmlspecialchars($success) ?>
                    </span>
                </div>

            <?php endif; ?>

            <form method="POST" class="space-y-6">

                <div>

                    <label class="text-[10px] font-black text-slate-400 uppercase ml-2 tracking-[0.2em] mb-2 block">
                        Nombre del grupo
                    </label>

                    <input
                        type="text"
                        name="nombre"
                        placeholder="Ej: 1A"
                        required
                        value="<?= htmlspecialchars($_POST['nombre'] ?? '') ?>"
                        class="w-full p-4 bg-slate-50 border-2 border-slate-50 rounded-2xl text-sm font-bold text-slate-700 focus:bg-white focus:border-purple-600 focus:outline-none transition-all uppercase"
                    >

                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

                    <div>

                        <label class="text-[10px] font-black text-slate-400 uppercase ml-2 tracking-[0.2em] mb-2 block">
                            Periodo Activo
                        </label>

                        <select
                            name="periodo_id"
                            required
                            class="w-full p-4 bg-slate-50 border-2 border-slate-50 rounded-2xl text-sm font-bold text-slate-700 focus:bg-white focus:border-purple-600 focus:outline-none transition-all cursor-pointer appearance-none"
                        >

                            <option value="">Seleccionar...</option>

                            <?php foreach ($periodos as $p): ?>

                                <option
                                    value="<?= $p['id'] ?>"
                                    <?= (($_POST['periodo_id'] ?? '') == $p['id']) ? 'selected' : '' ?>
                                >
                                    <?= htmlspecialchars($p['nombre']) ?>
                                </option>

                            <?php endforeach; ?>

                        </select>

                    </div>

                    <div>

                        <label class="text-[10px] font-black text-slate-400 uppercase ml-2 tracking-[0.2em] mb-2 block">
                            Carrera
                        </label>

                        <select
                            name="carrera_id"
                            required
                            class="w-full p-4 bg-slate-50 border-2 border-slate-50 rounded-2xl text-sm font-bold text-slate-700 focus:bg-white focus:border-purple-600 focus:outline-none transition-all cursor-pointer appearance-none"
                        >

                            <option value="">Seleccionar...</option>

                            <?php foreach ($carreras as $c): ?>

                                <option
                                    value="<?= $c['id'] ?>"
                                    <?= (($_POST['carrera_id'] ?? '') == $c['id']) ? 'selected' : '' ?>
                                >
                                    <?= htmlspecialchars($c['nombre']) ?>
                                </option>

                            <?php endforeach; ?>

                        </select>

                    </div>

                </div>

                <div>

                    <label class="text-[10px] font-black text-slate-400 uppercase ml-2 tracking-[0.2em] mb-2 block">
                        Grado Académico
                    </label>

                    <select
                        name="grado"
                        required
                        class="w-full p-4 bg-slate-50 border-2 border-slate-50 rounded-2xl text-sm font-bold text-slate-700 focus:bg-white focus:border-purple-600 focus:outline-none transition-all cursor-pointer appearance-none"
                    >

                        <option value="">Seleccionar grado...</option>

                        <?php for($i = 1; $i <= 12; $i++): ?>

                            <option
                                value="<?= $i ?>"
                                <?= (($_POST['grado'] ?? '') == $i) ? 'selected' : '' ?>
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
                        El grupo quedará vinculado a una carrera, grado y periodo activo.
                        Esto permitirá controlar asignaciones de alumnos, docentes, materias y horarios.
                    </p>

                </div>

                <button
                    type="submit"
                    class="w-full bg-slate-900 text-white p-5 rounded-3xl font-black uppercase tracking-[0.2em] text-xs hover:bg-purple-600 hover:shadow-xl transition-all active:scale-[0.98] mt-4"
                >
                    Crear Nuevo Grupo
                </button>

            </form>

        </div>

    </div>

</div>

<style>
@keyframes slide-up {
    from {
        opacity: 0;
        transform: translateY(20px);
    }

    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.animate-fade-in {
    animation: slide-up 0.6s cubic-bezier(0.16, 1, 0.3, 1);
}
</style>