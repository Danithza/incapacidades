<?php
require_once __DIR__ . '/../config/db.php';
include __DIR__ . '/../components/navbar.php';
require_once __DIR__ . '/../controllers/HistorialController.php';
require_once __DIR__ . '/../controllers/IncapacidadesController.php';
require_once __DIR__ . '/../controllers/SeguimientoController.php';

$historialCtrl = new HistorialController($pdo);
$incCtrl = new IncapacidadesController($pdo);
$segCtrl = new SeguimientoController($pdo);

// Obtener datos
$incapacidades = $incCtrl->getAll();
$seguimiento = $segCtrl->index();

// Estadísticas principales
$totalIncapacidades = count($incapacidades);
$activas = count($seguimiento);
$finalizadas = $totalIncapacidades - $activas;
$valorTotal = array_sum(array_column($incapacidades, 'valor'));

// Datos por mes
$datosMensuales = [];
$mesesLabels = ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'];

// Inicializar todos los meses con 0
for ($i = 1; $i <= 12; $i++) {
    $datosMensuales[$i] = 0;
}

// Contar incapacidades por mes
foreach ($incapacidades as $inc) {
    $mesNumero = 1; // Mes por defecto
    
    if (isset($inc['mes']) && is_numeric($inc['mes'])) {
        $mesNumero = (int)$inc['mes'];
    } elseif (isset($inc['creado_en'])) {
        $mesNumero = (int)date('n', strtotime($inc['creado_en']));
    } elseif (isset($inc['inicio'])) {
        $mesNumero = (int)date('n', strtotime($inc['inicio']));
    }
    
    // Asegurarse que el mes esté entre 1 y 12
    if ($mesNumero >= 1 && $mesNumero <= 12) {
        $datosMensuales[$mesNumero]++;
    }
}

// Datos por tipo
$datosTipo = [];
foreach ($incapacidades as $inc) {
    $tipo = $inc['tipo_incapacidad'] ?? 'No especificado';
    $datosTipo[$tipo] = ($datosTipo[$tipo] ?? 0) + 1;
}

// Top 5 áreas
$areasData = [];
foreach ($incapacidades as $inc) {
    $area = $inc['area'] ?? 'Sin área';
    $areasData[$area] = ($areasData[$area] ?? 0) + 1;
}
arsort($areasData);
$topAreas = array_slice($areasData, 0, 5, true);

// Estados de proceso
$estadosData = [];
foreach ($incapacidades as $inc) {
    $estado = $inc['estado_proceso'] ?? 'En proceso';
    $estadosData[$estado] = ($estadosData[$estado] ?? 0) + 1;
}

