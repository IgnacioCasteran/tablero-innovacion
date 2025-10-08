<?php
// guardar_informe.php
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

  $data = json_decode(file_get_contents("php://input"), true);
  if (!$data) {
    http_response_code(400);
    echo json_encode(["success" => false, "error" => "No se recibieron datos"]);
    exit;
  }

  $rol = role_slug($_SESSION['rol'] ?? '');
  if ($rol === 'stj') {
    http_response_code(403);
    echo json_encode(["success" => false, "error" => "Solo lectura (STJ)"]);
    exit;
  }

  // Validación de alcance para coordinadores
  if (is_coord($rol)) {
    $uid = (int)($_SESSION['user_id'] ?? 0);
    $stU = $cn->prepare("SELECT alcance_circ, alcance_oficina FROM usuarios WHERE id=? LIMIT 1");
    $stU->bind_param("i", $uid);
    $stU->execute();
    $scope = $stU->get_result()->fetch_assoc() ?: ['alcance_circ'=>null, 'alcance_oficina'=>null];
    $stU->close();

    if (!empty($scope['alcance_circ']) && $scope['alcance_circ'] !== ($data['circunscripcion'] ?? '')) {
      http_response_code(403);
      echo json_encode(["success" => false, "error" => "Sin permiso (circunscripción)"]);
      exit;
    }
    if (!empty($scope['alcance_oficina']) && $scope['alcance_oficina'] !== ($data['oficina_judicial'] ?? '')) {
      http_response_code(403);
      echo json_encode(["success" => false, "error" => "Sin permiso (oficina)"]);
      exit;
    }
  }

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
