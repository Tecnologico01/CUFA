<?php
session_start();

/* VERIFICAR SESIÓN */
if (!isset($_SESSION['user_id']) || $_SESSION['rol'] !== 'docente') {
    header("Location: ../login.php");
    exit;
}

$nombre = $_SESSION['nombres'] ?? 'Docente';
$modulo = $_GET['modulo'] ?? 'inicio';
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Docente - Sistema Académico</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Poppins', sans-serif; }
        
        /* Paleta Pastel Personalizada */
        .bg-main { background-color: #fdf2f8; } 
        .sidebar { background: linear-gradient(180deg, #f3e8ff 0%, #fce7f3 100%); } 
        
        .menu-item { 
            transition: all 0.3s ease; 
            color: #3730a3; 
            font-weight: 500;
            display: flex;
            align-items: center;
            padding: 0.75rem 1rem;
            border-radius: 0.75rem;
            margin-bottom: 0.25rem;
            cursor: pointer;
        }
        
        .menu-item:hover { 
            background-color: rgba(255, 255, 255, 0.6); 
            transform: translateX(4px);
        }

        .active-menu {
            background-color: white !important;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
            font-weight: 700;
            color: #4f46e5;
        }

        .submenu {
            margin-left: 1.5rem;
            border-left: 2px solid #c7d2fe;
            padding-left: 0.5rem;
        }
    </style>
    <script>
        function toggleMenu(id) {
            const menu = document.getElementById(id);
            menu.classList.toggle('hidden');
        }
    </script>
</head>

<body class="bg-main min-h-screen flex">

<aside class="sidebar w-72 p-6 shadow-2xl flex flex-col justify-between border-r border-indigo-100 sticky top-0 h-screen">
    <div>
        <div class="mb-10 px-2">
            <h2 class="text-2xl font-black text-indigo-900 tracking-tight">
                PANEL <span class="text-pink-500 underline decoration-indigo-300">DOCENTE</span>
            </h2>
            <p class="text-[10px] uppercase tracking-[0.2em] text-indigo-600 font-bold mt-1">Gestión Educativa</p>
        </div>

        <nav class="space-y-1">
            <a href="docente_dashboard.php?modulo=inicio" class="menu-item <?= $modulo == 'inicio' ? 'active-menu' : '' ?>">
                Inicio
            </a>

            <div>
                <button onclick="toggleMenu('materias-menu')" class="w-full menu-item justify-between">
                    <span>Mis Materias</span>
                    <span class="text-[10px]">▼</span>
                </button>
                <div id="materias-menu" class="submenu <?= in_array($modulo, ['mis_materias', 'ver_materia']) ? '' : 'hidden' ?>">
                    <a href="docente_dashboard.php?modulo=mis_materias" class="menu-item text-sm <?= $modulo == 'mis_materias' ? 'active-menu' : '' ?>">Ver Materias</a>
                </div>
            </div>

            </nav>
    </div>

    <div class="pt-4 border-t border-indigo-200">
        <a href="../logout.php" class="flex items-center p-3 text-red-500 font-bold hover:bg-red-50 rounded-xl transition">
            Cerrar Sesión
        </a>
    </div>
</aside>

<main class="flex-1 p-8 overflow-y-auto">
    <div class="flex justify-between items-center mb-8 bg-white p-6 rounded-3xl shadow-sm border border-indigo-50">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">Hola, <?= htmlspecialchars($nombre) ?> </h1>
            <p class="text-indigo-500 text-sm font-medium italic">Docente Titular</p>
        </div>
        <div class="bg-indigo-100 px-4 py-2 rounded-2xl text-indigo-700 text-sm font-bold">
            <?= date('d / m / Y') ?>
        </div>
    </div>

    <div class="bg-white rounded-3xl shadow-xl p-8 min-h-[70vh] border border-indigo-50 relative">
        <?php
        switch($modulo){
            /* MATERIAS */
            case 'mis_materias':
                include '../modules/docente/mis_materias.php';
                break;
            case 'ver_materia':
                include '../modules/docente/ver_materia.php';
                break;

            /* PLANEACIÓN */
            case 'planeacion_materia':
                include '../modules/docente/planeacion_materia.php';
                break;
            case 'planeacion_semanal':
                include '../modules/docente/planeacion_semanal.php';
                break;
            case 'crear_planeacion':
                include '../modules/docente/crear_planeacion.php';
                break;
            case 'guardar_planeacion':
                include '../modules/docente/guardar_planeacion.php';
                break;
            case 'editar_planeacion':
                include '../modules/docente/editar_planeacion.php';
                break;
            case 'actualizar_planeacion':
                include '../modules/docente/actualizar_planeacion.php';
                break;
            case 'definir_unidades':
                include '../modules/docente/definir_unidades.php';
                break;

            /* ACTIVIDADES */
            case 'mis_actividades':
                include '../modules/docente/mis_actividades.php';
                break;
            case 'crear_actividad':
                include '../modules/docente/crear_actividad.php';
                break;
            case 'semanas_actividades':
                include '../modules/docente/semanas_actividades.php';
                break;
            case 'subir_material':
                include '../modules/docente/subir_material.php';
                break;

            /* CALIFICACIONES */
            case 'calificaciones':
                include '../modules/docente/calificaciones.php';
                break;

            case 'inicio':
            default:
                ?>
                <div class="text-center py-20">
                    <h2 class="text-3xl font-black text-indigo-900 mb-2">Bienvenido, <?= htmlspecialchars($nombre) ?></h2>
                    <p class="text-gray-500 max-w-md mx-auto">Desde aquí puedes gestionar tus materias, crear planeaciones didácticas y registrar las actividades de tus alumnos.</p>
                    
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mt-12">
                        <div class="p-6 bg-indigo-50 rounded-2xl border border-indigo-100">
                            <span class="text-indigo-600 font-bold block mb-1">Rol</span>
                            <p class="text-gray-800 font-medium">Docente</p>
                        </div>
                        <div class="p-6 bg-pink-50 rounded-2xl border border-pink-100">
                            <span class="text-pink-600 font-bold block mb-1">ID Usuario</span>
                            <p class="text-gray-800 font-medium">#<?= $_SESSION['user_id'] ?></p>
                        </div>
                        <div class="p-6 bg-purple-50 rounded-2xl border border-purple-100">
                            <span class="text-purple-600 font-bold block mb-1">Estado</span>
                            <p class="text-gray-800 font-medium">Sesión Activa</p>
                        </div>
                    </div>
                </div>
                <?php
                break;
        }
        ?>
    </div>
</main>

</body>
</html>