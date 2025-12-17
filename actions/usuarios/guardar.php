<?php
require_once __DIR__ . '/../../controllers/UsuariosController.php';
require_once __DIR__ . '/../../config/db.php';

$controller = new UsuariosController($pdo);

$controller->crear($_POST);

header("Location: ../../views/usuarios.php");
exit;
