<?php
require_once __DIR__ . '/../config/db.php';
include __DIR__ . '/../components/navbar.php';
require_once __DIR__ . '/../controllers/IncapacidadesController.php';
require_once __DIR__ . '/../controllers/HistorialController.php';
require_once __DIR__ . '/../controllers/SeguimientoController.php';

$inc = new IncapacidadesController($pdo);
$hist = new HistorialController($pdo);
$seg = new SeguimientoController($pdo);

// Datos principales
$lista = $inc->getAll();
$historial = $hist->obtenerHistorial();
$seguimiento = $seg->index();
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8">
<title>Reportes</title>
<link rel="stylesheet" href="/incapacidades/public/css/reportes.css">
<script src="/incapacidades/public/js/reportes.js"></script>
</head>
<body>

<h1>Reportes Generales</h1>

<div class="acciones">
    <a class="btn" href="/incapacidades/actions/export_excel.php?type=incapacidades">Excel Incapacidades</a>
    <a class="btn" href="/incapacidades/actions/export_excel.php?type=historial">Excel Historial</a>
    <a class="btn" href="/incapacidades/actions/export_excel.php?type=seguimiento">Excel Seguimiento</a>

    <a class="btn rojo" href="/incapacidades/actions/export_pdf.php?type=incapacidades">PDF Incapacidades</a>
    <a class="btn rojo" href="/incapacidades/actions/export_pdf.php?type=historial">PDF Historial</a>
</div>

<!-- TABLA PRINCIPAL -->
<h2>Listado de incapacidades</h2>
<table class="tabla">
    <thead>
        <tr>
            <th>ID</th><th>Numero</th><th>Empleado</th><th>Área</th>
            <th>Diagnóstico</th><th>Inicio</th><th>Termina</th>
            <th>Días</th><th>Estado</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach($lista as $r): ?>
        <tr>
            <td><?= $r['id'] ?></td>
            <td><?= $r['numero_incapacidad'] ?></td>
            <td><?= $r['nombre_empleado'] ?></td>
            <td><?= $r['area'] ?></td>
            <td><?= $r['diagnostico'] ?></td>
            <td><?= $r['inicio'] ?></td>
            <td><?= $r['termina'] ?></td>
            <td><?= $r['dias_incapacidad'] ?></td>
            <td><?= $r['estado'] ?></td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>

</body>
</html>
