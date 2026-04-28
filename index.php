<?php
session_start();

/* FUNCIÓN PARA REDIRIGIR SEGÚN ROL (Mantenida exactamante igual) */
function redirigirPorRol($rol){
    switch($rol){
        case 'administrador':
            header("Location: dashboards/admin_dashboard.php");
        break;
        case 'coordinador_academico':
            header("Location: dashboards/coordinador_dashboard.php");
        break;
        case 'control_escolar':
            header("Location: dashboards/control_dashboard.php");
        break;
        case 'docente':
            header("Location: dashboards/docente_dashboard.php");
        break;
        case 'alumno':
            header("Location: dashboards/alumno_dashboard.php");
        break;
        case 'padre_familia':
            header("Location: dashboards/padre_dashboard.php");
        break;
    }
    exit;
}

/* SI YA HAY SESIÓN */
if (isset($_SESSION['user_id'])) {
    redirigirPorRol($_SESSION['rol']);
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    require_once 'includes/db.php';

    $stmt = $pdo->prepare("
        SELECT id, nombres, rol, password_hash 
        FROM usuarios 
        WHERE username = ? AND activo = 1
    ");

    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password_hash'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['nombres'] = $user['nombres'];
        $_SESSION['rol'] = $user['rol'];
        redirigirPorRol($user['rol']);
    } else {
        $error = "Credenciales incorrectas o usuario inactivo.";
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Acceso - Sistema Académico</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @keyframes slide-up { 
            from { opacity: 0; transform: translateY(30px); } 
            to { opacity: 1; transform: translateY(0); } 
        }
        .animate-fade-in { animation: slide-up 0.8s cubic-bezier(0.16, 1, 0.3, 1); }
    </style>
</head>

<body class="bg-slate-50 flex items-center justify-center min-h-screen p-6">

    <div class="w-full max-w-md animate-fade-in">
        <div class="bg-white p-10 rounded-[3.5rem] shadow-2xl shadow-slate-200 border border-slate-100 relative overflow-hidden">
            <div class="absolute top-0 right-0 p-8 opacity-[0.03] select-none pointer-events-none">
                <span class="text-8xl font-black italic uppercase tracking-tighter">Auth</span>
            </div>
            <header class="mb-10 relative z-10">
                <div class="text-center mb-10">
                    <img src="assets/images/lcufa.png" width="160" class="mx-auto drop-shadow-sm">
                </div>
            </header>
            <?php if ($error): ?>
            <div class="bg-red-50 border border-red-100 text-red-600 text-[11px] font-black uppercase tracking-wider p-4 rounded-2xl mb-8 text-center italic">
                <?= htmlspecialchars($error) ?>
            </div>
            <?php endif; ?>

            <form method="POST" class="space-y-6 relative z-10">

                <div class="space-y-2">
                    <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-2">Usuario</label>
                    <input type="text" name="username" required 
                        class="w-full p-5 bg-slate-50 border-2 border-slate-100 rounded-[1.5rem] font-bold text-slate-700 outline-none focus:border-purple-500 focus:bg-white transition-all shadow-inner placeholder:text-slate-200">
                </div>

                <div class="space-y-2">
                    <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-2">Contraseña</label>
                    <input type="password" name="password" required 
                        class="w-full p-5 bg-slate-50 border-2 border-slate-100 rounded-[1.5rem] font-bold text-slate-700 outline-none focus:border-purple-500 focus:bg-white transition-all shadow-inner">
                </div>

                <div class="pt-4">
                    <button class="group w-full py-6 bg-slate-900 text-white rounded-[2.5rem] font-black uppercase tracking-[0.3em] text-[11px] shadow-xl hover:bg-purple-600 transition-all transform hover:-translate-y-1 flex items-center justify-center gap-4">
                        <span>Iniciar Sesión</span>
                        <svg class="w-5 h-5 group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path d="M13 7l5 5m0 0l-5 5m5-5H6" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"></path>
                        </svg>
                    </button>
                </div>

            </form>

            <div class="mt-10 text-center">
                <p class="text-[9px] font-bold text-slate-300 uppercase tracking-widest italic">
                    Sistema de Gestión Académica CUFA
                </p>
            </div>
        </div>

    </div>

</body>
</html>