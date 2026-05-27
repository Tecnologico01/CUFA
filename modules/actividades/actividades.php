<?php
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/permisos.php';

validarPermiso('ver_actividades');

$puedeCrear = tienePermiso('crear_actividades');
$puedeEditar = tienePermiso('editar_actividades');
$puedeEliminar = tienePermiso('eliminar_actividades');

$stmt = $pdo->query("
    SELECT
        a.id,
        a.nombre,
        a.descripcion,
        a.fecha_apertura,
        a.fecha_cierre,
        a.valor,
        a.parcial,
        a.semana,
        a.tipo_archivo,
        a.material_archivo,
        m.nombre AS materia,
        g.nombre AS grupo,
        p.nombre AS periodo,
        CONCAT(u.nombres, ' ', u.apellido_paterno, ' ', u.apellido_materno) AS docente
    FROM actividades a
    INNER JOIN asignaciones_docentes ad
        ON ad.id = a.asignacion_id
    INNER JOIN materias m
        ON m.id = ad.materia_id
    INNER JOIN grupos g
        ON g.id = ad.grupo_id
    INNER JOIN periodos p
        ON p.id = ad.periodo_id
    INNER JOIN docentes d
        ON d.id = ad.docente_id
    INNER JOIN usuarios u
        ON u.id = d.usuario_id
    ORDER BY a.fecha_cierre DESC, a.id DESC
");

$actividades = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="max-w-7xl mx-auto">

    <div class="flex flex-col md:flex-row md:items-center justify-between gap-6 mb-10">
        <div>
            <span class="bg-indigo-100 text-indigo-700 px-4 py-2 rounded-full text-[10px] font-black uppercase tracking-[0.25em]">
                Actividades
            </span>

            <h1 class="text-4xl font-black text-slate-900 mt-5 uppercase tracking-tight">
                Gestión de
                <span class="text-indigo-600 italic">Actividades</span>
            </h1>

            <p class="text-slate-500 font-semibold mt-3">
                Consulta general de actividades registradas por los docentes.
            </p>
        </div>

        <?php if($puedeCrear): ?>
            <a href="?modulo=crear_actividad"
               class="bg-indigo-600 hover:bg-indigo-700 text-white px-7 py-4 rounded-2xl font-black uppercase tracking-[0.2em] text-[11px] shadow-xl shadow-indigo-200 transition-all">
                + Nueva Actividad
            </a>
        <?php endif; ?>
    </div>

    <div class="bg-white rounded-[2rem] shadow-sm border border-slate-100 p-6 mb-8">
        <div class="relative">
            <div class="absolute inset-y-0 left-0 pl-5 flex items-center pointer-events-none text-slate-300">
                🔍
            </div>

            <input
                type="text"
                id="buscadorActividades"
                placeholder="Buscar por actividad, materia, grupo, docente o periodo..."
                class="w-full bg-slate-50 border-2 border-transparent focus:border-indigo-600 rounded-2xl py-4 pl-14 pr-5 font-bold outline-none transition-all"
            >
        </div>
    </div>

    <?php if(count($actividades) > 0): ?>

        <div id="gridActividades" class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">

            <?php foreach($actividades as $a): ?>

                <?php
                    $hoy = date('Y-m-d');
                    $cerrada = !empty($a['fecha_cierre']) && $a['fecha_cierre'] < $hoy;
                ?>

                <div
                    class="actividad-card bg-white rounded-[2rem] shadow-sm hover:shadow-xl border border-slate-100 p-7 transition-all hover:-translate-y-1"
                    data-text="<?= strtolower(htmlspecialchars(
                        $a['nombre'].' '.$a['materia'].' '.$a['grupo'].' '.$a['periodo'].' '.$a['docente']
                    )) ?>"
                >

                    <div class="flex items-start justify-between gap-4 mb-6">

                        <div class="w-14 h-14 bg-indigo-100 text-indigo-700 rounded-2xl flex items-center justify-center text-2xl">
                            📝
                        </div>

                        <?php if($cerrada): ?>
                            <span class="bg-red-100 text-red-600 px-3 py-1 rounded-full text-[9px] font-black uppercase tracking-widest">
                                Cerrada
                            </span>
                        <?php else: ?>
                            <span class="bg-emerald-100 text-emerald-600 px-3 py-1 rounded-full text-[9px] font-black uppercase tracking-widest">
                                Activa
                            </span>
                        <?php endif; ?>

                    </div>

                    <h2 class="text-xl font-black text-slate-900 uppercase tracking-tight mb-2">
                        <?= htmlspecialchars($a['nombre']) ?>
                    </h2>

                    <p class="text-sm font-semibold text-slate-500 mb-5 line-clamp-3">
                        <?= htmlspecialchars($a['descripcion'] ?: 'Sin descripción registrada.') ?>
                    </p>

                    <div class="bg-slate-50 rounded-2xl p-5 border border-slate-100 mb-5 space-y-3">

                        <div>
                            <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest">
                                Asignatura
                            </p>
                            <p class="text-sm font-black text-slate-700">
                                <?= htmlspecialchars($a['materia']) ?>
                            </p>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest">
                                    Grupo
                                </p>
                                <p class="text-sm font-black text-indigo-600">
                                    <?= htmlspecialchars($a['grupo']) ?>
                                </p>
                            </div>

                            <div>
                                <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest">
                                    Periodo
                                </p>
                                <p class="text-sm font-black text-slate-700">
                                    <?= htmlspecialchars($a['periodo']) ?>
                                </p>
                            </div>
                        </div>

                    </div>

                    <div class="grid grid-cols-2 gap-4 mb-5">

                        <div class="bg-indigo-50 rounded-2xl p-4 text-center">
                            <p class="text-[9px] font-black text-indigo-400 uppercase tracking-widest">
                                Parcial
                            </p>
                            <p class="text-xl font-black text-indigo-700">
                                <?= (int)$a['parcial'] ?>
                            </p>
                        </div>

                        <div class="bg-purple-50 rounded-2xl p-4 text-center">
                            <p class="text-[9px] font-black text-purple-400 uppercase tracking-widest">
                                Valor
                            </p>
                            <p class="text-xl font-black text-purple-700">
                                <?= (float)$a['valor'] ?>%
                            </p>
                        </div>

                    </div>

                    <div class="space-y-2 mb-6">
                        <p class="text-[10px] font-bold text-slate-400 uppercase">
                            Docente:
                            <span class="text-slate-600">
                                <?= htmlspecialchars($a['docente']) ?>
                            </span>
                        </p>

                        <p class="text-[10px] font-bold text-slate-400 uppercase">
                            Apertura:
                            <span class="text-slate-600">
                                <?= htmlspecialchars($a['fecha_apertura'] ?: 'No definida') ?>
                            </span>
                        </p>

                        <p class="text-[10px] font-bold text-slate-400 uppercase">
                            Cierre:
                            <span class="<?= $cerrada ? 'text-red-500' : 'text-emerald-600' ?>">
                                <?= htmlspecialchars($a['fecha_cierre'] ?: 'No definida') ?>
                            </span>
                        </p>
                    </div>

                    <div class="grid grid-cols-1 gap-3">

                        <?php if(!empty($a['material_archivo'])): ?>
                            <a
                                href="/sistema_academico/<?= htmlspecialchars($a['material_archivo']) ?>"
                                target="_blank"
                                class="text-center bg-slate-900 hover:bg-indigo-600 text-white py-3 rounded-2xl font-black uppercase tracking-widest text-[10px] transition-all"
                            >
                                Ver material
                            </a>
                        <?php endif; ?>

                        <div class="grid grid-cols-2 gap-3">

                            <?php if($puedeEditar): ?>
                                <a
                                    href="?modulo=editar_actividad&id=<?= (int)$a['id'] ?>"
                                    class="text-center bg-indigo-600 hover:bg-indigo-700 text-white py-3 rounded-2xl font-black uppercase tracking-widest text-[10px] transition-all"
                                >
                                    Editar
                                </a>
                            <?php endif; ?>

                            <?php if($puedeEliminar): ?>
                                <a
                                    href="?modulo=eliminar_actividad&id=<?= (int)$a['id'] ?>"
                                    onclick="return confirm('¿Seguro que deseas eliminar esta actividad?')"
                                    class="text-center bg-red-500 hover:bg-red-600 text-white py-3 rounded-2xl font-black uppercase tracking-widest text-[10px] transition-all"
                                >
                                    Eliminar
                                </a>
                            <?php endif; ?>

                        </div>

                    </div>

                </div>

            <?php endforeach; ?>

        </div>

    <?php else: ?>

        <div class="bg-white rounded-[2rem] shadow-sm border border-slate-100 p-16 text-center">
            <div class="text-6xl mb-4">📝</div>

            <h2 class="text-2xl font-black text-slate-900 uppercase mb-3">
                Sin actividades registradas
            </h2>

            <p class="text-slate-500 font-semibold">
                Aún no existen actividades creadas en el sistema.
            </p>
        </div>

    <?php endif; ?>

    <div
        id="sinResultadosActividades"
        class="hidden bg-white rounded-[2rem] shadow-sm border border-slate-100 p-16 text-center mt-8"
    >
        <div class="text-6xl mb-4">🔎</div>

        <h2 class="text-2xl font-black text-slate-900 uppercase mb-3">
            Sin coincidencias
        </h2>

        <p class="text-slate-500 font-semibold">
            No se encontraron actividades con ese criterio de búsqueda.
        </p>
    </div>

</div>

<script>
const buscadorActividades = document.getElementById('buscadorActividades');
const tarjetasActividades = document.querySelectorAll('.actividad-card');
const sinResultadosActividades = document.getElementById('sinResultadosActividades');

if(buscadorActividades){

    buscadorActividades.addEventListener('keyup', function(){

        const filtro = this.value.toLowerCase().trim();
        let visibles = 0;

        tarjetasActividades.forEach(card => {

            const texto = card.dataset.text || '';

            if(texto.includes(filtro)){
                card.style.display = 'block';
                visibles++;
            }else{
                card.style.display = 'none';
            }

        });

        if(visibles === 0 && tarjetasActividades.length > 0){
            sinResultadosActividades.classList.remove('hidden');
        }else{
            sinResultadosActividades.classList.add('hidden');
        }

    });
}
</script>