<?php
require_once "../config/db.php";
require_once "../controllers/IncapacidadesController.php";

$controller = new IncapacidadesController($pdo);

$id = $controller->store($_POST);

// Ir a la trazabilidad
header("Location: ../views/listado_incapacidades.php");
exit();
