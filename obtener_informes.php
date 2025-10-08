<?php
// obtener_informes.php
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/conexion.php';
if (session_status() !== PHP_SESSION_ACTIVE) session_start();

function role_slug($r) {
  $r = mb_strtolower(trim((string)$r));
  return strtr($r, ['á'=>'a','é'=>'e','í'=>'i','ó'=>'o','ú'=>'u','ä'=>'a','ë'=>'e','ï'=>'i','ö'=>'o','ü'=>'u']);
}
function is_coord($r) { $r = role_slug($r); return substr($r, 0, 11) === 'coordinador'; }

try {
  $cn = db();
  $cn->set_charset('utf8mb4');

  // Traigo rol/alcance desde BD por si la sesión no lo tiene actualizado
  $uid = (int)($_SESSION['user_id'] ?? 0);
  $scope = ['rol'=>$_SESSION['rol'] ?? null, 'circ'=>null, 'ofi'=>null];

  if ($uid > 0) {
    $st = $cn->prepare("SELECT rol, alcance_circ, alcance_oficina FROM usuarios WHERE id=? LIMIT 1");
    $st->bind_param("i", $uid);
    if ($st->execute() && ($rs = $st->get_result()) && ($u = $rs->fetch_assoc())) {
      $scope['rol']  = $u['rol'] ?? $scope['rol'];
      $scope['circ'] = $u['alcance_circ'] ?? null;
      $scope['ofi']  = $u['alcance_oficina'] ?? null;
    }
    $st->close();
  }

  $rol = role_slug($scope['rol']);
  $where = '';
  $params = [];
  $types  = '';

  if (is_coord($rol)) {
    if (!empty($scope['circ'])) { $where .= ($where ? ' AND ' : ' WHERE ') . "circunscripcion = ?"; $params[] = $scope['circ']; $types .= 's'; }
    if (!empty($scope['ofi']))  { $where .= ($where ? ' AND ' : ' WHERE ') . "oficina_judicial = ?";  $params[] = $scope['ofi'];  $types .= 's'; }
  }

  $sql = "SELECT * FROM informes" . $where . " ORDER BY id DESC";
  if ($where) {
    $stmt = $cn->prepare($sql);
    if ($types === 's')           { $stmt->bind_param($types, $params[0]); }
    elseif ($types === 'ss')      { $stmt->bind_param($types, $params[0], $params[1]); }
    else                          { $stmt->bind_param($types, ...$params); }
    $stmt->execute();
    $res = $stmt->get_result();
  } else {
    $res = $cn->query($sql);
  }

  $canWrite = ($rol === 'secretaria' || is_coord($rol));
  $data = [];
  while ($row = $res->fetch_assoc()) {
    $row['can_edit']   = $canWrite && $rol !== 'stj';
    $row['can_delete'] = $canWrite && $rol !== 'stj';
    $data[] = $row;
  }

  echo json_encode($data, JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(['error' => 'DB error']);
}
