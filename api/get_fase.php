<?php
// api/get_fase.php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config/db.php';

$incId = $_GET['incapacidad_id'] ?? null;
$nombre = $_GET['nombre_fase'] ?? null;

if (!$incId || !$nombre) {
    echo json_encode(['success'=>false, 'error'=>'Faltan parámetros']);
    exit;
}

$sql = "SELECT * FROM fases WHERE incapacidad_id = :inc AND UPPER(nombre_fase) = UPPER(:nombre) LIMIT 1";
$stmt = $pdo->prepare($sql);
$stmt->execute(['inc' => $incId, 'nombre' => $nombre]);
$f = $stmt->fetch(PDO::FETCH_ASSOC);

echo json_encode(['success' => true, 'fase' => $f]);
