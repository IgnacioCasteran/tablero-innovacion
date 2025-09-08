<?php
// guardar_informe.php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/conexion.php';

try {
  $cn = db();

  // Leer datos enviados en JSON
  $data = json_decode(file_get_contents("php://input"), true);

  if (!$data) {
    http_response_code(400);
    echo json_encode(["success" => false, "error" => "No se recibieron datos"]);
    exit;
  }

  // Preparar INSERT (ajusta nombres de columnas si cambian en tu tabla)
  $sql = "INSERT INTO informes (
            circunscripcion, oficina_judicial, responsable, 
            desde, hasta, rubro, categoria, empleado, estado, 
            descripcion, observaciones
          ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

  $stmt = $cn->prepare($sql);
  $stmt->bind_param(
    "sssssssssss",
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
    http_response_code(500);
    echo json_encode(["success" => false, "error" => $stmt->error]);
  }
} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(["success" => false, "error" => "DB error"]);
}
