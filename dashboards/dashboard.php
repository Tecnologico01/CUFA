<?php
session_start();

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/permisos.php';

if(!isset($_SESSION['user_id'])){
    header("Location: ../index.php");
    exit;
}

$nombre = $_SESSION['nombres'] ?? 'Usuario';
$rol_slug = $_SESSION['rol_slug'] ?? '';
$modulo = $_GET['modulo'] ?? 'inicio';

/*
|--------------------------------------------------------------------------
| FUNCIONES DE ROL
|--------------------------------------------------------------------------
*/

function esAdmin(){
    global $rol_slug;
    return in_array($rol_slug, ['administrador', 'admin']);
}

function esCoordinador(){
    global $rol_slug;
    return in_array($rol_slug, ['coordinador']);
}

function esDocente(){
    global $rol_slug;
    return in_array($rol_slug, ['docente']);
}

function esAlumno(){
    global $rol_slug;
    return in_array($rol_slug, ['alumno', 'estudiante']);
}

function esControlEscolar(){
    global $rol_slug;
    return in_array($rol_slug, ['control_escolar']);
}

/*
|--------------------------------------------------------------------------
| MAPA DE MÓDULOS
|--------------------------------------------------------------------------
*/

$modulos = [

    /*
    |--------------------------------------------------------------------------
    | USUARIOS
    |--------------------------------------------------------------------------
    */

    'usuarios_lista' => [
        'archivo' => 'modules/usuarios/usuarios_lista.php',
        'permiso' => 'ver_usuarios'
    ],

    'crear_usuario' => [
        'archivo' => 'modules/usuarios/crear_usuario.php',
        'permiso' => 'crear_usuarios'
    ],

    'editar_usuario' => [
        'archivo' => 'modules/usuarios/editar_usuario.php',
        'permiso' => 'editar_usuarios'
    ],

    'guardar_usuario' => [
        'archivo' => 'modules/usuarios/guardar_usuario.php',
        'permiso' => 'editar_usuarios'
    ],

    'eliminar_usuario' => [
        'archivo' => 'modules/usuarios/eliminar_usuario.php',
        'permiso' => 'eliminar_usuarios'
    ],

    /*
    |--------------------------------------------------------------------------
    | ASIGNATURAS
    |--------------------------------------------------------------------------
    */

    'lista_asignaturas' => [
        'archivo' => 'modules/asignaturas/lista_asignaturas.php',
        'permiso' => 'ver_asignaturas'
    ],

    'crear_materia' => [
        'archivo' => 'modules/asignaturas/crear_materia.php',
        'permiso' => 'crear_asignaturas'
    ],

    'editar_materia' => [
        'archivo' => 'modules/asignaturas/editar_materia.php',
        'permiso' => 'editar_asignaturas'
    ],

    'eliminar_materia' => [
        'archivo' => 'modules/asignaturas/eliminar_materia.php',
        'permiso' => 'eliminar_asignaturas'
    ],

    'ver_asignatura' => [
        'archivo' => 'modules/asignaturas/ver_asignatura.php',
        'permiso' => 'ver_detalle_asignatura'
    ],

    'lista_subasignaturas' => [
        'archivo' => 'modules/asignaturas/lista_subasignaturas.php',
        'permiso' => 'ver_subasignaturas'
    ],

    'crear_subasignatura' => [
        'archivo' => 'modules/asignaturas/crear_subasignatura.php',
        'permiso' => 'crear_subasignaturas'
    ],

    'editar_subasignatura' => [
        'archivo' => 'modules/asignaturas/editar_subasignatura.php',
        'permiso' => 'editar_subasignaturas'
    ],

    'eliminar_subasignatura' => [
        'archivo' => 'modules/asignaturas/eliminar_subasignatura.php',
        'permiso' => 'eliminar_subasignaturas'
    ],

    'ver_subasignatura' => [
        'archivo' => 'modules/asignaturas/ver_subasignatura.php',
        'permiso' => 'ver_detalle_subasignatura'
    ],

    'ver_mallas' => [
        'archivo' => 'modules/asignaturas/ver_mallas.php',
        'permiso' => 'ver_mallas'
    ],

    /*
    |--------------------------------------------------------------------------
    | GRUPOS
    |--------------------------------------------------------------------------
    */

    'gestion_grupos' => [
        'archivo' => 'modules/grupos/gestion_grupos.php',
        'permiso' => 'crear_grupos'
    ],

    'lista_grupos' => [
        'archivo' => 'modules/grupos/lista_grupos.php',
        'permiso' => 'ver_grupos'
    ],

    'editar_grupo' => [
        'archivo' => 'modules/grupos/editar_grupo.php',
        'permiso' => 'editar_grupos'
    ],

    'eliminar_grupo' => [
        'archivo' => 'modules/grupos/eliminar_grupo.php',
        'permiso' => 'eliminar_grupos'
    ],

    'asignar_materias' => [
        'archivo' => 'modules/grupos/asignar_materias.php',
        'permiso' => 'asignar_materias'
    ],

    'ver_asignaciones' => [
        'archivo' => 'modules/grupos/ver_asignaciones.php',
        'permiso' => 'ver_asignaciones'
    ],

    'asignar_alumnos' => [
        'archivo' => 'modules/grupos/asignar_alumnos.php',
        'permiso' => 'asignar_alumnos'
    ],

    'ver_asignaciones_alumnos' => [
        'archivo' => 'modules/grupos/ver_asignaciones_alumnos.php',
        'permiso' => 'ver_asignaciones_alumnos'
    ],

    /*
    |--------------------------------------------------------------------------
    | DOCENTES
    |--------------------------------------------------------------------------
    */

    'lista_docentes' => [
        'archivo' => 'modules/docentes/docentes.php',
        'permiso' => 'ver_docentes'
    ],

    'crear_docente' => [
        'archivo' => 'modules/docentes/alta_docente.php',
        'permiso' => 'crear_docentes'
    ],

    'ver_docente' => [
        'archivo' => 'modules/docentes/detalle_docente.php',
        'permiso' => 'ver_detalle_docente'
    ],

    'editar_docente' => [
        'archivo' => 'modules/docentes/editar_docente.php',
        'permiso' => 'editar_docentes'
    ],

    'guardar_docente' => [
        'archivo' => 'modules/docentes/guardar_docente.php',
        'permiso' => 'editar_docentes'
    ],

    'mis_materias' => [
        'archivo' => 'modules/docentes/mis_materias.php',
        'permiso' => 'ver_mis_materias'
    ],

    'ver_materia' => [
        'archivo' => 'modules/docentes/ver_materia.php',
        'permiso' => 'ver_materia'
    ],

    /*
    |--------------------------------------------------------------------------
    | PLANEACIONES
    |--------------------------------------------------------------------------
    */

    'definir_unidades' => [
        'archivo' => 'modules/planeaciones/definir_unidades.php',
        'permiso' => 'definir_unidades'
    ],

    'crear_planeacion' => [
        'archivo' => 'modules/planeaciones/crear_planeacion.php',
        'permiso' => 'crear_planeaciones'
    ],

    'editar_planeacion' => [
        'archivo' => 'modules/planeaciones/editar_planeacion.php',
        'permiso' => 'editar_planeaciones'
    ],

    'actualizar_planeacion' => [
        'archivo' => 'modules/planeaciones/actualizar_planeacion.php',
        'permiso' => 'actualizar_planeaciones'
    ],

    'guardar_planeacion' => [
        'archivo' => 'modules/planeaciones/guardar_planeacion.php',
        'permiso' => 'guardar_planeaciones'
    ],

    'guardar_semana' => [
        'archivo' => 'modules/planeaciones/guardar_semana.php',
        'permiso' => 'guardar_planeaciones'
    ],

    'editar_semana' => [
        'archivo' => 'modules/planeaciones/editar_semana.php',
        'permiso' => 'editar_planeaciones'
    ],

    'planeacion_materia' => [
        'archivo' => 'modules/planeaciones/planeacion_materia.php',
        'permiso' => 'ver_planeaciones'
    ],

    'planeacion_semanal' => [
        'archivo' => 'modules/planeaciones/planeacion_semanal.php',
        'permiso' => 'ver_planeaciones'
    ],

    'ver_temas' => [
        'archivo' => 'modules/planeaciones/ver_temas.php',
        'permiso' => 'ver_planeaciones'
    ],

    'semanas_actividades' => [
        'archivo' => 'modules/planeaciones/semanas_actividades.php',
        'permiso' => 'ver_semanas_actividades'
    ],

    /*
    |--------------------------------------------------------------------------
    | ACTIVIDADES
    |--------------------------------------------------------------------------
    */

    'mis_actividades' => [
        'archivo' => 'modules/actividades/mis_actividades.php',
        'permiso' => 'ver_mis_actividades'
    ],

    'crear_actividad' => [
        'archivo' => 'modules/actividades/crear_actividad.php',
        'permiso' => 'crear_actividades'
    ],

    'subir_material' => [
        'archivo' => 'modules/actividades/subir_material.php',
        'permiso' => 'subir_material'
    ],

    'ver_unidad' => [
        'archivo' => 'modules/actividades/ver_unidad.php',
        'permiso' => 'ver_unidad'
    ],

    /*
    |--------------------------------------------------------------------------
    | EVALUACIONES / CALIFICACIONES
    |--------------------------------------------------------------------------
    */

    'calificaciones' => [
        'archivo' => 'modules/calificaciones/calificaciones.php',
        'permiso' => 'ver_calificaciones'
    ],

    'subir_calificaciones' => [
        'archivo' => 'modules/calificaciones/subir_calificaciones.php',
        'permiso' => 'subir_calificaciones'
    ],

    'editar_calificacion' => [
        'archivo' => 'modules/calificaciones/editar_calificacion.php',
        'permiso' => 'editar_calificaciones'
    ],

    'revisar_calificaciones' => [
        'archivo' => 'modules/calificaciones/revisar_calificaciones.php',
        'permiso' => 'revisar_calificaciones'
    ],

    'generar_actas' => [
        'archivo' => 'modules/calificaciones/generar_actas.php',
        'permiso' => 'generar_actas'
    ],

    'habilitar_actas' => [
        'archivo' => 'modules/calificaciones/habilitar_actas.php',
        'permiso' => 'habilitar_actas'
    ],

    'mis_examenes_alumnos' => [
        'archivo' => 'modules/examenes/mis_examenes_alumnos.php',
        'permiso' => 'ver_examenes'
    ],

    'habilitar_actas' => [
        'archivo' => 'modules/calificaciones/habilitar_actas.php',
        'permiso' => 'habilitar_actas'
    ],

    /*
    |--------------------------------------------------------------------------
    | CONTROL ESCOLAR
    |--------------------------------------------------------------------------
    */

    'consulta_academica' => [
        'archivo' => 'modules/control_escolar/consulta_academica.php',
        'permiso' => 'ver_consulta_academica'
    ],

    'ver_acta' => [
        'archivo' => 'modules/control_escolar/ver_acta.php',
        'permiso' => 'ver_actas'
    ],

    'actas' => [
        'archivo' => 'modules/control_escolar/actas.php',
        'permiso' => 'ver_actas'
    ],

    'boletas' => [
        'archivo' => 'modules/control_escolar/boletas.php',
        'permiso' => 'ver_boletas'
    ],

    /*
    |--------------------------------------------------------------------------
    | PERMISOS
    |--------------------------------------------------------------------------
    */

    'roles_lista' => [
        'archivo' => 'modules/permisos/roles_lista.php',
        'permiso' => 'ver_roles'
    ],

    'editar_rol' => [
    'archivo' => 'modules/permisos/editar_rol.php',
    'permiso' => 'editar_roles'
    ],

    'eliminar_rol' => [
    'archivo' => 'modules/permisos/eliminar_rol.php',
    'permiso' => 'eliminar_roles'
    ],

    'crear_rol' => [
        'archivo' => 'modules/permisos/crear_rol.php',
        'permiso' => 'crear_roles'
    ],

    'asignar_permisos' => [
        'archivo' => 'modules/permisos/asignar_permisos.php',
        'permiso' => 'asignar_permisos'
    ],

    'gestionar_permisos' => [
        'archivo' => 'modules/permisos/gestionar_permisos.php',
        'permiso' => 'gestionar_permisos'
    ],
];

