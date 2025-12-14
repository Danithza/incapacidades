<?php
require_once __DIR__ . '/../config/db.php';
include __DIR__ . '/../components/navbar.php';
require_once __DIR__ . '/../controllers/IncapacidadesController.php';
require_once __DIR__ . '/../controllers/HistorialController.php';
require_once __DIR__ . '/../controllers/SeguimientoController.php';

$inc = new IncapacidadesController($pdo);
$hist = new HistorialController($pdo);
$seg = new SeguimientoController($pdo);

// Obtener parámetros de filtro
$fecha = $_GET['fecha'] ?? '';
$empleado = $_GET['empleado'] ?? '';
$area = $_GET['area'] ?? '';
$diagnostico = $_GET['diagnostico'] ?? '';
$estado = $_GET['estado'] ?? '';
$tipo_reporte = $_GET['tipo_reporte'] ?? 'incapacidades';

// Aplicar filtros según tipo de reporte
switch($tipo_reporte) {
    case 'historial':
        $lista = $hist->getFiltered($fecha, $empleado);
        break;
    case 'seguimiento':
        $lista = $seg->getFiltered($fecha, $empleado, $area);
        break;
    case 'incapacidades':
    default:
        $lista = $inc->getFiltered($fecha, $empleado, $area, $diagnostico, $estado);
        break;
}

// Obtener datos para filtros (solo para incapacidades)
$areas = $tipo_reporte === 'incapacidades' ? $inc->getDistinctAreas() : [];
$diagnosticos = $tipo_reporte === 'incapacidades' ? $inc->getDistinctDiagnosticos() : [];
$estados = $tipo_reporte === 'incapacidades' ? $inc->getDistinctEstados() : [];
?>

<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8">
<title>Reportes con Filtros</title>
<link rel="stylesheet" href="/incapacidades/public/css/reportes.css">
<script src="/incapacidades/public/js/reportes.js"></script>
</head>
<body>

<h1></h1>

