<?php
// obtener_informes.php
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/conexion.php';

try {
  $cn = db();

  // Si necesitás ajustar el nombre de tabla/campos, hacelo acá
  $sql = "SELECT * FROM informes ORDER BY fecha_creacion DESC";
  $res = $cn->query($sql);

  $data = [];
  while ($row = $res->fetch_assoc()) {
    $data[] = $row;
  }

  echo json_encode($data, JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
  http_response_code(500);
  // En local podés mostrar detalle si APP_ENV=local, pero por seguridad devolvemos genérico
  echo json_encode(['error' => 'DB error']);
}
