<?php
include __DIR__ . '/../components/navbar.php';
require_once __DIR__ . '/../controllers/HistorialController.php';
require_once __DIR__ . '/../config/db.php';

$controller = new HistorialController($pdo);
$historial = $controller->obtenerHistorial();

// Datos reales de la base de datos
$totalIncapacidades = count($historial);
$incapacidadesActivas = array_filter($historial, function($h) {
    return isset($h['estado_proceso']) && $h['estado_proceso'] === 'Activo';
});
$totalActivas = count($incapacidadesActivas);
$totalValor = array_sum(array_column($historial, 'valor'));
$promedioDias = $totalIncapacidades > 0 ? 
    array_sum(array_column($historial, 'dias_incapacidad')) / $totalIncapacidades : 0;

// Datos reales para gráficos
// Tipos de incapacidad reales
$tiposIncapacidad = [];
foreach ($historial as $h) {
    if (isset($h['tipo_incapacidad'])) {
        $tipo = $h['tipo_incapacidad'];
        $tiposIncapacidad[$tipo] = ($tiposIncapacidad[$tipo] ?? 0) + 1;
    }
}

// Áreas reales
$areasCount = [];
foreach ($historial as $h) {
    if (isset($h['area']) && !empty($h['area'])) {
        $area = $h['area'];
        $areasCount[$area] = ($areasCount[$area] ?? 0) + 1;
    }
}
arsort($areasCount); // Ordenar de mayor a menor

// Estados reales
$estadosCount = [];
foreach ($historial as $h) {
    if (isset($h['estado_proceso'])) {
        $estado = $h['estado_proceso'];
        $estadosCount[$estado] = ($estadosCount[$estado] ?? 0) + 1;
    }
}

// Incapacidades por mes (últimos 6 meses)
$meses = [];
for ($i = 5; $i >= 0; $i--) {
    $mes = date('Y-m', strtotime("-$i months"));
    $meses[$mes] = 0;
}

