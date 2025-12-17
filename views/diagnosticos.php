<?php
include __DIR__ . '/../components/navbar.php';
require_once __DIR__ . '/../controllers/DiagnosticosController.php';
require_once __DIR__ . '/../config/db.php';

$controller = new DiagnosticosController($pdo);
$diagnosticos = $controller->listar();
?>

<link rel="stylesheet" href="../public/css/crud.css">

<div class="content-with-navbar">
  <div class="container">

    <h2 class="page-title">Diagnósticos</h2>

    <!-- BOTÓN BIEN UBICADO -->
    <div style="margin-bottom:20px;">
      <a href="diagnostico_form.php" class="btn btn-primary">➕ Nuevo Diagnóstico</a>
    </div>

    <div class="table-container">
      <table>
        <thead>
          <tr>
            <th>Código</th>
            <th>Diagnóstico</th>
            <th>Acciones</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($diagnosticos as $d): ?>
          <tr>
            <td><?= htmlspecialchars($d['cod_diagnostico']) ?></td>
            <td><?= htmlspecialchars($d['diagnostico']) ?></td>
            <td>
              <a href="diagnostico_form.php?codigo=<?= $d['cod_diagnostico'] ?>">✏️</a>
              <a href="../actions/diagnosticos/eliminar.php?codigo=<?= $d['cod_diagnostico'] ?>"
                 onclick="return confirm('¿Eliminar diagnóstico?')">🗑️</a>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>

  </div>
</div>
