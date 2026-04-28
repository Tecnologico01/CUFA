<?php
session_start();

// Verificación de seguridad para Administrador
if (!isset($_SESSION['user_id']) || $_SESSION['rol'] !== 'administrador') {
    header("Location: ../login.php");
    exit;
}

$nombre = $_SESSION['nombres'] ?? 'Usuario';
$modulo = $_GET['modulo'] ?? 'inicio';
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Administrador - Sistema Académico</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Poppins', sans-serif; }
        
        /* Paleta Pastel Personalizada */
        .bg-main { background-color: #fdf2f8; } 
        .sidebar { background: linear-gradient(180deg, #f3e8ff 0%, #fce7f3 100%); } 
        
        .menu-item { 
            transition: all 0.3s ease; 
            color: #5b21b6; 
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
            color: #7c3aed;
        }

        .submenu {
            margin-left: 1.5rem;
            border-left: 2px solid #ddd6fe;
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

<aside class="sidebar w-72 p-6 shadow-2xl flex flex-col justify-between border-r border-purple-100 sticky top-0 h-screen">
    <div>
        <div class="mb-10 px-2">
            <h2 class="text-2xl font-black text-purple-900 tracking-tight">
                PANEL <span class="text-pink-500 underline decoration-purple-300">CONTROL</span>
            </h2>
            <p class="text-[10px] uppercase tracking-[0.2em] text-purple-600 font-bold mt-1">Administración Global</p>
        </div>

        <nav class="space-y-1">
            <a href="?modulo=inicio" class="menu-item <?= $modulo == 'inicio' ? 'active-menu' : '' ?>">
                Inicio
            </a>

            <div>
                <button onclick="toggleMenu('asignaturas-menu')" class="w-full menu-item justify-between">
                    <span>Asignaturas</span>
                    <span class="text-[10px]">▼</span>
                </button>
                <div id="asignaturas-menu" class="submenu <?= in_array($modulo, ['lista_asignaturas', 'crear_materia', 'editar_materia']) ? '' : 'hidden' ?>">
                    <a href="?modulo=lista_asignaturas" class="menu-item text-sm <?= $modulo == 'lista_asignaturas' ? 'active-menu' : '' ?>">
                        Ver Asignaturas
                    </a>
                    <a href="?modulo=crear_materia" class="menu-item text-sm <?= $modulo == 'crear_materia' ? 'active-menu' : '' ?>">
                        Crear Asignatura
                    </a>
                </div>
            </div>

            <div>
                <button onclick="toggleMenu('subas-menu')" class="w-full menu-item justify-between">
                    <span>Subasignaturas</span>
                    <span class="text-[10px]">▼</span>
                </button>
                <div id="subas-menu" class="submenu <?= in_array($modulo, ['lista_subasignaturas', 'crear_subasignatura', 'editar_subasignatura']) ? '' : 'hidden' ?>">
                    <a href="?modulo=lista_subasignaturas" class="menu-item text-sm <?= $modulo == 'lista_subasignaturas' ? 'active-menu' : '' ?>">
                        Ver Subasignaturas
                    </a>
                    <a href="?modulo=crear_subasignatura" class="menu-item text-sm <?= $modulo == 'crear_subasignatura' ? 'active-menu' : '' ?>">
                        Crear Subasignatura
                    </a>
                </div>
            </div>

            <div>
                <button onclick="toggleMenu('periodos-menu')" class="w-full menu-item justify-between">
                    <span>Periodos Escolares</span>
                    <span class="text-[10px]">▼</span>
                </button>
                <div id="periodos-menu" class="submenu <?= in_array($modulo, ['periodos_lista', 'crear_periodo']) ? '' : 'hidden' ?>">
                    <a href="?modulo=periodos_lista" class="menu-item text-sm <?= $modulo == 'periodos_lista' ? 'active-menu' : '' ?>">Ver Periodos</a>
                    <a href="?modulo=crear_periodo" class="menu-item text-sm <?= $modulo == 'crear_periodo' ? 'active-menu' : '' ?>">Crear Periodo</a>
                </div>
            </div>

            <div>
                <button onclick="toggleMenu('parciales-menu')" class="w-full menu-item justify-between">
                    <span>Parciales</span>
                    <span class="text-[10px]">▼</span>
                </button>
                <div id="parciales-menu" class="submenu <?= in_array($modulo, ['parciales_activos', 'generar_parciales', 'parciales_anteriores']) ? '' : 'hidden' ?>">
                    <a href="?modulo=parciales_activos" class="menu-item text-sm <?= $modulo == 'parciales_activos' ? 'active-menu' : '' ?>">Parciales Activos</a>
                    <a href="?modulo=generar_parciales" class="menu-item text-sm <?= $modulo == 'generar_parciales' ? 'active-menu' : '' ?>">Nuevo Parcial</a>
                    <a href="?modulo=parciales_anteriores" class="menu-item text-sm <?= $modulo == 'parciales_anteriores' ? 'active-menu' : '' ?>">Historial</a>
                </div>
            </div>

            <div>
                <button onclick="toggleMenu('usuarios-menu')" class="w-full menu-item justify-between">
                    <span>Usuarios</span>
                    <span class="text-[10px]">▼</span>
                </button>
                <div id="usuarios-menu" class="submenu <?= in_array($modulo, ['usuarios_lista', 'crear_usuario', 'editar_usuario']) ? '' : 'hidden' ?>">
                    <a href="?modulo=usuarios_lista" class="menu-item text-sm <?= $modulo == 'usuarios_lista' ? 'active-menu' : '' ?>">Ver Usuarios</a>
                    <a href="?modulo=crear_usuario" class="menu-item text-sm <?= $modulo == 'crear_usuario' ? 'active-menu' : '' ?>">Crear Usuario</a>
                </div>
            </div>

            <div>
                <button onclick="toggleMenu('malla-menu')" class="w-full menu-item justify-between">
                    <span>Malla Curricular</span>
                    <span class="text-[10px]">▼</span>
                </button>
                <div id="malla-menu" class="submenu <?= in_array($modulo, ['ver_mallas', 'crear_malla', 'editar_malla']) ? '' : 'hidden' ?>">
                    <a href="?modulo=ver_mallas" class="menu-item text-sm <?= $modulo == 'ver_mallas' ? 'active-menu' : '' ?>">Ver Mallas</a>
                    <a href="?modulo=crear_malla" class="menu-item text-sm <?= $modulo == 'crear_malla' ? 'active-menu' : '' ?>">Crear Malla</a>
                </div>
            </div>
        </nav>
    </div>

    <div class="pt-4 border-t border-purple-200">
        <a href="../logout.php" class="flex items-center p-3 text-red-500 font-bold hover:bg-red-50 rounded-xl transition">
            Cerrar Sesión
        </a>
    </div>
</aside>

<main class="flex-1 p-8 overflow-y-auto">
    <div class="flex justify-between items-center mb-8 bg-white p-6 rounded-3xl shadow-sm border border-purple-50">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">Hola, <?= htmlspecialchars($nombre) ?> </h1>
            <p class="text-pink-500 text-sm font-medium italic">Panel de Administración</p>
        </div>
        <div class="bg-purple-100 px-4 py-2 rounded-2xl text-purple-700 text-sm font-bold">
            <?= date('d / m / Y') ?>
        </div>
    </div>

    <div class="bg-white rounded-3xl shadow-xl p-8 min-h-[70vh] border border-purple-50 relative">
        <?php
        switch($modulo){
            case 'crear_usuario':
                include '../modules/admin/crear_usuario.php';
                break;
            case 'usuarios_lista':
                include '../modules/admin/usuarios_lista.php';
                break;
            case 'editar_usuario':
                include '../modules/admin/editar_usuario.php';
                break;
            case 'guardar_usuario':
                include '../modules/admin/guardar_usuario.php';
                break;
            case 'borrar_usuario':
                include '../modules/admin/borrar_usuario.php';
                break;
            case 'crear_periodo':
                include '../modules/admin/crear_periodo.php';
                break;
            case 'periodos_lista':
                include '../modules/admin/periodos_lista.php';
                break;
            case 'periodo_activo':
                include '../modules/admin/periodo_activo.php';
                break;
            case 'eliminar_periodo':
                include '../modules/admin/eliminar_periodo.php';
                break;
            case 'generar_parciales':
                include '../modules/admin/generar_parciales.php';
                break;
            case 'parciales_activos':
                include '../modules/admin/parciales_activos.php';
                break;
            case 'parciales_anteriores':
                include '../modules/admin/parciales_anteriores.php';
                break;
            case 'ver_mallas':
                include '../modules/admin/ver_mallas.php';
                break;
            case 'crear_malla':
                include '../modules/admin/crear_malla.php';
                break;
            case 'editar_malla':
                include '../modules/admin/editar_malla.php';
                break;


            case 'lista_asignaturas':
                include '../modules/admin/lista_asignaturas.php';
                break;

            case 'crear_materia':
                include '../modules/admin/crear_materia.php';
                break;

            case 'editar_materia':
                include '../modules/admin/editar_materia.php';
                break;

            case 'eliminar_materia':
                include '../modules/admin/eliminar_materia.php';
                break;


            case 'lista_subasignaturas':
                include '../modules/admin/lista_subasignaturas.php';
                break;

            case 'crear_subasignatura':
                include '../modules/admin/crear_subasignatura.php';
                break;

            case 'editar_subasignatura':
                include '../modules/admin/editar_subasignatura.php';
                break;

            case 'eliminar_subasignatura':
                include '../modules/admin/eliminar_subasignatura.php';
                break;

            case 'inicio':
            default:
                ?>
                <div class="text-center py-20">
                    <h2 class="text-3xl font-black text-purple-900 mb-2">Bienvenido, <?= htmlspecialchars($_SESSION['nombres']) ?></h2>
                    <p class="text-gray-500 max-w-md mx-auto">Has ingresado al panel de administración. Aquí puedes gestionar usuarios, periodos y configuraciones globales.</p>
                    
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mt-12">
                        <div class="p-6 bg-purple-50 rounded-2xl border border-purple-100">
                            <span class="text-purple-600 font-bold block mb-1">Rol de Acceso</span>
                            <p class="text-gray-800 font-medium">Administrador</p>
                        </div>
                        <div class="p-6 bg-pink-50 rounded-2xl border border-pink-100">
                            <span class="text-pink-600 font-bold block mb-1">ID de Usuario</span>
                            <p class="text-gray-800 font-medium">#<?= $_SESSION['user_id'] ?></p>
                        </div>
                        <div class="p-6 bg-indigo-50 rounded-2xl border border-indigo-100">
                            <span class="text-indigo-600 font-bold block mb-1">Estado del Sistema</span>
                            <p class="text-gray-800 font-medium">En línea / Activo</p>
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