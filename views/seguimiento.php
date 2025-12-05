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
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Seguimiento de Incapacidades</title>
  <link rel="stylesheet" href="/incapacidades/public/css/seguimiento.css">
  <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
</head>
<body>
<div class="container">
  <div class="header">
    <h2>Seguimiento de Incapacidades</h2>
    <p class="fecha-actualizacion">Última actualización: <strong><?= htmlspecialchars($fecha_actualizacion) ?></strong></p>
  </div>

  <div class="table-responsive">
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
            $estado_texto = ($estado == 1) ? 'Activa' : 'Inactiva';
          ?>
          <tr>
            <td class="empleado-nombre"><?= htmlspecialchars($inc['nombre_empleado']) ?></td>
            <td class="empleado-cedula"><?= htmlspecialchars($inc['cedula']) ?></td>
            <td class="diagnostico"><?= htmlspecialchars($inc['cod_diagnostico']) ?></td>
            <td class="incapacidad-num"><?= htmlspecialchars($inc['numero_incapacidad']) ?></td>

            <!-- Círculo de estado -->
            <td class="estado-cell">
              <div class="estado-container">
                <span 
                  class="estado-circle <?= $color ?>"
                  data-id="<?= $inc['id'] ?>"      
                  data-estado="<?= $estado ?>"
                  title="<?= $estado_texto ?>">
                </span>
                <span class="estado-text"><?= $estado_texto ?></span>
              </div>
            </td>

            <!-- Fases -->
            <?php foreach ($fasesDefinidas as $f): ?>
              <?php $fase = $inc['fases'][$f] ?? null; ?>
              <td class="celda-fase">
                <div class="fase-card <?= empty($fase['descripcion']) ? 'empty' : '' ?>">
                  <div class="fase-content">
                    <div class="fase-desc"><?= !empty($fase['descripcion']) ? htmlspecialchars($fase['descripcion']) : 'Sin información' ?></div>
                    <?php if (!empty($fase['fecha_actualizacion'])): ?>
                      <div class="fase-footer">
                        <span class="fecha">
                          <i class="material-icons icon-small">calendar_today</i>
                          <?= htmlspecialchars($fase['fecha_actualizacion']) ?>
                        </span>
                      </div>
                    <?php endif; ?>
                  </div>
                  <div class="fase-actions">
                    <button class="btn btn-edit" data-incapacidad="<?= $inc['id'] ?>" data-fase="<?= htmlspecialchars($f) ?>">
                      <i class="material-icons">edit</i>
                      <span>Editar</span>
                    </button>
                    <?php if (!empty($fase['evidencia'])): ?>
                      <a class="btn btn-file" href="/incapacidades/uploads/fases/<?= rawurlencode($fase['evidencia']) ?>" target="_blank">
                        <i class="material-icons">attach_file</i>
                        <span>Ver</span>
                      </a>
                    <?php else: ?>
                      <span class="no-evidencia">
                        <i class="material-icons">remove_circle_outline</i>
                        Sin evidencia
                      </span>
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
</div>

<!-- Modal Editar Fase -->
<div id="modalFase" class="modal">
  <div class="modal-overlay"></div>
  <div class="modal-container">
    <div class="modal-header">
      <h3 class="modal-title">
        <i class="material-icons">edit_document</i>
        <span id="modalTitle">Editar Fase</span>
      </h3>
      <button id="modalClose" class="modal-close">
        <i class="material-icons">close</i>
      </button>
    </div>
    
    <div class="modal-body">
      <form id="formFase" class="modal-form" enctype="multipart/form-data">
        <input type="hidden" name="incapacidad_id" id="incapacidad_id">
        <input type="hidden" name="nombre_fase" id="nombre_fase">
        
        <div class="form-group">
          <label for="descripcion" class="form-label">
            <i class="material-icons icon-label">description</i>
            Descripción
          </label>
          <textarea name="descripcion" id="descripcion" rows="5" class="form-textarea" 
                    placeholder="Ingrese la descripción de la fase..."></textarea>
        </div>
        
        <div class="form-group">
          <label for="evidencia" class="form-label">
            <i class="material-icons icon-label">attach_file</i>
            Evidencia (PDF, JPG, PNG)
          </label>
          <div class="file-input-container">
            <input type="file" name="evidencia" id="evidencia" class="form-file" 
                   accept=".pdf,.jpg,.jpeg,.png">
            <div class="file-info" id="fileInfo">No se ha seleccionado ningún archivo</div>
          </div>
        </div>
        
        <div id="existingEvidencia" class="existing-file"></div>
        
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" id="btnCancel">Cancelar</button>
          <button type="submit" class="btn btn-primary">
            <i class="material-icons">save</i>
            Guardar Cambios
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<script src="/incapacidades/public/js/seguimiento.js"></script>
</body>
</html>