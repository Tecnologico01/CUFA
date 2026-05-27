<?php
session_start();

/* VERIFICACIÓN DE SEGURIDAD */
if (
    !isset($_SESSION['user_id']) ||
    $_SESSION['rol_slug'] !== 'alumno'
) {
    header("Location: ../index.php");
    exit;
}

$nombre = $_SESSION['nombres'] ?? 'Alumno';
$modulo = $_GET['modulo'] ?? 'inicio';
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Alumno - Sistema Académico</title>

    <script src="https://cdn.tailwindcss.com"></script>

    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700;800&display=swap" rel="stylesheet">

    <style>
        body{
            font-family:'Poppins',sans-serif;
        }

        /* PALETA */
        .bg-main{
            background-color:#f8fafc;
        }

        .sidebar{
            background:linear-gradient(180deg,#dbeafe 0%, #ede9fe 100%);
        }

        .menu-item{
            transition:all .3s ease;
            color:#1e3a8a;
            font-weight:600;
            display:flex;
            align-items:center;
            padding:.85rem 1rem;
            border-radius:.9rem;
            margin-bottom:.3rem;
        }

        .menu-item:hover{
            background-color:rgba(255,255,255,.7);
            transform:translateX(4px);
        }

        .active-menu{
            background:white !important;
            box-shadow:0 4px 12px rgba(0,0,0,.05);
            color:#4f46e5;
            font-weight:700;
        }

        .submenu{
            margin-left:1.4rem;
            border-left:2px solid #c7d2fe;
            padding-left:.7rem;
        }

        .glass{
            backdrop-filter: blur(12px);
        }
    </style>

    <script>
        function toggleMenu(id){
            const menu = document.getElementById(id);
            menu.classList.toggle('hidden');
        }
    </script>
</head>

<body class="bg-main min-h-screen flex">

    <!-- SIDEBAR -->
    <aside class="sidebar w-72 p-6 shadow-2xl flex flex-col justify-between border-r border-indigo-100 sticky top-0 h-screen">

        <div>

            <!-- LOGO -->
            <div class="mb-10 px-2">
                <h2 class="text-2xl font-black text-indigo-900 tracking-tight">
                    PANEL <span class="text-blue-500 underline decoration-indigo-300">ESTUDIANTIL</span>
                </h2>

                <p class="text-[10px] uppercase tracking-[0.25em] text-indigo-600 font-bold mt-1">
                    Plataforma Académica
                </p>
            </div>

            <!-- MENÚ -->
            <nav class="space-y-1">

                <!-- INICIO -->
                <a href="?modulo=inicio"
                   class="menu-item <?= $modulo == 'inicio' ? 'active-menu' : '' ?>">

                    <span class="mr-3 text-lg"></span>
                    Inicio
                </a>

                <!-- ACADÉMICO -->
                <div>

                    <button onclick="toggleMenu('academico-menu')"
                            class="w-full menu-item justify-between">

                        <span class="flex items-center">
                            <span class="mr-3 text-lg"></span>
                            Académico
                        </span>

                        <span class="text-[10px]">▼</span>
                    </button>

                    <div id="academico-menu"
                         class="submenu <?= in_array($modulo, [
                            'mis_materias',
                            'mis_calificaciones',
                            'horario'
                         ]) ? '' : 'hidden' ?>">

                        <a href="?modulo=mis_materias"
                           class="menu-item text-sm <?= $modulo == 'mis_materias' ? 'active-menu' : '' ?>">
                            Mis Materias
                        </a>

                        <a href="?modulo=mis_calificaciones"
                           class="menu-item text-sm <?= $modulo == 'mis_calificaciones' ? 'active-menu' : '' ?>">
                            Calificaciones
                        </a>

                        <a href="?modulo=horario"
                           class="menu-item text-sm <?= $modulo == 'horario' ? 'active-menu' : '' ?>">
                            Horario
                        </a>

                    </div>
                </div>

                <!-- ACTIVIDADES -->
                <div>

                    <button onclick="toggleMenu('actividades-menu')"
                            class="w-full menu-item justify-between">

                        <span class="flex items-center">
                            <span class="mr-3 text-lg"></span>
                            Actividades
                        </span>

                        <span class="text-[10px]">▼</span>
                    </button>

                    <div id="actividades-menu"
                         class="submenu <?= in_array($modulo, [
                            'tareas',
                            'examenes'
                         ]) ? '' : 'hidden' ?>">

                        <a href="?modulo=tareas"
                           class="menu-item text-sm <?= $modulo == 'tareas' ? 'active-menu' : '' ?>">
                            Tareas
                        </a>

                        <a href="?modulo=mis_examenes_alumnos"
                        class="menu-item text-sm <?= $modulo == 'mis_examenes_alumnos' || $modulo == 'examen' ? 'active-menu' : '' ?>">
                            Exámenes
                        </a>

                    </div>
                </div>

                <!-- PERFIL -->
                <a href="?modulo=perfil"
                   class="menu-item <?= $modulo == 'perfil' ? 'active-menu' : '' ?>">

                    <span class="mr-3 text-lg"></span>
                    Mi Perfil
                </a>

            </nav>
        </div>

        <!-- FOOTER -->
        <div class="pt-4 border-t border-indigo-200">

            <a href="../logout.php"
               class="flex items-center p-3 text-red-500 font-bold hover:bg-red-50 rounded-xl transition">

                <span class="mr-3"></span>
                Cerrar Sesión
            </a>

        </div>

    </aside>

    <!-- CONTENIDO -->
    <main class="flex-1 p-8 overflow-y-auto">

        <!-- TOPBAR -->
        <div class="flex justify-between items-center mb-8 bg-white p-6 rounded-3xl shadow-sm border border-indigo-50">

            <div>
                <h1 class="text-2xl font-bold text-gray-800">
                    Bienvenido, <?= htmlspecialchars($nombre) ?>
                </h1>

                <p class="text-indigo-500 text-sm font-medium italic">
                    Portal Estudiantil
                </p>
            </div>

            <div class="bg-indigo-100 px-4 py-2 rounded-2xl text-indigo-700 text-sm font-bold">
                <?= date('d / m / Y') ?>
            </div>

        </div>

        <!-- CONTENEDOR -->
        <div class="bg-white rounded-3xl shadow-xl p-8 min-h-[70vh] border border-indigo-50 relative">

            <?php

            switch($modulo){

                case 'mis_materias':
                    include '../modules/alumno/mis_materias.php';
                    break;

                case 'mis_calificaciones':
                    include '../modules/alumno/mis_calificaciones.php';
                    break;

                case 'horario':
                    include '../modules/alumno/horario.php';
                    break;

                case 'tareas':

                if(isset($_GET['materia_id'])){

                    include '../modules/alumno/tareas.php';

                } else {

                    include '../modules/alumno/mis_actividades_alumno.php';

                }

                break;

                case 'mis_examenes_alumnos':
                    include '../modules/alumno/mis_examenes_alumnos.php';
                    break;

                case 'examen':
                    include '../modules/alumno/examen.php';
                    break;

                case 'examenes':
                    include '../modules/alumno/examenes.php';
                    break;

                case 'perfil':
                    include '../modules/alumno/perfil.php';
                    break;

                case 'inicio':
                default:
                    ?>

                    <div class="text-center py-20">

                        <h2 class="text-4xl font-black text-indigo-900 mb-3">
                            Bienvenido al Sistema Académico
                        </h2>

                        <p class="text-gray-500 max-w-2xl mx-auto">
                            Desde este panel podrás consultar tus materias, tareas,
                            exámenes, horarios y seguimiento académico.
                        </p>

                        <!-- CARDS -->
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mt-14">

                            <div class="p-8 bg-indigo-50 rounded-3xl border border-indigo-100 hover:scale-105 transition">

                                <span class="text-indigo-600 font-black block mb-2 uppercase text-xs tracking-widest">
                                    Rol Actual
                                </span>

                                <p class="text-gray-800 font-semibold text-lg">
                                    Alumno
                                </p>

                            </div>

                            <div class="p-8 bg-blue-50 rounded-3xl border border-blue-100 hover:scale-105 transition">

                                <span class="text-blue-600 font-black block mb-2 uppercase text-xs tracking-widest">
                                    ID Escolar
                                </span>

                                <p class="text-gray-800 font-semibold text-lg">
                                    #<?= $_SESSION['user_id'] ?>
                                </p>

                            </div>

                            <div class="p-8 bg-violet-50 rounded-3xl border border-violet-100 hover:scale-105 transition">

                                <span class="text-violet-600 font-black block mb-2 uppercase text-xs tracking-widest">
                                    Estado
                                </span>

                                <p class="text-gray-800 font-semibold text-lg">
                                    Activo
                                </p>

                            </div>

                        </div>

                        <!-- BLOQUE EXTRA -->
                        <div class="mt-16 bg-gradient-to-r from-indigo-500 to-violet-500 text-white rounded-[2rem] p-10 shadow-2xl max-w-4xl mx-auto">

                            <h3 class="text-2xl font-black mb-3">
                                Plataforma de Seguimiento Académico
                            </h3>

                            <p class="text-sm opacity-90 leading-relaxed">
                                Consulta tus actividades, revisa tus calificaciones
                                y mantente al día con tus asignaturas desde un solo lugar.
                            </p>

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