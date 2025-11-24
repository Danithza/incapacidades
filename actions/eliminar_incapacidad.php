<?php
require_once "../config/db.php";

$id = $_GET['id'];

$sql = "DELETE FROM incapacidades WHERE id = ?";
$stmt = $pdo->prepare($sql);
$stmt->execute([$id]);

header("Location: ../views/listado_incapacidades.php");
exit;
