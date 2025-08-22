<?php
header('Content-Type: application/json');

// Conexión a la base de datos
$conexion = new mysqli("localhost", "root", "", "informes_pj");

if ($conexion->connect_error) {
    echo json_encode(['success' => false, 'error' => 'Error de conexión']);
    exit;
}

// Obtener datos desde el JSON del cuerpo de la petición
$data = json_decode(file_get_contents("php://input"), true);

if (!$data || !isset($data['id'], $data['titulo'], $data['descripcion'], $data['fecha'])) {
    echo json_encode(['success' => false, 'error' => 'Datos incompletos']);
    exit;
}

$id = $conexion->real_escape_string($data['id']);
$titulo = $conexion->real_escape_string($data['titulo']);
$descripcion = $conexion->real_escape_string($data['descripcion']);
$fecha = $conexion->real_escape_string($data['fecha']);

// Actualizar evento en la base de datos
$sql = "UPDATE eventos SET titulo = '$titulo', descripcion = '$descripcion', fecha = '$fecha' WHERE id = '$id'";

if ($conexion->query($sql) === TRUE) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'error' => $conexion->error]);
}

$conexion->close();


