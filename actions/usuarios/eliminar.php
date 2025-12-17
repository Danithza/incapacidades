<?php
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../controllers/UsuariosController.php';

if (isset($_GET['id'])) {

    $controller = new UsuariosController($pdo);
    $controller->eliminar($_GET['id']);

    header('Location: ../../views/usuarios.php');
    exit;
}
