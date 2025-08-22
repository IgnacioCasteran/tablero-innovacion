<?php
$conexion = new mysqli("localhost", "root", "", "informes_pj");
if ($conexion->connect_error) {
  die("Conexión fallida: " . $conexion->connect_error);
}

$data = json_decode(file_get_contents("php://input"), true);

$stmt = $conexion->prepare("INSERT INTO informes (circunscripcion, oficina_judicial, responsable, desde, hasta, rubro, categoria, empleado, estado, descripcion, observaciones) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
$stmt->bind_param("sssssssssss",
  $data["circunscripcion"],
  $data["oficina_judicial"],
  $data["responsable"],
  $data["desde"],
  $data["hasta"],
  $data["rubro"],
  $data["categoria"],
  $data["empleado"],
  $data["estado"],
  $data["descripcion"],
  $data["observaciones"]
);

if ($stmt->execute()) {
  echo json_encode(["success" => true]);
} else {
  echo json_encode(["success" => false, "error" => $stmt->error]);
}
?>
