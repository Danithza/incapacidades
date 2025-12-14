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
// OBTENER PARÁMETROS DE FILTROS
// --------------------------------------------------------------------------------
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
        'data' => $inc->getFiltered($fecha, $empleado, $area, $diagnostico, $estado, $eps_arl),
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
            'eps_arl' => 'EPS/ARL',
            'tipo_incapacidad' => 'Tipo'
        ]
    ],
    
    'historial' => [
        'data' => $hist->getFiltered($fecha, $empleado, $accion, $usuario),
        'filename' => "Reporte_Historial_" . date('Y-m-d_His') . ".xlsx",
        'title' => "HISTORIAL DE INCAPACIDADES FINALIZADAS",
        'subtitles' => [
            "Terminal de Transporte de Ibagué",
            "Historial con Seguimiento de Fases"
        ],
        'column_names' => [
            'id' => 'ID Historial',
            'incapacidad_id' => 'ID Incapacidad',
            'numero_incapacidad' => 'N° Incapacidad',
            'nombre_empleado' => 'Empleado',
            'cedula' => 'Cédula',
            'area' => 'Área',
            'diagnostico' => 'Diagnóstico',
            'inicio' => 'Fecha Inicio',
            'termina' => 'Fecha Fin',
            'dias_incapacidad' => 'Días',
            'estado' => 'Estado',
            'eps_arl' => 'EPS/ARL',
            'tipo_incapacidad' => 'Tipo Incapacidad',
            'fases_formateadas' => 'Fases del Seguimiento'
        ]
    ],
    
    'seguimiento' => [
        'data' => $seg->getFiltered($fecha, $empleado, $area, $estado_proceso),
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
            'fases_formateadas' => 'Fases del Seguimiento'
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
    
    // Si es específicamente para el campo de fases formateadas
    if (($type === 'seguimiento' || $type === 'historial') && $key === 'fases_formateadas') {
        return $value; // Ya viene formateado
    }
    
    // Si es el array original de fases
    if (($type === 'seguimiento' || $type === 'historial') && $key === 'fases') {
        return ''; // Vacío porque lo manejamos aparte
    }
    
    // Si es JSON de fases en historial
    if ($type === 'historial' && $key === 'fases_json') {
        return ''; // Vacío porque lo manejamos aparte en fases_formateadas
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
        strpos($key, 'actualizacion') !== false ||
        strpos($key, 'creado_en') !== false) {
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
// FUNCIÓN PARA FORMATEAR FASES PARA HISTORIAL
// ------------------------------------------------------------------------------------
function formatearFasesHistorial($fases_json) {
    if (empty($fases_json) || !is_array($fases_json)) {
        return "No hay fases registradas";
    }
    
    $fasesFormateadas = [];
    $fasesDefinidas = ["RADICADO", "SIN RADICAR", "RESPUESTA EPS", "PAGO", "FINALIZADO"];
    
    foreach ($fasesDefinidas as $nombreFase) {
        // Buscar esta fase en el JSON
        $faseEncontrada = null;
        foreach ($fases_json as $fase) {
            if (isset($fase['nombre_fase']) && strtoupper(trim($fase['nombre_fase'])) === $nombreFase) {
                $faseEncontrada = $fase;
                break;
            }
        }
        
        if ($faseEncontrada) {
            $estado = $faseEncontrada['estado'] ?? 'PENDIENTE';
            $fecha = isset($faseEncontrada['fecha_actualizacion']) ? 
                     formatearFecha($faseEncontrada['fecha_actualizacion']) : 'N/A';
            $descripcion = $faseEncontrada['descripcion'] ?? '';
            $evidencia = $faseEncontrada['evidencia'] ?? '';
            
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
            $fasesFormateadas[] = "• {$nombreFase}: NO REGISTRADA";
        }
    }
    
    return implode("\n\n", $fasesFormateadas);
}

// ------------------------------------------------------------------------------------
// FUNCIÓN PARA LIMPIAR Y FORMATEAR DATOS - CORREGIDA PARA SEGUIMIENTO
// ------------------------------------------------------------------------------------
function limpiarYFormatearDatos($data, $type, $columnNames) {
    $cleanData = [];
    
    if (empty($data)) {
        return $cleanData;
    }
    
    // Campos a excluir según tipo
    $excludeFields = [
        'incapacidades' => ['created_at', 'updated_at', 'deleted_at', 'fases_json', 'estado_proceso', 'aplicacion_pago', 'numero_orden', 'creado_en'],
        'historial' => [
            'fases_json', 'fecha_finalizacion', 'cod_diagnostico', 'valor', 'valor_aprox', 
            'estado_proceso', 'aplicacion_pago', 'numero_orden', 'creado_en',
            'mes', 'dias_a_cargo_entidad', 'observaciones'  // Excluir estos también
        ],
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
        // PROCESAMIENTO ESPECIAL PARA HISTORIAL - EXTRAER Y FORMATEAR FASES DEL JSON
        // ========================================================================
        if ($type === 'historial') {
            // Formatear fases desde el JSON
            if (isset($row['fases_json']) && !empty($row['fases_json'])) {
                if (is_string($row['fases_json'])) {
                    $fases_json = json_decode($row['fases_json'], true);
                } else {
                    $fases_json = $row['fases_json'];
                }
                
                if (is_array($fases_json)) {
                    $row['fases_formateadas'] = formatearFasesHistorial($fases_json);
                } else {
                    $row['fases_formateadas'] = "No hay datos de fases";
                }
            } else {
                $row['fases_formateadas'] = "No hay fases registradas";
            }
            
            // Para historial, usar 'creado_en' como fecha si no hay otra
            if (!isset($row['fecha_registro']) && isset($row['creado_en'])) {
                $row['fecha_registro'] = $row['creado_en'];
            }
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
            if (($type === 'seguimiento' || $type === 'historial') && $key === 'fases_formateadas') {
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
$sheet->mergeCells("A{$row}:N{$row}");
$sheet->getStyle("A{$row}")->applyFromArray($styles['titulo_principal']);
$sheet->getRowDimension($row)->setRowHeight(25);
$row++;

// Subtítulos
foreach ($subtitles as $subtitle) {
    $sheet->setCellValue("A{$row}", $subtitle);
    $sheet->mergeCells("A{$row}:N{$row}");
    $sheet->getStyle("A{$row}")->applyFromArray($styles['subtitulo']);
    $sheet->getRowDimension($row)->setRowHeight(20);
    $row++;
}
$row++;

// Título del reporte
$sheet->setCellValue("A{$row}", $title);
$sheet->mergeCells("A{$row}:N{$row}");
$sheet->getStyle("A{$row}")->applyFromArray($styles['titulo_principal']);
$sheet->getRowDimension($row)->setRowHeight(30);
$row++;

// Información del reporte (CON HORA CORRECTA DE COLOMBIA)
$sheet->setCellValue("A{$row}", "Fecha de generación: " . date('d/m/Y H:i:s'));
$sheet->mergeCells("A{$row}:N{$row}");
$sheet->getStyle("A{$row}")->getFont()->setItalic(true);
$sheet->getRowDimension($row)->setRowHeight(20);
$row++;

$sheet->setCellValue("A{$row}", "Total de registros: " . count($cleanData));
$sheet->mergeCells("A{$row}:N{$row}");
$sheet->getStyle("A{$row}")->getFont()->setItalic(true);
$sheet->getRowDimension($row)->setRowHeight(20);
$row++;

// Mostrar filtros aplicados (si hay)
$filtros_aplicados = [];
if(!empty($fecha)) $filtros_aplicados[] = "Fecha: $fecha";
if(!empty($empleado)) $filtros_aplicados[] = "Empleado: $empleado";
if(!empty($area)) $filtros_aplicados[] = "Área: $area";
if(!empty($diagnostico)) $filtros_aplicados[] = "Diagnóstico: $diagnostico";
if(!empty($estado)) $filtros_aplicados[] = "Estado: $estado";
if(!empty($accion)) $filtros_aplicados[] = "Acción: $accion";
if(!empty($usuario)) $filtros_aplicados[] = "Usuario: $usuario";
if(!empty($estado_proceso)) $filtros_aplicados[] = "Estado Proceso: $estado_proceso";
if(!empty($eps_arl)) $filtros_aplicados[] = "EPS/ARL: $eps_arl";

if(!empty($filtros_aplicados)) {
    $filtros_text = "Filtros aplicados: " . implode(" | ", $filtros_aplicados);
    $sheet->setCellValue("A{$row}", $filtros_text);
    $sheet->mergeCells("A{$row}:N{$row}");
    $sheet->getStyle("A{$row}")->getFont()->setItalic(true)->setSize(10);
    $sheet->getRowDimension($row)->setRowHeight(20);
    $row++;
}

$row += 1; // Espacio antes de la tabla

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
    
    // Aplicar estilo a encabezados - VERIFICAR QUE HAYA HEADERS
    $lastCol = 'A';
    if (count($headers) > 0) {
        $lastCol = chr(ord('A') + count($headers) - 1);
        $headerRange = "A{$row}:{$lastCol}{$row}";
        $sheet->getStyle($headerRange)->applyFromArray($styles['encabezado']);
    }
    
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
            $headerName = $headers[$col === 'A' ? 0 : ord($col) - 65] ?? '';
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
        if ($index % 2 == 0 && $lastCol !== 'A') {
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
    if ($lastCol !== 'A') {
        foreach (range('A', $lastCol) as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
            
            // Ancho específico para columnas con mucho contenido
            $colIndex = ord($column) - 65;
            $headerName = $headers[$colIndex] ?? '';
            
            if ($headerName === 'Fases del Seguimiento') {
                $sheet->getColumnDimension($column)->setWidth(40);
            } elseif (strpos($headerName, 'Observaciones') !== false || 
                      strpos($headerName, 'Descripción') !== false ||
                      strpos($headerName, 'Diagnóstico') !== false) {
                $sheet->getColumnDimension($column)->setWidth(30);
            } elseif (strpos($headerName, 'Empleado') !== false) {
                $sheet->getColumnDimension($column)->setWidth(25);
            } else {
                $sheet->getColumnDimension($column)->setWidth(15);
            }
        }
        
        // Congelar paneles (encabezados fijos) solo si hay datos
        if ($startDataRow <= $row) {
            $sheet->freezePane("A" . ($startDataRow));
        }
        
        // Aplicar bordes a todo el rango de datos
        $endRow = $startDataRow + count($cleanData) - 1;
        if ($endRow >= $startDataRow) {
            $dataRange = "A{$startDataRow}:{$lastCol}{$endRow}";
            $sheet->getStyle($dataRange)
                  ->getBorders()->getAllBorders()
                  ->setBorderStyle(Border::BORDER_THIN)
                  ->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('DDDDDD'));
        }
    }
    
    // ----------------------------------------------------------------
    // PIE DE PÁGINA
    // ----------------------------------------------------------------
    $row += 2;
    if ($lastCol !== 'A') {
        $sheet->setCellValue("A{$row}", "*** FIN DEL REPORTE ***");
        $sheet->mergeCells("A{$row}:{$lastCol}{$row}");
        $sheet->getStyle("A{$row}")->getFont()->setBold(true)
              ->getColor()->setARGB('808080');
        $sheet->getStyle("A{$row}")->getAlignment()
              ->setHorizontal(Alignment::HORIZONTAL_CENTER);
    }
} else {
    // Si no hay datos, mostrar mensaje
    $sheet->setCellValue("A{$row}", "No hay datos para mostrar con los filtros aplicados.");
    $sheet->mergeCells("A{$row}:N{$row}");
    $sheet->getStyle("A{$row}")->getFont()->setItalic(true)->setSize(12);
    $sheet->getStyle("A{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    $sheet->getRowDimension($row)->setRowHeight(30);
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