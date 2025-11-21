<?php
include __DIR__ . '/../components/navbar.php';
require_once __DIR__ . '/../controllers/HistorialController.php';
require_once __DIR__ . '/../config/db.php';

$controller = new HistorialController($pdo);
$historial = $controller->obtenerHistorial();

// Datos para las tarjetas de resumen (simulados - deberías reemplazar con datos reales)
$totalIncapacidades = count($historial);
$incapacidadesActivas = array_filter($historial, function($h) {
    return $h['estado_proceso'] === 'Activo';
});
$totalActivas = count($incapacidadesActivas);
$totalValor = array_sum(array_column($historial, 'valor'));
$promedioDias = $totalIncapacidades > 0 ? 
    array_sum(array_column($historial, 'dias_incapacidad')) / $totalIncapacidades : 0;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Gestión de Incapacidades</title>
        <link rel="stylesheet" href="../public/css/dashboard.css">
</head>
<body>
    <div class="dashboard-container">
        <!-- Header del Dashboard -->
        <div class="dashboard-header">
            <h1>Dashboard de Incapacidades</h1>
            <div class="header-actions">
                <button class="btn btn-primary" id="btnExport">
                    <i class="icon-download"></i> Exportar Reporte
                </button>
                <button class="btn btn-secondary" id="btnRefresh">
                    <i class="icon-refresh"></i> Actualizar
                </button>
            </div>
        </div>

        <!-- Tarjetas de Resumen -->
        <div class="summary-cards">
            <div class="card card-primary">
                <div class="card-icon">
                    <i class="icon-document"></i>
                </div>
                <div class="card-content">
                    <h3><?= $totalIncapacidades ?></h3>
                    <p>Total Incapacidades</p>
                </div>
            </div>

            <div class="card card-warning">
                <div class="card-icon">
                    <i class="icon-active"></i>
                </div>
                <div class="card-content">
                    <h3><?= $totalActivas ?></h3>
                    <p>Incapacidades Activas</p>
                </div>
            </div>

            <div class="card card-success">
                <div class="card-icon">
                    <i class="icon-money"></i>
                </div>
                <div class="card-content">
                    <h3>$<?= number_format($totalValor, 0, ',', '.') ?></h3>
                    <p>Valor Total</p>
                </div>
            </div>

            <div class="card card-info">
                <div class="card-icon">
                    <i class="icon-calendar"></i>
                </div>
                <div class="card-content">
                    <h3><?= number_format($promedioDias, 1) ?></h3>
                    <p>Promedio de Días</p>
                </div>
            </div>
        </div>

        <!-- Gráficos y Estadísticas -->
        <div class="dashboard-content">
            <div class="row">
                <div class="col-md-8">
                    <div class="chart-container">
                        <div class="chart-header">
                            <h3>Incapacidades por Mes</h3>
                            <select id="chartPeriod" class="form-select">
                                <option value="3m">Últimos 3 meses</option>
                                <option value="6m">Últimos 6 meses</option>
                                <option value="1y">Último año</option>
                            </select>
                        </div>
                        <canvas id="incapacityChart"></canvas>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="stats-container">
                        <h3>Distribución por Tipo</h3>
                        <canvas id="typeChart"></canvas>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6">
                    <div class="stats-container">
                        <h3>Top Áreas con Más Incapacidades</h3>
                        <div id="areasChart"></div>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="stats-container">
                        <h3>Estado de Procesos</h3>
                        <div id="statusChart"></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tabla de Historial -->
        <div class="table-section">
            <div class="section-header">
                <h2>Historial de Incapacidades</h2>
                <div class="table-actions">
                    <input type="text" id="searchInput" placeholder="Buscar..." class="form-control">
                    <button class="btn btn-outline" id="btnFilter">
                        <i class="icon-filter"></i> Filtros
                    </button>
                </div>
            </div>

            <div class="table-responsive">
                <table class="data-table" id="historialTable">
                    <thead>
                        <tr>
                            <th data-sort="id">ID</th>
                            <th data-sort="numero_incapacidad">Número</th>
                            <th data-sort="nombre_empleado">Empleado</th>
                            <th data-sort="cedula">Cédula</th>
                            <th data-sort="area">Área</th>
                            <th data-sort="tipo_incapacidad">Tipo</th>
                            <th data-sort="eps_arl">EPS/ARL</th>
                            <th data-sort="inicio">Inicio</th>
                            <th data-sort="termina">Termina</th>
                            <th data-sort="dias_incapacidad">Días</th>
                            <th data-sort="valor">Valor</th>
                            <th data-sort="estado_proceso">Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($historial)): ?>
                            <?php foreach ($historial as $h): ?>
                                <tr>
                                    <td><?= $h['id'] ?></td>
                                    <td><?= $h['numero_incapacidad'] ?></td>
                                    <td><?= $h['nombre_empleado'] ?></td>
                                    <td><?= $h['cedula'] ?></td>
                                    <td><?= $h['area'] ?></td>
                                    <td>
                                        <span class="badge badge-type-<?= strtolower(str_replace(' ', '-', $h['tipo_incapacidad'])) ?>">
                                            <?= $h['tipo_incapacidad'] ?>
                                        </span>
                                    </td>
                                    <td><?= $h['eps_arl'] ?></td>
                                    <td><?= date('d/m/Y', strtotime($h['inicio'])) ?></td>
                                    <td><?= date('d/m/Y', strtotime($h['termina'])) ?></td>
                                    <td><?= $h['dias_incapacidad'] ?></td>
                                    <td>$<?= number_format($h['valor'], 0, ',', '.') ?></td>
                                    <td>
                                        <span class="status-badge status-<?= strtolower($h['estado_proceso']) ?>">
                                            <?= $h['estado_proceso'] ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="action-buttons">
                                            <button class="btn-action btn-view" 
                                                    data-fases='<?= htmlspecialchars(json_encode($h["fases_json"]), ENT_QUOTES) ?>'
                                                    title="Ver detalles">
                                                <i class="icon-eye"></i>
                                            </button>
                                            <button class="btn-action btn-edit" title="Editar">
                                                <i class="icon-edit"></i>
                                            </button>
                                            <button class="btn-action btn-delete" title="Eliminar">
                                                <i class="icon-delete"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="13" class="no-data">No hay registros en el historial</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Paginación -->
            <div class="pagination">
                <button class="btn-pagination" id="prevPage">Anterior</button>
                <div class="page-numbers" id="pageNumbers"></div>
                <button class="btn-pagination" id="nextPage">Siguiente</button>
            </div>
        </div>
    </div>

    <!-- Modal de Detalles -->
    <div id="detailModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Detalles de Incapacidad</h3>
                <span class="close-modal">&times;</span>
            </div>
            <div class="modal-body" id="modalBody">
                <!-- Contenido dinámico -->
            </div>
        </div>
    </div>

    <script src="../public/js/dashboard.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</body>
</html>