<?php
ob_start();

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../controllers/IncapacidadesController.php';
require_once __DIR__ . '/../controllers/HistorialController.php';
require_once __DIR__ . '/../controllers/SeguimientoController.php';
require_once __DIR__ . '/../vendor/autoload.php';

use Dompdf\Dompdf;
use Dompdf\Options;

// Configurar zona horaria de Colombia
date_default_timezone_set('America/Bogota');

// Obtener parámetros de filtros
$fecha = $_GET['fecha'] ?? '';
$empleado = $_GET['empleado'] ?? '';
$area = $_GET['area'] ?? '';
$diagnostico = $_GET['diagnostico'] ?? '';
$estado = $_GET['estado'] ?? '';
$accion = $_GET['accion'] ?? '';
$usuario = $_GET['usuario'] ?? '';
$estado_proceso = $_GET['estado_proceso'] ?? '';
$eps_arl = $_GET['eps_arl'] ?? '';
$type = $_GET['tipo_reporte'] ?? 'incapacidades';

// Inicializar controladores
$inc = new IncapacidadesController($pdo);
$hist = new HistorialController($pdo);
$seg = new SeguimientoController($pdo);

// Obtener datos filtrados según tipo de reporte
if ($type === "historial") {
    $data = $hist->getFiltered($fecha, $empleado, $accion, $usuario);
    $title = "HISTORIAL DEL SISTEMA";
} elseif ($type === "seguimiento") {
    $data = $seg->getFiltered($fecha, $empleado, $area, $estado_proceso);
    $title = "SEGUIMIENTO DE INCAPACIDADES";
} else {
    $data = $inc->getFiltered($fecha, $empleado, $area, $diagnostico, $estado, $eps_arl);
    $title = "INCAPACIDADES LABORALES";
}

if (!$data || count($data) === 0) {
    die("<script>alert('No hay datos para generar el informe con los filtros aplicados'); window.history.back();</script>");
}

// Información de filtros aplicados
$filtros_aplicados = [];
if(!empty($fecha)) $filtros_aplicados[] = "Fecha: " . date('d/m/Y', strtotime($fecha));
if(!empty($empleado)) $filtros_aplicados[] = "Empleado: " . htmlspecialchars($empleado);
if(!empty($area)) $filtros_aplicados[] = "Área: " . htmlspecialchars($area);
if(!empty($diagnostico)) $filtros_aplicados[] = "Diagnóstico: " . htmlspecialchars($diagnostico);
if(!empty($estado)) $filtros_aplicados[] = "Estado: " . htmlspecialchars($estado);
if(!empty($accion)) $filtros_aplicados[] = "Acción: " . htmlspecialchars($accion);
if(!empty($usuario)) $filtros_aplicados[] = "Usuario: " . htmlspecialchars($usuario);
if(!empty($estado_proceso)) $filtros_aplicados[] = "Estado Proceso: " . htmlspecialchars($estado_proceso);
if(!empty($eps_arl)) $filtros_aplicados[] = "EPS/ARL: " . htmlspecialchars($eps_arl);

