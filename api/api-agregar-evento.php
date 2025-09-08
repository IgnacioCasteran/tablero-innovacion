<?php
// api/api-agregar-evento.php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../conexion.php';

try {
  $cn = db();

  // Tomar datos del POST
  $titulo      = $_POST['titulo']      ?? '';
  $descripcion = $_POST['descripcion'] ?? '';
  $fecha       = $_POST['fecha']       ?? '';

  if ($titulo === '' || $fecha === '') {
    http_response_code(400);
    echo json_encode(["success" => false, "error" => "Faltan campos obligatorios"]);
    exit;
  }

  $stmt = $cn->prepare("INSERT INTO eventos (titulo, descripcion, fecha) VALUES (?, ?, ?)");
  $stmt->bind_param("sss", $titulo, $descripcion, $fecha);

  if ($stmt->execute()) {
    echo json_encode(["success" => true, "id" => $stmt->insert_id]);
  } else {
    http_response_code(500);
    echo json_encode(["success" => false, "error" => $stmt->error]);
  }
} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(["success" => false, "error" => "DB error"]);
}
