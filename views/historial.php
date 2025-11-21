<?php
include __DIR__ . '/../components/navbar.php';
require_once __DIR__ . '/../controllers/HistorialController.php';
require_once __DIR__ . '/../config/db.php';

$controller = new HistorialController($pdo);
$historial = $controller->obtenerHistorial();
?>

<div class="container mt-4">
    <h2 class="mb-4">Historial de Incapacidades</h2>

    <div class="table-responsive">
        <table class="table table-bordered table-striped table-hover">
            <thead class="table-dark">
                <tr>
                    <th>ID</th>
                    <th>Incapacidad ID</th>
                    <th>Número</th>
                    <th>Mes</th>
                    <th>Empleado</th>
                    <th>Cédula</th>
                    <th>Área</th>
                    <th>Cód Diagnóstico</th>
                    <th>Diagnóstico</th>
                    <th>Tipo</th>
                    <th>EPS/ARL</th>
                    <th>Inicio</th>
                    <th>Termina</th>
                    <th>Días</th>
                    <th>Días Entidad</th>
                    <th>Valor</th>
                    <th>Valor Aprox</th>
                    <th>Estado Proceso</th>
                    <th>Aplicación Pago</th>
                    <th>Observaciones</th>
                    <th>Número Orden</th>
                    <th>Creado En</th>
                    <th>Estado</th>
                    <th>Acción</th>
                </tr>
            </thead>

            <tbody>
                <?php if (!empty($historial)): ?>
                    <?php foreach ($historial as $h): ?>
                        <tr>
                            <td><?= $h['id'] ?></td>
                            <td><?= $h['incapacidad_id'] ?></td>
                            <td><?= $h['numero_incapacidad'] ?></td>
                            <td><?= $h['mes'] ?></td>
                            <td><?= $h['nombre_empleado'] ?></td>
                            <td><?= $h['cedula'] ?></td>
                            <td><?= $h['area'] ?></td>
                            <td><?= $h['cod_diagnostico'] ?></td>
                            <td><?= $h['diagnostico'] ?></td>
                            <td><?= $h['tipo_incapacidad'] ?></td>
                            <td><?= $h['eps_arl'] ?></td>
                            <td><?= $h['inicio'] ?></td>
                            <td><?= $h['termina'] ?></td>
                            <td><?= $h['dias_incapacidad'] ?></td>
                            <td><?= $h['dias_a_cargo_entidad'] ?></td>
                            <td><?= number_format($h['valor'],0,',','.') ?></td>
                            <td><?= number_format($h['valor_aprox'],0,',','.') ?></td>
                            <td><?= $h['estado_proceso'] ?></td>
                            <td><?= $h['aplicacion_pago'] ?></td>
                            <td><?= $h['observaciones'] ?></td>
                            <td><?= $h['numero_orden'] ?></td>
                            <td><?= $h['creado_en'] ?></td>

                            <td class="text-center">
                                <span style="display:inline-block;width:18px;height:18px;border-radius:50%;background:#28a745;"></span>
                            </td>

                            <td>
                                <button 
                                    class="btn btn-primary ver-fases" 
                                    data-fases='<?= htmlspecialchars(json_encode($h["fases_json"]), ENT_QUOTES) ?>'>
                                    Ver Fases
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>

                <?php else: ?>
                    <tr>
                        <td colspan="24" class="text-center">No hay registros en el historial</td>
                    </tr>

                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>


<!-- Modal -->
<div id="modalFases" class="modal" style="display:none;">
    <div class="modal-content" style="padding:20px; width:600px;">
        <span id="closeFases" style="float:right; cursor:pointer;">&times;</span>
        <h3>Fases de la Incapacidad</h3>
        <div id="listaFases"></div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function(){

    const modal = document.getElementById('modalFases');
    const close = document.getElementById('closeFases');
    const lista = document.getElementById('listaFases');

    document.querySelectorAll('.ver-fases').forEach(btn => {

        btn.addEventListener('click', () => {

            const fases = JSON.parse(btn.dataset.fases || "[]");

            lista.innerHTML = "";

            if (fases.length === 0) {
                lista.innerHTML = `<p>No hay fases registradas.</p>`;
            } else {

                fases.forEach(f => {
                    lista.innerHTML += `
                        <div style="border-bottom:1px solid #ddd;padding:8px;margin:8px 0;">
                            <strong>${f.nombre_fase}</strong>
                            <p>${f.descripcion ?? ""}</p>

                            ${f.evidencia 
                                ? `<a href="/incapacidades/uploads/fases/${f.evidencia}" target="_blank">Ver evidencia</a>` 
                                : `Sin evidencia`
                            }

                            <br>
                            <small>Actualizado: ${f.fecha_actualizacion ?? "---"}</small>
                        </div>
                    `;
                });
            }

            modal.style.display = "flex";
        });
    });

    close.addEventListener('click', () => modal.style.display = 'none');

    window.addEventListener('click', e => {
        if (e.target === modal) modal.style.display = 'none';
    });
});
</script>
