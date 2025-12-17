<?php
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../controllers/DiagnosticosController.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $data = [
        'codigo'      => $_POST['codigo'] ?? '',
        'descripcion' => $_POST['descripcion'] ?? ''
    ];

    $controller = new DiagnosticosController($pdo);
    $controller->actualizar($data['codigo'], $data);

    header('Location: ../../views/diagnosticos.php');
    exit;
}
