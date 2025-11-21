<?php
require_once "../config/db.php";

// totales
$total = $pdo->query("SELECT COUNT(*) FROM incapacidades")->fetchColumn();
$activas = $pdo->query("SELECT COUNT(*) FROM incapacidades WHERE estado_proceso!='CUMPLIDA'")->fetchColumn();
$cumplidas = $pdo->query("SELECT COUNT(*) FROM incapacidades WHERE estado_proceso='CUMPLIDA'")->fetchColumn();
?>
<h2>Dashboard de Incapacidades</h2>

<div class="card">Total registradas: <?= $total ?></div>
<div class="card">Activas: <?= $activas ?></div>
<div class="card">Cumplidas: <?= $cumplidas ?></div>
