<?php
ob_start();

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../controllers/IncapacidadesController.php';
require_once __DIR__ . '/../controllers/HistorialController.php';
require_once __DIR__ . '/../vendor/autoload.php';

use Dompdf\Dompdf;
use Dompdf\Options;

$type = $_GET['type'] ?? 'incapacidades';

$inc = new IncapacidadesController($pdo);
$hist = new HistorialController($pdo);

$data = $type === "historial" ? $hist->obtenerHistorial() : $inc->getAll();
$title = $type === "historial" ? "Informe Ejecutivo de Historial" : "Informe Ejecutivo de Incapacidades";

// Si no hay datos
if (!$data || count($data) === 0) {
    $data = [["mensaje" => "No existen registros disponibles para este informe."]];
}

// CAMPOS A OCULTAR
$ocultar = [
    "id", "id_incapacidad", "id_historial", "id_registro",
    "valor_aprox", "estado_proceso", "aplicacion_pago", "observaciones"
];

function limpiarValor($v) {
    if (is_array($v) || is_object($v)) {
        return json_encode($v, JSON_UNESCAPED_UNICODE);
    }
    return (string)$v;
}

// ----------------------
// ESTILO DEL DOCUMENTO
// ----------------------
$html = "
<style>
body {
    font-family: DejaVu Sans, sans-serif;
    font-size: 13px;
    line-height: 1.6;
    margin: 30px;
}
.header {
    text-align: center;
    margin-bottom: 30px;
}
.header h1 {
    font-size: 22px;
    margin: 0;
}
.header h3 {
    margin-top: 5px;
    font-size: 15px;
    color: #555;
}
.section-title {
    margin-top: 25px;
    font-size: 16px;
    font-weight: bold;
    color: #222;
    border-bottom: 1px solid #ccc;
    padding-bottom: 5px;
}
.record {
    margin-top: 15px;
    padding: 12px;
    background: #f7f7f7;
    border-left: 4px solid #444;
}
.label {
    font-weight: bold;
}
</style>

<div class='header'>
    <h1>TERMINAL DE TRANSPORTES DE IBAGUÉ - TOLIMA</h1>
    <h3>$title</h3>
</div>

<p>
Este informe ejecutivo presenta de manera narrativa los registros procesados por el sistema,
correspondientes al módulo <b>$title</b>. La información ha sido consolidada con el fin de facilitar
el análisis administrativo de cada caso.
</p>

<div class='section-title'>Resumen General</div>
<p>Cantidad total de registros incluidos en este informe: <b>" . count($data) . "</b></p>

<div class='section-title'>Detalle Narrativo de los Registros</div>
";

// ----------------------
// INFORME NARRATIVO
// ----------------------
foreach ($data as $i => $row) {

    $html .= "<div class='record'><b>Registro " . ($i+1) . ":</b><br>";

    foreach ($row as $key => $val) {

        // No mostrar campos prohibidos
        if (in_array(strtolower($key), $ocultar)) {
            continue;
        }

        $keyLabel = ucfirst(str_replace("_", " ", $key));
        $html .= "<span class='label'>$keyLabel:</span> " . limpiarValor($val) . "<br>";
    }

    $html .= "</div>";
}

$dompdf = new Dompdf((new Options())->set('isRemoteEnabled', true));
$dompdf->loadHtml($html, "UTF-8");
$dompdf->setPaper("A4", "portrait");
$dompdf->render();

// Headers PDF
header("Content-Type: application/pdf");
header("Content-Disposition: attachment; filename=\"$title.pdf\"");
header("Cache-Control: public, must-revalidate, max-age=0");

echo $dompdf->output();
exit;
