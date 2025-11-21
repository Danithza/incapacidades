<?php 
require_once "../config/db.php";
require_once "../controllers/IncapacidadesController.php";

$controller = new IncapacidadesController($pdo);
$usuarios = $controller->obtenerUsuarios();
?>

<!DOCTYPE html>
<html>
<head>
<title>Registrar Incapacidad</title>
<link rel="stylesheet" href="../public/css/styles.css">

<style>

    body {
        background: #f4f6f9;
        font-family: Arial;
        padding: 25px;
    }

    h2 {
        color: #003366;
        margin-bottom: 20px;
        text-align: center;
    }

    .card {
        background: white;
        padding: 25px;
        border-radius: 12px;
        max-width: 900px;
        margin: auto;
        box-shadow: 0 3px 12px rgba(0,0,0,0.15);
    }

    .grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 18px;
        margin-top: 10px;
    }

    label {
        font-weight: bold;
        color: #333;
    }

    input, select, textarea {
        width: 100%;
        padding: 10px;
        border-radius: 8px;
        border: 1px solid #ccc;
        transition: .2s;
        font-size: 15px;
    }

    input:focus, select:focus, textarea:focus {
        border-color: #007bff;
        outline: none;
        box-shadow: 0px 0px 4px rgba(0,123,255,0.6);
    }

    textarea {
        min-height: 70px;
    }

    .btn-guardar {
        margin-top: 25px;
        padding: 12px 20px;
        width: 100%;
        background: #007bff;
        color: white;
        border: none;
        border-radius: 10px;
        font-size: 17px;
        font-weight: bold;
        cursor: pointer;
        transition: 0.2s;
    }

    .btn-guardar:hover {
        background: #005fcc;
    }

</style>

</head>
<body>

<h2>Registro de Incapacidad</h2>

<div class="card">

<form action="../actions/guardar_incapacidad.php" method="POST">

<div class="grid">

    <div>
        <label>Mes</label>
        <select name="mes" required>
            <option value="">Seleccione...</option>
            <?php 
            $meses = ["ENERO","FEBRERO","MARZO","ABRIL","MAYO","JUNIO","JULIO","AGOSTO",
                      "SEPTIEMBRE","OCTUBRE","NOVIEMBRE","DICIEMBRE"];
            foreach ($meses as $m): ?>
                <option value="<?= $m ?>"><?= $m ?></option>
            <?php endforeach; ?>
        </select>
    </div>

    <div>
        <label>Número incapacidad</label>
        <input name="numero_incapacidad" required>
    </div>

    <div>
        <label>Nombre del empleado</label>
        <select id="usuarioSelect" name="nombre_empleado" required>
            <option value="">Seleccione...</option>
            <?php foreach ($usuarios as $u): ?>
                <option value="<?= $u['nombre_completo'] ?>" 
                        data-cedula="<?= $u['cedula'] ?>" 
                        data-area="<?= $u['area'] ?>">
                    <?= $u['nombre_completo'] ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>

    <div>
        <label>Cédula</label>
        <input type="text" id="cedula" name="cedula" readonly required>
    </div>

    <div>
        <label>Área</label>
        <input type="text" id="area" name="area" readonly required>
    </div>

    <div>
        <label>Código diagnóstico</label>
        <input name="cod_diagnostico">
    </div>

    <div>
        <label>Diagnóstico</label>
        <textarea name="diagnostico"></textarea>
    </div>

    <div>
        <label>Tipo incapacidad</label>
        <input name="tipo_incapacidad">
    </div>

    <div>
        <label>EPS / ARL</label>
        <input name="eps_arl">
    </div>

    <div>
        <label>Fecha inicio</label>
        <input type="date" name="inicio">
    </div>

    <div>
        <label>Fecha fin</label>
        <input type="date" name="termina">
    </div>

    <div>
        <label>Días incapacidad</label>
        <input type="number" name="dias_incapacidad">
    </div>

    <div>
        <label>Días a cargo entidad</label>
        <input type="number" name="dias_a_cargo_entidad">
    </div>

    <div>
        <label>Valor</label>
        <input type="number" step="0.01" name="valor">
    </div>

    <div>
        <label>Valor Aprox</label>
        <input type="number" step="0.01" name="valor_aprox">
    </div>

    <div>
        <label>Estado del proceso</label>
        <input name="estado_proceso">
    </div>

    <div>
        <label>Aplicación del pago</label>
        <input name="aplicacion_pago">
    </div>

    <div style="grid-column: span 2;">
        <label>Observaciones</label>
        <textarea name="observaciones"></textarea>
    </div>

    <div>
        <label>Número de orden</label>
        <input name="numero_orden">
    </div>

</div>

<button class="btn-guardar">Guardar</button>

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
