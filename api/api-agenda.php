<?php
// api/api-agenda.php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../conexion.php';

try {
  $cn = db();

  $sql = "SELECT id, titulo, fecha, descripcion FROM eventos ORDER BY fecha DESC";
  $res = $cn->query($sql);

  $eventos = [];
  while ($fila = $res->fetch_assoc()) {
    $eventos[] = [
      'id'    => (int)$fila['id'],
      'title' => $fila['titulo'],
      'start' => $fila['fecha'],      // ISO (YYYY-MM-DD o YYYY-MM-DD HH:mm:ss)
      'extendedProps' => [
        'descripcion' => $fila['descripcion']
      ],
    ];
  }

  echo json_encode($eventos, JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(['error' => 'DB error']);
}
