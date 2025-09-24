<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../conexion.php';

try {
  $cn = db();
  $sql = "SELECT id, titulo, fecha, hora_inicio, hora_fin, descripcion, COALESCE(categoria,'general') AS categoria
            FROM eventos
        ORDER BY fecha DESC, COALESCE(hora_inicio,'00:00:00')";
  $res = $cn->query($sql);

  $eventos = [];
  while ($e = $res->fetch_assoc()) {
    $allDay    = empty($e['hora_inicio']); // si no hay hora => evento de todo el día
    $categoria = $e['categoria'] ?: 'general';

    if ($allDay) {
      $eventos[] = [
        'id'           => (int)$e['id'],
        'title'        => $e['titulo'],
        'start'        => $e['fecha'], // allDay
        'allDay'       => true,
        'classNames'   => ["cat-{$categoria}"],
        'extendedProps'=> [
          'descripcion' => $e['descripcion'],
          'categoria'   => $categoria
        ]
      ];
    } else {
      $start = $e['fecha'] . 'T' . $e['hora_inicio'];
      $end   = !empty($e['hora_fin']) ? ($e['fecha'] . 'T' . $e['hora_fin']) : null;

      $ev = [
        'id'           => (int)$e['id'],
        'title'        => $e['titulo'],
        'start'        => $start,
        'allDay'       => false,
        'classNames'   => ["cat-{$categoria}"],
        'extendedProps'=> [
          'descripcion' => $e['descripcion'],
          'categoria'   => $categoria
        ]
      ];
      if ($end) $ev['end'] = $end;
      $eventos[] = $ev;
    }
  }

  echo json_encode($eventos, JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(['error' => 'DB error']);
}