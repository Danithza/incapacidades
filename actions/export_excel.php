<?php
// --------------------------------------------------------------------------------
// ESTABLECER ZONA HORARIA DE COLOMBIA PARA CORREGIR LA HORA
// --------------------------------------------------------------------------------
date_default_timezone_set('America/Bogota'); // Ibagué, Colombia está en esta zona horaria

// --------------------------------------------------------------------------------
// INCLUIR DEPENDENCIAS
// --------------------------------------------------------------------------------
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../controllers/IncapacidadesController.php';
require_once __DIR__ . '/../controllers/HistorialController.php';
require_once __DIR__ . '/../controllers/SeguimientoController.php';
require_once __DIR__ . '/../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

// ----------------------------------------------------------------
// LIMPIA LA SALIDA ANTES DE GENERAR EL ARCHIVO
// ----------------------------------------------------------------
ob_clean();

// --------------------------------------------------------------------------------
// OBTENER TIPO DE REPORTE
// --------------------------------------------------------------------------------
$type = $_GET['type'] ?? 'incapacidades';

// --------------------------------------------------------------------------------
// INICIALIZAR CONTROLADORES
// --------------------------------------------------------------------------------
$inc = new IncapacidadesController($pdo);
$hist = new HistorialController($pdo);
$seg = new SeguimientoController($pdo);

// --------------------------------------------------------------------------------
// CONFIGURACIONES POR TIPO DE REPORTE
// --------------------------------------------------------------------------------
$config = [
    'incapacidades' => [
        'data' => $inc->getAll(),
        'filename' => "Reporte_Incapacidades_" . date('Y-m-d_His') . ".xlsx",
        'title' => "REPORTE DE INCAPACIDADES",
        'subtitles' => [
            "Terminal de Transporte de Ibagué",
            "Sistema de Gestión de Incapacidades"
        ],
        'column_names' => [
            'id' => 'ID',
            'numero_incapacidad' => 'N° Incapacidad',
            'nombre_empleado' => 'Empleado',
            'cedula' => 'Cédula',
            'area' => 'Área',
            'diagnostico' => 'Diagnóstico',
            'inicio' => 'Fecha Inicio',
            'termina' => 'Fecha Fin',
            'dias_incapacidad' => 'Días',
            'estado' => 'Estado',
            'fecha_registro' => 'Fecha Registro',
            'eps' => 'EPS',
            'tipo_incapacidad' => 'Tipo'
        ]
    ],
    
    'historial' => [
        'data' => $hist->obtenerHistorial(),
        'filename' => "Reporte_Historial_" . date('Y-m-d_His') . ".xlsx",
        'title' => "HISTORIAL DE MOVIMIENTOS",
        'subtitles' => [
            "Terminal de Transporte de Ibagué",
            "Registro Histórico del Sistema"
        ],
        'column_names' => [
            'id_historial' => 'ID Historial',
            'id_incapacidad' => 'ID Incapacidad',
            'numero_incapacidad' => 'N° Incapacidad',
            'nombre_empleado' => 'Empleado',
            'accion' => 'Acción Realizada',
            'descripcion' => 'Descripción',
            'usuario' => 'Usuario',
            'fecha_accion' => 'Fecha y Hora',
            'detalles_cambio' => 'Detalles del Cambio'
        ]
    ],
    
    'seguimiento' => [
        'data' => $seg->index(),
        'filename' => "Reporte_Seguimiento_" . date('Y-m-d_His') . ".xlsx",
        'title' => "SEGUIMIENTO DE INCAPACIDADES",
        'subtitles' => [
            "Terminal de Transporte de Ibagué",
            "Sistema de Seguimiento"
        ],
        'column_names' => [
            'id' => 'ID',
            'numero_incapacidad' => 'N° Incapacidad',
            'nombre_empleado' => 'Empleado',
            'cedula' => 'Cédula',
            'area' => 'Área',
            'diagnostico' => 'Diagnóstico',
            'inicio' => 'Fecha Inicio',
            'termina' => 'Fecha Fin',
            'estado_proceso' => 'Estado Proceso',
            'observaciones' => 'Observaciones',
            'numero_orden' => 'N° Orden',
            'fases_formateadas' => 'Fases del Seguimiento' // Nueva columna para fases
        ]
    ]
];

