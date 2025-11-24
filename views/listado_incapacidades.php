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

    <!-- ICONOS -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

    <style>
        body {
            font-family: Arial;
            padding: 20px;
        }

        h2 {
            color: #145A32; /* Verde oscuro */
            margin-bottom: 15px;
        }

        .btn-nueva {
            background: #1E8449;
            color: white;
            padding: 10px 15px;
            border-radius: 6px;
            text-decoration: none;
        }
        .btn-nueva:hover { background: #145A32; }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
            background: white;
            border-radius: 10px;
            overflow: hidden;
        }

        th {
            background: #1E8449;
            color: white;
            padding: 10px;
        }

        td {
            padding: 10px;
            border-bottom: 1px solid #eee;
        }

        .acciones a {
            padding: 6px;
            margin: 3px;
            border-radius: 6px;
            color: white;
            text-decoration: none;
        }

        .edit-btn { background: #F1C40F; }
        .edit-btn:hover { background: #D4AC0D; }

        .delete-btn { background: #E74C3C; }
        .delete-btn:hover { background: #C0392B; }

        .info-btn {
            background: #2874A6;
            color: white;
            padding: 6px 10px;
            border-radius: 6px;
            cursor: pointer;
            text-decoration: none;
        }
        .info-btn:hover { background: #1B4F72; }

        /* MODAL */
        .modal {
            display: none;
            position: fixed;
            top: 0; left: 0;
            width: 100%; height: 100%;
            background: rgba(0,0,0,0.6);
            align-items: center;
            justify-content: center;
        }

        .modal-contenido {
            background: white;
            width: 70%;
            padding: 20px;
            border-radius: 10px;
        }

        .cerrar {
            float: right;
            cursor: pointer;
            font-size: 22px;
        }
    </style>
</head>
<body>

<h2>Listado Completo de Incapacidades</h2>

<a href="crear_incapacidad.php" class="btn-nueva">➕ Nueva Incapacidad</a>

<table>
    <thead>
        <tr>
            <th>ID</th>
            <th>Mes</th>
            <th>Número</th>
            <th>Empleado</th>
            <th>Área</th>
            <th>Inicio</th>
            <th>Termina</th>
            <th>Días</th>
            <th>Valor</th>
            <th style="text-align:center;">Acciones</th>
        </tr>
    </thead>

    <tbody>
    <?php foreach ($lista as $row): ?>
        <tr>
            <td><?= $row['id'] ?></td>
            <td><?= $row['mes'] ?></td>
            <td><?= $row['numero_incapacidad'] ?></td>
            <td><?= $row['nombre_empleado'] ?></td>
            <td><?= $row['area'] ?></td>
            <td><?= $row['inicio'] ?></td>
            <td><?= $row['termina'] ?></td>
            <td><?= $row['dias_incapacidad'] ?></td>
            <td><?= number_format($row['valor'], 0, ',', '.') ?></td>

            <td class="acciones" style="text-align:center;">

                <!-- VER DETALLES -->
                <button class="info-btn"
                    onclick='abrirDetalles(<?= json_encode($row) ?>)'>
                    <i class="fa fa-eye"></i>
                </button>

                <!-- EDITAR -->
                <a href="editar_incapacidad.php?id=<?= $row['id'] ?>" class="edit-btn">
                    <i class="fa fa-edit"></i>
                </a>

                <!-- ELIMINAR -->
                <a href="../actions/eliminar_incapacidad.php?id=<?= $row['id'] ?>"
                   class="delete-btn"
                   onclick="return confirm('¿Seguro deseas eliminar esta incapacidad?');">
                   <i class="fa fa-trash"></i>
                </a>
            </td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>


<!-- MODAL DE DETALLES -->
<div class="modal" id="modalDetalles">
    <div class="modal-contenido">
        <span class="cerrar" onclick="document.getElementById('modalDetalles').style.display='none'">&times;</span>
        <h3>Detalles de la Incapacidad</h3>
        <div id="contenidoDetalles"></div>
    </div>
</div>


<script>
function abrirDetalles(data){
    let html = `
        <p><b>ID:</b> ${data.id}</p>
        <p><b>Número:</b> ${data.numero_incapacidad}</p>
        <p><b>Mes:</b> ${data.mes}</p>
        <p><b>Empleado:</b> ${data.nombre_empleado}</p>
        <p><b>Cédula:</b> ${data.cedula}</p>
        <p><b>Área:</b> ${data.area}</p>
        <p><b>Código Diagnóstico:</b> ${data.cod_diagnostico}</p>
        <p><b>Diagnóstico:</b> ${data.diagnostico}</p>
        <p><b>Tipo:</b> ${data.tipo_incapacidad}</p>
        <p><b>EPS/ARL:</b> ${data.eps_arl}</p>
        <p><b>Inicio:</b> ${data.inicio}</p>
        <p><b>Termina:</b> ${data.termina}</p>
        <p><b>Días:</b> ${data.dias_incapacidad}</p>
        <p><b>Días a cargo:</b> ${data.dias_a_cargo_entidad}</p>
        <p><b>Valor:</b> ${Number(data.valor).toLocaleString('es-CO')}</p>
        <p><b>Observaciones:</b> ${data.observaciones}</p>
        <p><b>Estado:</b> ${data.estado}</p>
        <p><b>Fecha finalización:</b> ${data.fecha_finalizacion}</p>
    `;

    document.getElementById("contenidoDetalles").innerHTML = html;
    document.getElementById("modalDetalles").style.display = "flex";
}
</script>

</body>
</html>
