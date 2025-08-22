<?php
header('Content-Type: application/json');
$conexion = new mysqli("localhost", "root", "", "informes_pj");
$conexion->set_charset("utf8");

// Leer el JSON del cuerpo
$data = json_decode(file_get_contents("php://input"), true);

if (!isset($data['id'])) {
    echo json_encode(["success" => false, "error" => "ID no especificado"]);
    exit;
}

$id = $data['id'];

$sql = "DELETE FROM eventos WHERE id=?";
$stmt = $conexion->prepare($sql);
$stmt->bind_param("i", $id);

if ($stmt->execute()) {
    echo json_encode(["success" => true]);
} else {
    echo json_encode(["success" => false, "error" => $stmt->error]);
}