// -------------------------------------------------------------------------
// ANÁLISIS ESTADÍSTICO Y CÁLCULOS INTELIGENTES
// -------------------------------------------------------------------------
function generarAnalisis($data, $type, $filtros_aplicados) {
    $analisis = [];
    
    if ($type === 'incapacidades') {
        // Cálculos para incapacidades
        $totalRegistros = count($data);
        $hoy = new DateTime();
        $hace30Dias = clone $hoy;
        $hace30Dias->modify('-30 days');
        $hace60Dias = clone $hoy;
        $hace60Dias->modify('-60 days');
        
        // Filtrar registros por periodos
        $ultimos30Dias = array_filter($data, function($item) use ($hace30Dias) {
            try {
                $fecha = new DateTime($item['fecha_registro'] ?? $item['inicio'] ?? '');
                return $fecha >= $hace30Dias;
            } catch (Exception $e) {
                return false;
            }
        });
        
        $periodoAnterior = array_filter($data, function($item) use ($hace30Dias, $hace60Dias) {
            try {
                $fecha = new DateTime($item['fecha_registro'] ?? $item['inicio'] ?? '');
                return $fecha >= $hace60Dias && $fecha < $hace30Dias;
            } catch (Exception $e) {
                return false;
            }
        });
        
        $ultimos30Count = count($ultimos30Dias);
        $periodoAnteriorCount = count($periodoAnterior);
        
        // Cálculo de porcentaje de cambio
        $porcentajeCambio = 0;
        if ($periodoAnteriorCount > 0) {
            $porcentajeCambio = (($ultimos30Count - $periodoAnteriorCount) / $periodoAnteriorCount) * 100;
        }
        
        // Análisis por empleado
        $porEmpleado = [];
        foreach ($data as $item) {
            $empleado = $item['nombre_empleado'] ?? 'Sin nombre';
            if (!isset($porEmpleado[$empleado])) {
                $porEmpleado[$empleado] = 0;
            }
            $porEmpleado[$empleado]++;
        }
        
        // Identificar empleados con mayor frecuencia
        arsort($porEmpleado);
        $empleadoMasFrecuente = key($porEmpleado);
        $frecuenciaMasAlta = current($porEmpleado);
        
        // Análisis por área/departamento
        $porArea = [];
        foreach ($data as $item) {
            $area = $item['area'] ?? 'Sin área';
            if (!isset($porArea[$area])) {
                $porArea[$area] = 0;
            }
            $porArea[$area]++;
        }
        
        // Área con más incapacidades
        arsort($porArea);
        $areaMasAfectada = key($porArea);
        $incapacidadesArea = current($porArea);
        
        // Análisis por diagnóstico común
        $porDiagnostico = [];
        foreach ($data as $item) {
            $diagnostico = $item['diagnostico'] ?? 'Sin diagnóstico';
            if (!isset($porDiagnostico[$diagnostico])) {
                $porDiagnostico[$diagnostico] = 0;
            }
            $porDiagnostico[$diagnostico]++;
        }
        
        arsort($porDiagnostico);
        $diagnosticoMasComun = key($porDiagnostico);
        $frecuenciaDiagnostico = current($porDiagnostico);
        
        // Calcular días promedio de incapacidad
        $totalDias = 0;
        $contadorDias = 0;
        foreach ($data as $item) {
            if (isset($item['dias_incapacidad']) && is_numeric($item['dias_incapacidad'])) {
                $totalDias += $item['dias_incapacidad'];
                $contadorDias++;
            }
        }
        $diasPromedio = $contadorDias > 0 ? round($totalDias / $contadorDias, 1) : 0;
        
        // Análisis por estado
        $porEstado = [];
        foreach ($data as $item) {
            $estado = $item['estado'] ?? 'Sin estado';
            if (!isset($porEstado[$estado])) {
                $porEstado[$estado] = 0;
            }
            $porEstado[$estado]++;
        }
        
        // Análisis por EPS
        $porEPS = [];
        foreach ($data as $item) {
            $eps = $item['eps_arl'] ?? 'Sin EPS';
            if (!isset($porEPS[$eps])) {
                $porEPS[$eps] = 0;
            }
            $porEPS[$eps]++;
        }
        
        arsort($porEPS);
        $epsMasComun = key($porEPS);
        
        // Generar análisis textual
        $mesActual = date('F', strtotime('-1 month')); // Mes anterior para análisis
        $mesAnterior = date('F', strtotime('-2 months'));
        
        $analisis['texto'] = "
        <h3 style='color: #2E75B6; margin-top: 20px;'>ANÁLISIS</h3>
        
        <p><strong>1. TENDENCIA TEMPORAL:</strong><br>
        En los últimos 30 días se han registrado <strong>{$ultimos30Count} incapacidades</strong>. ";
        
        if ($porcentajeCambio > 0) {
            $analisis['texto'] .= "Esto representa un <strong>incremento del " . number_format($porcentajeCambio, 1) . "%</strong> en comparación con el periodo anterior (30-60 días atrás).";
        } elseif ($porcentajeCambio < 0) {
            $analisis['texto'] .= "Esto representa una <strong>disminución del " . number_format(abs($porcentajeCambio), 1) . "%</strong> en comparación con el periodo anterior.";
        } else {
            $analisis['texto'] .= "La cantidad se mantiene estable en comparación con el periodo anterior.";
        }
        
        $analisis['texto'] .= "</p>
        
        <p><strong>2. ANÁLISIS POR EMPLEADO:</strong><br>
        El empleado <strong>{$empleadoMasFrecuente}</strong> presenta la mayor frecuencia con <strong>{$frecuenciaMasAlta} incapacidades</strong> registradas. ";
        
        if ($frecuenciaMasAlta > 3) {
            $analisis['texto'] .= "Este patrón sugiere la necesidad de un seguimiento especializado para identificar causas recurrentes.";
        } elseif ($frecuenciaMasAlta > 1) {
            $analisis['texto'] .= "Se recomienda monitorear este caso para prevenir recurrencias.";
        }
        
        $analisis['texto'] .= "</p>
        
        <p><strong>3. DISTRIBUCIÓN POR ÁREA:</strong><br>
        El área de <strong>{$areaMasAfectada}</strong> concentra el mayor número de casos ({$incapacidadesArea} incapacidades). ";
        
        $porcentajeArea = ($incapacidadesArea / $totalRegistros) * 100;
        if ($porcentajeArea > 30) {
            $analisis['texto'] .= "Con un " . number_format($porcentajeArea, 1) . "% del total, se sugiere revisar las condiciones laborales específicas de esta área.";
        }
        
        $analisis['texto'] .= "</p>
        
        <p><strong>4. DIAGNÓSTICOS PREDOMINANTES:</strong><br>
        El diagnóstico más frecuente es <strong>'{$diagnosticoMasComun}'</strong> ({$frecuenciaDiagnostico} casos). ";
        
        if (stripos($diagnosticoMasComun, 'respirator') !== false) {
            $analisis['texto'] .= "Los casos respiratorios sugieren posibles factores ambientales o estacionales.";
        } elseif (stripos($diagnosticoMasComun, 'músculo') !== false || stripos($diagnosticoMasComun, 'óseo') !== false) {
            $analisis['texto'] .= "Los casos músculo-esqueléticos pueden relacionarse con actividades laborales específicas.";
        }
        
        $analisis['texto'] .= "</p>
        
        <p><strong>5. IMPACTO OPERATIVO:</strong><br>
        La duración promedio de las incapacidades es de <strong>{$diasPromedio} días</strong>. ";
        
        if ($diasPromedio > 5) {
            $analisis['texto'] .= "Esta duración sugiere un impacto significativo en la continuidad operativa.";
        } else {
            $analisis['texto'] .= "Esta duración permite una gestión más ágil de las ausencias.";
        }
        
        $analisis['texto'] .= "</p>
        
        <p><strong>6. DISTRIBUCIÓN POR EPS:</strong><br>
        La EPS <strong>{$epsMasComun}</strong> presenta la mayor cantidad de casos. Esto puede indicar patrones específicos de atención médica en la organización.</p>";
        
        // Métricas para gráficos
        $analisis['metricas'] = [
            'total_registros' => $totalRegistros,
            'ultimos_30_dias' => $ultimos30Count,
            'porcentaje_cambio' => $porcentajeCambio,
            'empleado_mas_frecuente' => $empleadoMasFrecuente,
            'frecuencia_mas_alta' => $frecuenciaMasAlta,
            'area_mas_afectada' => $areaMasAfectada,
            'incapacidades_area' => $incapacidadesArea,
            'diagnostico_mas_comun' => $diagnosticoMasComun,
            'frecuencia_diagnostico' => $frecuenciaDiagnostico,
            'dias_promedio' => $diasPromedio,
            'eps_mas_comun' => $epsMasComun,
            'distribucion_estado' => $porEstado
        ];
        
    } elseif ($type === 'historial') {
        // Análisis para historial
        $totalRegistros = count($data);
        
        // Agrupar por acción
        $porAccion = [];
        foreach ($data as $item) {
            $accion = $item['accion'] ?? 'Registro Finalizado';
            if (!isset($porAccion[$accion])) {
                $porAccion[$accion] = 0;
            }
            $porAccion[$accion]++;
        }
        
        // Acción más común
        arsort($porAccion);
        $accionMasComun = key($porAccion);
        $frecuenciaAccion = current($porAccion);
        
        // Usuario más activo
        $porUsuario = [];
        foreach ($data as $item) {
            $usuario = $item['usuario'] ?? 'Sistema';
            if (!isset($porUsuario[$usuario])) {
                $porUsuario[$usuario] = 0;
            }
            $porUsuario[$usuario]++;
        }
        
        arsort($porUsuario);
        $usuarioMasActivo = key($porUsuario);
        $actividadUsuario = current($porUsuario);
        
        // Análisis por empleado
        $porEmpleado = [];
        foreach ($data as $item) {
            $empleado = $item['nombre_empleado'] ?? 'Sin nombre';
            if (!isset($porEmpleado[$empleado])) {
                $porEmpleado[$empleado] = 0;
            }
            $porEmpleado[$empleado]++;
        }
        
        arsort($porEmpleado);
        $empleadoMasComun = key($porEmpleado);
        $frecuenciaEmpleado = current($porEmpleado);
        
        // Análisis por área
        $porArea = [];
        foreach ($data as $item) {
            $area = $item['area'] ?? 'Sin área';
            if (!isset($porArea[$area])) {
                $porArea[$area] = 0;
            }
            $porArea[$area]++;
        }
        
        arsort($porArea);
        $areaMasComun = key($porArea);
        $frecuenciaArea = current($porArea);
        
        $analisis['texto'] = "
        <h3 style='color: #2E75B6; margin-top: 20px;'>ANÁLISIS DEL HISTORIAL</h3>
        
        <p><strong>1. ACTIVIDAD GENERAL:</strong><br>
        Se han registrado <strong>{$totalRegistros} registros históricos</strong> de incapacidades finalizadas.</p>
        
        <p><strong>2. EMPLEADOS CON MÁS REGISTROS:</strong><br>
        El empleado <strong>{$empleadoMasComun}</strong> tiene {$frecuenciaEmpleado} registros en el historial.</p>
        
        <p><strong>3. DISTRIBUCIÓN POR ÁREA:</strong><br>
        El área <strong>{$areaMasComun}</strong> concentra {$frecuenciaArea} registros históricos.</p>
        
        <p><strong>4. TIPO DE ACCIONES:</strong><br>
        La acción más frecuente es <strong>'{$accionMasComun}'</strong> ({$frecuenciaAccion} veces).</p>
        
        <p><strong>5. USUARIOS ACTIVOS:</strong><br>
        El usuario <strong>{$usuarioMasActivo}</strong> es el más activo con <strong>{$actividadUsuario} acciones</strong> realizadas.</p>
        
        <p><strong>6. INFORMACIÓN ADICIONAL:</strong><br>
        Este historial incluye el seguimiento completo de fases de cada incapacidad que fue finalizada en el sistema.</p>";
        
        $analisis['metricas'] = [
            'total_registros' => $totalRegistros,
            'accion_mas_comun' => $accionMasComun,
            'frecuencia_accion' => $frecuenciaAccion,
            'usuario_mas_activo' => $usuarioMasActivo,
            'actividad_usuario' => $actividadUsuario,
            'empleado_mas_comun' => $empleadoMasComun,
            'frecuencia_empleado' => $frecuenciaEmpleado,
            'area_mas_comun' => $areaMasComun,
            'frecuencia_area' => $frecuenciaArea
        ];
        
    } elseif ($type === 'seguimiento') {
        // Análisis para seguimiento
        $totalRegistros = count($data);
        
        // Análisis por empleado
        $porEmpleado = [];
        foreach ($data as $item) {
            $empleado = $item['nombre_empleado'] ?? 'Sin nombre';
            if (!isset($porEmpleado[$empleado])) {
                $porEmpleado[$empleado] = 0;
            }
            $porEmpleado[$empleado]++;
        }
        
        arsort($porEmpleado);
        $empleadoMasComun = key($porEmpleado);
        $frecuenciaEmpleado = current($porEmpleado);
        
        // Análisis por área
        $porArea = [];
        foreach ($data as $item) {
            $area = $item['area'] ?? 'Sin área';
            if (!isset($porArea[$area])) {
                $porArea[$area] = 0;
            }
            $porArea[$area]++;
        }
        
        arsort($porArea);
        $areaMasComun = key($porArea);
        $frecuenciaArea = current($porArea);
        
        // Análisis por estado de proceso
        $porEstadoProceso = [];
        foreach ($data as $item) {
            $estado_proceso = $item['estado_proceso'] ?? 'Sin estado';
            if (!isset($porEstadoProceso[$estado_proceso])) {
                $porEstadoProceso[$estado_proceso] = 0;
            }
            $porEstadoProceso[$estado_proceso]++;
        }
        
        arsort($porEstadoProceso);
        $estadoProcesoMasComun = key($porEstadoProceso);
        $frecuenciaEstadoProceso = current($porEstadoProceso);
        
        // Análisis de fases
        $fasesActivas = 0;
        $fasesCompletadas = 0;
        foreach ($data as $item) {
            if (isset($item['fases']) && is_array($item['fases'])) {
                foreach ($item['fases'] as $fase) {
                    if (is_array($fase)) {
                        if (isset($fase['estado']) && $fase['estado'] === 'COMPLETADO') {
                            $fasesCompletadas++;
                        } else {
                            $fasesActivas++;
                        }
                    }
                }
            }
        }
        
        $analisis['texto'] = "
        <h3 style='color: #2E75B6; margin-top: 20px;'>ANÁLISIS DEL SEGUIMIENTO</h3>
        
        <p><strong>1. ACTIVIDAD GENERAL:</strong><br>
        Se están realizando seguimiento a <strong>{$totalRegistros} incapacidades</strong> activas.</p>
        
        <p><strong>2. EMPLEADOS EN SEGUIMIENTO:</strong><br>
        El empleado <strong>{$empleadoMasComun}</strong> tiene {$frecuenciaEmpleado} incapacidades en seguimiento.</p>
        
        <p><strong>3. DISTRIBUCIÓN POR ÁREA:</strong><br>
        El área <strong>{$areaMasComun}</strong> concentra {$frecuenciaArea} casos en seguimiento.</p>
        
        <p><strong>4. ESTADO DE PROCESO:</strong><br>
        El estado de proceso más común es <strong>'{$estadoProcesoMasComun}'</strong> ({$frecuenciaEstadoProceso} casos).</p>
        
        <p><strong>5. AVANCE DE FASES:</strong><br>
        Se han completado <strong>{$fasesCompletadas} fases</strong> y hay <strong>{$fasesActivas} fases activas</strong> en seguimiento.</p>
        
        <p><strong>6. SEGUIMIENTO ACTIVO:</strong><br>
        Todas estas incapacidades tienen un seguimiento activo de fases que se monitorea constantemente.</p>";
        
        $analisis['metricas'] = [
            'total_registros' => $totalRegistros,
            'empleado_mas_comun' => $empleadoMasComun,
            'frecuencia_empleado' => $frecuenciaEmpleado,
            'area_mas_comun' => $areaMasComun,
            'frecuencia_area' => $frecuenciaArea,
            'estado_proceso_mas_comun' => $estadoProcesoMasComun,
            'frecuencia_estado_proceso' => $frecuenciaEstadoProceso,
            'fases_completadas' => $fasesCompletadas,
            'fases_activas' => $fasesActivas
        ];
    }
    
    // Agregar información de filtros aplicados al análisis
    if (!empty($filtros_aplicados)) {
        $filtros_texto = "<p><strong>FILTROS APLICADOS:</strong><br>" . implode(" | ", $filtros_aplicados) . "</p>";
        $analisis['texto'] = $filtros_texto . $analisis['texto'];
    }
    
    return $analisis;
}

