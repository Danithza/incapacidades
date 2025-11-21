<?php
require_once __DIR__ . '/../config/db.php';

header("Content-Type: application/json");

$data = json_decode(file_get_contents("php://input"), true);

// Verificar datos
if (!$data || empty($data['id'])) {
    echo json_encode(["success" => false, "error" => "Datos incompletos"]);
    exit;
}

$id = $data['id'];
$estado = 'finalizado'; // 🔥 Estado fijo, porque al marcar pasa a finalizado

try {
    $stmt = $pdo->prepare("UPDATE incapacidades SET estado = ? WHERE id = ?");
    $stmt->execute([$estado, $id]);

    echo json_encode([
        "success" => true,
        "redirect" => "/incapacidades/historial" // 🔥 Redirección automática
    ]);

} catch (Exception $e) {
    echo json_encode(["success" => false, "error" => $e->getMessage()]);
}
