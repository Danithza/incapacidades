<?php
require_once __DIR__ . '/../config/db.php';

$id = $_POST['id'] ?? null;

if (!$id) {
    echo "ID inválido";
    exit;
}

// Cambiar estado y mover a historial
$stmt = $pdo->prepare("
    UPDATE incapacidades 
    SET estado = 'pagado', en_historial = 1
    WHERE id = ?
");
$stmt->execute([$id]);

echo "OK";
