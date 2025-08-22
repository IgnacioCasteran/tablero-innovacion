<?php
$conexion = new mysqli("localhost", "root", "", "informes_pj");
$conexion->set_charset("utf8");

$titulo = $_POST['titulo'];
$descripcion = $_POST['descripcion'];
$fecha = $_POST['fecha'];

$stmt = $conexion->prepare("INSERT INTO eventos (titulo, descripcion, fecha) VALUES (?, ?, ?)");
$stmt->bind_param("sss", $titulo, $descripcion, $fecha);

if ($stmt->execute()) {
    echo json_encode(["success" => true]);
} else {
    echo json_encode(["success" => false]);
}
?>