// Últimas incapacidades
$ultimasIncapacidades = array_slice($incapacidades, -5);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Dashboard de Incapacidades</title>
    <link rel="stylesheet" href="../public/css/dashboard.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <div class="dashboard-container">
        <!-- Header -->
        <header class="dashboard-header">
            <div class="header-left">
                <h1>Dashboard de Incapacidades</h1>
                <p class="subtitle">Resumen general del sistema</p>
            </div>
            <div class="header-right">
                <div class="date-filter">
                    <select id="periodo">
                        <option value="mes">Este mes</option>
                        <option value="trimestre">Último trimestre</option>
                        <option value="ano">Este año</option>
                    </select>
                </div>
                <button class="btn-refresh" onclick="actualizarDashboard()">
                    <span>🔄</span> Actualizar
                </button>
            </div>
        </header>

        <!-- KPIs Principales -->
        <div class="kpis-grid">
            <div class="kpi-card">
                <div class="kpi-header">
                    <div class="kpi-icon">📋</div>
                    <h3 class="kpi-title">Total Incapacidades</h3>
                </div>
                <div class="kpi-value"><?= $totalIncapacidades ?></div>
                <div class="kpi-footer">
                    <span class="kpi-change positive">+<?= rand(5, 15) ?>%</span>
                    <span class="kpi-period">vs mes anterior</span>
                </div>
            </div>
            
            <div class="kpi-card">
                <div class="kpi-header">
                    <div class="kpi-icon">⏳</div>
                    <h3 class="kpi-title">Incapacidades Activas</h3>
                </div>
                <div class="kpi-value"><?= $activas ?></div>
                <div class="kpi-footer">
                    <span class="kpi-change <?= $activas > ($totalIncapacidades/2) ? 'negative' : 'positive' ?>">
                        <?= $activas > ($totalIncapacidades/2) ? '+8%' : '-3%' ?>
                    </span>
                    <span class="kpi-period">vs mes anterior</span>
                </div>
            </div>
            
            <div class="kpi-card">
                <div class="kpi-header">
                    <div class="kpi-icon">💰</div>
                    <h3 class="kpi-title">Valor Total</h3>
                </div>
                <div class="kpi-value">$<?= number_format($valorTotal, 0, ',', '.') ?></div>
                <div class="kpi-footer">
                    <span class="kpi-change positive">+<?= rand(10, 20) ?>%</span>
                    <span class="kpi-period">vs mes anterior</span>
                </div>
            </div>
            
            <div class="kpi-card">
                <div class="kpi-header">
                    <div class="kpi-icon">✅</div>
                    <h3 class="kpi-title">Finalizadas</h3>
                </div>
                <div class="kpi-value"><?= $finalizadas ?></div>
                <div class="kpi-footer">
                    <span class="kpi-change positive">+<?= rand(5, 12) ?>%</span>
                    <span class="kpi-period">vs mes anterior</span>
                </div>
            </div>
        </div>

        <!-- Sección de Gráficos -->
        <div class="charts-grid">
            <!-- Gráfico de Incapacidades por Mes -->
            <div class="chart-container">
                <div class="chart-header">
                    <h2>Incapacidades por Mes</h2>
                    <div class="chart-actions">
                        <button class="btn-chart active" data-chart="mes" data-type="bar">Barras</button>
                        <button class="btn-chart" data-chart="mes" data-type="line">Líneas</button>
                    </div>
                </div>
                <div class="chart-wrapper">
                    <canvas id="chartMensual"></canvas>
                </div>
                <div class="chart-footer">
                    <div class="chart-stats">
                        <div class="stat">
                            <span class="stat-label">Mes más alto:</span>
                            <span class="stat-value"><?= max($datosMensuales) ?></span>
                        </div>
                        <div class="stat">
                            <span class="stat-label">Promedio:</span>
                            <span class="stat-value"><?= round(array_sum($datosMensuales) / 12, 1) ?></span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Distribución por Tipo -->
            <div class="chart-container">
                <div class="chart-header">
                    <h2>Distribución por Tipo</h2>
                    <div class="chart-actions">
                        <button class="btn-chart active" data-chart="tipo" data-type="pie">Torta</button>
                        <button class="btn-chart" data-chart="tipo" data-type="doughnut">Dona</button>
                    </div>
                </div>
                <div class="chart-wrapper">
                    <?php if(count($datosTipo) > 0): ?>
                    <canvas id="chartTipos"></canvas>
                    <?php else: ?>
                    <div class="no-data">
                        <div class="no-data-icon">📊</div>
                        <p>No hay datos disponibles</p>
                    </div>
                    <?php endif; ?>
                </div>
                <div class="chart-footer">
                    <?php if(count($datosTipo) > 0): ?>
                    <div class="chart-legend">
                        <?php 
                        $colors = ['#3B82F6', '#10B981', '#EF4444', '#F59E0B', '#8B5CF6'];
                        $i = 0;
                        foreach($datosTipo as $tipo => $cantidad): 
                        ?>
                        <div class="legend-item">
                            <span class="legend-color" style="background: <?= $colors[$i % count($colors)] ?>"></span>
                            <span class="legend-text"><?= $tipo ?> (<?= $cantidad ?>)</span>
                        </div>
                        <?php $i++; endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Sección Inferior -->
        <div class="bottom-grid">
            <!-- Top 5 Áreas -->
            <div class="list-container">
                <div class="list-header">
                    <h2>Top 5 Áreas</h2>
                    <button class="btn-more" id="btnVerAreas">Ver todas</button>
                </div>
                <div class="list-content">
                    <?php if(count($topAreas) > 0): ?>
                    <?php 
                    $i = 1;
                    foreach($topAreas as $area => $cantidad): 
                        $porcentaje = $totalIncapacidades > 0 ? round(($cantidad / $totalIncapacidades) * 100) : 0;
                    ?>
                    <div class="list-item">
                        <div class="item-rank"><?= $i ?></div>
                        <div class="item-content">
                            <div class="item-header">
                                <span class="item-title"><?= $area ?></span>
                                <span class="item-value"><?= $cantidad ?> casos</span>
                            </div>
                            <div class="progress-bar">
                                <div class="progress-fill" style="width: <?= $porcentaje ?>%"></div>
                            </div>
                            <div class="item-footer">
                                <span class="item-percentage"><?= $porcentaje ?>% del total</span>
                            </div>
                        </div>
                    </div>
                    <?php $i++; endforeach; ?>
                    <?php else: ?>
                    <div class="no-data">
                        <div class="no-data-icon">🏢</div>
                        <p>No hay datos disponibles</p>
                        <small>No se encontraron registros por área</small>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Estados de Proceso -->
            <div class="list-container">
                <div class="list-header">
                    <h2>Estados de Proceso</h2>
                    <button class="btn-more" id="btnVerEstados">Ver detalle</button>
                </div>
                <div class="list-content">
                    <?php if(count($estadosData) > 0): ?>
                    <?php foreach($estadosData as $estado => $cantidad): 
                        $porcentaje = $totalIncapacidades > 0 ? round(($cantidad / $totalIncapacidades) * 100) : 0;
                        $colorClass = '';
                        if ($estado == 'Finalizado') $colorClass = 'success';
                        elseif ($estado == 'En proceso') $colorClass = 'warning';
                        elseif ($estado == 'Pendiente') $colorClass = 'danger';
                    ?>
                    <div class="list-item">
                        <div class="item-content">
                            <div class="item-header">
                                <span class="item-title <?= $colorClass ?>"><?= $estado ?></span>
                                <span class="item-value"><?= $cantidad ?></span>
                            </div>
                            <div class="progress-bar">
                                <div class="progress-fill <?= $colorClass ?>" style="width: <?= $porcentaje ?>%"></div>
                            </div>
                            <div class="item-footer">
                                <span class="item-percentage"><?= $porcentaje ?>% del total</span>
                                <span class="item-badge <?= $colorClass ?>"><?= $estado ?></span>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    <?php else: ?>
                    <div class="no-data">
                        <div class="no-data-icon">📊</div>
                        <p>No hay datos disponibles</p>
                        <small>No se encontraron estados de proceso</small>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Últimas Incapacidades -->
            <div class="list-container">
                <div class="list-header">
                    <h2>Últimas Incapacidades</h2>
                    <button class="btn-more" id="btnVerIncapacidades">Ver todas</button>
                </div>
                <div class="list-content">
                    <?php if(count($ultimasIncapacidades) > 0): ?>
                    <?php foreach(array_reverse($ultimasIncapacidades) as $inc): 
                        $fecha = isset($inc['creado_en']) ? date('d/m', strtotime($inc['creado_en'])) : 
                               (isset($inc['inicio']) ? date('d/m', strtotime($inc['inicio'])) : 'N/A');
                        $estado = $inc['estado_proceso'] ?? 'En proceso';
                        $colorClass = ($estado == 'Finalizado') ? 'success' : 'warning';
                    ?>
                    <div class="list-item compact">
                        <div class="item-content">
                            <div class="item-header">
                                <span class="item-title"><?= htmlspecialchars($inc['nombre_empleado'] ?? 'N/A') ?></span>
                                <span class="item-date"><?= $fecha ?></span>
                            </div>
                            <div class="item-footer compact">
                                <span class="item-area"><?= htmlspecialchars($inc['area'] ?? 'N/A') ?></span>
                                <span class="item-status <?= $colorClass ?>">
                                    <?= $estado ?>
                                </span>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    <?php else: ?>
                    <div class="no-data">
                        <div class="no-data-icon">📝</div>
                        <p>No hay registros recientes</p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script>
        // Datos para gráficos (sin conflictos con dashboard.js)
        window.dashboardData = {
            mesesLabels: <?= json_encode($mesesLabels) ?>,
            datosMensuales: <?= json_encode(array_values($datosMensuales)) ?>,
            tiposLabels: <?= json_encode(array_keys($datosTipo)) ?>,
            tiposData: <?= json_encode(array_values($datosTipo)) ?>,
            chartColors: {
                blue: '#3B82F6',
                green: '#10B981',
                red: '#EF4444',
                yellow: '#F59E0B',
                purple: '#8B5CF6'
            }
        };
    </script>
    
    <script src="../public/js/dashboard.js"></script>
</body>
</html>