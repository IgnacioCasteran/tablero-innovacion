<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../conexion.php';

function normTime(?string $t): ?string {
  $t = trim((string)$t);
  if ($t === '') return null;
  if (preg_match('/^\d{2}:\d{2}(:\d{2})?$/', $t)) {
    return strlen($t) === 5 ? ($t . ':00') : $t;
  }
  return null;
}

try { $cn = db(); }
catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(['success' => false, 'error' => 'DB error']); exit;
}

$data = json_decode(file_get_contents('php://input'), true);
if (!$data || !isset($data['id'], $data['titulo'], $data['descripcion'], $data['fecha'])) {
  http_response_code(400);
  echo json_encode(['success' => false, 'error' => 'Datos incompletos']); exit;
}

$id          = (int)$data['id'];
$titulo      = trim($data['titulo']);
$descripcion = trim($data['descripcion']);
$fecha       = trim($data['fecha']);          // YYYY-MM-DD
$hIni        = normTime($data['hora_inicio'] ?? '');
$hFin        = normTime($data['hora_fin']    ?? '');

if ($hFin && !$hIni) $hFin = null;
if ($hIni && $hFin && $hFin <= $hIni) $hFin = null;

$stmt = $cn->prepare('UPDATE eventos SET titulo=?, descripcion=?, fecha=?, hora_inicio=?, hora_fin=? WHERE id=?');
$stmt->bind_param('sssssi', $titulo, $descripcion, $fecha, $hIni, $hFin, $id);

if ($stmt->execute()) echo json_encode(['success' => true]);
else { http_response_code(500); echo json_encode(['success' => false, 'error' => $stmt->error]); }