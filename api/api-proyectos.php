<?php
// api/api-proyectos.php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

// Preflight
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

$method     = $_SERVER['REQUEST_METHOD'];
$uploadDir  = realpath(__DIR__ . '/../uploads') ?: (__DIR__ . '/../uploads');
$uploadProy = $uploadDir . '/proyectos';

// Asegura carpeta destino
if (!is_dir($uploadProy)) {
  @mkdir($uploadProy, 0777, true);
}

// Nombre de archivo seguro
function safe_filename($name) {
  $name = preg_replace('/[^\w\-.]+/u', '_', $name); // letras, números, _ - .
  return $name ?: ('file_' . uniqid());
}

// Helper para adjuntar URL segura de descarga
function with_url_ficha(array $row) {
  if (!empty($row['ficha'])) {
    $row['url_ficha'] = '/descargas/proyecto.php?f=' . rawurlencode($row['ficha']);
  } else {
    $row['url_ficha'] = null;
  }
  return $row;
}

switch ($method) {
  /* =========================================================
   * GET: listar proyectos
   * ======================================================= */
  case 'GET':
    try {
      $res  = $cn->query("SELECT * FROM proyectos ORDER BY fecha DESC");
      $rows = [];
      while ($r = $res->fetch_assoc()) {
        $rows[] = with_url_ficha($r);
      }
      echo json_encode($rows, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    } catch (Throwable $e) {
      http_response_code(500);
      echo json_encode(['error' => 'Error al listar proyectos']);
    }
    break;

  /* =========================================================
   * POST: alta o edición (si viene id en form-data)
   *  - multipart/form-data para permitir archivo 'ficha'
   * Campos: titulo, responsable, descripcion, estado, fecha
   * Si incluye 'id' => UPDATE; si no => INSERT
   * ======================================================= */
  case 'POST':
    $isUpdate    = isset($_POST['id']) && $_POST['id'] !== '';
    $titulo      = $_POST['titulo']      ?? '';
    $responsable = $_POST['responsable'] ?? '';
    $descripcion = $_POST['descripcion'] ?? '';
    $estado      = $_POST['estado']      ?? '';
    $fecha       = $_POST['fecha']       ?? '';
    $fichaNombreFinal = null;

    if ($titulo === '' || $responsable === '' || $descripcion === '' || $estado === '' || $fecha === '') {
      http_response_code(400);
      echo json_encode(['error' => 'Faltan datos del formulario']);
      break;
    }

    // Archivo (opcional)
    if (isset($_FILES['ficha']) && $_FILES['ficha']['error'] === UPLOAD_ERR_OK) {
      $original = $_FILES['ficha']['name'];
      $ext      = pathinfo($original, PATHINFO_EXTENSION);
      $seguro   = safe_filename(pathinfo($original, PATHINFO_FILENAME));
      $fichaNombreFinal = $seguro . '_' . uniqid('ficha_') . ($ext ? '.' . $ext : '');
      $destino  = $uploadProy . '/' . $fichaNombreFinal;

      if (!move_uploaded_file($_FILES['ficha']['tmp_name'], $destino)) {
        http_response_code(500);
        echo json_encode(['error' => 'Error al guardar el archivo']);
        break;
      }
    }

    if ($isUpdate) {
      // ===== UPDATE =====
      $id = (int)($_POST['id']);

      if ($fichaNombreFinal !== null) {
        // borrar anterior si existía
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

        $stmt = $cn->prepare("UPDATE proyectos SET titulo=?, responsable=?, descripcion=?, estado=?, fecha=?, ficha=? WHERE id=?");
        $stmt->bind_param('ssssssi', $titulo, $responsable, $descripcion, $estado, $fecha, $fichaNombreFinal, $id);
      } else {
        $stmt = $cn->prepare("UPDATE proyectos SET titulo=?, responsable=?, descripcion=?, estado=?, fecha=? WHERE id=?");
        $stmt->bind_param('sssssi', $titulo, $responsable, $descripcion, $estado, $fecha, $id);
      }

      if ($stmt->execute()) {
        // devolver registro mínimo actualizado (con URL si hay ficha)
        $row = [
          'id'          => $id,
          'titulo'      => $titulo,
          'responsable' => $responsable,
          'descripcion' => $descripcion,
          'estado'      => $estado,
          'fecha'       => $fecha,
          'ficha'       => $fichaNombreFinal ?? null,
        ];
        echo json_encode(with_url_ficha($row), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
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
      $out = [
        'id'          => $stmt->insert_id,
        'titulo'      => $titulo,
        'responsable' => $responsable,
        'descripcion' => $descripcion,
        'estado'      => $estado,
        'fecha'       => $fecha,
        'ficha'       => $fichaNombreFinal,
      ];
      echo json_encode(with_url_ficha($out), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    } else {
      http_response_code(500);
      echo json_encode(['error' => 'Error al crear el proyecto']);
    }
    $stmt->close();
    break;

  /* =========================================================
   * PUT: actualización (sin archivo) vía raw body (x-www-form-urlencoded)
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
      $row = [
        'id'          => $id,
        'titulo'      => $put['titulo'],
        'responsable' => $put['responsable'],
        'descripcion' => $put['descripcion'],
        'estado'      => $put['estado'],
        'fecha'       => $put['fecha'],
        'ficha'       => null, // no se toca ficha en PUT
      ];
      echo json_encode(with_url_ficha($row), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    } else {
      http_response_code(500);
      echo json_encode(['error' => 'Error al actualizar el proyecto']);
    }
    $stmt->close();
    break;

  /* =========================================================
   * DELETE: elimina proyecto (y su archivo si existe)
   * ?id=123
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

