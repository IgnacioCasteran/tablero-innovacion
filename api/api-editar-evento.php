<?php
// api/api-editar-evento.php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../conexion.php';

try {
  $cn = db();
} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(['success' => false, 'error' => 'DB error']);
  exit;
}

// Datos desde JSON
$data = json_decode(file_get_contents('php://input'), true);
if (!$data || !isset($data['id'], $data['titulo'], $data['descripcion'], $data['fecha'])) {
  http_response_code(400);
  echo json_encode(['success' => false, 'error' => 'Datos incompletos']);
  exit;
}

$id          = (int)$data['id'];
$titulo      = $data['titulo'];
$descripcion = $data['descripcion'];
$fecha       = $data['fecha']; // YYYY-MM-DD o YYYY-MM-DD HH:mm:ss

$stmt = $cn->prepare('UPDATE eventos SET titulo = ?, descripcion = ?, fecha = ? WHERE id = ?');
$stmt->bind_param('sssi', $titulo, $descripcion, $fecha, $id);

if ($stmt->execute()) {
  echo json_encode(['success' => true]);
} else {
  http_response_code(500);
  echo json_encode(['success' => false, 'error' => $stmt->error]);
}
