<?php
require_once __DIR__ . '/../controllers/DiagnosticosController.php';
require_once __DIR__ . '/../config/db.php';

$controller = new DiagnosticosController($pdo);
$diag = null;

if (isset($_GET['codigo'])) {
    $diag = $controller->obtener($_GET['codigo']);
}
?>

<link rel="stylesheet" href="../public/css/form.css">

<div class="form-container">
    <h2 class="form-title"><?= $diag ? 'Editar Diagnóstico' : 'Nuevo Diagnóstico' ?></h2>

    <form method="POST"
          action="<?= $diag ? '../actions/diagnosticos/actualizar.php' : '../actions/diagnosticos/guardar.php' ?>">

        <div class="form-group">
            <label>Código</label>
            <input type="text" name="codigo" required
                   value="<?= $diag['cod_diagnostico'] ?? '' ?>" <?= $diag ? 'readonly' : '' ?>>
        </div>

        <div class="form-group">
            <label>Descripción</label>
            <textarea name="descripcion" required><?= $diag['diagnostico'] ?? '' ?></textarea>
        </div>

        <div class="form-actions">
            <a href="diagnosticos.php" class="btn-cancel">Cancelar</a>
            <button type="submit" class="btn-save">💾 Guardar</button>
        </div>
    </form>
</div>