// --------------------------------------------------------------------------------
// VALIDAR TIPO DE REPORTE
// --------------------------------------------------------------------------------
if (!isset($config[$type])) {
    die("Tipo de reporte no válido");
}

$configData = $config[$type];
$data = $configData['data'];
$filename = $configData['filename'];
$title = $configData['title'];
$subtitles = $configData['subtitles'];
$columnNames = $configData['column_names'];

// ------------------------------------------------------------------------------------
// FUNCIÓN PARA FORMATEAR FECHAS (CORREGIDA PARA ZONA HORARIA DE COLOMBIA)
// ------------------------------------------------------------------------------------
function formatearFecha($dateString) {
    if (empty($dateString) || $dateString === '0000-00-00' || $dateString === '0000-00-00 00:00:00') {
        return 'N/A';
    }
    
    try {
        // Crear objeto DateTime con la zona horaria de Bogotá
        $date = new DateTime($dateString, new DateTimeZone('America/Bogota'));
        return $date->format('d/m/Y H:i');
    } catch (Exception $e) {
        // Si falla, intentar formatear directamente
        if (strtotime($dateString) !== false) {
            return date('d/m/Y H:i', strtotime($dateString));
        }
        return $dateString;
    }
}

// ------------------------------------------------------------------------------------
// FUNCIÓN PARA PROCESAR VALORES ESPECIALES (JSON, ARRAYS, ETC.)
// ------------------------------------------------------------------------------------
function procesarValor($value, $key, $type) {
    if (empty($value) && $value !== 0 && $value !== '0') {
        return 'N/A';
    }
    
    // Si es específicamente para el campo de fases formateadas en seguimiento
    if ($type === 'seguimiento' && $key === 'fases_formateadas') {
        return $value; // Ya viene formateado
    }
    
    // Si es el array original de fases (lo ignoramos porque usamos fases_formateadas)
    if ($type === 'seguimiento' && $key === 'fases') {
        return ''; // Vacío porque lo manejamos aparte
    }
    
    // Manejar JSON strings
    if (is_string($value) && (strpos($value, '{') === 0 || strpos($value, '[') === 0)) {
        $decoded = json_decode($value, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            return formatearJSON($decoded, $key);
        }
    }
    
    // Manejar arrays directamente
    if (is_array($value)) {
        return formatearJSON($value, $key);
    }
    
    // Manejar booleanos
    if (is_bool($value)) {
        return $value ? 'Sí' : 'No';
    }
    
    // Formatear fechas
    if (strpos($key, 'fecha') !== false || 
        strpos($key, 'inicio') !== false || 
        strpos($key, 'termina') !== false ||
        strpos($key, 'date') !== false ||
        strpos($key, 'actualizacion') !== false) {
        return formatearFecha($value);
    }
    
    // Limpiar texto
    return htmlspecialchars_decode(strip_tags(trim($value)));
}

// ------------------------------------------------------------------------------------
// FUNCIÓN PARA FORMATEAR JSON/ARRAYS
// ------------------------------------------------------------------------------------
function formatearJSON($data, $key) {
    $result = '';
    
    if (is_array($data)) {
        foreach ($data as $subKey => $subValue) {
            if (is_array($subValue)) {
                $result .= "{$subKey}:\n";
                foreach ($subValue as $k => $v) {
                    $result .= "  {$k}: {$v}\n";
                }
            } else {
                $result .= "{$subKey}: {$subValue}\n";
            }
        }
    }
    
    return trim($result);
}

