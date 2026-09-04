<?php
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/permisos.php';

validarPermiso('habilitar_actas');

/* =========================
   CAMBIAR ESTADO
========================= */
if(isset($_GET['accion'], $_GET['id'])){

    $id = (int) $_GET['id'];
    $accion = $_GET['accion'];

    if($id > 0 && in_array($accion, ['abrir', 'cerrar'])){

        $nuevoEstado = $accion === 'abrir' ? 'abierta' : 'cerrada';

        try{

            $stmt = $pdo->prepare("
                UPDATE actas
                SET estado = ?
                WHERE id = ?
            ");

            $stmt->execute([$nuevoEstado, $id]);

            echo "
            <script>
                alert('Estado del acta actualizado correctamente');
                window.location.href='?modulo=habilitar_actas';
            </script>";
            exit;

        }catch(Exception $e){

            $msg = htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');

            echo "
            <script>
                alert('Error al actualizar el acta: {$msg}');
                window.location.href='?modulo=habilitar_actas';
            </script>";
            exit;
        }
    }
}

/* =========================
   CONSULTAR ACTAS
========================= */
$stmt = $pdo->query("
    SELECT 
        a.id,
        a.codigo,
        a.estado,
        a.fecha_generacion,
        a.created_at,
        m.nombre AS materia,
        g.nombre AS grupo,
        p.nombre AS periodo,
        CONCAT(u.nombres, ' ', u.apellido_paterno, ' ', u.apellido_materno) AS docente
    FROM actas a
    LEFT JOIN asignaciones_docentes ad 
        ON ad.id = a.asignacion_id
    LEFT JOIN materias m 
        ON m.id = a.materia_id
    LEFT JOIN grupos g 
        ON g.id = a.grupo_id
    LEFT JOIN periodos p 
        ON p.id = a.periodo_id
    LEFT JOIN docentes d 
        ON d.id = ad.docente_id
    LEFT JOIN usuarios u 
        ON u.id = d.usuario_id
    ORDER BY a.id DESC
");

$actas = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="max-w-7xl mx-auto p-6 bg-slate-50 min-h-screen">

    <div class="mb-10">
        <span class="bg-emerald-100 text-emerald-700 px-4 py-2 rounded-full text-[10px] font-black uppercase tracking-[0.25em]">
            Control de Actas
        </span>

        <h1 class="text-5xl font-black italic uppercase tracking-tighter text-slate-800 mt-5">
            Habilitar Actas
        </h1>

        <p class="text-slate-500 font-semibold mt-3">
            Apertura y cierre de actas oficiales generadas.
        </p>
    </div>

    <?php if(!$actas): ?>

        <div class="bg-white p-16 rounded-[3rem] text-center border border-slate-100 shadow-sm">
            <h2 class="text-2xl font-black text-slate-700 uppercase">
                Sin actas generadas
            </h2>

            <p class="text-slate-400 font-semibold mt-3">
                Primero debes generar un acta oficial.
            </p>
        </div>

    <?php else: ?>

        <div class="bg-white rounded-[3rem] shadow-xl border border-slate-100 overflow-hidden">

            <table class="min-w-full">
                <thead class="bg-slate-900 text-white">
                    <tr>
                        <th class="p-5 text-left text-[10px] font-black uppercase tracking-widest">Código</th>
                        <th class="p-5 text-left text-[10px] font-black uppercase tracking-widest">Asignatura</th>
                        <th class="p-5 text-left text-[10px] font-black uppercase tracking-widest">Grupo</th>
                        <th class="p-5 text-left text-[10px] font-black uppercase tracking-widest">Periodo</th>
                        <th class="p-5 text-left text-[10px] font-black uppercase tracking-widest">Docente</th>
                        <th class="p-5 text-center text-[10px] font-black uppercase tracking-widest">Estado</th>
                        <th class="p-5 text-center text-[10px] font-black uppercase tracking-widest">Acción</th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-slate-100">
                    <?php foreach($actas as $a): ?>

                        <?php
                            $estado = $a['estado'] ?? 'generada';

                            $colorEstado = 'bg-blue-100 text-blue-700';

                            if($estado === 'abierta'){
                                $colorEstado = 'bg-emerald-100 text-emerald-700';
                            }

                            if($estado === 'cerrada'){
                                $colorEstado = 'bg-red-100 text-red-700';
                            }
                        ?>

                        <tr class="hover:bg-slate-50 transition">

                            <td class="p-5">
                                <span class="font-black text-slate-700">
                                    <?= htmlspecialchars($a['codigo'] ?: 'ACTA-' . $a['id']) ?>
                                </span>

                                <p class="text-[10px] text-slate-400 font-bold mt-1">
                                    <?= htmlspecialchars($a['fecha_generacion'] ?: $a['created_at']) ?>
                                </p>
                            </td>

                            <td class="p-5 font-bold text-slate-700">
                                <?= htmlspecialchars($a['materia'] ?? 'Sin materia') ?>
                            </td>

                            <td class="p-5 font-bold text-slate-600">
                                <?= htmlspecialchars($a['grupo'] ?? 'Sin grupo') ?>
                            </td>

                            <td class="p-5 font-bold text-slate-600">
                                <?= htmlspecialchars($a['periodo'] ?? 'Sin periodo') ?>
                            </td>

                            <td class="p-5 font-bold text-slate-500">
                                <?= htmlspecialchars($a['docente'] ?? 'Sin docente') ?>
                            </td>

                            <td class="p-5 text-center">
                                <span class="<?= $colorEstado ?> px-4 py-2 rounded-full text-[10px] font-black uppercase tracking-widest">
                                    <?= htmlspecialchars($estado) ?>
                                </span>
                            </td>

                            <td class="p-5 text-center">

                                <?php if($estado === 'cerrada'): ?>
                                    <a href="?modulo=habilitar_actas&accion=abrir&id=<?= (int)$a['id'] ?>"
                                       onclick="return confirm('¿Deseas abrir esta acta nuevamente?')"
                                       class="inline-block bg-emerald-600 hover:bg-emerald-700 text-white px-5 py-3 rounded-2xl text-[10px] font-black uppercase tracking-widest">
                                        Abrir
                                    </a>
                                <?php else: ?>
                                    <a href="?modulo=habilitar_actas&accion=cerrar&id=<?= (int)$a['id'] ?>"
                                       onclick="return confirm('¿Deseas cerrar esta acta?')"
                                       class="inline-block bg-red-600 hover:bg-red-700 text-white px-5 py-3 rounded-2xl text-[10px] font-black uppercase tracking-widest">
                                        Cerrar
                                    </a>
                                <?php endif; ?>

                            </td>

                        </tr>

                    <?php endforeach; ?>
                </tbody>
            </table>

        </div>

    <?php endif; ?>

</div>