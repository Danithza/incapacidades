<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../controllers/HistorialController.php';

header("Content-Type: application/json");
$data = json_decode(file_get_contents("php://input"), true);

if (!$data || !isset($data['id'])) {
    echo json_encode(["success" => false, "error" => "ID faltante"]);
    exit;
}

$id = intval($data['id']);
$historialController = new HistorialController($pdo);

try {
    $mover = $historialController->moverAlHistorial($id);

    if ($mover) {
        echo json_encode(["success" => true, "redirect" => "/incapacidades/views/historial.php"]);
    } else {
        echo json_encode(["success" => false, "error" => "Incapacidad no encontrada"]);
    }

} catch (Exception $e) {
    echo json_encode(["success" => false, "error" => $e->getMessage()]);
}
