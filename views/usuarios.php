<?php
include __DIR__ . '/../components/navbar.php';
require_once __DIR__ . '/../controllers/UsuariosController.php';
require_once __DIR__ . '/../config/db.php';

$controller = new UsuariosController($pdo);
$filtro = $_GET['q'] ?? null;
$usuarios = $controller->listar($filtro);
?>

<link rel="stylesheet" href="../public/css/crud.css">

<div class="content-with-navbar">
  <div class="container">

    <h2 class="page-title">Usuarios</h2>

    <form method="GET" class="filter-form">
        <input type="text" name="q" placeholder="Buscar por nombre, cédula o área"
               value="<?= htmlspecialchars($filtro ?? '') ?>">
        <button type="submit" class="btn btn-search">Buscar</button>
        <a href="usuarios.php" class="btn btn-clear">Limpiar</a>
    </form>

    <!-- BOTÓN BAJADO Y ORDENADO -->
    <div style="margin-bottom:20px;">
      <a href="usuario_form.php" class="btn btn-primary">➕ Nuevo Usuario</a>
    </div>

    <div class="table-container">
      <table>
        <thead>
          <tr>
            <th>Cédula</th>
            <th>Nombre</th>
            <th>Área</th>
            <th>Acciones</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($usuarios)): ?>
            <tr>
              <td colspan="4" style="text-align:center;">No hay resultados</td>
            </tr>
          <?php endif; ?>

          <?php foreach ($usuarios as $u): ?>
          <tr>
            <td><?= htmlspecialchars($u['cedula']) ?></td>
            <td><?= htmlspecialchars($u['nombre_completo']) ?></td>
            <td><?= htmlspecialchars($u['area']) ?></td>
            <td>
              <a href="usuario_form.php?id=<?= $u['id'] ?>">✏️</a>
              <a href="../actions/usuarios/eliminar.php?id=<?= $u['id'] ?>"
                 onclick="return confirm('¿Eliminar usuario?')">🗑️</a>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>

  </div>
</div>