<!-- Filtros -->
<div class="filtros-container">
    <h2>Filtros de Reportes</h2>
    
    <form method="GET" action="" class="filtros-form">
        <div class="filtro-group">
            <label>Tipo de Reporte:</label>
            <select name="tipo_reporte" id="tipo_reporte" onchange="this.form.submit()">
                <option value="incapacidades" <?= $tipo_reporte === 'incapacidades' ? 'selected' : '' ?>>Incapacidades</option>
                <option value="historial" <?= $tipo_reporte === 'historial' ? 'selected' : '' ?>>Historial</option>
                <option value="seguimiento" <?= $tipo_reporte === 'seguimiento' ? 'selected' : '' ?>>Seguimiento</option>
            </select>
        </div>

        <div class="filtro-group">
            <label>Fecha:</label>
            <input type="date" name="fecha" value="<?= htmlspecialchars($fecha) ?>" placeholder="YYYY-MM-DD">
        </div>

        <div class="filtro-group">
            <label>Nombre Empleado:</label>
            <input type="text" name="empleado" value="<?= htmlspecialchars($empleado) ?>" placeholder="Buscar por nombre">
        </div>

        <?php if($tipo_reporte === 'incapacidades'): ?>
        <div class="filtro-group">
            <label>Área:</label>
            <select name="area">
                <option value="">Todas las áreas</option>
                <?php foreach($areas as $a): ?>
                <option value="<?= htmlspecialchars($a['area']) ?>" <?= $area === $a['area'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($a['area']) ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="filtro-group">
            <label>Diagnóstico:</label>
            <select name="diagnostico">
                <option value="">Todos los diagnósticos</option>
                <?php foreach($diagnosticos as $d): ?>
                <option value="<?= htmlspecialchars($d['diagnostico']) ?>" <?= $diagnostico === $d['diagnostico'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($d['diagnostico']) ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="filtro-group">
            <label>Estado:</label>
            <select name="estado">
                <option value="">Todos los estados</option>
                <?php foreach($estados as $e): ?>
                <option value="<?= htmlspecialchars($e['estado']) ?>" <?= $estado === $e['estado'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($e['estado']) ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>
        <?php endif; ?>

        <div class="filtro-buttons">
            <button type="submit" class="btn">Aplicar Filtros</button>
            <a href="?" class="btn rojo">Limpiar Filtros</a>
            <span class="resultados-count">
                <?= count($lista) ?> registros encontrados
            </span>
        </div>
    </form>
</div>

<!-- Botones de exportación -->
<div class="acciones">
    <h3>Exportar Reporte Actual</h3>
    
    <?php
    // Construir query string para exportaciones
    $export_query = http_build_query([
        'fecha' => $fecha,
        'empleado' => $empleado,
        'area' => $area,
        'diagnostico' => $diagnostico,
        'estado' => $estado,
        'tipo_reporte' => $tipo_reporte
    ]);
    ?>
    
    <a class="btn" href="/incapacidades/actions/export_excel.php?<?= $export_query ?>">
        Exportar a Excel
    </a>
    
    <a class="btn rojo" href="/incapacidades/actions/export_pdf.php?<?= $export_query ?>">
        Exportar a PDF
    </a>
</div>

<!-- TABLA DE RESULTADOS -->
<h2>
    <?= $tipo_reporte === 'incapacidades' ? 'Listado de Incapacidades' : 
       ($tipo_reporte === 'historial' ? 'Historial de Movimientos' : 'Seguimiento de Incapacidades') ?>
</h2>

<?php if(empty($lista)): ?>
    <div class="no-results">
        <p>No se encontraron registros con los filtros aplicados.</p>
    </div>
<?php else: ?>

<table class="tabla">
    <thead>
        <?php if($tipo_reporte === 'incapacidades'): ?>
        <tr>
            <th>ID</th><th>Número</th><th>Empleado</th><th>Área</th>
            <th>Diagnóstico</th><th>Inicio</th><th>Termina</th>
            <th>Días</th><th>Estado</th><th>EPS/ARL</th>
        </tr>
        <?php elseif($tipo_reporte === 'historial'): ?>
        <tr>
            <th>ID</th><th>N° Incapacidad</th><th>Empleado</th><th>Acción</th>
            <th>Descripción</th><th>Fecha Acción</th>
        </tr>
        <?php else: ?>
        <tr>
            <th>ID</th><th>N° Incapacidad</th><th>Empleado</th><th>Área</th>
            <th>Diagnóstico</th><th>Estado Proceso</th><th>Observaciones</th>
        </tr>
        <?php endif; ?>
    </thead>
    <tbody>
        <?php foreach($lista as $r): ?>
        <?php if($tipo_reporte === 'incapacidades'): ?>
        <tr>
            <td><?= $r['id'] ?></td>
            <td><?= htmlspecialchars($r['numero_incapacidad']) ?></td>
            <td><?= htmlspecialchars($r['nombre_empleado']) ?></td>
            <td><?= htmlspecialchars($r['area']) ?></td>
            <td><?= htmlspecialchars($r['diagnostico']) ?></td>
            <td><?= $r['inicio'] ?></td>
            <td><?= $r['termina'] ?></td>
            <td><?= $r['dias_incapacidad'] ?></td>
            <td><span class="estado-badge estado-<?= strtolower($r['estado']) ?>">
                <?= $r['estado'] ?>
            </span></td>
            <td><?= htmlspecialchars($r['eps_arl']) ?></td>
        </tr>
        
        <?php elseif($tipo_reporte === 'historial'): ?>
        <tr>
            <td><?= $r['id'] ?></td>
            <td><?= htmlspecialchars($r['numero_incapacidad']) ?></td>
            <td><?= htmlspecialchars($r['nombre_empleado']) ?></td>
            <td><?= htmlspecialchars($r['accion']) ?></td>
            <td><?= htmlspecialchars($r['descripcion']) ?></td>
            <td><?= $r['fecha_accion'] ?></td>
        </tr>
        
        <?php else: ?>
        <tr>
            <td><?= $r['id'] ?></td>
            <td><?= htmlspecialchars($r['numero_incapacidad']) ?></td>
            <td><?= htmlspecialchars($r['nombre_empleado']) ?></td>
            <td><?= htmlspecialchars($r['area']) ?></td>
            <td><?= htmlspecialchars($r['diagnostico']) ?></td>
            <td><?= htmlspecialchars($r['estado']) ?></td>
            <td><?= htmlspecialchars($r['observaciones']) ?></td>
        </tr>
        <?php endif; ?>
        <?php endforeach; ?>
    </tbody>
</table>
<?php endif; ?>

</body>
</html>