<?php
include "../components/navbar.php";
require_once "../config/db.php";
require_once "../controllers/IncapacidadesController.php";

$controller = new IncapacidadesController($pdo);
$lista = $controller->getAll();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Listado de Incapacidades</title>
    <link rel="stylesheet" href="../public/css/estilos_incapacidades.css">
</head>
<body>

<h2>Listado Completo de Incapacidades</h2>

<a href="crear_incapacidad.php" class="btn btn-primary">➕ Nueva Incapacidad</a>

<table>
    <thead>
        <tr>
            <th>ID</th>
            <th>Mes</th>
            <th>Número</th>
            <th>Empleado</th>
            <th>Cédula</th>
            <th>Área</th>
            <th>Inicio</th>
            <th>Termina</th>
            <th>Días</th>
            <th>Valor</th>
            <th>Acciones</th>
        </tr>
    </thead>

    <tbody>
    <?php foreach ($lista as $row): ?>
        <tr>
            <td><?= $row['id'] ?></td>
            <td><?= $row['mes'] ?></td>
            <td><?= $row['numero_incapacidad'] ?></td>
            <td><?= $row['nombre_empleado'] ?></td>
            <td><?= $row['cedula'] ?></td>
            <td><?= $row['area'] ?></td>
            <td><?= $row['inicio'] ?></td>
            <td><?= $row['termina'] ?></td>
            <td><?= $row['dias_incapacidad'] ?></td>
            <td><?= number_format($row['valor'], 0, ',', '.') ?></td>

            <td class="acciones">

                <a href="editar_incapacidad.php?id=<?= $row['id'] ?>" class="btn btn-warning">✏ Editar</a>

                <a href="../actions/eliminar_incapacidad.php?id=<?= $row['id'] ?>" 
                   class="btn btn-danger"
                   onclick="return confirm('¿Seguro que deseas eliminar esta incapacidad?');">
                   🗑 Eliminar
                </a>

            </td>

        </tr>
    <?php endforeach; ?>
    </tbody>
</table>

</body>
</html>
