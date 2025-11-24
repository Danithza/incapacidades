<?php
require_once "../config/db.php";
require_once "../controllers/IncapacidadesController.php";

$controller = new IncapacidadesController($pdo);

$id = $_POST['id'];

$data = [
    "numero_incapacidad" => $_POST['numero_incapacidad'],
    "mes" => $_POST['mes'],
    "nombre_empleado" => $_POST['nombre_empleado'],
    "cedula" => $_POST['cedula'],
    "area" => $_POST['area'],
    "numero_orden" => $_POST['numero_orden'],
    "cod_diagnostico" => $_POST['cod_diagnostico'],
    "diagnostico" => $_POST['diagnostico'],
    "tipo_incapacidad" => $_POST['tipo_incapacidad'],
    "eps_arl" => $_POST['eps_arl'],
    "inicio" => $_POST['inicio'],
    "termina" => $_POST['termina'],
    "dias_incapacidad" => $_POST['dias_incapacidad'],
    "dias_a_cargo_entidad" => $_POST['dias_a_cargo_entidad'],
    "valor" => $_POST['valor'],
    "observaciones" => $_POST['observaciones'],
    "estado" => $_POST['estado'],
    "fecha_finalizacion" => $_POST['fecha_finalizacion']
];

$controller->update($id, $data);

header("Location: ../views/listado_incapacidades.php");
exit;
