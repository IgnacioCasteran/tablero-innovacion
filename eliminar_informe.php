<?php
// eliminar_informe.php
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
  echo json_encode(["success" => false, "error" => "ID no proporcionado"]);
  exit;
}

$id = (int)$data["id"];

$stmt = $cn->prepare("DELETE FROM informes WHERE id = ?");
$stmt->bind_param("i", $id);

if ($stmt->execute()) {
  echo json_encode(["success" => true]);
} else {
  http_response_code(500);
  echo json_encode(["success" => false, "error" => $stmt->error]);
}
