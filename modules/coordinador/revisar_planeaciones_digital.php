<?php
require_once __DIR__ . '/../../includes/db.php';

$stmt = $pdo->query("
    SELECT ad.id, u.nombres AS docente, m.nombre AS materia, g.nombre AS grupo, 
           ad.estado_planeacion, ad.docente_id, d.usuario_id
    FROM asignaciones_docentes ad
    JOIN docentes d ON ad.docente_id = d.id
    JOIN usuarios u ON d.usuario_id = u.id
    JOIN materias m ON ad.materia_id = m.id
    JOIN grupos g ON ad.grupo_id = g.id
    ORDER BY ad.estado_planeacion DESC
");
?>

<div class="p-6">
    <h1 class="text-3xl font-bold mb-6 text-gray-800">Revisión de Planeaciones Digitales</h1>
    
    <div class="bg-white rounded-xl shadow overflow-hidden">
        <table class="w-full text-left">
            <thead class="bg-indigo-600 text-white">
                <tr>
                    <th class="p-4">Docente</th>
                    <th class="p-4">Materia / Grupo</th>
                    <th class="p-4">Estado</th>
                    <th class="p-4 text-center">Acción</th>
                </tr>
            </thead>
            <tbody>
                <?php while($r = $stmt->fetch()): ?>
                <tr class="border-b hover:bg-gray-50">
                    <td class="p-4 font-bold"><?= htmlspecialchars($r['docente']) ?></td>
                    <td class="p-4"><?= htmlspecialchars($r['materia']) ?> (<?= htmlspecialchars($r['grupo']) ?>)</td>
                    <td class="p-4">
                        <?php 
                        $colores = [
                            'pendiente' => 'bg-gray-100 text-gray-600',
                            'revision'  => 'bg-blue-100 text-blue-600',
                            'aprobado'  => 'bg-green-100 text-green-600',
                            'rechazado' => 'bg-red-100 text-red-600'
                        ];
                        $clase = $colores[$r['estado_planeacion']] ?? 'bg-gray-100';
                        ?>
                        <span class="px-3 py-1 rounded-full text-xs font-bold uppercase <?= $clase ?>">
                            <?= $r['estado_planeacion'] ?>
                        </span>
                    </td>
                    <td class="p-4 text-center">
                        <a href="coordinador_dashboard.php?modulo=ver_detalle_planeacion&asignacion_id=<?= $r['id'] ?>" 
                           class="bg-indigo-500 text-white px-4 py-2 rounded-lg hover:bg-indigo-600 transition text-sm">
                            Revisar Contenido
                        </a>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>