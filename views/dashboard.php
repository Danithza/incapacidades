<?php
include __DIR__ . '/../components/navbar.php';
require_once __DIR__ . '/../controllers/HistorialController.php';
require_once __DIR__ . '/../config/db.php';

$controller = new HistorialController($pdo);
$historial = $controller->obtenerHistorial();

// ============================
// MÉTRICAS PRINCIPALES
// ============================
$totalIncapacidades = count($historial);

$incapacidadesActivas = array_filter($historial, function ($h) {
    return isset($h['estado']) && $h['estado'] === 'Activo';
});
$totalActivas = count($incapacidadesActivas);

$totalValor = array_sum(array_column($historial, 'valor'));

$promedioDias = $totalIncapacidades > 0
    ? array_sum(array_column($historial, 'dias_incapacidad')) / $totalIncapacidades
    : 0;

// ============================
// DATOS PARA GRÁFICOS
// ============================

// Tipos de incapacidad
$tiposIncapacidad = [];
foreach ($historial as $h) {
    if (!empty($h['tipo_incapacidad'])) {
        $tipo = $h['tipo_incapacidad'];
        $tiposIncapacidad[$tipo] = ($tiposIncapacidad[$tipo] ?? 0) + 1;
    }
}

// Áreas
$areasCount = [];
foreach ($historial as $h) {
    if (!empty($h['area'])) {
        $area = $h['area'];
        $areasCount[$area] = ($areasCount[$area] ?? 0) + 1;
    }
}
arsort($areasCount);

// Estados
$estadosCount = [];
foreach ($historial as $h) {
    if (!empty($h['estado'])) {
        $estado = $h['estado'];
        $estadosCount[$estado] = ($estadosCount[$estado] ?? 0) + 1;
    }
}

// Incapacidades por mes (últimos 6 meses)
$meses = [];
for ($i = 5; $i >= 0; $i--) {
    $key = date('Y-m', strtotime("-$i months"));
    $meses[$key] = 0;
}

foreach ($historial as $h) {
    if (!empty($h['inicio'])) {
        $mesInicio = date('Y-m', strtotime($h['inicio']));
        if (isset($meses[$mesInicio])) {
            $meses[$mesInicio]++;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Dashboard - Gestión de Incapacidades</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <link rel="stylesheet" href="../public/css/dashboard.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>

<div class="dashboard-container">

    <h1 class="dashboard-title">Dashboard de Incapacidades</h1>

    <!-- ============================ -->
    <!-- TARJETAS RESUMEN -->
    <!-- ============================ -->
    <div class="summary-cards">

        <div class="card card-primary">
            <div class="card-icon">📄</div>
            <div class="card-content">
                <h3><?= $totalIncapacidades ?></h3>
                <p>Total Incapacidades</p>
            </div>
        </div>

        <div class="card card-warning">
            <div class="card-icon">🟢</div>
            <div class="card-content">
                <h3><?= $totalActivas ?></h3>
                <p>Incapacidades Activas</p>
            </div>
        </div>

        <div class="card card-success">
            <div class="card-icon">💰</div>
            <div class="card-content">
                <h3>$<?= number_format($totalValor, 0, ',', '.') ?></h3>
                <p>Valor Total</p>
            </div>
        </div>

        <div class="card card-info">
            <div class="card-icon">📅</div>
            <div class="card-content">
                <h3><?= number_format($promedioDias, 1) ?></h3>
                <p>Promedio de Días</p>
            </div>
        </div>

    </div>

    <!-- ============================ -->
    <!-- GRÁFICOS -->
    <!-- ============================ -->
    <div class="dashboard-content">

        <div class="row">

            <div class="col-md-8">
                <div class="chart-container">
                    <h3>Incapacidades por Mes</h3>
                    <canvas id="incapacityChart" height="260"></canvas>
                </div>
            </div>

            <div class="col-md-4">
                <div class="chart-container">
                    <h3>Distribución por Tipo</h3>

                    <?php if (!empty($tiposIncapacidad)): ?>
                        <canvas id="typeChart" height="260"></canvas>
                    <?php else: ?>
                        <p class="no-data">No hay datos disponibles</p>
                    <?php endif; ?>
                </div>
            </div>

        </div>

        <div class="row">

            <!-- TOP ÁREAS -->
            <div class="col-md-6">
                <div class="stats-container">
                    <h3>Top 5 Áreas</h3>

                    <?php if (!empty($areasCount)): ?>
                        <?php foreach (array_slice($areasCount, 0, 5) as $area => $count): ?>
                            <div class="area-item">
                                <span><?= htmlspecialchars($area) ?></span>
                                <strong><?= $count ?></strong>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p class="no-data">No hay datos</p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- ESTADOS -->
            <div class="col-md-6">
                <div class="stats-container">
                    <h3>Estados de Proceso</h3>

                    <?php if (!empty($estadosCount)): ?>
                        <?php foreach ($estadosCount as $estado => $count): ?>
                            <div class="status-item">
                                <span><?= $estado ?></span>
                                <strong><?= $count ?></strong>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p class="no-data">No hay datos</p>
                    <?php endif; ?>
                </div>
            </div>

        </div>
    </div>

</div>

<!-- ============================ -->
<!-- DATOS PARA JS (ANTES DEL JS) -->
<!-- ============================ -->
<script>
    window.chartData = {
        meses: <?= json_encode(array_keys($meses)) ?>,
        incapacidadesPorMes: <?= json_encode(array_values($meses)) ?>,
        tiposIncapacidad: <?= json_encode($tiposIncapacidad) ?>
    };
</script>

<!-- ============================ -->
<!-- JS DEL DASHBOARD -->
<!-- ============================ -->
<script src="../public/js/dashboard.js"></script>

</body>
</html>
