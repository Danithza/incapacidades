<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../controllers/IncapacidadesController.php';
require_once __DIR__ . '/../controllers/HistorialController.php';
require_once __DIR__ . '/../controllers/SeguimientoController.php';

require_once __DIR__ . '/../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

// ----------------------------------------------------------------
// LIMPIA LA SALIDA ANTES DE GENERAR EL ARCHIVO
// ----------------------------------------------------------------
ob_clean();

$type = $_GET['type'] ?? 'incapacidades';

$inc = new IncapacidadesController($pdo);
$hist = new HistorialController($pdo);
$seg = new SeguimientoController($pdo);

switch ($type) {
    case 'incapacidades':
        $data = $inc->getAll();
        $filename = "reporte_incapacidades.xlsx";
        $title = "REPORTE DE INCAPACIDADES";
        break;

    case 'historial':
        $data = $hist->obtenerHistorial();
        $filename = "reporte_historial.xlsx";
        $title = "REPORTE HISTORIAL DE INCAPACIDADES";
        break;

    case 'seguimiento':
        $data = $seg->index();
        $filename = "reporte_seguimiento.xlsx";
        $title = "REPORTE DE SEGUIMIENTO";
        break;
}

// ------------------------------------------------------------------------------------
// CAMPOS QUE DEBEN OCULTARSE EN EL EXCEL (no aparecerán en las columnas exportadas)
// ------------------------------------------------------------------------------------
$ocultar = [
    "id", "id_incapacidad", "id_historial", "id_registro",
    "valor_aprox", "estado_proceso", "aplicacion_pago", "observaciones"
];

// ------------------------------------------------------------------------------------
// LIMPIAR LOS DATOS Y ELIMINAR CAMPOS NO DESEADOS
// ------------------------------------------------------------------------------------
$cleanData = [];

foreach ($data as $row) {
    $fila = [];

    foreach ($row as $key => $value) {

        // Ocultar campos no deseados
        if (in_array(strtolower($key), $ocultar)) {
            continue;
        }

        // Convertir JSON/arrays en texto legible
        if (is_array($value)) {
            $texto = "";

            foreach ($value as $faseNombre => $faseData) {
                if (is_array($faseData)) {
                    $estado = $faseData["estado"] ?? "sin estado";
                    $fecha = $faseData["fecha_actualizacion"] ?? "-";
                    $texto .= "$faseNombre: $estado — $fecha\n";
                }
            }
            $value = trim($texto);
        }

        if ($value === null) $value = "";
        if (is_bool($value)) $value = $value ? "Sí" : "No";

        $fila[$key] = $value;
    }

    $cleanData[] = $fila;
}

// ----------------------------------------------------------------
// CREAR EXCEL
// ----------------------------------------------------------------
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();

// ----------------------------------------------------------------
// ENCABEZADO DEL DOCUMENTO
// ----------------------------------------------------------------
$sheet->setCellValue("A1", "TERMINAL DE TRANSPORTE DE IBAGUÉ");
$sheet->setCellValue("A2", $title);
$sheet->setCellValue("A3", "Fecha: " . date("Y-m-d H:i"));

$sheet->mergeCells("A1:D1");
$sheet->mergeCells("A2:D2");
$sheet->mergeCells("A3:D3");

$sheet->getStyle("A1:A3")->getFont()->setBold(true)->setSize(14);

// ----------------------------------------------------------------
// ENCABEZADOS DEL EXCEL
// ----------------------------------------------------------------
if (!empty($cleanData)) {

    $sheet->fromArray(array_values(array_keys($cleanData[0])), NULL, 'A5');

    // Estilo encabezados
    $headerStyle = [
        'font' => [
            'bold' => true,
            'color' => ['rgb' => 'FFFFFF']
        ],
        'fill' => [
            'fillType' => Fill::FILL_SOLID,
            'startColor' => ['rgb' => '1F4E78']
        ]
    ];

    $lastColumn = chr(ord('A') + count($cleanData[0]) - 1);

    $sheet->getStyle("A5:{$lastColumn}5")->applyFromArray($headerStyle);

    // ----------------------------------------------------------------
    // DATOS
    // ----------------------------------------------------------------
    $sheet->fromArray($cleanData, NULL, 'A6');

    // Bordes
    $dataEnd = 6 + count($cleanData) - 1;
    $sheet->getStyle("A5:{$lastColumn}{$dataEnd}")
        ->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);

    // Ajustar columnas
    foreach (range('A', $lastColumn) as $col) {
        $sheet->getColumnDimension($col)->setAutoSize(true);
    }
}

// ----------------------------------------------------------------
// DESCARGA DEL ARCHIVO
// ----------------------------------------------------------------
header("Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet");
header("Content-Disposition: attachment; filename=$filename");
header("Cache-Control: max-age=0");

$writer = new Xlsx($spreadsheet);
$writer->save("php://output");
exit;
