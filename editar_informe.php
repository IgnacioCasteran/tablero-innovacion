<?php
// editar_informe.php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/conexion.php';
if (session_status() !== PHP_SESSION_ACTIVE) session_start();

function role_slug($r) {
  $r = mb_strtolower(trim((string)$r));
  return strtr($r, ['á'=>'a','é'=>'e','í'=>'i','ó'=>'o','ú'=>'u','ä'=>'a','ë'=>'e','ï'=>'i','ö'=>'o','ü'=>'u']);
}
function is_coord($r) { return substr(role_slug($r), 0, 11) === 'coordinador'; }

try {
  $cn = db();
  $cn->set_charset('utf8mb4');
} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(["success" => false, "error" => "DB error"]);
  exit;
}

$payload = json_decode(file_get_contents("php://input"), true);
if (!$payload || empty($payload["id"])) {
  http_response_code(400);
  echo json_encode(["success" => false, "error" => "Datos inválidos"]);
  exit;
}

$id            = (int)$payload["id"];
$responsable   = $payload["responsable"]   ?? '';
$empleado      = $payload["empleado"]      ?? '';
$desde         = $payload["desde"]         ?? '';
$hasta         = $payload["hasta"]         ?? '';
$rubro         = $payload["rubro"]         ?? '';
$categoria     = $payload["categoria"]     ?? '';
$descripcion   = $payload["descripcion"]   ?? '';
$observaciones = $payload["observaciones"] ?? '';

$uid = (int)($_SESSION['user_id'] ?? 0);
$rol = role_slug($_SESSION['rol'] ?? '');

if ($rol === 'stj') {
  http_response_code(403);
  echo json_encode(["success" => false, "error" => "Solo lectura (STJ)"]);
  exit;
}

// Si es coordinador, validar que el informe esté dentro de su alcance
if (is_coord($rol) && $uid > 0) {
  $stU = $cn->prepare("SELECT alcance_circ, alcance_oficina FROM usuarios WHERE id=? LIMIT 1");
  $stU->bind_param("i", $uid);
  $stU->execute();
  $scope = $stU->get_result()->fetch_assoc() ?: ['alcance_circ'=>null, 'alcance_oficina'=>null];
  $stU->close();

  $stI = $cn->prepare("SELECT circunscripcion, oficina_judicial FROM informes WHERE id=? LIMIT 1");
  $stI->bind_param("i", $id);
  $stI->execute();
  $inf = $stI->get_result()->fetch_assoc();
  $stI->close();

  if (!$inf) {
    http_response_code(404);
    echo json_encode(["success" => false, "error" => "No existe"]);
    exit;
  }

  if (!empty($scope['alcance_circ']) && $scope['alcance_circ'] !== $inf['circunscripcion']) {
    http_response_code(403);
    echo json_encode(["success" => false, "error" => "Sin permiso (circunscripción)"]);
    exit;
  }
  if (!empty($scope['alcance_oficina']) && $scope['alcance_oficina'] !== $inf['oficina_judicial']) {
    http_response_code(403);
    echo json_encode(["success" => false, "error" => "Sin permiso (oficina)"]);
    exit;
  }
}

$sql = "UPDATE informes SET
          responsable   = ?,
          empleado      = ?,
          desde         = ?,
          hasta         = ?,
          rubro         = ?,
          categoria     = ?,
          descripcion   = ?,
          observaciones = ?
        WHERE id = ?";

$stmt = $cn->prepare($sql);
$stmt->bind_param(
  "ssssssssi",
  $responsable, $empleado, $desde, $hasta, $rubro, $categoria, $descripcion, $observaciones, $id
);

if ($stmt->execute()) {
  echo json_encode(["success" => true]);
} else {
  http_response_code(500);
  echo json_encode(["success" => false, "error" => $stmt->error]);
}

