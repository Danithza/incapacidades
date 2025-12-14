<?php
ob_start();

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../controllers/IncapacidadesController.php';
require_once __DIR__ . '/../controllers/HistorialController.php';
require_once __DIR__ . '/../vendor/autoload.php';

use Dompdf\Dompdf;
use Dompdf\Options;

// Configurar zona horaria de Colombia
date_default_timezone_set('America/Bogota');

$type = $_GET['type'] ?? 'incapacidades';

$inc = new IncapacidadesController($pdo);
$hist = new HistorialController($pdo);

$data = $type === "historial" ? $hist->obtenerHistorial() : $inc->getAll();

if (!$data || count($data) === 0) {
    die("<script>alert('No hay datos para generar el informe'); window.history.back();</script>");
}

// -------------------------------------------------------------------------
// ANÁLISIS ESTADÍSTICO Y CÁLCULOS INTELIGENTES
// -------------------------------------------------------------------------
function generarAnalisis($data, $type) {
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
            $accion = $item['accion'] ?? 'Sin acción';
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
            $usuario = $item['usuario'] ?? 'Sin usuario';
            if (!isset($porUsuario[$usuario])) {
                $porUsuario[$usuario] = 0;
            }
            $porUsuario[$usuario]++;
        }
        
        arsort($porUsuario);
        $usuarioMasActivo = key($porUsuario);
        $actividadUsuario = current($porUsuario);
        
        // Tendencias por hora del día
        $porHora = array_fill(0, 24, 0);
        foreach ($data as $item) {
            if (isset($item['fecha_accion'])) {
                try {
                    $fecha = new DateTime($item['fecha_accion']);
                    $hora = (int)$fecha->format('H');
                    $porHora[$hora]++;
                } catch (Exception $e) {
                    continue;
                }
            }
        }
        
        // Hora pico de actividad
        $horaPico = array_search(max($porHora), $porHora);
        
        $analisis['texto'] = "
        <h3 style='color: #2E75B6; margin-top: 20px;'>ANÁLISIS DE ACTIVIDAD DEL SISTEMA</h3>
        
        <p><strong>1. ACTIVIDAD GENERAL:</strong><br>
        Se han registrado <strong>{$totalRegistros} acciones</strong> en el historial del sistema.</p>
        
        <p><strong>2. TIPO DE ACCIONES:</strong><br>
        La acción más frecuente es <strong>'{$accionMasComun}'</strong> ({$frecuenciaAccion} veces). ";
        
        if (strpos($accionMasComun, 'crear') !== false || strpos($accionMasComun, 'nuevo') !== false) {
            $analisis['texto'] .= "Esto indica un alto volumen de registros nuevos en el sistema.";
        } elseif (strpos($accionMasComun, 'actualizar') !== false || strpos($accionMasComun, 'modificar') !== false) {
            $analisis['texto'] .= "Esto sugiere un proceso activo de seguimiento y actualización de casos.";
        }
        
        $analisis['texto'] .= "</p>
        
        <p><strong>3. USUARIOS ACTIVOS:</strong><br>
        El usuario <strong>{$usuarioMasActivo}</strong> es el más activo con <strong>{$actividadUsuario} acciones</strong> realizadas.</p>
        
        <p><strong>4. PATRÓN TEMPORAL:</strong><br>
        La hora de mayor actividad en el sistema es alrededor de las <strong>{$horaPico}:00 horas</strong>.</p>
        
        <p><strong>5. RECOMENDACIONES:</strong><br>
        • Monitorear la distribución de actividades entre usuarios<br>
        • Identificar procesos que requieren mayor frecuencia de actualización<br>
        • Optimizar los horarios de gestión según los picos de actividad</p>";
        
        $analisis['metricas'] = [
            'total_registros' => $totalRegistros,
            'accion_mas_comun' => $accionMasComun,
            'frecuencia_accion' => $frecuenciaAccion,
            'usuario_mas_activo' => $usuarioMasActivo,
            'actividad_usuario' => $actividadUsuario,
            'hora_pico' => $horaPico
        ];
    }
    
    return $analisis;
}

// Generar análisis
$analisis = generarAnalisis($data, $type);

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
    <title>Informe - ' . ($type === 'historial' ? 'Historial' : 'Incapacidades') . '</title>
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
        <div class="report-title">INFORME ' . ($type === 'historial' ? 'DE HISTORIAL DEL SISTEMA' : 'DE INCAPACIDADES LABORALES') . '</div>
        <div class="subtitle">Gerencia de Talento Humano</div>
        <div class="subtitle">Ibagué, Tolima - Colombia</div>
    </div>
    
    <div class="report-meta">
        <strong>Información del Reporte:</strong><br>
        • Fecha de generación: ' . formatearFechaColombia(date('Y-m-d H:i:s')) . '<br>
        • Periodo analizado: Últimos 90 días<br>
        • Año de referencia: 2025
    </div>
    
    <div class="highlight-box">
        <h3 style="margin-top: 0; color: #1F4E78;">RESUMEN</h3>
        <p>Este informe presenta un análisis detallado de ' . 
           ($type === 'historial' ? 'la actividad del sistema de gestión' : 'las incapacidades laborales') . 
           ' registradas en el Terminal de Transporte de Ibagué. El análisis incluye tendencias temporales, distribución por áreas, 
           patrones de comportamiento y recomendaciones para la gestión preventiva.</p>
    </div>';
    
