<?php
// api/api-reuniones.php
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../conexion.php';

try {
  $cn = db();
} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(['error' => 'DB error']);
  exit;
}

$method = $_SERVER['REQUEST_METHOD'];

// Utils
$uploadDir = realpath(__DIR__ . '/../uploads') ?: (__DIR__ . '/../uploads');
$uploadReu = $uploadDir . '/reuniones';
if (!is_dir($uploadReu)) {
  @mkdir($uploadReu, 0777, true);
}

// Convierte '' o dd/mm/aaaa => NULL o yyyy-mm-dd
function to_mysql_date_or_null($s): ?string
{
  if ($s === null) return null;
  $s = trim((string)$s);
  if ($s === '') return null; // <-- vacío => NULL
  if (preg_match('/^(\d{2})\/(\d{2})\/(\d{4})$/', $s, $m)) {
    return "{$m[3]}-{$m[2]}-{$m[1]}";
  }
  return $s; // ya viene yyyy-mm-dd
}


function safe_filename($name)
{
  $name = preg_replace('/[^\w\-.]+/u', '_', $name);
  return $name ?: ('archivo_' . uniqid());
}

/* =========================================================
 * POST: alta o edición (si viene id)
 * - Form-data con posible archivo 'archivo'
 * ======================================================= */
if ($method === 'POST') {
  $id           = $_POST['id']           ?? null;
  $tipo         = $_POST['tipo']         ?? '';
  $tarea        = $_POST['tarea']        ?? '';
  $estado       = $_POST['estado']       ?? null;
  $notas        = $_POST['notas']        ?? null;
  $fecha_inicio = to_mysql_date_or_null($_POST['fecha_inicio'] ?? null);
  $fecha_fin    = to_mysql_date_or_null($_POST['fecha_fin']    ?? null);
  $asistentes   = $_POST['asistentes']   ?? null;

  if ($tipo === '' || $tarea === '') {
    http_response_code(400);
    echo json_encode(['error' => 'Faltan datos obligatorios (tipo, tarea)']);
    exit;
  }

  // Subida de archivo (opcional)
  $archivoNuevo = null;
  if (isset($_FILES['archivo']) && $_FILES['archivo']['error'] === UPLOAD_ERR_OK) {
    $orig = $_FILES['archivo']['name'];
    $ext  = pathinfo($orig, PATHINFO_EXTENSION);
    $base = safe_filename(pathinfo($orig, PATHINFO_FILENAME));
    $archivoNuevo = $base . '_' . uniqid('reunion_') . ($ext ? ".{$ext}" : '');
    $destino = $uploadReu . '/' . $archivoNuevo;
    if (!move_uploaded_file($_FILES['archivo']['tmp_name'], $destino)) {
      http_response_code(500);
      echo json_encode(['error' => 'Error al guardar el archivo']);
      exit;
    }
  }

  // EDICIÓN
  if (!empty($id)) {
    $id = (int)$id;

    // Si hay archivo nuevo, eliminar el anterior
    if ($archivoNuevo) {
      $stmtPrev = $cn->prepare("SELECT archivo FROM reuniones_actividades WHERE id = ?");
      $stmtPrev->bind_param('i', $id);
      $stmtPrev->execute();
      $stmtPrev->bind_result($archivoAnt);
      $stmtPrev->fetch();
      $stmtPrev->close();

      if (!empty($archivoAnt)) {
        $rutaAnt = $uploadReu . '/' . $archivoAnt;
        if (is_file($rutaAnt)) {
          @unlink($rutaAnt);
        }
      }
    }

    if ($archivoNuevo) {
      $sql = "UPDATE reuniones_actividades
                SET tipo=?, tarea=?, estado=?, notas=?, fecha_inicio=?, fecha_fin=?, asistentes=?, archivo=?
              WHERE id=?";
      $stmt = $cn->prepare($sql);
      $stmt->bind_param(
        'ssssssssi',
        $tipo,
        $tarea,
        $estado,
        $notas,
        $fecha_inicio,
        $fecha_fin,
        $asistentes,
        $archivoNuevo,
        $id
      );
    } else {
      $sql = "UPDATE reuniones_actividades
                SET tipo=?, tarea=?, estado=?, notas=?, fecha_inicio=?, fecha_fin=?, asistentes=?
              WHERE id=?";
      $stmt = $cn->prepare($sql);
      $stmt->bind_param(
        'sssssssi',
        $tipo,
        $tarea,
        $estado,
        $notas,
        $fecha_inicio,
        $fecha_fin,
        $asistentes,
        $id
      );
    }

    if ($stmt->execute()) {
      echo json_encode(['mensaje' => 'Registro actualizado correctamente']);
    } else {
      http_response_code(500);
      echo json_encode(['error' => 'Error al actualizar']);
    }
    exit;
  }

  // ALTA
  $sql = "INSERT INTO reuniones_actividades (tipo, tarea, estado, notas, fecha_inicio, fecha_fin, asistentes, archivo)
          VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
  $stmt = $cn->prepare($sql);
  $stmt->bind_param(
    'ssssssss',
    $tipo,
    $tarea,
    $estado,
    $notas,
    $fecha_inicio,
    $fecha_fin,
    $asistentes,
    $archivoNuevo
  );

  if ($stmt->execute()) {
    echo json_encode(['mensaje' => 'Reunión/Actividad cargada correctamente', 'id' => $stmt->insert_id]);
  } else {
    http_response_code(500);
    echo json_encode(['error' => 'Error al guardar en la base de datos']);
  }
  exit;
}

/* =========================================================
 * DELETE: elimina registro y archivo asociado
 * - Recibe id en el body (x-www-form-urlencoded) o querystring
 * ======================================================= */
if ($method === 'DELETE') {
  // intentar leer id del body o query
  parse_str(file_get_contents('php://input'), $body);
  $id = $body['id'] ?? ($_GET['id'] ?? null);

  if (!$id) {
    http_response_code(400);
    echo json_encode(['error' => 'Falta el ID']);
    exit;
  }

  $id = (int)$id;

  // Obtener archivo previo
  $stmt = $cn->prepare("SELECT archivo FROM reuniones_actividades WHERE id = ?");
  $stmt->bind_param('i', $id);
  $stmt->execute();
  $stmt->bind_result($archivo);
  $stmt->fetch();
  $stmt->close();

  // Borrar registro
  $stmtDel = $cn->prepare("DELETE FROM reuniones_actividades WHERE id = ?");
  $stmtDel->bind_param('i', $id);
  if ($stmtDel->execute()) {
    // Borrar archivo si existía
    if (!empty($archivo)) {
      $ruta = $uploadReu . '/' . $archivo;
      if (is_file($ruta)) {
        @unlink($ruta);
      }
    }
    echo json_encode(['mensaje' => 'Registro eliminado correctamente']);
  } else {
    http_response_code(500);
    echo json_encode(['error' => 'Error al eliminar']);
  }
  exit;
}

// Si llega otro método:
http_response_code(405);
echo json_encode(['error' => 'Método no permitido']);
