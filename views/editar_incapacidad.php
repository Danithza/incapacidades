<?php
include "../components/navbar.php";
require_once "../config/db.php";
require_once "../controllers/IncapacidadesController.php";

if (!isset($_GET['id'])) {
    die("ID no proporcionado");
}

$controller = new IncapacidadesController($pdo);
$incapacidad = $controller->find($_GET['id']);

if (!$incapacidad) {
    die("Incapacidad no encontrada");
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Editar Incapacidad</title>

    <style>
        body { font-family: Arial; padding: 20px; }

        h2 {
            color: #1E8449;
        }

        form {
            background: #fff;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 0 8px rgba(0,0,0,0.1);
            width: 70%;
        }

        label {
            display: block;
            margin-top: 10px;
            font-weight: bold;
            color: #145A32;
        }

        input, select, textarea {
            width: 100%;
            padding: 10px;
            border-radius: 8px;
            border: 1px solid #ccc;
            margin-top: 5px;
        }

        .botones {
            margin-top: 20px;
        }

        .btn {
            padding: 10px 15px;
            border-radius: 8px;
            text-decoration: none;
            color: white;
            margin-right: 10px;
        }

        .guardar { background: #1E8449; }
        .guardar:hover { background: #145A32; }

        .cancelar { background: #A93226; }
        .cancelar:hover { background: #7B241C; }
    </style>
</head>
<body>

<h2>Editar Incapacidad</h2>

<form action="../actions/actualizar_incapacidad.php" method="POST">

    <input type="hidden" name="id" value="<?= $incapacidad['id'] ?>">

    <label>Mes</label>
    <input type="text" name="mes" value="<?= $incapacidad['mes'] ?>">

    <label>Número de incapacidad</label>
    <input type="text" name="numero_incapacidad" value="<?= $incapacidad['numero_incapacidad'] ?>">

    <label>Empleado</label>
    <input type="text" name="nombre_empleado" value="<?= $incapacidad['nombre_empleado'] ?>">

    <label>Cédula</label>
    <input type="text" name="cedula" value="<?= $incapacidad['cedula'] ?>">

    <label>Área</label>
    <input type="text" name="area" value="<?= $incapacidad['area'] ?>">

    <label>Número Orden</label>
    <input type="text" name="numero_orden" value="<?= $incapacidad['numero_orden'] ?>">

    <label>Código Diagnóstico</label>
    <input type="text" name="cod_diagnostico" value="<?= $incapacidad['cod_diagnostico'] ?>">

    <label>Diagnóstico</label>
    <textarea name="diagnostico"><?= $incapacidad['diagnostico'] ?></textarea>

    <label>Tipo de incapacidad</label>
    <input type="text" name="tipo_incapacidad" value="<?= $incapacidad['tipo_incapacidad'] ?>">

    <label>EPS / ARL</label>
    <input type="text" name="eps_arl" value="<?= $incapacidad['eps_arl'] ?>">

    <label>Fecha inicio</label>
    <input type="date" name="inicio" value="<?= $incapacidad['inicio'] ?>">

    <label>Fecha termina</label>
    <input type="date" name="termina" value="<?= $incapacidad['termina'] ?>">

    <label>Días incapacidad</label>
    <input type="number" name="dias_incapacidad" value="<?= $incapacidad['dias_incapacidad'] ?>">

    <label>Días a cargo entidad</label>
    <input type="number" name="dias_a_cargo_entidad" value="<?= $incapacidad['dias_a_cargo_entidad'] ?>">

    <label>Valor</label>
    <input type="number" name="valor" value="<?= $incapacidad['valor'] ?>">

    <label>Observaciones</label>
    <textarea name="observaciones"><?= $incapacidad['observaciones'] ?></textarea>

    <label>Estado</label>
    <select name="estado">
        <option <?= $incapacidad['estado']=='Activo'?'selected':'' ?>>Activo</option>
        <option <?= $incapacidad['estado']=='Finalizado'?'selected':'' ?>>Finalizado</option>
    </select>

    <label>Fecha finalización</label>
    <input type="date" name="fecha_finalizacion" value="<?= $incapacidad['fecha_finalizacion'] ?>">

    <div class="botones">
        <button class="btn guardar" type="submit">Guardar Cambios</button>
        <a href="listado_incapacidades.php" class="btn cancelar">Cancelar</a>
    </div>
</form>

</body>
</html>
