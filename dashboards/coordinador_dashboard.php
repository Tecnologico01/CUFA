<?php
session_start();

// Verificación de seguridad
if (!isset($_SESSION['user_id']) || $_SESSION['rol'] !== 'coordinador_academico') {
    header("Location: ../login.php");
    exit;
}

$nombre = $_SESSION['nombre'] ?? 'Usuario';
$modulo = $_GET['modulo'] ?? 'inicio';
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Coordinador - Sistema Académico</title>
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
                PANEL <span class="text-pink-500 underline decoration-purple-300">ACADÉMICO</span>
            </h2>
            <p class="text-[10px] uppercase tracking-[0.2em] text-purple-600 font-bold mt-1">Coordinación General</p>
        </div>

        <nav class="space-y-1">
            <a href="?modulo=inicio" class="menu-item <?= $modulo == 'inicio' ? 'active-menu' : '' ?>">
                <span class="mr-3 text-lg"></span> Inicio
            </a>

            <div>
                <button onclick="toggleMenu('cat-menu')" class="w-full menu-item justify-between">
                    <span class="flex items-center"><span class="mr-3 text-lg"></span> Catálogos</span>
                    <span class="text-[10px]">▼</span>
                </button>
                <div id="cat-menu" class="submenu <?= in_array($modulo, ['materias', 'carreras', 'gestionar_subasignaturas', 'tipo_subasignaturas']) ? '' : 'hidden' ?>">
                    <a href="?modulo=materias" class="menu-item text-sm <?= $modulo == 'materias' ? 'active-menu' : '' ?>">Gestión de Asignaturas</a>
                    <a href="?modulo=lista_asignaturas" class="menu-item text-sm <?= $modulo == 'lista_asignaturas' ? 'active-menu' : '' ?>">Asignaturas</a>
                    <a href="?modulo=carreras" class="menu-item text-sm <?= $modulo == 'carreras' ? 'active-menu' : '' ?>">Carreras</a>
                    <a href="?modulo=gestionar_subasignaturas" class="menu-item text-sm <?= $modulo == 'gestionar_subasignaturas' ? 'active-menu' : '' ?>">Sub-Asignaturas</a>
                    <a href="?modulo=lista_subasignaturas" class="menu-item text-sm <?= $modulo == 'lista_subasignaturas' ? 'active-menu' : '' ?>">Ver Sub-Asignaturas</a>
                    <a href="?modulo=alta_docente" class="menu-item block hover:bg-purple-200 <?= $modulo == 'alta_docente' ? 'active-menu' : '' ?>">Alta de Docente</a>
                    <a href="?modulo=docentes" class="menu-item block hover:bg-purple-200 <?= $modulo == 'docentes' ? 'active-menu' : '' ?>">Ver Docentes</a>
                </div>
            </div>

            <div>
                <button onclick="toggleMenu('doc-menu')" class="w-full menu-item justify-between">
                    <span class="flex items-center"><span class="mr-3 text-lg"></span> Docentes</span>
                    <span class="text-[10px]">▼</span>
                </button>
                <div id="doc-menu" class="submenu <?= in_array($modulo, ['asignar_materias', 'ver_asignaciones']) ? '' : 'hidden' ?>">
                    <a href="?modulo=asignar_materias" class="menu-item text-sm <?= $modulo == 'asignar_materias' ? 'active-menu' : '' ?>">Asignar Asignaturas</a>
                    <a href="?modulo=ver_asignaciones" class="menu-item text-sm <?= $modulo == 'ver_asignaciones' ? 'active-menu' : '' ?>">Ver Asignaciones</a>
                </div>
            </div>

            <div>
                <button onclick="toggleMenu('plan-menu')" class="w-full menu-item justify-between">
                    <span class="flex items-center"><span class="mr-3 text-lg"></span> Planeaciones</span>
                    <span class="text-[10px]">▼</span>
                </button>
                <div id="plan-menu" class="submenu <?= in_array($modulo, ['revisar_planeaciones_digital', 'ver_detalle_planeacion']) ? '' : 'hidden' ?>">
                    <a href="?modulo=revisar_planeaciones_digital" class="menu-item text-sm <?= $modulo == 'revisar_planeaciones_digital' ? 'active-menu' : '' ?>">Revisar Digitales</a>
                    <a href="?modulo=revisar_planeaciones" class="menu-item text-sm opacity-70 italic">Archivos PDF</a>
                </div>
            </div>

            <div>
                <button onclick="toggleMenu('cal-menu')" class="w-full menu-item justify-between">
                    <span class="flex items-center"><span class="mr-3 text-lg"></span> Calificaciones</span>
                    <span class="text-[10px]">▼</span>
                </button>
                <div id="cal-menu" class="submenu <?= in_array($modulo, ['revisar_calificaciones', 'ver_calificaciones', 'habilitar_actas']) ? '' : 'hidden' ?>">
                    <a href="?modulo=revisar_calificaciones" class="menu-item text-sm">Revisar Notas</a>
                    <a href="?modulo=ver_calificaciones" class="menu-item text-sm">Reporte General</a>
                    <a href="?modulo=habilitar_actas" class="menu-item text-sm">Habilitar Actas</a>
                </div>
            </div>

            <a href="?modulo=riesgo_academico" class="menu-item <?= $modulo == 'riesgo_academico' ? 'active-menu' : '' ?>">
                <span class="mr-3 text-lg"></span> Alumnos en Riesgo
            </a>
        </nav>
    </div>

    <div class="pt-4 border-t border-purple-200">
        <a href="../logout.php" class="flex items-center p-3 text-red-500 font-bold hover:bg-red-50 rounded-xl transition">
            <span class="mr-3"></span> Cerrar Sesión
        </a>
    </div>
