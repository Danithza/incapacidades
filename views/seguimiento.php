<?php
include __DIR__ . '/../components/navbar.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../controllers/SeguimientoController.php';

$controller = new SeguimientoController($pdo);
$incapacidades = $controller->index();
$fasesDefinidas = $controller->fasesDefinidas();
$fecha_actualizacion = date("Y-m-d H:i:s");
?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <title>Seguimiento de Incapacidades</title>
  <link rel="stylesheet" href="/incapacidades/public/css/seguimiento.css">
</head>
<body>
<div class="container">
  <h2>Seguimiento de Incapacidades</h2>
  <p class="fecha-actualizacion">Última actualización: <strong><?= htmlspecialchars($fecha_actualizacion) ?></strong></p>

  <table class="tabla">
    <thead>
      <tr>
        <th>Trabajador</th>
        <th>Cédula</th>
        <th>Cód Diagnóstico</th>
        <th>N° Incapacidad</th>
        <th>Estado</th>
        <?php foreach ($fasesDefinidas as $f): ?>
          <th><?= htmlspecialchars($f) ?></th>
        <?php endforeach; ?>
      </tr>
    </thead>

    <tbody>
      <?php foreach ($incapacidades as $inc): ?>
        <?php 
          $estado = $inc['estado'] ?? 0;
          $color = ($estado == 1) ? 'verde' : 'rojo';
        ?>
        <tr>
          <td><?= htmlspecialchars($inc['nombre_empleado']) ?></td>
          <td><?= htmlspecialchars($inc['cedula']) ?></td>
          <td><?= htmlspecialchars($inc['cod_diagnostico']) ?></td>
          <td><?= htmlspecialchars($inc['numero_incapacidad']) ?></td>

          <!-- Círculo de estado -->
          <td>
            <span 
              class="estado-circle <?= $color ?>"
              data-id="<?= $inc['id'] ?>"      
              data-estado="<?= $estado ?>"
              style="cursor:pointer; display:inline-block; width:18px; height:18px; border-radius:50%; background:<?= ($estado==1?'#28a745':'#dc3545') ?>;">
            </span>
          </td>

          <!-- Fases -->
          <?php foreach ($fasesDefinidas as $f): ?>
            <?php $fase = $inc['fases'][$f] ?? null; ?>
            <td class="celda-fase">
              <div class="fase-body">
                <div class="fase-desc"><?= htmlspecialchars($fase['descripcion'] ?? '') ?></div>
                <?php if (!empty($fase['fecha_actualizacion'])): ?>
                  <small class="fecha"><?= htmlspecialchars($fase['fecha_actualizacion']) ?></small>
                <?php endif; ?>
                <div class="fase-actions">
                  <button class="btn btn-edit" data-incapacidad="<?= $inc['id'] ?>" data-fase="<?= htmlspecialchars($f) ?>">✏ Editar</button>
                  <?php if (!empty($fase['evidencia'])): ?>
                    <a class="btn btn-file" href="/incapacidades/uploads/fases/<?= rawurlencode($fase['evidencia']) ?>" target="_blank">📎 Ver evidencia</a>
                  <?php else: ?>
                    <span class="text-muted">Sin evidencia</span>
                  <?php endif; ?>
                </div>
              </div>
            </td>
          <?php endforeach; ?>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>

<!-- Modal Editar Fase -->
<div id="modalFase" class="modal" style="display:none;">
  <div class="modal-content">
    <span id="modalClose" class="modal-close">&times;</span>
    <h3 id="modalTitle">Editar fase</h3>
    <form id="formFase" enctype="multipart/form-data">
      <input type="hidden" name="incapacidad_id" id="incapacidad_id">
      <input type="hidden" name="nombre_fase" id="nombre_fase">
      <label>Descripción</label>
      <textarea name="descripcion" id="descripcion" rows="4"></textarea>
      <label>Evidencia (pdf/jpg/png)</label>
      <input type="file" name="evidencia" id="evidencia">
      <div id="existingEvidencia"></div>
      <button type="submit" class="btn btn-save">Guardar</button>
    </form>
  </div>
</div>

<script src="/incapacidades/public/js/seguimiento.js"></script>
</body>
</html>
