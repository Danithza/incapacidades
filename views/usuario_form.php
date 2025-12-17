<?php
require_once __DIR__ . '/../controllers/UsuariosController.php';
require_once __DIR__ . '/../config/db.php';

$controller = new UsuariosController($pdo);
$usuario = null;
$areas = $controller->obtenerAreas();

if (isset($_GET['id'])) {
    $usuario = $controller->obtener($_GET['id']);
}
?>
<link rel="stylesheet" href="../public/css/form.css">
<h2><?= $usuario ? 'Editar Usuario' : 'Nuevo Usuario' ?></h2>

<form method="POST"
      action="<?= $usuario ? '../actions/usuarios/actualizar.php?id='.$usuario['id'] : '../actions/usuarios/guardar.php' ?>">

    <div class="form-group">
        <label>Cédula</label>
        <input type="text" name="cedula" required placeholder="Cédula"
               value="<?= $usuario['cedula'] ?? '' ?>">
    </div>

    <div class="form-group">
        <label>Nombre completo</label>
        <input type="text" name="nombre_completo" required placeholder="Nombre completo"
               value="<?= $usuario['nombre_completo'] ?? '' ?>">
    </div>

    <div class="form-group">
        <label>Área</label>
        <select name="area" required>
            <option value="" disabled selected>Seleccione el área</option>
            <?php foreach ($areas as $area): ?>
                <option value="<?= $area['area'] ?>" <?= isset($usuario) && $usuario['area'] == $area['area'] ? 'selected' : '' ?>>
                    <?= $area['area'] ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>

    <button type="submit">💾 Guardar</button>
</form>
