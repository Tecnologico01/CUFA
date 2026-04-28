<?php
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['rol'] !== 'control_escolar') {
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
    <title>Control Escolar - Sistema Académico</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Poppins', sans-serif; }
        
        /* Paleta Pastel Personalizada */
        .bg-main { background-color: #fdf2f8; } 
        .sidebar { background: linear-gradient(180deg, #f3e8ff 0%, #fce7f3 100%); } 
        
        .menu-item { 
            transition: all 0.3s ease; 
            color: #4c1d95; 
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
    </style>
</head>

<body class="bg-main min-h-screen flex">

<aside class="sidebar w-72 p-6 shadow-2xl flex flex-col justify-between border-r border-purple-100 sticky top-0 h-screen">
    <div>
        <div class="mb-10 px-2">
            <h2 class="text-2xl font-black text-purple-900 tracking-tight">
                CONTROL <span class="text-pink-500 underline decoration-purple-300">ESCOLAR</span>
            </h2>
            <p class="text-[10px] uppercase tracking-[0.2em] text-purple-600 font-bold mt-1">Servicios Estudiantiles</p>
        </div>

        <nav class="space-y-1">
            <a href="control_dashboard.php?modulo=inicio" class="menu-item <?= $modulo == 'inicio' ? 'active-menu' : '' ?>">
                Inicio
            </a>

            <a href="control_dashboard.php?modulo=consulta_academica" class="menu-item <?= $modulo == 'consulta_academica' ? 'active-menu' : '' ?>">
                Consulta Académica
            </a>

            <a href="control_dashboard.php?modulo=actas" class="menu-item <?= $modulo == 'actas' || $modulo == 'generar_acta' ? 'active-menu' : '' ?>">
                Generar Actas
            </a>

            <a href="control_dashboard.php?modulo=boletas" class="menu-item <?= $modulo == 'boletas' || $modulo == 'ver_boleta' ? 'active-menu' : '' ?>">
                Generar Boletas
            </a>
            
            <a href="control_dashboard.php?modulo=alumnos" class="menu-item <?= $modulo == 'alumnos' ? 'active-menu' : '' ?>">
                Alumnos
            </a>
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
            <p class="text-purple-500 text-sm font-medium italic">Administración de Expedientes</p>
        </div>
        <div class="bg-purple-100 px-4 py-2 rounded-2xl text-purple-700 text-sm font-bold">
            <?= date('d / m / Y') ?>
        </div>
    </div>

    <div class="bg-white rounded-3xl shadow-xl p-8 min-h-[70vh] border border-purple-50">
        <?php
        switch($modulo){
            case 'alumnos':
                include '../modules/control_escolar/alumnos.php';
                break;
            case 'grupos':
                include '../modules/control_escolar/grupos.php';
                break;
            case 'consulta_academica':
                include '../modules/control_escolar/consulta_academica.php';
                break;
            case 'calificaciones':
                include '../modules/control_escolar/calificaciones.php';
                break;
            case 'actas':
                include '../modules/control_escolar/actas_lista.php';
                break;
            case 'generar_acta':
                include '../modules/control_escolar/generar_acta.php';
                break;
            case 'boletas':
                include '../modules/control_escolar/boletas.php';
                break;
            case 'ver_boleta':
                include '../modules/control_escolar/ver_boleta.php';
                break;

            case 'inicio':
            default:
                ?>
                <div class="text-center py-20">
                    <h2 class="text-3xl font-black text-purple-900 mb-2">Bienvenido, <?= htmlspecialchars($nombre) ?></h2>
                    <p class="text-gray-500 max-w-md mx-auto">Selecciona una opción del menú lateral para gestionar la documentación oficial de la institución.</p>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8 mt-12">
                        <div class="p-8 bg-purple-50 rounded-3xl border border-purple-100 group hover:bg-white hover:shadow-lg transition-all">
                            <div class="w-12 h-12 bg-purple-200 rounded-2xl mb-4 flex items-center justify-center text-purple-600 text-xl font-bold">A</div>
                            <h3 class="text-xl font-bold text-purple-900 mb-2">Actas</h3>
                            <p class="text-gray-600 text-sm">Generación y validación de actas de calificaciones oficiales por periodo.</p>
                        </div>

                        <div class="p-8 bg-pink-50 rounded-3xl border border-pink-100 group hover:bg-white hover:shadow-lg transition-all">
                            <div class="w-12 h-12 bg-pink-200 rounded-2xl mb-4 flex items-center justify-center text-pink-600 text-xl font-bold">B</div>
                            <h3 class="text-xl font-bold text-pink-900 mb-2">Boletas</h3>
                            <p class="text-gray-600 text-sm">Consulta y emisión de boletas parciales y finales para el alumnado.</p>
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