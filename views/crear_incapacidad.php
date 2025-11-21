<?php 
require_once "../config/db.php";
require_once "../controllers/IncapacidadesController.php";

$controller = new IncapacidadesController($pdo);
$usuarios = $controller->obtenerUsuarios();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrar Incapacidad</title>
    <link rel="stylesheet" href="../public/css/styles.css">
</head>
<body>

<h2>Registro de Incapacidad</h2>

<div class="card">
    <div class="form-header">
        <h2>Nueva Incapacidad</h2>
        <p>Complete todos los campos requeridos (*) para registrar la incapacidad</p>
    </div>

    <form id="formIncapacidad" action="../actions/guardar_incapacidad.php" method="POST">
        <div class="grid">
            <div class="form-group">
                <label>Mes</label>
                <select name="mes" required>
                    <option value="">Seleccione el mes...</option>
                    <?php 
                    $meses = ["ENERO","FEBRERO","MARZO","ABRIL","MAYO","JUNIO","JULIO","AGOSTO",
                              "SEPTIEMBRE","OCTUBRE","NOVIEMBRE","DICIEMBRE"];
                    foreach ($meses as $m): ?>
                        <option value="<?= $m ?>"><?= $m ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label>Número incapacidad</label>
                <input name="numero_incapacidad" placeholder="Ingrese el número de incapacidad" required>
            </div>

            <div class="form-group">
                <label>Nombre del empleado</label>
                <select id="usuarioSelect" name="nombre_empleado" required>
                    <option value="">Seleccione un empleado...</option>
                    <?php foreach ($usuarios as $u): ?>
                        <option value="<?= htmlspecialchars($u['nombre_completo']) ?>" 
                                data-cedula="<?= htmlspecialchars($u['cedula']) ?>" 
                                data-area="<?= htmlspecialchars($u['area']) ?>">
                            <?= htmlspecialchars($u['nombre_completo']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label>Cédula</label>
                <input type="text" id="cedula" name="cedula" readonly required placeholder="Se completará automáticamente">
            </div>

            <div class="form-group">
                <label>Área</label>
                <input type="text" id="area" name="area" readonly required placeholder="Se completará automáticamente">
            </div>

            <div class="form-group">
                <label>Número de orden</label>
                <input name="numero_orden" placeholder="Número de orden (opcional)">
            </div>

            <div class="form-group">
                <label>Código diagnóstico</label>
                <input name="cod_diagnostico" placeholder="Código del diagnóstico (opcional)">
            </div>

            <div class="form-group">
                <label>Diagnóstico</label>
                <textarea name="diagnostico" placeholder="Descripción del diagnóstico (opcional)"></textarea>
            </div>

            <div class="form-group">
                <label>Tipo incapacidad</label>
                <select id="tipoIncapacidad" name="tipo_incapacidad" required>
                    <option value="">Seleccione el tipo...</option>
                    <option value="ORIGEN COMUN">ORIGEN COMUN</option>
                    <option value="ORIGEN LABORAL">ORIGEN LABORAL</option>
                </select>
            </div>

            <div class="form-group">
                <label>EPS / ARL</label>
                <select id="epsArl" name="eps_arl" required>
                    <option value="">Seleccione EPS/ARL...</option>
                    <option value="COLMENA SAS">COLMENA SAS</option>
                    <option value="NUEVA EPS">NUEVA EPS</option>
                    <option value="SALUD TOTAL">SALUD TOTAL</option>
                    <option value="SALUD VIDA">SALUD VIDA</option>
                    <option value="SANITAS">SANITAS</option>
                    <option value="FAMISANAR EPS">FAMISANAR EPS</option>
                    <option value="COMPENSAR EPS">COMPENSAR EPS</option>
                </select>
            </div>

            <div class="form-group">
                <label>Fecha inicio</label>
                <input type="date" name="inicio" id="fechaInicio" required>
            </div>

            <div class="form-group">
                <label>Fecha fin</label>
                <input type="date" name="termina" id="fechaFin" required>
            </div>

            <div class="form-group">
                <label>Días incapacidad</label>
                <input type="number" name="dias_incapacidad" id="diasIncapacidad" min="1" required placeholder="0">
            </div>

            <div class="form-group">
                <label>Días a cargo entidad</label>
                <input type="number" name="dias_a_cargo_entidad" id="diasCargoEntidad" min="0" required placeholder="0">
            </div>

            <div class="form-group">
                <label>Valor</label>
                <input type="number" step="0.01" name="valor" placeholder="0.00">
            </div>

            <div class="form-group full-width">
                <label>Observaciones</label>
                <textarea name="observaciones" placeholder="Observaciones adicionales..."></textarea>
            </div>
        </div>

        <button type="submit" class="btn-guardar">Guardar Incapacidad</button>
    </form>
</div>

<script>
document.getElementById('usuarioSelect').addEventListener('change', function () {
    let option = this.options[this.selectedIndex];
    document.getElementById('cedula').value = option.dataset.cedula || "";
    document.getElementById('area').value = option.dataset.area || "";
});
</script>
</body>
</html>