/*
|--------------------------------------------------------------------------
| MENÚ BASE
|--------------------------------------------------------------------------
*/

$menu = [
    'Usuarios' => [
        'items' => [
            ['texto' => 'Ver usuarios', 'modulo' => 'usuarios_lista', 'permiso' => 'ver_usuarios'],
            ['texto' => 'Crear usuario', 'modulo' => 'crear_usuario', 'permiso' => 'crear_usuarios'],
        ]
    ],

    'Asignaturas' => [
        'items' => [
            ['texto' => 'Ver asignaturas', 'modulo' => 'lista_asignaturas', 'permiso' => 'ver_asignaturas'],
            ['texto' => 'Crear asignatura', 'modulo' => 'crear_materia', 'permiso' => 'crear_asignaturas'],
            ['texto' => 'Ver subasignaturas', 'modulo' => 'lista_subasignaturas', 'permiso' => 'ver_subasignaturas'],
            ['texto' => 'Crear subasignatura', 'modulo' => 'crear_subasignatura', 'permiso' => 'crear_subasignaturas'],
            ['texto' => 'Malla curricular', 'modulo' => 'ver_mallas', 'permiso' => 'ver_mallas'],
        ]
    ],

    'Grupos' => [
        'items' => [
            ['texto' => 'Ver grupos', 'modulo' => 'lista_grupos', 'permiso' => 'ver_grupos'],
            ['texto' => 'Crear grupo', 'modulo' => 'gestion_grupos', 'permiso' => 'crear_grupos'],
            ['texto' => 'Asignar materias', 'modulo' => 'asignar_materias', 'permiso' => 'asignar_materias'],
            ['texto' => 'Asignaciones docentes', 'modulo' => 'ver_asignaciones', 'permiso' => 'ver_asignaciones'],
            ['texto' => 'Asignar alumnos', 'modulo' => 'asignar_alumnos', 'permiso' => 'asignar_alumnos'],
            ['texto' => 'Asignaciones alumnos', 'modulo' => 'ver_asignaciones_alumnos', 'permiso' => 'ver_asignaciones_alumnos'],
        ]
    ],
];

