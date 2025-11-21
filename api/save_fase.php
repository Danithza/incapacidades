<?php
// api/save_fase.php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config/db.php';

// params
$incapacidad_id = $_POST['incapacidad_id'] ?? null;
$nombre_fase = $_POST['nombre_fase'] ?? null;
$descripcion = $_POST['descripcion'] ?? null;

if (!$incapacidad_id || !$nombre_fase) {
    echo json_encode(['success'=>false, 'error'=>'Faltan parámetros']);
    exit;
}

$nombre_fase_up = strtoupper($nombre_fase);

// handle file upload
$evidenceFilename = null;
if (!empty($_FILES['evidencia']) && $_FILES['evidencia']['error'] !== UPLOAD_ERR_NO_FILE) {
    $f = $_FILES['evidencia'];
    if ($f['error'] !== UPLOAD_ERR_OK) {
        echo json_encode(['success'=>false, 'error'=>'Error en archivo']);
        exit;
    }

    // validate mime / extension
    $allowed = ['application/pdf','image/jpeg','image/png'];
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $m = finfo_file($finfo, $f['tmp_name']);
    finfo_close($finfo);
    if (!in_array($m, $allowed)) {
        echo json_encode(['success'=>false,'error'=>'Tipo de archivo no permitido']);
        exit;
    }

    // generar nombre seguro
    $ext = pathinfo($f['name'], PATHINFO_EXTENSION);
    $safe = uniqid('fase_') . '.' . $ext;
    $destDir = __DIR__ . '/../uploads/fases/';
    if (!is_dir($destDir)) mkdir($destDir, 0755, true);
    $dest = $destDir . $safe;
    if (!move_uploaded_file($f['tmp_name'], $dest)) {
        echo json_encode(['success'=>false,'error'=>'No se pudo mover el archivo']);
        exit;
    }
    $evidenceFilename = $safe;
}

// check if existe la fase (por incapacidad y nombre_fase)
$sql = "SELECT id FROM fases WHERE incapacidad_id = :inc AND UPPER(nombre_fase) = UPPER(:nombre) LIMIT 1";
$stmt = $pdo->prepare($sql);
$stmt->execute(['inc' => $incapacidad_id, 'nombre' => $nombre_fase_up]);
$exists = $stmt->fetch(PDO::FETCH_ASSOC);

if ($exists) {
    // update
    $params = [
        'descripcion' => $descripcion,
        'fecha_actualizacion' => date('Y-m-d H:i:s'),
        'inc' => $incapacidad_id,
        'nombre' => $nombre_fase_up
    ];
    $sqlu = "UPDATE fases SET descripcion = :descripcion, fecha_actualizacion = :fecha_actualizacion";
    if ($evidenceFilename) {
        $sqlu .= ", evidencia = :evidence";
        $params['evidence'] = $evidenceFilename;
    }
    $sqlu .= " WHERE incapacidad_id = :inc AND UPPER(nombre_fase) = UPPER(:nombre)";
    $stmtu = $pdo->prepare($sqlu);
    $ok = $stmtu->execute($params);
    if ($ok) echo json_encode(['success'=>true]);
    else echo json_encode(['success'=>false,'error'=>'Error al actualizar fase']);
} else {
    // insert
    $sqlIns = "INSERT INTO fases (incapacidad_id, nombre_fase, descripcion, evidencia, fecha_actualizacion)
               VALUES (:inc, :nombre, :descripcion, :evidence, :fecha_actualizacion)";
    $stmtIns = $pdo->prepare($sqlIns);
    $params = [
        'inc' => $incapacidad_id,
        'nombre' => $nombre_fase_up,
        'descripcion' => $descripcion,
        'evidence' => $evidenceFilename,
        'fecha_actualizacion' => date('Y-m-d H:i:s')
    ];
    $ok = $stmtIns->execute($params);
    if ($ok) echo json_encode(['success'=>true]);
    else echo json_encode(['success'=>false,'error'=>'Error al insertar fase']);
}
