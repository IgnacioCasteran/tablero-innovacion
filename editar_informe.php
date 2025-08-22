<?php
// Conexión a la base de datos
$conexion = new mysqli("localhost", "root", "", "informes_pj");

if ($conexion->connect_error) {
  die(json_encode(["success" => false, "error" => "Error de conexión"]));
}

$data = json_decode(file_get_contents("php://input"), true);

if (!$data) {
  echo json_encode(["success" => false, "error" => "Datos inválidos"]);
  exit;
}

// Sanitización (podés mejorar esto con prepared statements)
$id = intval($data["id"]);
$responsable = $conexion->real_escape_string($data["responsable"]);
$empleado = $conexion->real_escape_string($data["empleado"]);
$desde = $conexion->real_escape_string($data["desde"]);
$hasta = $conexion->real_escape_string($data["hasta"]);
$rubro = $conexion->real_escape_string($data["rubro"]);
$categoria = $conexion->real_escape_string($data["categoria"]);
$descripcion = $conexion->real_escape_string($data["descripcion"]);
$observaciones = $conexion->real_escape_string($data["observaciones"]);

$sql = "UPDATE informes SET 
  responsable = '$responsable',
  empleado = '$empleado',
  desde = '$desde',
  hasta = '$hasta',
  rubro = '$rubro',
  categoria = '$categoria',
  descripcion = '$descripcion',
  observaciones = '$observaciones'
  WHERE id = $id";

if ($conexion->query($sql)) {
  echo json_encode(["success" => true]);
} else {
  echo json_encode(["success" => false, "error" => $conexion->error]);
}

$conexion->close();
?>