function puedeVerSeccion($items){
    foreach($items as $item){
        if(tienePermiso($item['permiso'])){
            return true;
        }
    }

    return false;
}

function panelTitulo($modulo){
    $titulos = [
        'inicio' => 'Panel Principal',
        'gestion_personas' => 'Gestión de Personas',
        'control_academico' => 'Control Académico',
        'docentes_panel' => 'Gestión Docente',
        'docente_panel' => 'Mi Aula',
        'alumno_panel' => 'Portal Estudiantil',
        'planeaciones_panel' => 'Planeaciones',
        'control_escolar_panel' => 'Control Escolar',
        'permisos_panel' => 'Seguridad y Permisos',
    ];

    return $titulos[$modulo] ?? ucfirst(str_replace('_', ' ', $modulo));
}

?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Sistema Académico CUFA</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <script src="https://cdn.tailwindcss.com"></script>

    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;900&display=swap" rel="stylesheet">

    <style>
        body{
            font-family:'Poppins',sans-serif;
            background-color:#f8fafc;
            color:#334155;
        }

        .nav-link{
            padding:.5rem 1rem;
            border-radius:9999px;
            font-weight:600;
            font-size:.875rem;
            color:#64748b;
            transition:all .3s ease;
            white-space:nowrap;
        }

        .nav-link:hover{
            color:#4f46e5;
            background-color:#e0e7ff;
        }

        .nav-link.active{
            background:linear-gradient(135deg,#6366f1 0%,#a855f7 100%);
            color:white;
            box-shadow:0 4px 6px -1px rgba(99,102,241,.2);
        }

        .action-card{
            display:flex;
            align-items:center;
            padding:1rem 1.25rem;
            background-color:#f1f5f9;
            border-radius:1rem;
            color:#475569;
            font-weight:500;
            transition:all .2s ease;
            border:1px solid transparent;
        }

        .action-card:hover{
            background:white;
            border-color:#c7d2fe;
            color:#4f46e5;
            box-shadow:0 10px 15px -3px rgba(0,0,0,.05);
            transform:translateY(-2px);
        }

        .action-card::before{
            content:'→';
            margin-right:.75rem;
            color:#a5b4fc;
            transition:margin .2s;
        }

        .action-card:hover::before{
            margin-right:1rem;
            color:#4f46e5;
        }
    </style>
</head>

<body class="min-h-screen flex flex-col">

<header class="bg-white/80 backdrop-blur-md border-b border-indigo-100 shadow-sm sticky top-0 z-50">

    <div class="max-w-[1400px] mx-auto px-6 h-20 flex items-center justify-between">

        <div class="flex items-center gap-10">

            <a href="dashboard.php" class="flex items-center gap-3 group">
                <div class="w-12 h-12 rounded-2xl bg-gradient-to-br from-indigo-100 to-purple-100 flex items-center justify-center font-black text-indigo-700 shadow-inner group-hover:scale-105 transition-transform">
                    CUFA
                </div>
            </a>

            <nav class="hidden lg:flex items-center gap-2">

                <a href="dashboard.php"
                   class="nav-link <?= $modulo === 'inicio' ? 'active' : '' ?>">
                    Dashboard
                </a>

                <?php if(
                    esAdmin() ||
                    esCoordinador() ||
                    esControlEscolar()
                ): ?>

                    <?php if(
                        tienePermiso('ver_usuarios') ||
                        tienePermiso('ver_alumnos') ||
                        tienePermiso('ver_docentes')
                    ): ?>
                        <a href="?modulo=gestion_personas"
                           class="nav-link <?= $modulo === 'gestion_personas' ? 'active' : '' ?>">
                            Personas
                        </a>
                    <?php endif; ?>

                    <?php if(
                        tienePermiso('ver_asignaturas') ||
                        tienePermiso('ver_grupos') ||
                        tienePermiso('ver_mallas')
                    ): ?>
                        <a href="?modulo=control_academico"
                           class="nav-link <?= $modulo === 'control_academico' ? 'active' : '' ?>">
                            Académico
                        </a>
                    <?php endif; ?>

                    <?php if(
                        tienePermiso('ver_docentes') ||
                        tienePermiso('crear_docentes')
                    ): ?>
                        <a href="?modulo=docentes_panel"
                           class="nav-link <?= $modulo === 'docentes_panel' ? 'active' : '' ?>">
                            Docentes
                        </a>
                    <?php endif; ?>

                    <?php if(
                        tienePermiso('revisar_planeaciones') ||
                        tienePermiso('ver_planeaciones')
                    ): ?>
                        <a href="?modulo=planeaciones_panel"
                           class="nav-link <?= $modulo === 'planeaciones_panel' ? 'active' : '' ?>">
                            Planeaciones
                        </a>
                    <?php endif; ?>

                    <?php if(
                        tienePermiso('ver_boletas') ||
                        tienePermiso('ver_actas') ||
                        tienePermiso('ver_consulta_academica')
                    ): ?>
                        <a href="?modulo=control_escolar_panel"
                           class="nav-link <?= $modulo === 'control_escolar_panel' ? 'active' : '' ?>">
                            Control Escolar
                        </a>
                    <?php endif; ?>

                    <?php if(
                        tienePermiso('gestionar_permisos') ||
                        tienePermiso('asignar_permisos')
                    ): ?>
                        <a href="?modulo=permisos_panel"
                           class="nav-link <?= $modulo === 'permisos_panel' ? 'active' : '' ?>">
                            Seguridad
                        </a>
                    <?php endif; ?>

                <?php endif; ?>

                <?php if(esDocente()): ?>

                    <a href="?modulo=docente_panel"
                       class="nav-link <?= $modulo === 'docente_panel' ? 'active' : '' ?>">
                        Mi Aula
                    </a>

                    <?php if(tienePermiso('ver_planeaciones')): ?>
                        <a href="?modulo=planeaciones_panel"
                           class="nav-link <?= $modulo === 'planeaciones_panel' ? 'active' : '' ?>">
                            Planeaciones
                        </a>
                    <?php endif; ?>

                <?php endif; ?>

                <?php if(esAlumno()): ?>

                    <a href="?modulo=alumno_panel"
                       class="nav-link <?= $modulo === 'alumno_panel' ? 'active' : '' ?>">
                        Portal Estudiantil
                    </a>

                <?php endif; ?>

            </nav>

        </div>

        <div class="relative group py-6">

            <button class="flex items-center gap-3 text-slate-700 font-bold bg-slate-100 hover:bg-slate-200 px-4 py-2 rounded-full transition">

                <span class="w-8 h-8 rounded-full bg-indigo-500 text-white flex items-center justify-center text-xs">
                    <?= strtoupper(substr($nombre,0,1)) ?>
                </span>

                <?= htmlspecialchars($nombre) ?>

                <span class="text-xs text-slate-400">▼</span>

            </button>

            <div class="hidden group-hover:block absolute right-0 top-16 bg-white border border-slate-100 rounded-2xl shadow-2xl w-56 overflow-hidden z-50">

                <div class="p-4 border-b border-slate-50 bg-slate-50/50">
                    <p class="text-xs text-slate-500 uppercase tracking-wider font-bold">
                        Conectado como
                    </p>

                    <p class="text-sm font-bold text-indigo-600 truncate">
                        <?= htmlspecialchars(str_replace('_',' ',$rol_slug)) ?>
                    </p>
                </div>

                <a href="../logout.php" class="block px-4 py-3 text-red-500 hover:bg-red-50 font-bold text-sm transition">
                    Cerrar sesión
                </a>

            </div>

        </div>

    </div>

</header>

<?php if(!isset($modulos[$modulo])): ?>

    <div class="bg-gradient-to-r from-indigo-50 to-purple-50 border-b border-indigo-100">
        <div class="max-w-[1400px] mx-auto px-6 py-10">
            <h1 class="text-3xl font-black text-indigo-900 capitalize tracking-tight">
                <?= htmlspecialchars(panelTitulo($modulo)) ?>
            </h1>

            <p class="text-indigo-400 mt-2 font-medium">
                Selecciona una acción del menú para comenzar
            </p>
        </div>
    </div>

<?php endif; ?>

<main class="max-w-[1400px] mx-auto px-6 py-12 flex-1">

<?php if($modulo === 'inicio'): ?>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">

        <div class="bg-white rounded-3xl shadow-xl shadow-indigo-100/50 border border-indigo-50 p-8">
            <h3 class="text-xs uppercase tracking-[0.2em] font-bold text-indigo-400 mb-2">
                Rol actual
            </h3>

            <p class="text-2xl font-black text-indigo-900 capitalize">
                <?= htmlspecialchars(str_replace('_',' ',$rol_slug)) ?>
            </p>
        </div>

        <div class="bg-white rounded-3xl shadow-xl shadow-purple-100/50 border border-purple-50 p-8">
            <h3 class="text-xs uppercase tracking-[0.2em] font-bold text-purple-400 mb-2">
                Usuario ID
            </h3>

            <p class="text-2xl font-black text-purple-900">
                #<?= (int) $_SESSION['user_id'] ?>
            </p>
        </div>

        <div class="bg-white rounded-3xl shadow-xl shadow-emerald-100/50 border border-emerald-50 p-8 relative overflow-hidden">
            <div class="absolute -right-4 -top-4 w-24 h-24 bg-emerald-100 rounded-full blur-2xl"></div>

            <h3 class="text-xs uppercase tracking-[0.2em] font-bold text-emerald-500 mb-2 relative z-10">
                Estado del sistema
            </h3>

            <div class="flex items-center gap-3 relative z-10">
                <span class="relative flex h-4 w-4">
                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                    <span class="relative inline-flex rounded-full h-4 w-4 bg-emerald-500"></span>
                </span>

                <p class="text-2xl font-black text-emerald-700">
                    En línea
                </p>
            </div>
        </div>

    </div>

    <div class="bg-white rounded-3xl shadow-xl border border-slate-100 p-10 text-center">

        <h2 class="text-2xl font-bold text-slate-800 mb-4">
            Bienvenido, <?= htmlspecialchars($nombre) ?>
        </h2>

        <p class="text-slate-500 max-w-2xl mx-auto">
            Estás en el panel unificado del Sistema Académico CUFA. Las opciones visibles dependen de tu rol y de los permisos asignados por el administrador.
        </p>
    </div>

<?php elseif($modulo === 'gestion_personas'): ?>

    <div class="space-y-8">

        <?php if(tienePermiso('ver_usuarios') || tienePermiso('crear_usuarios')): ?>

            <section class="bg-white rounded-3xl shadow-xl shadow-blue-100/30 border border-blue-50 p-8">

                <h2 class="text-xl font-bold mb-6 text-blue-900 border-b border-blue-50 pb-4 flex items-center gap-3">
                    <span class="p-2 bg-blue-100 rounded-xl"></span>
                    Usuarios del sistema
                </h2>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    <?php if(tienePermiso('ver_usuarios')): ?>
                        <a href="?modulo=usuarios_lista" class="action-card">Ver usuarios</a>
                    <?php endif; ?>

                    <?php if(tienePermiso('crear_usuarios')): ?>
                        <a href="?modulo=crear_usuario" class="action-card">Crear usuario</a>
                    <?php endif; ?>
                </div>

            </section>

        <?php endif; ?>

        <?php if(tienePermiso('ver_alumnos') || tienePermiso('asignar_alumnos')): ?>

            <section class="bg-white rounded-3xl shadow-xl shadow-cyan-100/30 border border-cyan-50 p-8">

                <h2 class="text-xl font-bold mb-6 text-cyan-900 border-b border-cyan-50 pb-4 flex items-center gap-3">
                    <span class="p-2 bg-cyan-100 rounded-xl"></span>
                    Alumnos
                </h2>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    <?php if(tienePermiso('ver_alumnos')): ?>
                        <a href="?modulo=alumnos" class="action-card">Ver alumnos</a>
                    <?php endif; ?>

                    <?php if(tienePermiso('asignar_alumnos')): ?>
                        <a href="?modulo=asignar_alumnos" class="action-card">Asignar alumnos</a>
                    <?php endif; ?>

                    <?php if(tienePermiso('ver_asignaciones_alumnos')): ?>
                        <a href="?modulo=ver_asignaciones_alumnos" class="action-card">Asignaciones de alumnos</a>
                    <?php endif; ?>
                </div>

            </section>

        <?php endif; ?>

    </div>

<?php elseif($modulo === 'control_academico'): ?>

    <div class="space-y-8">

        <?php if(puedeVerSeccion($menu['Asignaturas']['items'] ?? [])): ?>

            <section class="bg-white rounded-3xl shadow-xl shadow-indigo-100/30 border border-indigo-50 p-8">

                <h2 class="text-xl font-bold mb-6 text-indigo-900 border-b border-indigo-50 pb-4 flex items-center gap-3">
                    <span class="p-2 bg-indigo-100 rounded-xl"></span>
                    Asignaturas
                </h2>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    <?php if(tienePermiso('ver_asignaturas')): ?>
                        <a href="?modulo=lista_asignaturas" class="action-card">Ver asignaturas</a>
                    <?php endif; ?>

                    <?php if(tienePermiso('crear_asignaturas')): ?>
                        <a href="?modulo=crear_materia" class="action-card">Crear asignatura</a>
                    <?php endif; ?>

                    <?php if(tienePermiso('ver_subasignaturas')): ?>
                        <a href="?modulo=lista_subasignaturas" class="action-card">Ver subasignaturas</a>
                    <?php endif; ?>

                    <?php if(tienePermiso('crear_subasignaturas')): ?>
                        <a href="?modulo=crear_subasignatura" class="action-card">Crear subasignatura</a>
                    <?php endif; ?>

                    <?php if(tienePermiso('ver_mallas')): ?>
                        <a href="?modulo=ver_mallas" class="action-card">Malla curricular</a>
                    <?php endif; ?>
                </div>

            </section>

        <?php endif; ?>

        <?php if(puedeVerSeccion($menu['Grupos']['items'] ?? [])): ?>

            <section class="bg-white rounded-3xl shadow-xl shadow-purple-100/30 border border-purple-50 p-8">

                <h2 class="text-xl font-bold mb-6 text-purple-900 border-b border-purple-50 pb-4 flex items-center gap-3">
                    <span class="p-2 bg-purple-100 rounded-xl"></span>
                    Grupos y asignaciones
                </h2>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    <?php if(tienePermiso('ver_grupos')): ?>
                        <a href="?modulo=lista_grupos" class="action-card">Ver grupos</a>
                    <?php endif; ?>

                    <?php if(tienePermiso('crear_grupos')): ?>
                        <a href="?modulo=gestion_grupos" class="action-card">Crear grupo</a>
                    <?php endif; ?>

                    <?php if(tienePermiso('asignar_materias')): ?>
                        <a href="?modulo=asignar_materias" class="action-card">Asignar materias</a>
                    <?php endif; ?>

                    <?php if(tienePermiso('ver_asignaciones')): ?>
                        <a href="?modulo=ver_asignaciones" class="action-card">Asignaciones docentes</a>
                    <?php endif; ?>

                    <?php if(tienePermiso('asignar_alumnos')): ?>
                        <a href="?modulo=asignar_alumnos" class="action-card">Asignar alumnos</a>
                    <?php endif; ?>
                </div>

            </section>

        <?php endif; ?>

    </div>

<?php elseif($modulo === 'docentes_panel'): ?>

    <section class="bg-white rounded-3xl shadow-xl shadow-orange-100/30 border border-orange-50 p-8">

        <h2 class="text-xl font-bold mb-6 text-orange-900 border-b border-orange-50 pb-4 flex items-center gap-3">
            <span class="p-2 bg-orange-100 rounded-xl"></span>
            Gestión docente
        </h2>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            <?php if(tienePermiso('ver_docentes')): ?>
                <a href="?modulo=lista_docentes" class="action-card">Ver docentes</a>
            <?php endif; ?>

            <?php if(tienePermiso('crear_docentes')): ?>
                <a href="?modulo=crear_docente" class="action-card">Crear docente</a>
            <?php endif; ?>
        </div>

    </section>

<?php elseif($modulo === 'docente_panel'): ?>

    <section class="bg-white rounded-3xl shadow-xl shadow-indigo-100/30 border border-indigo-50 p-8">

        <h2 class="text-xl font-bold mb-6 text-indigo-900 border-b border-indigo-50 pb-4 flex items-center gap-3">
            <span class="p-2 bg-indigo-100 rounded-xl"></span>
            Mi aula docente
        </h2>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            <?php if(tienePermiso('ver_mis_materias')): ?>
                <a href="?modulo=mis_materias" class="action-card">Mis materias</a>
            <?php endif; ?>

            <?php if(tienePermiso('ver_mis_actividades')): ?>
                <a href="?modulo=mis_actividades" class="action-card">Mis actividades</a>
            <?php endif; ?>

            <?php if(tienePermiso('ver_calificaciones')): ?>
                <a href="?modulo=calificaciones" class="action-card">Calificaciones</a>
            <?php endif; ?>

            <?php if(tienePermiso('ver_examenes')): ?>
                <a href="?modulo=mis_examenes_alumnos" class="action-card">Exámenes</a>
            <?php endif; ?>
        </div>

    </section>

<?php elseif($modulo === 'alumno_panel'): ?>

    <section class="bg-white rounded-3xl shadow-xl shadow-cyan-100/30 border border-cyan-50 p-8">

        <h2 class="text-xl font-bold mb-6 text-cyan-900 border-b border-cyan-50 pb-4 flex items-center gap-3">
            <span class="p-2 bg-cyan-100 rounded-xl"></span>
            Portal estudiantil
        </h2>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            <?php if(tienePermiso('ver_mis_actividades')): ?>
                <a href="?modulo=mis_actividades_alumno" class="action-card">Mis actividades</a>
            <?php endif; ?>

            <?php if(tienePermiso('ver_mis_examenes')): ?>
                <a href="?modulo=mis_examenes_alumnos" class="action-card">Mis exámenes</a>
            <?php endif; ?>

            <?php if(tienePermiso('ver_calificaciones')): ?>
                <a href="?modulo=mis_calificaciones" class="action-card">Mis calificaciones</a>
            <?php endif; ?>

            <?php if(tienePermiso('ver_boletas')): ?>
                <a href="?modulo=boletas" class="action-card">Boletas</a>
            <?php endif; ?>

            <?php if(tienePermiso('ver_consulta_academica')): ?>
                <a href="?modulo=consulta_academica" class="action-card">Consulta académica</a>
            <?php endif; ?>
        </div>

    </section>

<?php elseif($modulo === 'planeaciones_panel'): ?>

    <section class="bg-white rounded-3xl shadow-xl shadow-amber-100/30 border border-amber-50 p-8">

        <h2 class="text-xl font-bold mb-6 text-amber-900 border-b border-amber-50 pb-4 flex items-center gap-3">
            <span class="p-2 bg-amber-100 rounded-xl"></span>
            Planeaciones didácticas
        </h2>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">

            <?php if(esDocente() && tienePermiso('ver_planeaciones')): ?>
                <a href="?modulo=mis_materias" class="action-card">Definir unidades</a>
            <?php endif; ?>

            <?php if(!esDocente() && tienePermiso('ver_planeaciones')): ?>
                <a href="?modulo=planeacion_materia" class="action-card">Ver planeaciones</a>
            <?php endif; ?>

            <?php if(tienePermiso('revisar_planeaciones')): ?>
                <a href="?modulo=revisar_planeaciones" class="action-card">Revisar planeaciones</a>
            <?php endif; ?>

        </div>

    </section>

<?php elseif($modulo === 'control_escolar_panel'): ?>

    <section class="bg-white rounded-3xl shadow-xl shadow-teal-100/30 border border-teal-50 p-8">

        <h2 class="text-xl font-bold mb-6 text-teal-900 border-b border-teal-50 pb-4 flex items-center gap-3">
            <span class="p-2 bg-teal-100 rounded-xl"></span>
            Control escolar
        </h2>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            <?php if(tienePermiso('ver_consulta_academica')): ?>
                <a href="?modulo=consulta_academica" class="action-card">Consulta académica</a>
            <?php endif; ?>

            <?php if(tienePermiso('habilitar_actas')): ?>
                <a href="?modulo=habilitar_actas" class="action-card">
                    Habilitar actas
                </a>
            <?php endif; ?>

            <?php if(tienePermiso('ver_actas')): ?>
                <a href="?modulo=actas" class="action-card">Actas oficiales</a>
            <?php endif; ?>

            <?php if(tienePermiso('ver_boletas')): ?>
                <a href="?modulo=boletas" class="action-card">Boletas estudiantiles</a>
            <?php endif; ?>

            <?php if(tienePermiso('revisar_calificaciones')): ?>
                <a href="?modulo=revisar_calificaciones" class="action-card">
                    Revisar calificaciones
                </a>
            <?php endif; ?>

            <?php if(tienePermiso('generar_actas')): ?>
                <a href="?modulo=generar_actas" class="action-card">
                    Generar actas
                </a>
            <?php endif; ?>
        </div>

    </section>

<?php elseif($modulo === 'permisos_panel'): ?>

    <section class="bg-white rounded-3xl shadow-xl shadow-red-100/30 border border-red-50 p-8">

        <h2 class="text-xl font-bold mb-6 text-red-900 border-b border-red-50 pb-4 flex items-center gap-3">
            <span class="p-2 bg-red-100 rounded-xl"></span>
            Seguridad y permisos
        </h2>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            <?php if(tienePermiso('ver_roles')): ?>
                <a href="?modulo=roles_lista" class="action-card">Ver roles</a>
            <?php endif; ?>

            <?php if(tienePermiso('crear_roles')): ?>
                <a href="?modulo=crear_rol" class="action-card">Crear rol</a>
            <?php endif; ?>

            <?php if(tienePermiso('gestionar_permisos')): ?>
                <a href="?modulo=gestionar_permisos" class="action-card">Gestionar permisos</a>
            <?php endif; ?>
        </div>

    </section>

<?php else: ?>

    <?php

    if(!isset($modulos[$modulo])){

        echo "
        <div class='bg-red-50 border-l-4 border-red-500 text-red-700 p-8 rounded-2xl shadow-sm'>
            <div class='flex items-center gap-3 mb-2'>
                <span class='text-2xl'>⚠️</span>
                <h2 class='text-xl font-bold'>Módulo no encontrado</h2>
            </div>
            <p class='ml-9'>El módulo solicitado no existe en el mapa de rutas.</p>
        </div>
        ";

    }else{

        $archivo = __DIR__ . '/../' . $modulos[$modulo]['archivo'];
        $permiso = $modulos[$modulo]['permiso'];

        if(!tienePermiso($permiso)){

            echo "
            <div class='bg-red-50 border-l-4 border-red-500 text-red-700 p-8 rounded-2xl shadow-sm'>
                <div class='flex items-center gap-3 mb-2'>
                    <span class='text-2xl'>🔒</span>
                    <h2 class='text-xl font-bold'>Acceso denegado</h2>
                </div>
                <p class='ml-9'>No tienes permisos suficientes para acceder a este módulo.</p>
            </div>
            ";

        }elseif(!file_exists($archivo)){

            echo "
            <div class='bg-amber-50 border-l-4 border-amber-500 text-amber-800 p-8 rounded-2xl shadow-sm'>
                <div class='flex items-center gap-3 mb-2'>
                    <span class='text-2xl'>🛠️</span>
                    <h2 class='text-xl font-bold'>Archivo faltante</h2>
                </div>
                <p class='ml-9'>
                    El archivo del módulo está registrado pero no se encuentra en el servidor:
                    <br>
                    <code class='bg-amber-100 px-2 py-1 rounded text-amber-900 mt-2 inline-block'>
                        {$modulos[$modulo]['archivo']}
                    </code>
                </p>
            </div>
            ";

        }else{

            include $archivo;
        }
    }

    ?>

<?php endif; ?>

</main>

</body>
</html>