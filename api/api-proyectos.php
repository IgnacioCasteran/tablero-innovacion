<?php
// api/api-proyectos.php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

// Responder preflight rápidamente
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
  http_response_code(204);
  exit;
}

require_once __DIR__ . '/../conexion.php';

try {
  $cn = db();
} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(['error' => 'DB error']);
  exit;
}

$method = $_SERVER['REQUEST_METHOD'];
$uploadDir = realpath(__DIR__ . '/../uploads') ?: (__DIR__ . '/../uploads');
$uploadProy = $uploadDir . '/proyectos';

// Asegura carpeta de destino
if (!is_dir($uploadProy)) {
  @mkdir($uploadProy, 0777, true);
}

// Utilidad mínima para nombre de archivo seguro
function safe_filename($name) {
  $name = preg_replace('/[^\w\-.]+/u', '_', $name); // letras, números, _ - .
  return $name ?: ('file_' . uniqid());
}

switch ($method) {
  /* =========================================================
   * GET: listar proyectos
   * ======================================================= */
  case 'GET':
    try {
      $res = $cn->query("SELECT * FROM proyectos ORDER BY fecha DESC");
      $rows = [];
      while ($r = $res->fetch_assoc()) { $rows[] = $r; }
      echo json_encode($rows, JSON_UNESCAPED_UNICODE);
    } catch (Throwable $e) {
      http_response_code(500);
      echo json_encode(['error' => 'Error al listar proyectos']);
    }
    break;

  /* =========================================================
   * POST: alta o edición (si viene id en form-data)
   *  - Usa multipart/form-data para permitir archivo 'ficha'
   * Campos esperados: titulo, responsable, descripcion, estado, fecha
   * Si incluye 'id' => ACTUALIZA; si no, CREA
   * ======================================================= */
  case 'POST':
    $isUpdate = isset($_POST['id']) && $_POST['id'] !== '';
    $titulo = $_POST['titulo'] ?? '';
    $responsable = $_POST['responsable'] ?? '';
    $descripcion = $_POST['descripcion'] ?? '';
    $estado = $_POST['estado'] ?? '';
    $fecha = $_POST['fecha'] ?? '';
    $fichaNombreFinal = null;

    if ($titulo === '' || $responsable === '' || $descripcion === '' || $estado === '' || $fecha === '') {
      http_response_code(400);
      echo json_encode(['error' => 'Faltan datos del formulario']);
      break;
    }

    // Manejo de archivo (opcional)
    if (isset($_FILES['ficha']) && $_FILES['ficha']['error'] === UPLOAD_ERR_OK) {
      $original = $_FILES['ficha']['name'];
      $ext = pathinfo($original, PATHINFO_EXTENSION);
      $seguro = safe_filename(pathinfo($original, PATHINFO_FILENAME));
      $fichaNombreFinal = $seguro . '_' . uniqid('ficha_') . ($ext ? '.' . $ext : '');
      $destino = $uploadProy . '/' . $fichaNombreFinal;
      if (!move_uploaded_file($_FILES['ficha']['tmp_name'], $destino)) {
        http_response_code(500);
        echo json_encode(['error' => 'Error al guardar el archivo']);
        break;
      }
    }

    if ($isUpdate) {
      // ===== UPDATE =====
      $id = (int)$_POST['id'];

      // Si hay una nueva ficha, eliminar la anterior
      if ($fichaNombreFinal !== null) {
        $stmtPrev = $cn->prepare("SELECT ficha FROM proyectos WHERE id = ?");
        $stmtPrev->bind_param('i', $id);
        $stmtPrev->execute();
        $stmtPrev->bind_result($fichaAnterior);
        $stmtPrev->fetch();
        $stmtPrev->close();

        if (!empty($fichaAnterior)) {
          $rutaAnterior = $uploadProy . '/' . $fichaAnterior;
          if (is_file($rutaAnterior)) { @unlink($rutaAnterior); }
        }

        // update con ficha
        $stmt = $cn->prepare("UPDATE proyectos SET titulo=?, responsable=?, descripcion=?, estado=?, fecha=?, ficha=? WHERE id=?");
        $stmt->bind_param('ssssssi', $titulo, $responsable, $descripcion, $estado, $fecha, $fichaNombreFinal, $id);
      } else {
        // update sin tocar ficha
        $stmt = $cn->prepare("UPDATE proyectos SET titulo=?, responsable=?, descripcion=?, estado=?, fecha=? WHERE id=?");
        $stmt->bind_param('sssssi', $titulo, $responsable, $descripcion, $estado, $fecha, $id);
      }

      if ($stmt->execute()) {
        echo json_encode(['mensaje' => 'Proyecto actualizado correctamente']);
      } else {
        http_response_code(500);
        echo json_encode(['error' => 'Error al actualizar el proyecto']);
      }
      $stmt->close();
      break;
    }

    // ===== INSERT =====
    $stmt = $cn->prepare("INSERT INTO proyectos (titulo, responsable, descripcion, estado, fecha, ficha) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param('ssssss', $titulo, $responsable, $descripcion, $estado, $fecha, $fichaNombreFinal);
    if ($stmt->execute()) {
      echo json_encode(['mensaje' => 'Proyecto creado correctamente', 'id' => $stmt->insert_id]);
    } else {
      http_response_code(500);
      echo json_encode(['error' => 'Error al crear el proyecto']);
    }
    $stmt->close();
    break;

  /* =========================================================
   * PUT: actualización (sin archivo) vía raw body (x-www-form-urlencoded)
   * Nota: si necesitás archivo en update, usá POST con id como arriba.
   * ======================================================= */
  case 'PUT':
    parse_str(file_get_contents('php://input'), $put);
    if (!isset($put['id'], $put['titulo'], $put['responsable'], $put['descripcion'], $put['estado'], $put['fecha'])) {
      http_response_code(400);
      echo json_encode(['error' => 'Datos incompletos']);
      break;
    }

    $id = (int)$put['id'];
    $stmt = $cn->prepare("UPDATE proyectos SET titulo=?, responsable=?, descripcion=?, estado=?, fecha=? WHERE id=?");
    $stmt->bind_param('sssssi', $put['titulo'], $put['responsable'], $put['descripcion'], $put['estado'], $put['fecha'], $id);

    if ($stmt->execute()) {
      echo json_encode(['mensaje' => 'Proyecto actualizado correctamente']);
    } else {
      http_response_code(500);
      echo json_encode(['error' => 'Error al actualizar el proyecto']);
    }
    $stmt->close();
    break;

  /* =========================================================
   * DELETE: elimina proyecto (y su archivo si existe)
   * - Recibe id por querystring ?id=123
   * ======================================================= */
  case 'DELETE':
    if (!isset($_GET['id'])) {
      http_response_code(400);
      echo json_encode(['error' => 'Falta el ID para eliminar']);
      break;
    }
    $id = (int)$_GET['id'];

    // obtener ficha antes de borrar
    $stmt = $cn->prepare("SELECT ficha FROM proyectos WHERE id = ?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $stmt->bind_result($ficha);
    $stmt->fetch();
    $stmt->close();

    // borrar registro
    $stmtDel = $cn->prepare("DELETE FROM proyectos WHERE id = ?");
    $stmtDel->bind_param('i', $id);

    if ($stmtDel->execute()) {
      // borrar archivo si existía
      if (!empty($ficha)) {
        $ruta = $uploadProy . '/' . $ficha;
        if (is_file($ruta)) { @unlink($ruta); }
      }
      echo json_encode(['mensaje' => 'Proyecto eliminado correctamente']);
    } else {
      http_response_code(500);
      echo json_encode(['error' => 'Error al eliminar el proyecto']);
    }
    $stmtDel->close();
    break;

  default:
    http_response_code(405);
    echo json_encode(['error' => 'Método no permitido']);
}