// Mostrar métricas clave
$html .= '
    <div class="section">
        <div class="section-title">MÉTRICAS CLAVE</div>
        <div class="metric-grid">';
        
if ($type === 'incapacidades') {
    $html .= '
            <div class="metric-card">
                <div class="metric-value">' . $analisis['metricas']['total_registros'] . '</div>
                <div class="metric-label">Total de Incapacidades</div>
            </div>
            <div class="metric-card">
                <div class="metric-value">' . $analisis['metricas']['ultimos_30_dias'] . '</div>
                <div class="metric-label">Últimos 30 días</div>
            </div>
            <div class="metric-card">
                <div class="metric-value">' . $analisis['metricas']['dias_promedio'] . ' días</div>
                <div class="metric-label">Duración Promedio</div>
            </div>
            <div class="metric-card">
                <div class="metric-value">' . $analisis['metricas']['area_mas_afectada'] . '</div>
                <div class="metric-label">Área Más Afectada</div>
            </div>';
} else {
    $html .= '
            <div class="metric-card">
                <div class="metric-value">' . $analisis['metricas']['total_registros'] . '</div>
                <div class="metric-label">Total de Acciones</div>
            </div>
            <div class="metric-card">
                <div class="metric-value">' . $analisis['metricas']['accion_mas_comun'] . '</div>
                <div class="metric-label">Acción Más Frecuente</div>
            </div>
            <div class="metric-card">
                <div class="metric-value">' . $analisis['metricas']['usuario_mas_activo'] . '</div>
                <div class="metric-label">Usuario Más Activo</div>
            </div>
            <div class="metric-card">
                <div class="metric-value">' . $analisis['metricas']['hora_pico'] . ':00</div>
                <div class="metric-label">Hora Pico de Actividad</div>
            </div>';
}

$html .= '
        </div>
    </div>';
    
// Análisis ejecutivo
$html .= $analisis['texto'];
    
// Sección de casos destacados
$html .= '
    <div class="section">
        <div class="section-title">CASOS DESTACADOS</div>';
        
if ($type === 'incapacidades' && count($data) > 0) {
    // Mostrar primeros 5 casos como ejemplo
    $casosDestacados = array_slice($data, 0, min(5, count($data)));
    
    foreach ($casosDestacados as $index => $caso) {
        $empleado = $caso['nombre_empleado'] ?? 'Empleado no identificado';
        $area = $caso['area'] ?? 'Área no especificada';
        $diagnostico = $caso['diagnostico'] ?? 'Diagnóstico no registrado';
        $dias = $caso['dias_incapacidad'] ?? 'No especificado';
        $fechaInicio = isset($caso['inicio']) ? formatearFechaColombia($caso['inicio']) : 'Fecha no disponible';
        
        $html .= '
        <div class="case-item">
            <div class="case-header">Caso ' . ($index + 1) . ': ' . htmlspecialchars($empleado) . '</div>
            <div class="case-details">
                • Área: ' . htmlspecialchars($area) . '<br>
                • Diagnóstico: ' . htmlspecialchars($diagnostico) . '<br>
                • Duración: ' . $dias . ' días<br>
                • Inició: ' . $fechaInicio . '<br>
                • Estado: <span class="badge badge-' . strtolower($caso['estado'] ?? 'info') . '">' . ($caso['estado'] ?? 'Pendiente') . '</span>
            </div>
        </div>';
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
    
// Recomendaciones finales
$html .= '
    <div class="section">
        <div class="section-title">CONCLUSIONES Y RECOMENDACIONES</div>
        
        <div class="recommendation">
            <strong>Recomendaciones Estratégicas:</strong><br>
            1. Implementar un programa de bienestar laboral focalizado en el área más afectada<br>
            2. Establecer seguimiento personalizado para empleados con recurrencia de casos<br>
            3. Realizar análisis trimestral comparativo para identificar tendencias<br>
            4. Fortalecer la gestión preventiva en temporadas de mayor incidencia
        </div>
        
        <div class="insight">
            <strong>Insight Clave:</strong><br>
            ' . ($type === 'incapacidades' ? 
                'El análisis revela patrones específicos por área y diagnóstico que permiten focalizar las acciones preventivas.' : 
                'La actividad del sistema muestra concentración en ciertos horarios y usuarios, indicando oportunidades para optimizar procesos.') . '
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
$filename = "Informe_Incapacidades_" . ($type === 'historial' ? 'Historial' : 'Incapacidades') . "_" . date('Y-m-d') . ".pdf";

header("Content-Type: application/pdf");
header("Content-Disposition: attachment; filename=\"" . $filename . "\"");
header("Cache-Control: public, must-revalidate, max-age=0");
header("Pragma: public");
header("Expires: 0");

echo $dompdf->output();
exit;