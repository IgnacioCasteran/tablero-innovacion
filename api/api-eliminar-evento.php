<?php
// api/api-eliminar-evento.php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../conexion.php';

try {
  $cn = db();
} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(['success' => false, 'error' => 'DB error']);
  exit;
}

// Leer JSON del cuerpo
$data = json_decode(file_get_contents('php://input'), true);
if (!$data || !isset($data['id'])) {
  http_response_code(400);
  echo json_encode(['success' => false, 'error' => 'ID no especificado']);
  exit;
}

$id = (int)$data['id'];

$stmt = $cn->prepare('DELETE FROM eventos WHERE id = ?');
$stmt->bind_param('i', $id);

if ($stmt->execute()) {
  echo json_encode(['success' => true]);
} else {
  http_response_code(500);
  echo json_encode(['success' => false, 'error' => $stmt->error]);
}