// Generar análisis
$analisis = generarAnalisis($data, $type, $filtros_aplicados);

// -------------------------------------------------------------------------
// FUNCIÓN PARA FORMATEAR DATOS INDIVIDUALES
// -------------------------------------------------------------------------
function formatearFechaColombia($fecha) {
    if (empty($fecha) || $fecha === '0000-00-00') {
        return 'Fecha no disponible';
    }
    
    try {
        $date = new DateTime($fecha);
        
        // Nombres de meses en español
        $meses = [
            'January' => 'Enero', 'February' => 'Febrero', 'March' => 'Marzo',
            'April' => 'Abril', 'May' => 'Mayo', 'June' => 'Junio',
            'July' => 'Julio', 'August' => 'Agosto', 'September' => 'Septiembre',
            'October' => 'Octubre', 'November' => 'Noviembre', 'December' => 'Diciembre'
        ];
        
        $mes = $meses[$date->format('F')] ?? $date->format('F');
        
        return $date->format('d') . ' de ' . $mes . ' de ' . $date->format('Y') . 
               ' a las ' . $date->format('H:i') . ' horas';
    } catch (Exception $e) {
        return $fecha;
    }
}

// -------------------------------------------------------------------------
// GENERAR HTML PARA PDF
// -------------------------------------------------------------------------
$html = '
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Informe - ' . ($type === 'historial' ? 'Historial' : ($type === 'seguimiento' ? 'Seguimiento' : 'Incapacidades')) . '</title>
    <style>
        @page {
            margin: 2.5cm 2cm;
        }
        
        body {
            font-family: "DejaVu Sans", "Helvetica", Arial, sans-serif;
            font-size: 12px;
            line-height: 1.6;
            color: #333;
            margin: 0;
            padding: 0;
        }
        
        .header {
            text-align: center;
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 3px solid #2E75B6;
        }
        
        .company-name {
            font-size: 18px;
            font-weight: bold;
            color: #1F4E78;
            margin-bottom: 5px;
        }
        
        .report-title {
            font-size: 16px;
            font-weight: bold;
            color: #2E75B6;
            margin: 10px 0;
        }
        
        .subtitle {
            font-size: 12px;
            color: #666;
            margin: 3px 0;
        }
        
        .report-meta {
            background-color: #f8f9fa;
            padding: 12px;
            border-radius: 5px;
            margin: 15px 0;
            border-left: 4px solid #2E75B6;
        }
        
        .filtros-box {
            background-color: #e8f4fd;
            padding: 10px;
            border-radius: 5px;
            margin: 10px 0;
            border: 1px solid #b6d4fe;
        }
        
        .section {
            margin: 25px 0;
        }
        
        .section-title {
            font-size: 14px;
            font-weight: bold;
            color: #1F4E78;
            margin-bottom: 10px;
            padding-bottom: 5px;
            border-bottom: 2px solid #eee;
        }
        
        .highlight-box {
            background-color: #e8f4fd;
            padding: 15px;
            border-radius: 5px;
            margin: 15px 0;
            border-left: 5px solid #2E75B6;
        }
        
        .metric-grid {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin: 15px 0;
        }
        
        .metric-card {
            flex: 1;
            min-width: 150px;
            background: white;
            border: 1px solid #ddd;
            border-radius: 5px;
            padding: 10px;
            text-align: center;
        }
        
        .metric-value {
            font-size: 20px;
            font-weight: bold;
            color: #2E75B6;
        }
        
        .metric-label {
            font-size: 10px;
            color: #666;
            margin-top: 5px;
        }
        
        .case-item {
            background-color: #f9f9f9;
            margin: 10px 0;
            padding: 10px;
            border-radius: 4px;
            border-left: 4px solid #4CAF50;
        }
        
        .case-header {
            font-weight: bold;
            color: #1F4E78;
            margin-bottom: 5px;
        }
        
        .case-details {
            font-size: 11px;
            color: #555;
        }
        
        .observation {
            background-color: #fff3cd;
            padding: 10px;
            border-radius: 4px;
            margin: 10px 0;
            border-left: 4px solid #ffc107;
        }
        
        .recommendation {
            background-color: #d4edda;
            padding: 10px;
            border-radius: 4px;
            margin: 10px 0;
            border-left: 4px solid #28a745;
        }
        
        .footer {
            margin-top: 30px;
            padding-top: 10px;
            border-top: 1px solid #ddd;
            font-size: 10px;
            color: #666;
            text-align: center;
        }
        
        .badge {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 3px;
            font-size: 10px;
            font-weight: bold;
            margin-right: 5px;
        }
        
        .badge-success { background-color: #d4edda; color: #155724; }
        .badge-warning { background-color: #fff3cd; color: #856404; }
        .badge-danger { background-color: #f8d7da; color: #721c24; }
        .badge-info { background-color: #d1ecf1; color: #0c5460; }
        
        .trend-up { color: #dc3545; font-weight: bold; }
        .trend-down { color: #28a745; font-weight: bold; }
        .trend-stable { color: #6c757d; }
        
        .insight {
            background-color: #f0f8ff;
            padding: 12px;
            margin: 15px 0;
            border-radius: 5px;
            border: 1px dashed #2E75B6;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="company-name">TERMINAL DE TRANSPORTE DE IBAGUÉ S.A.</div>
        <div class="report-title">INFORME ' . ($type === 'historial' ? 'DE HISTORIAL DEL SISTEMA' : 
                                              ($type === 'seguimiento' ? 'DE SEGUIMIENTO DE INCAPACIDADES' : 'DE INCAPACIDADES LABORALES')) . '</div>
        <div class="subtitle">Gerencia de Talento Humano</div>
        <div class="subtitle">Ibagué, Tolima - Colombia</div>
    </div>
    
    <div class="report-meta">
        <strong>Información del Reporte:</strong><br>
        • Fecha de generación: ' . formatearFechaColombia(date('Y-m-d H:i:s')) . '<br>
        • Periodo analizado: Datos actualizados al ' . date('d/m/Y') . '<br>
        • Total de registros: ' . count($data) . '<br>
        • Tipo de reporte: ' . ucfirst($type) . '
    </div>';
    
// Mostrar filtros aplicados si hay
if(!empty($filtros_aplicados)) {
    $html .= '
    <div class="filtros-box">
        <strong>Filtros aplicados en este reporte:</strong><br>
        ' . implode(' | ', $filtros_aplicados) . '
    </div>';
}

$html .= '
    <div class="highlight-box">
        <h3 style="margin-top: 0; color: #1F4E78;">RESUMEN EJECUTIVO</h3>
        <p>Este informe presenta un análisis detallado de ' . 
           ($type === 'historial' ? 'la actividad histórica del sistema de gestión de incapacidades, incluyendo registros finalizados y su seguimiento completo.' : 
            ($type === 'seguimiento' ? 'las incapacidades en proceso de seguimiento activo, mostrando el avance en cada fase del proceso.' : 
             'las incapacidades laborales activas registradas en el Terminal de Transporte de Ibagué.')) . 
           ' El análisis incluye tendencias temporales, distribución por áreas, patrones de comportamiento y recomendaciones para la gestión preventiva.</p>
    </div>';
    
// Mostrar métricas clave
$html .= '
    <div class="section">
        <div class="section-title">MÉTRICAS CLAVE</div>
        <div class="metric-grid">';
        
// Mostrar métricas según tipo de reporte
if ($type === 'incapacidades') {
    $metricas = $analisis['metricas'];
    $html .= '
            <div class="metric-card">
                <div class="metric-value">' . $metricas['total_registros'] . '</div>
                <div class="metric-label">Total de Incapacidades</div>
            </div>
            <div class="metric-card">
                <div class="metric-value">' . $metricas['ultimos_30_dias'] . '</div>
                <div class="metric-label">Últimos 30 días</div>
            </div>
            <div class="metric-card">
                <div class="metric-value">' . $metricas['dias_promedio'] . ' días</div>
                <div class="metric-label">Duración Promedio</div>
            </div>
            <div class="metric-card">
                <div class="metric-value">' . (isset($metricas['area_mas_afectada']) ? substr($metricas['area_mas_afectada'], 0, 15) . '...' : 'N/A') . '</div>
                <div class="metric-label">Área Más Afectada</div>
            </div>';
} elseif ($type === 'historial') {
    $metricas = $analisis['metricas'];
    $html .= '
            <div class="metric-card">
                <div class="metric-value">' . $metricas['total_registros'] . '</div>
                <div class="metric-label">Registros Históricos</div>
            </div>
            <div class="metric-card">
                <div class="metric-value">' . (isset($metricas['empleado_mas_comun']) ? substr($metricas['empleado_mas_comun'], 0, 12) . '...' : 'N/A') . '</div>
                <div class="metric-label">Empleado Más Común</div>
            </div>
            <div class="metric-card">
                <div class="metric-value">' . (isset($metricas['area_mas_comun']) ? substr($metricas['area_mas_comun'], 0, 15) . '...' : 'N/A') . '</div>
                <div class="metric-label">Área Más Común</div>
            </div>
            <div class="metric-card">
                <div class="metric-value">' . $metricas['frecuencia_accion'] . '</div>
                <div class="metric-label">Acción Principal</div>
            </div>';
} else { // seguimiento
    $metricas = $analisis['metricas'];
    $html .= '
            <div class="metric-card">
                <div class="metric-value">' . $metricas['total_registros'] . '</div>
                <div class="metric-label">En Seguimiento</div>
            </div>
            <div class="metric-card">
                <div class="metric-value">' . (isset($metricas['empleado_mas_comun']) ? substr($metricas['empleado_mas_comun'], 0, 12) . '...' : 'N/A') . '</div>
                <div class="metric-label">Empleado Principal</div>
            </div>
            <div class="metric-card">
                <div class="metric-value">' . $metricas['fases_completadas'] . '</div>
                <div class="metric-label">Fases Completadas</div>
            </div>
            <div class="metric-card">
                <div class="metric-value">' . $metricas['fases_activas'] . '</div>
                <div class="metric-label">Fases Activas</div>
            </div>';
}

$html .= '
        </div>
    </div>';
    
// Análisis ejecutivo
$html .= $analisis['texto'];
    
// Sección de casos destacados (solo para incapacidades y seguimiento)
if ($type === 'incapacidades' || $type === 'seguimiento') {
    $html .= '
    <div class="section">
        <div class="section-title">CASOS DESTACADOS</div>';
        
    if (count($data) > 0) {
        // Mostrar primeros 5 casos como ejemplo
        $casosDestacados = array_slice($data, 0, min(5, count($data)));
        
        foreach ($casosDestacados as $index => $caso) {
            $empleado = $caso['nombre_empleado'] ?? 'Empleado no identificado';
            $area = $caso['area'] ?? 'Área no especificada';
            $diagnostico = $caso['diagnostico'] ?? 'Diagnóstico no registrado';
            
            if ($type === 'incapacidades') {
                $dias = $caso['dias_incapacidad'] ?? 'No especificado';
                $fechaInicio = isset($caso['inicio']) ? formatearFechaColombia($caso['inicio']) : 'Fecha no disponible';
                $estado = $caso['estado'] ?? 'Pendiente';
                
                $html .= '
                <div class="case-item">
                    <div class="case-header">Caso ' . ($index + 1) . ': ' . htmlspecialchars($empleado) . '</div>
                    <div class="case-details">
                        • Área: ' . htmlspecialchars($area) . '<br>
                        • Diagnóstico: ' . htmlspecialchars($diagnostico) . '<br>
                        • Duración: ' . $dias . ' días<br>
                        • Inició: ' . $fechaInicio . '<br>
                        • Estado: <span class="badge badge-' . strtolower($estado) . '">' . $estado . '</span>
                    </div>
                </div>';
            } else { // seguimiento
                $estadoProceso = $caso['estado_proceso'] ?? 'Sin estado';
                $observaciones = $caso['observaciones'] ?? 'Sin observaciones';
                $fechaInicio = isset($caso['inicio']) ? formatearFechaColombia($caso['inicio']) : 'Fecha no disponible';
                
                $html .= '
                <div class="case-item">
                    <div class="case-header">Caso ' . ($index + 1) . ': ' . htmlspecialchars($empleado) . '</div>
                    <div class="case-details">
                        • Área: ' . htmlspecialchars($area) . '<br>
                        • Diagnóstico: ' . htmlspecialchars($diagnostico) . '<br>
                        • Estado Proceso: ' . htmlspecialchars($estadoProceso) . '<br>
                        • Inició: ' . $fechaInicio . '<br>
                        • Observaciones: ' . (strlen($observaciones) > 100 ? substr(htmlspecialchars($observaciones), 0, 100) . '...' : htmlspecialchars($observaciones)) . '
                    </div>
                </div>';
            }
        }
        
        if (count($data) > 5) {
            $html .= '
            <div class="observation">
                <strong>Nota:</strong> Se muestran ' . count($casosDestacados) . ' casos de ' . count($data) . ' registros totales. 
                Para el análisis completo, consulte la base de datos del sistema.
            </div>';
        }
    }
    
    $html .= '
    </div>';
}

// Recomendaciones finales
$html .= '
    <div class="section">
        <div class="section-title">CONCLUSIONES Y RECOMENDACIONES</div>
        
        <div class="recommendation">
            <strong>Recomendaciones Estratégicas:</strong><br>';
            
if ($type === 'incapacidades') {
    $html .= '1. Implementar un programa de bienestar laboral focalizado en el área más afectada<br>
            2. Establecer seguimiento personalizado para empleados con recurrencia de casos<br>
            3. Realizar análisis trimestral comparativo para identificar tendencias<br>
            4. Fortalecer la gestión preventiva en temporadas de mayor incidencia';
} elseif ($type === 'historial') {
    $html .= '1. Analizar patrones recurrentes en incapacidades finalizadas<br>
            2. Identificar áreas de mejora en el proceso de seguimiento<br>
            3. Establecer métricas de eficiencia en la gestión de casos<br>
            4. Documentar lecciones aprendidas para mejorar procesos futuros';
} else { // seguimiento
    $html .= '1. Monitorear activamente las fases pendientes de completar<br>
            2. Establecer alertas para casos con seguimiento prolongado<br>
            3. Optimizar la comunicación entre áreas involucradas<br>
            4. Revisar periódicamente el estado de procesos críticos';
}

$html .= '
        </div>
        
        <div class="insight">
            <strong>Insight Clave:</strong><br>
            ' . ($type === 'incapacidades' ? 
                'El análisis revela patrones específicos por área y diagnóstico que permiten focalizar las acciones preventivas.' : 
                ($type === 'historial' ? 
                 'El historial muestra la trazabilidad completa de cada caso, permitiendo evaluar la eficiencia del proceso de gestión.' :
                 'El seguimiento activo permite identificar cuellos de botella y optimizar tiempos de respuesta en la gestión de incapacidades.')) . '
        </div>
    </div>';
    
// Footer
$html .= '
    <div class="footer">
        Documento confidencial - Uso interno<br>
        Generado automáticamente por el Sistema de Gestión de Incapacidades<br>
        Terminal de Transporte de Ibagué S.A. | ' . formatearFechaColombia(date('Y-m-d H:i:s')) . '
    </div>
</body>
</html>';

// -------------------------------------------------------------------------
// GENERAR PDF
// -------------------------------------------------------------------------
$dompdf = new Dompdf((new Options())->set('isRemoteEnabled', true));
$dompdf->loadHtml($html, "UTF-8");
$dompdf->setPaper("A4", "portrait");
$dompdf->render();

// Headers para descarga PDF
$filename = "Informe_" . ($type === 'historial' ? 'Historial' : 
                         ($type === 'seguimiento' ? 'Seguimiento' : 'Incapacidades')) . 
           "_" . date('Y-m-d_Hi') . ".pdf";

header("Content-Type: application/pdf");
header("Content-Disposition: attachment; filename=\"" . $filename . "\"");
header("Cache-Control: public, must-revalidate, max-age=0");
header("Pragma: public");
header("Expires: 0");

echo $dompdf->output();
exit;