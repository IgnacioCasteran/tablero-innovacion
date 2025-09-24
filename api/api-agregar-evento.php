<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../conexion.php';

function normTime(?string $t): ?string {
  $t = trim((string)$t);
  if ($t === '') return null;
  // admite HH:mm o HH:mm:ss
  if (preg_match('/^\d{2}:\d{2}(:\d{2})?$/', $t)) {
    return strlen($t) === 5 ? ($t . ':00') : $t;
  }
  return null;
}

try {
  $cn = db();
} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(["success" => false, "error" => "DB error"]);
  exit;
}

$titulo      = trim($_POST['titulo']      ?? '');
$descripcion = trim($_POST['descripcion'] ?? '');
$fecha       = trim($_POST['fecha']       ?? '');       // YYYY-MM-DD
$categoria   = trim($_POST['categoria']   ?? 'general');
$hIni        = normTime($_POST['hora_inicio'] ?? '');
$hFin        = normTime($_POST['hora_fin']    ?? '');

if ($titulo === '' || $fecha === '') {
  http_response_code(400);
  echo json_encode(["success" => false, "error" => "Faltan campos obligatorios"]);
  exit;
}

// Si hay hora fin sin hora inicio, la descartamos
if ($hFin && !$hIni) $hFin = null;
// Si hay ambas y fin <= inicio, anulamos fin
if ($hIni && $hFin && $hFin <= $hIni) $hFin = null;

$stmt = $cn->prepare("
  INSERT INTO eventos (titulo, descripcion, fecha, hora_inicio, hora_fin, categoria)
  VALUES (?,?,?,?,?,?)
");
$stmt->bind_param("ssssss", $titulo, $descripcion, $fecha, $hIni, $hFin, $categoria);

if ($stmt->execute()) {
  echo json_encode(["success" => true, "id" => $stmt->insert_id]);
} else {
  http_response_code(500);
  echo json_encode(["success" => false, "error" => $stmt->error]);
}