</aside>

<main class="flex-1 p-8 overflow-y-auto">
    <div class="flex justify-between items-center mb-8 bg-white p-6 rounded-3xl shadow-sm border border-purple-50">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">Hola, <?= htmlspecialchars($nombre) ?> </h1>
            <p class="text-purple-500 text-sm font-medium italic">Gestión de la Licenciatura</p>
        </div>
        <div class="bg-purple-100 px-4 py-2 rounded-2xl text-purple-700 text-sm font-bold">
            <?= date('d / m / Y') ?>
        </div>
    </div>

    <div class="bg-white rounded-3xl shadow-xl p-8 min-h-[70vh] border border-purple-50 relative">
        <?php
        switch($modulo){
            case 'materias':
                include '../modules/coordinador/alta_materia.php';
                break;
            case 'asignar_materias':
                include '../modules/coordinador/asignar_materias.php';
                break;
            case 'ver_asignaciones':
                include '../modules/coordinador/ver_asignaciones.php';
                break;
            case 'revisar_planeaciones_digital':
                include '../modules/coordinador/revisar_planeaciones_digital.php';
                break;
            case 'ver_detalle_planeacion':
                include '../modules/coordinador/ver_detalle_planeacion.php';
                break;
            case 'gestionar_subasignaturas':
                include '../modules/coordinador/gestionar_subasignaturas.php';
                break;
            case 'editar_subasignatura':
                include '/../modules/coordinador/editar_subasignatura.php';
                break;
            case 'lista_asignaturas':
                include '../modules/coordinador/lista_asignaturas.php';
                break;
            case 'ver_asignatura':
                include '../modules/coordinador/ver_asignatura.php';
                break;

            case 'lista_subasignaturas':
                include '../modules/coordinador/lista_subasignaturas.php';
                break;

            case 'ver_subasignatura':
                include '../modules/coordinador/ver_subasignatura.php';
                break;

            case 'revisar_planeaciones':
                include '../modules/coordinador/revisar_planeaciones.php';
                break;
            case 'revisar_calificaciones':
                include '../modules/coordinador/revisar_calificaciones.php';
                break;
            case 'ver_calificaciones':
                include '../modules/coordinador/ver_calificaciones.php';
                break;
            case 'definir_temas':
                include '../modules/coordinador/definir_temas.php';
                break;
            case 'habilitar_actas':
                include '../modules/coordinador/habilitar_actas.php';
                break;
            case 'riesgo_academico':
                include '../modules/coordinador/riesgo_academico.php';
                break;
            case 'alta_docente':
                include '../modules/coordinador/alta_docente.php';
                break;
            case 'docentes':
                include '../modules/coordinador/docentes.php';
                break;
            case 'detalle_docente':
                include '../modules/coordinador/detalle_docente.php';
                break;
            case 'editar_docente':
                include '../modules/coordinador/editar_docente.php';
                break;

            case 'inicio':
            default:
                ?>
                <div class="text-center py-20">
                    <h2 class="text-3xl font-black text-purple-900 mb-2">Bienvenido al Sistema Académico, <?= htmlspecialchars($_SESSION['nombres']) ?></h2>
                    <p class="text-gray-500 max-w-md mx-auto">Selecciona una opción del menú lateral para comenzar a gestionar los procesos escolares.</p>
                    
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mt-12">
                        <div class="p-6 bg-purple-50 rounded-2xl border border-purple-100">
                            <span class="text-purple-600 font-bold block mb-1">Rol Actual</span>
                            <p class="text-gray-800 font-medium">Coordinador Académico</p>
                        </div>
                        <div class="p-6 bg-pink-50 rounded-2xl border border-pink-100">
                            <span class="text-pink-600 font-bold block mb-1">ID Acceso</span>
                            <p class="text-gray-800 font-medium">#<?= $_SESSION['user_id'] ?></p>
                        </div>
                        <div class="p-6 bg-indigo-50 rounded-2xl border border-indigo-100">
                            <span class="text-indigo-600 font-bold block mb-1">Estado</span>
                            <p class="text-gray-800 font-medium">Sincronizado</p>
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