// ------------------------------------------------------------------------------------
// FUNCIÓN PARA LIMPIAR Y FORMATEAR DATOS - CORREGIDA PARA SEGUIMIENTO
// ------------------------------------------------------------------------------------
function limpiarYFormatearDatos($data, $type, $columnNames) {
    $cleanData = [];
    
    // Campos a excluir según tipo
    $excludeFields = [
        'incapacidades' => ['created_at', 'updated_at', 'deleted_at'],
        'historial' => ['id_historial', 'id_incapacidad'],
        'seguimiento' => ['fases', 'estado', 'tipo_incapacidad', 'eps_arl', 'cod_diagnostico', 'mes'] // Excluir fases originales
    ];
    
    foreach ($data as $row) {
        $fila = [];
        
        // ========================================================================
        // PROCESAMIENTO ESPECIAL PARA SEGUIMIENTO - EXTRAER Y FORMATEAR FASES
        // ========================================================================
        if ($type === 'seguimiento' && isset($row['fases']) && is_array($row['fases'])) {
            $fasesFormateadas = [];
            $fasesDefinidas = ["RADICADO", "SIN RADICAR", "RESPUESTA EPS", "PAGO", "FINALIZADO"];
            
            foreach ($fasesDefinidas as $nombreFase) {
                $faseKey = strtoupper(trim($nombreFase));
                
                if (isset($row['fases'][$faseKey]) && is_array($row['fases'][$faseKey])) {
                    $detallesFase = $row['fases'][$faseKey];
                    
                    $estado = $detallesFase['estado'] ?? 'PENDIENTE';
                    $fecha = isset($detallesFase['fecha_actualizacion']) ? 
                             formatearFecha($detallesFase['fecha_actualizacion']) : 'N/A';
                    $descripcion = $detallesFase['descripcion'] ?? '';
                    $evidencia = $detallesFase['evidencia'] ?? '';
                    
                    $linea = "• {$nombreFase}: {$estado}";
                    if ($fecha !== 'N/A') {
                        $linea .= " (Actualizado: {$fecha})";
                    }
                    
                    if (!empty($descripcion)) {
                        $linea .= "\n  Descripción: {$descripcion}";
                    }
                    
                    if (!empty($evidencia)) {
                        $linea .= "\n  Evidencia: {$evidencia}";
                    }
                    
                    $fasesFormateadas[] = $linea;
                } else {
                    $fasesFormateadas[] = "• {$nombreFase}: PENDIENTE";
                }
            }
            
            $row['fases_formateadas'] = implode("\n\n", $fasesFormateadas);
        }
        
        // ========================================================================
        // PROCESAR TODOS LOS CAMPOS
        // ========================================================================
        foreach ($row as $key => $value) {
            // Normalizar nombre de columna
            $key = strtolower(trim($key));
            
            // Excluir campos no deseados
            $exclude = $excludeFields[$type] ?? [];
            if (in_array($key, $exclude)) {
                continue;
            }
            
            // Si es el campo fases_formateadas que creamos
            if ($type === 'seguimiento' && $key === 'fases_formateadas') {
                $displayName = 'Fases del Seguimiento';
                $displayValue = $value;
            } 
            // Para otros campos, usar nombre amigable
            else {
                $displayName = $columnNames[$key] ?? ucfirst(str_replace('_', ' ', $key));
                $displayValue = procesarValor($value, $key, $type);
            }
            
            $fila[$displayName] = $displayValue;
        }
        
        $cleanData[] = $fila;
    }
    
    return $cleanData;
}

// --------------------------------------------------------------------------------
// LIMPIAR Y FORMATEAR LOS DATOS
// --------------------------------------------------------------------------------
$cleanData = limpiarYFormatearDatos($data, $type, $columnNames);

// --------------------------------------------------------------------------------
// CREAR EXCEL
// --------------------------------------------------------------------------------
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();

// ----------------------------------------------------------------
// ESTILOS PREDEFINIDOS
// ----------------------------------------------------------------
$styles = [
    'titulo_principal' => [
        'font' => [
            'bold' => true,
            'size' => 16,
            'color' => ['rgb' => '1F4E78']
        ],
        'alignment' => [
            'horizontal' => Alignment::HORIZONTAL_CENTER,
            'vertical' => Alignment::VERTICAL_CENTER
        ]
    ],
    
    'subtitulo' => [
        'font' => [
            'bold' => true,
            'size' => 12,
            'color' => ['rgb' => '2E75B6']
        ],
        'alignment' => [
            'horizontal' => Alignment::HORIZONTAL_CENTER
        ]
    ],
    
    'encabezado' => [
        'font' => [
            'bold' => true,
            'color' => ['rgb' => 'FFFFFF'],
            'size' => 11
        ],
        'fill' => [
            'fillType' => Fill::FILL_SOLID,
            'startColor' => ['rgb' => '2E75B6']
        ],
        'alignment' => [
            'horizontal' => Alignment::HORIZONTAL_CENTER,
            'vertical' => Alignment::VERTICAL_CENTER,
            'wrapText' => true
        ],
        'borders' => [
            'allBorders' => [
                'borderStyle' => Border::BORDER_THIN,
                'color' => ['rgb' => 'FFFFFF']
            ]
        ]
    ],
    
    'celda_datos' => [
        'borders' => [
            'allBorders' => [
                'borderStyle' => Border::BORDER_THIN,
                'color' => ['rgb' => 'DDDDDD']
            ]
        ],
        'alignment' => [
            'vertical' => Alignment::VERTICAL_TOP,
            'wrapText' => true
        ]
    ],
    
    'celda_fecha' => [
        'numberFormat' => [
            'formatCode' => 'dd/mm/yyyy hh:mm'
        ]
    ],
    
    'celda_fases' => [
        'alignment' => [
            'vertical' => Alignment::VERTICAL_TOP,
            'wrapText' => true,
            'horizontal' => Alignment::HORIZONTAL_LEFT
        ]
    ]
];

