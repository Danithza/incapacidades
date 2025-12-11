<?php
include __DIR__ . '/../components/navbar.php';
require_once __DIR__ . '/../controllers/HistorialController.php';
require_once __DIR__ . '/../config/db.php';

$controller = new HistorialController($pdo);
$historial = $controller->obtenerHistorial();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Historial de Incapacidades</title>
    <link rel="stylesheet" href="/incapacidades/public/css/historial.css">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* Pequeño ajuste para que los valores nulos no rompan el layout */
        .text-muted { color: #6c757d; }
        .registro-id { font-weight: 600; }
        .valor-real, .valor-aprox { display:block; }
    </style>
</head>
<body>
    <div class="table-container">
        <div class="table-toolbar">
            <div class="search-box">
                <i class="fas fa-search search-icon"></i>
                <input type="text" id="searchInput" placeholder="Buscar en el historial...">
            </div>
            <div class="table-info">
                Mostrando <span id="rowCount"><?= count($historial) ?></span> registros
            </div>
        </div>

        <div class="table-responsive">
            <table class="historial-table" id="historialTable">
                <thead>
                    <tr>

                        <th>
                            <div class="th-content">
                                <i class="fas fa-id-card"></i>
                                <span>Empleado</span>
                            </div>
                        </th>
                        <th>
                            <div class="th-content">
                                <i class="fas fa-fingerprint"></i>
                                <span>Cédula</span>
                            </div>
                        </th>
                        <th>
                            <div class="th-content">
                                <i class="fas fa-file-medical"></i>
                                <span>N° Incapacidad</span>
                            </div>
                        </th>
                        <th>
                            <div class="th-content">
                                <i class="fas fa-calendar-alt"></i>
                                <span>Mes</span>
                            </div>
                        </th>
                        <th>
                            <div class="th-content">
                                <i class="fas fa-building"></i>
                                <span>Área</span>
                            </div>
                        </th>
                        <th>
                            <div class="th-content">
                                <i class="fas fa-stethoscope"></i>
                                <span>Diagnóstico</span>
                            </div>
                        </th>
                        <th>
                            <div class="th-content">
                                <i class="fas fa-heartbeat"></i>
                                <span>Tipo</span>
                            </div>
                        </th>
                        <th>
                            <div class="th-content">
                                <i class="fas fa-hospital"></i>
                                <span>EPS/ARL</span>
                            </div>
                        </th>
                        <th>
                            <div class="th-content">
                                <i class="fas fa-calendar-day"></i>
                                <span>Periodo</span>
                            </div>
                        </th>
                        <th>
                            <div class="th-content">
                                <i class="fas fa-clock"></i>
                                <span>Días</span>
                            </div>
                        </th>
                        <th>
                            <div class="th-content">
                                <i class="fas fa-money-bill-wave"></i>
                                <span>Valor</span>
                            </div>
                        </th>
                        <th>
                            <div class="th-content">
                                <i class="fas fa-tasks"></i>
                                <span>Estado</span>
                            </div>
                        </th>
                        <th>
                            <div class="th-content">
                                <i class="fas fa-file-invoice-dollar"></i>
                                <span>Pago</span>
                            </div>
                        </th>
                        <th class="sticky-col-right">
                            <div class="th-content">
                                <i class="fas fa-cogs"></i>
                                <span>Acciones</span>
                            </div>
                        </th>
                    </tr>
                </thead>

                <tbody>
                    <?php if (!empty($historial)): ?>
                        <?php foreach ($historial as $h): ?>

                            <?php
                            // Helpers para mostrar seguros
                            $incapacidad_id = isset($h['incapacidad_id']) ? $h['incapacidad_id'] : '-';
                            $numero_incapacidad = isset($h['numero_incapacidad']) ? $h['numero_incapacidad'] : '-';
                            $mes = isset($h['mes']) ? $h['mes'] : '-';
                            $nombre_empleado = isset($h['nombre_empleado']) ? $h['nombre_empleado'] : '-';
                            $cedula = isset($h['cedula']) ? $h['cedula'] : '-';
                            $area = isset($h['area']) ? $h['area'] : '-';
                            $cod_diagnostico = isset($h['cod_diagnostico']) ? $h['cod_diagnostico'] : '-';
                            $diagnostico = isset($h['diagnostico']) ? $h['diagnostico'] : '-';
                            $tipo_incapacidad = isset($h['tipo_incapacidad']) ? $h['tipo_incapacidad'] : '-';
                            $eps_arl = isset($h['eps_arl']) ? $h['eps_arl'] : '-';
                            $inicio = (!empty($h['inicio']) && strtotime($h['inicio']) !== false) ? date('d/m/y', strtotime($h['inicio'])) : '-';
                            $termina = (!empty($h['termina']) && strtotime($h['termina']) !== false) ? date('d/m/y', strtotime($h['termina'])) : '-';
                            $dias_incapacidad = isset($h['dias_incapacidad']) ? $h['dias_incapacidad'] : '-';
                            $dias_a_cargo_entidad = isset($h['dias_a_cargo_entidad']) ? $h['dias_a_cargo_entidad'] : '-';
                            // Evitar pasar null a number_format: verificamos y casteamos
                            $valor_real = (isset($h['valor']) && $h['valor'] !== null && $h['valor'] !== '') ? '$' . number_format((float)$h['valor'], 0, ',', '.') : '<span class="text-muted">-</span>';
                            $valor_aprox = (isset($h['valor_aprox']) && $h['valor_aprox'] !== null && $h['valor_aprox'] !== '') ? '≈ $' . number_format((float)$h['valor_aprox'], 0, ',', '.') : '<span class="text-muted">-</span>';
                            $estado_proceso = isset($h['estado_proceso']) ? $h['estado_proceso'] : '-';
                            $aplicacion_pago = isset($h['aplicacion_pago']) ? $h['aplicacion_pago'] : '-';
                            $numero_orden = isset($h['numero_orden']) ? $h['numero_orden'] : '';
                            $observaciones = isset($h['observaciones']) ? $h['observaciones'] : '';
                            $fases_json = isset($h['fases_json']) ? $h['fases_json'] : null;

                            // data-search completo (incluye todos los campos solicitados)
                            $data_search = strtolower(htmlspecialchars(
                                $incapacidad_id . ' ' .
                                $numero_incapacidad . ' ' .
                                $mes . ' ' .
                                $nombre_empleado . ' ' .
                                $cedula . ' ' .
                                $area . ' ' .
                                $cod_diagnostico . ' ' .
                                $diagnostico . ' ' .
                                $tipo_incapacidad . ' ' .
                                $eps_arl . ' ' .
                                $inicio . ' ' .
                                $termina . ' ' .
                                $dias_incapacidad . ' ' .
                                $dias_a_cargo_entidad . ' ' .
                                (is_string($h['valor']) || is_numeric($h['valor']) ? $h['valor'] : '') . ' ' .
                                $observaciones . ' ' .
                                $estado_proceso
                            ));
                            ?>

                            <tr class="historial-row" data-search="<?= $data_search ?>">

                                <!-- NUEVO TD ID -->

                                <td>
                                    <div class="empleado-info">
                                        <div class="empleado-nombre"><?= htmlspecialchars($nombre_empleado) ?></div>
                                        <small class="text-muted">ID: <?= htmlspecialchars($incapacidad_id) ?></small>
                                    </div>
                                </td>

                                <td>
                                    <span class="cedula"><?= htmlspecialchars($cedula) ?></span>
                                </td>

                                <td>
                                    <span class="badge badge-incapacidad"><?= htmlspecialchars($numero_incapacidad) ?></span>
                                </td>

                                <td>
                                    <span class="mes"><?= htmlspecialchars($mes) ?></span>
                                </td>

                                <td>
                                    <span class="badge badge-area"><?= htmlspecialchars($area) ?></span>
                                </td>

                                <td>
                                    <div class="diagnostico-info">
                                        <div class="cod-diagnostico"><?= htmlspecialchars($cod_diagnostico) ?></div>
                                        <div class="diagnostico-desc" title="<?= htmlspecialchars($diagnostico) ?>">
                                            <?= strlen($diagnostico) > 30 ? htmlspecialchars(substr($diagnostico, 0, 30)) . '...' : htmlspecialchars($diagnostico) ?>
                                        </div>
                                    </div>
                                </td>

                                <td>
                                    <span class="badge badge-tipo"><?= htmlspecialchars($tipo_incapacidad) ?></span>
                                </td>

                                <td>
                                    <span class="eps-arl"><?= htmlspecialchars($eps_arl) ?></span>
                                </td>

                                <td>
                                    <div class="periodo">
                                        <div class="fecha-inicio">
                                            <i class="fas fa-play-circle"></i>
                                            <?= $inicio ?>
                                        </div>
                                        <div class="fecha-fin">
                                            <i class="fas fa-stop-circle"></i>
                                            <?= $termina ?>
                                        </div>
                                    </div>
                                </td>

                                <td>
                                    <div class="dias-container">
                                        <div class="dias-total">
                                            <i class="fas fa-calendar-check"></i>
                                            <?= htmlspecialchars($dias_incapacidad) ?> <?= $dias_incapacidad !== '-' ? 'días' : '' ?>
                                        </div>
                                        <div class="dias-entidad">
                                            <small><?= $dias_a_cargo_entidad !== '-' ? htmlspecialchars($dias_a_cargo_entidad) . ' días entidad' : '<span class="text-muted">-</span>' ?></small>
                                        </div>
                                    </div>
                                </td>

                                <td>
                                    <div class="valor-container">
                                        <div class="valor-real">
                                            <i class="fas fa-dollar-sign"></i>
                                            <?= $valor_real ?>
                                        </div>
                                        <div class="valor-aprox">
                                            <small><?= $valor_aprox ?></small>
                                        </div>
                                    </div>
                                </td>

                                <td>
                                    <div class="estado-container">
                                        <div class="estado-proceso">
                                            <span class="badge badge-proceso"><?= htmlspecialchars($estado_proceso) ?></span>
                                        </div>
                                        <div class="creado-en">
                                            <?php if (!empty($h['creado_en']) && strtotime($h['creado_en']) !== false): ?>
                                                <small><?= date('d/m/y', strtotime($h['creado_en'])) ?></small>
                                            <?php else: ?>
                                                <small class="text-muted">-</small>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </td>

                                <td>
                                    <div class="pago-info">
                                        <div class="aplicacion-pago">
                                            <span class="badge badge-pago"><?= htmlspecialchars($aplicacion_pago) ?></span>
                                        </div>
                                        <?php if (!empty($numero_orden)): ?>
                                            <div class="numero-orden">
                                                <small>Orden: <?= htmlspecialchars($numero_orden) ?></small>
                                            </div>
                                        <?php else: ?>
                                            <div class="numero-orden">
                                                <small class="text-muted">-</small>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </td>

                                <td class="sticky-col-right">
                                    <div class="action-buttons">

                                        <button class="btn btn-view-fases ver-fases"
                                            data-fases='<?= htmlspecialchars(json_encode($fases_json), ENT_QUOTES) ?>'
                                            data-empleado="<?= htmlspecialchars($nombre_empleado) ?>"
                                            data-incapacidad="<?= htmlspecialchars($numero_incapacidad) ?>">
                                            <i class="fas fa-layer-group"></i>
                                            <span>Ver Fases</span>
                                        </button>

                                        <?php if (!empty($observaciones)): ?>
                                            <button class="btn btn-info btn-sm btn-observaciones"
                                                    title="Ver observaciones"
                                                    data-observaciones="<?= htmlspecialchars($observaciones) ?>">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </td>

                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="15" class="text-center empty-state">
                                <div class="empty-icon">
                                    <i class="fas fa-inbox"></i>
                                </div>
                                <h3>No hay registros en el historial</h3>
                                <p>No se han encontrado incapacidades procesadas.</p>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal Fases -->
<div id="modalFases" class="modal">
    <div class="modal-overlay"></div>
    <div class="modal-container">
        <div class="modal-header">
            <h3 class="modal-title">
                <i class="fas fa-layer-group"></i>
                <span id="modalTitle">Fases de la Incapacidad</span>
            </h3>
            <button id="closeFases" class="modal-close">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <div class="modal-body">
            <div class="incapacidad-info">
                <div class="info-item">
                    <i class="fas fa-user"></i>
                    <span id="modalEmpleado"></span>
                </div>
                <div class="info-item">
                    <i class="fas fa-file-medical"></i>
                    <span id="modalIncapacidad"></span>
                </div>
            </div>

            <div id="listaFases" class="fases-container"></div>

            <div id="noFases" class="no-fases">
                <i class="fas fa-inbox"></i>
                <h4>No hay fases registradas</h4>
                <p>Esta incapacidad no tiene fases registradas en el sistema.</p>
            </div>
        </div>
    </div>
</div>

<!-- Modal Observaciones -->
<div id="modalObservaciones" class="modal modal-sm">
    <div class="modal-overlay"></div>
    <div class="modal-container">
        <div class="modal-header">
            <h3 class="modal-title">
                <i class="fas fa-comment-alt"></i>
                Observaciones
            </h3>
            <button class="modal-close close-observaciones">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <div class="modal-body">
            <div class="observaciones-content" id="observacionesContent"></div>
        </div>
    </div>
</div>

<script src="/incapacidades/public/js/historial.js"></script>
</body>
</html>
