<?php
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../controllers/DiagnosticosController.php';

if (isset($_GET['codigo'])) {

    $controller = new DiagnosticosController($pdo);
    $controller->eliminar($_GET['codigo']);

    header('Location: ../../views/diagnosticos.php');
    exit;
}
