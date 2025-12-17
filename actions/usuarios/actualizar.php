<?php
require_once __DIR__ . '/../../controllers/UsuariosController.php';
require_once __DIR__ . '/../../config/db.php';

$controller = new UsuariosController($pdo);

$id = $_GET['id'];

$controller->actualizar($id, $_POST);

header("Location: ../../views/usuarios.php");
exit;