// ----------------------------------------------------------------
// CABECERA DEL REPORTE
// ----------------------------------------------------------------
$row = 1;

// Logo o título de la empresa
$sheet->setCellValue("A{$row}", "TERMINAL DE TRANSPORTE DE IBAGUÉ S.A.");
$sheet->mergeCells("A{$row}:K{$row}");
$sheet->getStyle("A{$row}")->applyFromArray($styles['titulo_principal']);
$sheet->getRowDimension($row)->setRowHeight(25);
$row++;

// Subtítulos
foreach ($subtitles as $subtitle) {
    $sheet->setCellValue("A{$row}", $subtitle);
    $sheet->mergeCells("A{$row}:K{$row}");
    $sheet->getStyle("A{$row}")->applyFromArray($styles['subtitulo']);
    $sheet->getRowDimension($row)->setRowHeight(20);
    $row++;
}
$row++;

// Título del reporte
$sheet->setCellValue("A{$row}", $title);
$sheet->mergeCells("A{$row}:K{$row}");
$sheet->getStyle("A{$row}")->applyFromArray($styles['titulo_principal']);
$sheet->getRowDimension($row)->setRowHeight(30);
$row++;

// Información del reporte (CON HORA CORRECTA DE COLOMBIA)
$sheet->setCellValue("A{$row}", "Fecha de generación: " . date('d/m/Y H:i:s'));
$sheet->mergeCells("A{$row}:K{$row}");
$sheet->getStyle("A{$row}")->getFont()->setItalic(true);
$sheet->getRowDimension($row)->setRowHeight(20);
$row++;

$sheet->setCellValue("A{$row}", "Total de registros: " . count($cleanData));
$sheet->mergeCells("A{$row}:K{$row}");
$sheet->getStyle("A{$row}")->getFont()->setItalic(true);
$sheet->getRowDimension($row)->setRowHeight(20);
$row += 2; // Espacio antes de la tabla

