<?php
$conexion = new mysqli("localhost", "root", "", "informes_pj");
if ($conexion->connect_error) {
  die("Conexión fallida: " . $conexion->connect_error);
}

$result = $conexion->query("SELECT * FROM informes ORDER BY fecha_creacion DESC");

$datos = [];
while ($fila = $result->fetch_assoc()) {
  $datos[] = $fila;
}

echo json_encode($datos);
?>
