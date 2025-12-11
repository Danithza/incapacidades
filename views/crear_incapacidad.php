<?php 
require_once "../config/db.php";
include __DIR__ . '/../components/navbar.php';
require_once "../controllers/IncapacidadesController.php";

$controller = new IncapacidadesController($pdo);

// Usuarios
$usuarios = $controller->obtenerUsuarios();

// Diagnósticos
$diagnosticos = $controller->obtenerDiagnosticos();
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrar Incapacidad</title>
    <link rel="stylesheet" href="../public/css/styles.css">

    <!-- SELECT2 -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <script src="https://cdn.jsdelivr.net/npm/jquery@3.6.0/dist/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

    <style>
        /* Para que Select2 combine con tus inputs */
        .select2-container .select2-selection--single {
            height: 42px !important;
            padding: 6px;
            border: 1px solid #ccc;
            border-radius: 6px;
        }

        .select2-container--default .select2-selection--single .select2-selection__arrow {
            top: 8px;
        }
    </style>
</head>

<body>

<h2></h2>

<div class="card">
    <div class="form-header">
        <h2>Nueva Incapacidad</h2>
        <p>Complete todos los campos requeridos (*) para registrar la incapacidad</p>
    </div>

    <form id="formIncapacidad" action="../actions/guardar_incapacidad.php" method="POST">
        <div class="grid">

            <!-- MES -->
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

            <!-- No INCAPACIDAD -->
            <div class="form-group">
                <label>Número incapacidad</label>
                <input name="numero_incapacidad" required placeholder="Ingrese el número de incapacidad">
            </div>

            <!-- EMPLEADO -->
            <div class="form-group">
                <label>Nombre del empleado</label>
                <select id="usuarioSelect" name="nombre_empleado" required>
                    <option value="">Seleccione un empleado...</option>
                    <?php foreach ($usuarios as $u): ?>
                        <option 
                            value="<?= htmlspecialchars($u['nombre_completo']) ?>"
                            data-cedula="<?= htmlspecialchars($u['cedula']) ?>"
                            data-area="<?= htmlspecialchars($u['area']) ?>">
                            <?= htmlspecialchars($u['nombre_completo']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- CEDULA -->
            <div class="form-group">
                <label>Cédula</label>
                <input id="cedula" name="cedula" readonly required placeholder="Se completará automáticamente">
            </div>

            <!-- AREA -->
            <div class="form-group">
                <label>Área</label>
                <input id="area" name="area" readonly required placeholder="Se completará automáticamente">
            </div>

            <!-- ORDEN -->
            <div class="form-group">
                <label>Número de orden</label>
                <input name="numero_orden" placeholder="Número de orden (opcional)">
            </div>

            <!-- COD DIAGNOSTICO -->
            <div class="form-group">
                <label>Código diagnóstico</label>
                <select id="codDiagnostico" name="cod_diagnostico">
                    <option value="">Seleccione un diagnóstico...</option>
                    <?php foreach ($diagnosticos as $d): ?>
                        <option 
                            value="<?= htmlspecialchars($d['cod_diagnostico']) ?>"
                            data-diagnostico="<?= htmlspecialchars($d['diagnostico']) ?>">
                            <?= htmlspecialchars($d['cod_diagnostico']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- DIAGNOSTICO -->
            <div class="form-group">
                <label>Diagnóstico</label>
                <textarea id="diagnosticoTxt" name="diagnostico" placeholder="Se llenará automáticamente"></textarea>
            </div>

            <!-- TIPO -->
            <div class="form-group">
                <label>Tipo incapacidad</label>
                <select name="tipo_incapacidad" required>
                    <option value="">Seleccione el tipo...</option>
                    <option value="ORIGEN COMUN">ORIGEN COMUN</option>
                    <option value="ORIGEN LABORAL">ORIGEN LABORAL</option>
                </select>
            </div>

            <!-- EPS -->
            <div class="form-group">
                <label>EPS / ARL</label>
                <select name="eps_arl" required>
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
                <input type="date" name="inicio" required>
            </div>

            <div class="form-group">
                <label>Fecha fin</label>
                <input type="date" name="termina" required>
            </div>

            <div class="form-group">
                <label>Días incapacidad</label>
                <input type="number" id="diasIncapacidad" name="dias_incapacidad" min="1" required>
            </div>

            <div class="form-group">
                <label>Días a cargo entidad</label>
                <input type="number" id="diasCargoEntidad" name="dias_a_cargo_entidad" min="0" required>
            </div>

            <div class="form-group">
                <label>Valor</label>
                <input type="number" step="0.01" name="valor">
            </div>

            <div class="form-group full-width">
                <label>Observaciones</label>
                <textarea name="observaciones"></textarea>
            </div>

        </div>

        <button type="submit" class="btn-guardar">Guardar Incapacidad</button>

    </form>
</div>

<script>
// ACTIVAR SELECT2 CON BÚSQUEDA
$(document).ready(function() {
    $('#usuarioSelect').select2({
        placeholder: "Seleccione un empleado...",
        width: '100%'
    });

    $('#codDiagnostico').select2({
        placeholder: "Seleccione un diagnóstico...",
        width: '100%'
    });
});

// AUTOCOMPLETAR EMPLEADO
$('#usuarioSelect').on('change', function() {
    let op = this.options[this.selectedIndex];
    $('#cedula').val(op.dataset.cedula || "");
    $('#area').val(op.dataset.area || "");
});

// AUTOCOMPLETAR DIAGNOSTICO
$('#codDiagnostico').on('change', function() {
    let op = this.options[this.selectedIndex];
    $('#diagnosticoTxt').val(op.dataset.diagnostico || "");
});
</script>

</body>
</html>