// ----------------------------------------------------------------
// ENCABEZADOS DE LA TABLA
// ----------------------------------------------------------------
if (!empty($cleanData)) {
    $headers = array_keys($cleanData[0]);
    $col = 'A';
    
    foreach ($headers as $header) {
        $sheet->setCellValue("{$col}{$row}", $header);
        $col++;
    }
    
    // Aplicar estilo a encabezados
    $lastCol = chr(ord('A') + count($headers) - 1);
    $headerRange = "A{$row}:{$lastCol}{$row}";
    $sheet->getStyle($headerRange)->applyFromArray($styles['encabezado']);
    
    // Altura de fila para encabezado
    $sheet->getRowDimension($row)->setRowHeight(30);
    $row++;
    
    // ----------------------------------------------------------------
    // DATOS DE LA TABLA
    // ----------------------------------------------------------------
    $startDataRow = $row;
    
    foreach ($cleanData as $index => $fila) {
        $col = 'A';
        
        foreach ($fila as $valor) {
            $cell = "{$col}{$row}";
            $sheet->setCellValue($cell, $valor);
            
            // Aplicar formato especial para fechas
            $headerName = $headers[$col === 'A' ? 0 : ord($col) - 65];
            if (strpos($headerName, 'Fecha') !== false || 
                strpos($headerName, 'Inicio') !== false || 
                strpos($headerName, 'Fin') !== false) {
                $sheet->getStyle($cell)->applyFromArray($styles['celda_fecha']);
            }
            
            // Aplicar estilo especial para fases
            if ($headerName === 'Fases del Seguimiento') {
                $sheet->getStyle($cell)->applyFromArray($styles['celda_fases']);
                $sheet->getStyle($cell)->getFont()->setSize(10);
            }
            
            // Aplicar estilo de datos básico
            $sheet->getStyle($cell)->applyFromArray($styles['celda_datos']);
            
            $col++;
        }
        
        // Alternar colores de fila para mejor legibilidad
        if ($index % 2 == 0) {
            $range = "A{$row}:{$lastCol}{$row}";
            $sheet->getStyle($range)->getFill()
                  ->setFillType(Fill::FILL_SOLID)
                  ->getStartColor()->setARGB('F0F8FF');
        }
        
        // Auto-ajustar altura para contenido largo
        $sheet->getRowDimension($row)->setRowHeight(-1);
        $row++;
    }
    
    // ----------------------------------------------------------------
    // AJUSTAR ANCHO DE COLUMNAS
    // ----------------------------------------------------------------
    foreach (range('A', $lastCol) as $column) {
        $sheet->getColumnDimension($column)->setAutoSize(true);
        
        // Ancho específico para columnas con mucho contenido
        $colIndex = ord($column) - 65;
        $headerName = $headers[$colIndex] ?? '';
        
        if ($headerName === 'Fases del Seguimiento') {
            $sheet->getColumnDimension($column)->setWidth(40);
        } elseif (strpos($headerName, 'Observaciones') !== false || 
                  strpos($headerName, 'Descripción') !== false) {
            $sheet->getColumnDimension($column)->setWidth(30);
        } else {
            $sheet->getColumnDimension($column)->setWidth(20);
        }
    }
    
    // Congelar paneles (encabezados fijos)
    $sheet->freezePane("A" . ($startDataRow));
    
    // Aplicar bordes a todo el rango de datos
    $endRow = $startDataRow + count($cleanData) - 1;
    if ($endRow >= $startDataRow) {
        $dataRange = "A{$startDataRow}:{$lastCol}{$endRow}";
        $sheet->getStyle($dataRange)
              ->getBorders()->getAllBorders()
              ->setBorderStyle(Border::BORDER_THIN)
              ->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('DDDDDD'));
    }
    
    // ----------------------------------------------------------------
    // PIE DE PÁGINA
    // ----------------------------------------------------------------
    $row += 2;
    $sheet->setCellValue("A{$row}", "*** FIN DEL REPORTE ***");
    $sheet->mergeCells("A{$row}:{$lastCol}{$row}");
    $sheet->getStyle("A{$row}")->getFont()->setBold(true)
          ->getColor()->setARGB('808080');
    $sheet->getStyle("A{$row}")->getAlignment()
          ->setHorizontal(Alignment::HORIZONTAL_CENTER);
}

// ----------------------------------------------------------------
// CONFIGURACIÓN DE LA HOJA
// ----------------------------------------------------------------
$sheet->setTitle(substr($title, 0, 31)); // Máximo 31 caracteres para nombre de hoja
$sheet->getPageSetup()->setOrientation(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::ORIENTATION_LANDSCAPE);
$sheet->getPageSetup()->setPaperSize(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::PAPERSIZE_A4);
$sheet->getPageSetup()->setFitToWidth(1);
$sheet->getPageSetup()->setFitToHeight(0);

// Configurar márgenes
$sheet->getPageMargins()->setTop(0.5);
$sheet->getPageMargins()->setRight(0.5);
$sheet->getPageMargins()->setLeft(0.5);
$sheet->getPageMargins()->setBottom(0.5);

// Mostrar líneas de cuadrícula
$sheet->setShowGridlines(true);

// ----------------------------------------------------------------
// DESCARGA DEL ARCHIVO
// ----------------------------------------------------------------
header("Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet");
header("Content-Disposition: attachment; filename=\"" . rawurlencode($filename) . "\"");
header("Cache-Control: max-age=0");
header("Expires: 0");
header("Pragma: public");

$writer = new Xlsx($spreadsheet);
$writer->save("php://output");
exit;