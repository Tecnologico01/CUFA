<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistema Académico</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="assets/css/custom.css">
</head>
<body class="bg-gray-100">
    <!-- Barra superior -->
    <div class="bg-white shadow p-4 flex items-center justify-between">
        <div class="flex items-center gap-4">
            <div class="text-3xl">📘</div>
            <div>
                <h1 class="font-bold text-2xl">Instituto Educativo</h1>
                <p class="text-sm text-gray-500"><?= htmlspecialchars($_SESSION['nombre'] ?? 'Usuario') ?> - <?= htmlspecialchars($rol ?? 'Sin rol') ?></p>
            </div>
        </div>
        <a href="logout.php" class="bg-red-600 text-white px-6 py-3 rounded-3xl">Cerrar sesión</a>
    </div>