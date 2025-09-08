<?php
// editar_informe.php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/conexion.php';

try {
  $cn = db();
} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(["success" => false, "error" => "DB error"]);
  exit;
}

$data = json_decode(file_get_contents("php://input"), true);
if (!$data || !isset($data["id"])) {
  http_response_code(400);
  echo json_encode(["success" => false, "error" => "Datos inválidos"]);
  exit;
}

$id            = (int)$data["id"];
$responsable   = $data["responsable"]   ?? '';
$empleado      = $data["empleado"]      ?? '';
$desde         = $data["desde"]         ?? '';
$hasta         = $data["hasta"]         ?? '';
$rubro         = $data["rubro"]         ?? '';
$categoria     = $data["categoria"]     ?? '';
$descripcion   = $data["descripcion"]   ?? '';
$observaciones = $data["observaciones"] ?? '';

$sql = "UPDATE informes SET
          responsable   = ?,
          empleado      = ?,
          desde         = ?,
          hasta         = ?,
          rubro         = ?,
          categoria     = ?,
          descripcion   = ?,
          observaciones = ?
        WHERE id = ?";

$stmt = $cn->prepare($sql);
$stmt->bind_param(
  "ssssssssi",
  $responsable,
  $empleado,
  $desde,
  $hasta,
  $rubro,
  $categoria,
  $descripcion,
  $observaciones,
  $id
);

if ($stmt->execute()) {
  echo json_encode(["success" => true]);
} else {
  http_response_code(500);
  echo json_encode(["success" => false, "error" => $stmt->error]);
}