foreach ($historial as $h) {
    if (isset($h['inicio'])) {
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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Gestión de Incapacidades</title>
    <link rel="stylesheet" href="../public/css/dashboard.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <div class="dashboard-container">
        <!-- Header del Dashboard -->
        <div class="dashboard-header">
            <h1>Dashboard de Incapacidades</h1>
        </div>

        <!-- Tarjetas de Resumen -->
        <div class="summary-cards">
            <div class="card card-primary">
                <div class="card-icon">
                    📄
                </div>
                <div class="card-content">
                    <h3><?= $totalIncapacidades ?></h3>
                    <p>Total Incapacidades</p>
                </div>
            </div>

            <div class="card card-warning">
                <div class="card-icon">
                    🟢
                </div>
                <div class="card-content">
                    <h3><?= $totalActivas ?></h3>
                    <p>Incapacidades Activas</p>
                </div>
            </div>

            <div class="card card-success">
                <div class="card-icon">
                    💰
                </div>
                <div class="card-content">
                    <h3>$<?= number_format($totalValor, 0, ',', '.') ?></h3>
                    <p>Valor Total</p>
                </div>
            </div>

            <div class="card card-info">
                <div class="card-icon">
                    📅
                </div>
                <div class="card-content">
                    <h3><?= number_format($promedioDias, 1) ?></h3>
                    <p>Promedio de Días</p>
                </div>
            </div>
        </div>

        <!-- Estadísticas Principales -->
        <div class="dashboard-content">
            <div class="row">
                <div class="col-md-8">
                    <div class="chart-container">
                        <div class="chart-header">
                            <h3>Incapacidades por Mes (Últimos 6 meses)</h3>
                        </div>
                        <canvas id="incapacityChart" height="250"></canvas>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="stats-container">
                        <h3>Distribución por Tipo</h3>
                        <?php if (!empty($tiposIncapacidad)): ?>
                            <canvas id="typeChart" height="250"></canvas>
                        <?php else: ?>
                            <p class="no-data">No hay datos de tipos de incapacidad</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6">
                    <div class="stats-container">
                        <h3>Top 5 Áreas con Más Incapacidades</h3>
                        <?php if (!empty($areasCount)): ?>
                            <div class="areas-list">
                                <?php 
                                $topAreas = array_slice($areasCount, 0, 5);
                                $totalAreas = array_sum($areasCount);
                                foreach ($topAreas as $area => $count): 
                                    $porcentaje = ($totalAreas > 0) ? ($count / $totalAreas * 100) : 0;
                                ?>
                                    <div class="area-item">
                                        <div class="area-info">
                                            <span class="area-name"><?= htmlspecialchars($area) ?></span>
                                            <span class="area-count"><?= $count ?> (<?= number_format($porcentaje, 1) ?>%)</span>
                                        </div>
                                        <div class="area-bar">
                                            <div class="area-bar-fill" style="width: <?= $porcentaje ?>%"></div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <p class="no-data">No hay datos de áreas</p>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="stats-container">
                        <h3>Estado de Procesos</h3>
                        <?php if (!empty($estadosCount)): ?>
                            <div class="status-grid">
                                <?php foreach ($estadosCount as $estado => $count): 
                                    $class = strtolower(str_replace(' ', '-', $estado));
                                ?>
                                    <div class="status-item">
                                        <span class="status-badge status-<?= $class ?>">
                                            <?= $estado ?>
                                        </span>
                                        <span class="status-count"><?= $count ?></span>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <p class="no-data">No hay datos de estados</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Resumen Rápido -->
        <div class="quick-summary">
            <div class="summary-header">
                <h2>Resumen General</h2>
            </div>
            <div class="summary-grid">
                <div class="summary-item">
                    <h4>Tipo Más Común</h4>
                    <p>
                        <?php 
                        if (!empty($tiposIncapacidad)) {
                            arsort($tiposIncapacidad);
                            $tipoMasComun = key($tiposIncapacidad);
                            echo htmlspecialchars($tipoMasComun) . " (" . reset($tiposIncapacidad) . ")";
                        } else {
                            echo "No hay datos";
                        }
                        ?>
                    </p>
                </div>
                
                <div class="summary-item">
                    <h4>Área con Más Casos</h4>
                    <p>
                        <?php 
                        if (!empty($areasCount)) {
                            $areaMasCasos = key($areasCount);
                            echo htmlspecialchars($areaMasCasos) . " (" . reset($areasCount) . ")";
                        } else {
                            echo "No hay datos";
                        }
                        ?>
                    </p>
                </div>
                
                <div class="summary-item">
                    <h4>Mes con Más Casos</h4>
                    <p>
                        <?php 
                        if (!empty($meses)) {
                            arsort($meses);
                            $mesMasCasos = key($meses);
                            echo date('F Y', strtotime($mesMasCasos)) . " (" . reset($meses) . ")";
                        } else {
                            echo "No hay datos";
                        }
                        ?>
                    </p>
                </div>
                
                <div class="summary-item">
                    <h4>Valor Promedio por Incapacidad</h4>
                    <p>$<?= $totalIncapacidades > 0 ? number_format($totalValor / $totalIncapacidades, 0, ',', '.') : 0 ?></p>
                </div>
            </div>
        </div>
    </div>

    <script src="../public/js/dashboard.js"></script>
    
    <script>
        // Datos para gráficos desde PHP
        window.chartData = {
            meses: <?= json_encode(array_keys($meses)) ?>,
            incapacidadesPorMes: <?= json_encode(array_values($meses)) ?>,
            tiposIncapacidad: <?= json_encode($tiposIncapacidad) ?>
        };
        
        // Inicializar dashboard después de definir chartData
        document.addEventListener('DOMContentLoaded', function() {
            if (typeof Dashboard !== 'undefined') {
                new Dashboard();
            }
        });
    </script>
</body>
</html>