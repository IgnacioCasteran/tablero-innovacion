<?php
$conexion = new mysqli("localhost", "root", "", "informes_pj");

if ($conexion->connect_error) {
  die(json_encode(["success" => false, "error" => "Error de conexión"]));
}

$data = json_decode(file_get_contents("php://input"), true);

if (!isset($data["id"])) {
  echo json_encode(["success" => false, "error" => "ID no proporcionado"]);
  exit;
}

$id = intval($data["id"]);

$sql = "DELETE FROM informes WHERE id = $id";

if ($conexion->query($sql)) {
  echo json_encode(["success" => true]);
} else {
  echo json_encode(["success" => false, "error" => $conexion->error]);
}

$conexion->close();
?>
