<?php
// api-proyectos.php (multi-archivo)

header('Content-Type: application/json; charset=utf-8');
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

// Responder preflight CORS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }

require_once __DIR__ . '/../conexion.php';
$conn = db();
// --- Helpers ---
function ensure_attachments_table($conn) {
  $sql = "CREATE TABLE IF NOT EXISTS proyecto_archivos (
            id INT AUTO_INCREMENT PRIMARY KEY,
            proyecto_id INT NOT NULL,
            nombre_original VARCHAR(255) NOT NULL,
            archivo VARCHAR(255) NOT NULL,
            mime VARCHAR(127) DEFAULT NULL,
            size INT DEFAULT NULL,
            creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (proyecto_id) REFERENCES proyectos(id) ON DELETE CASCADE
          ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
  $conn->query($sql);
}

function project_dir($projectId) {
  $base = realpath(__DIR__ . '/../uploads') ?: (__DIR__ . '/../uploads');
  $dir = $base . '/proyectos/' . intval($projectId) . '/';
  if (!is_dir($dir)) { @mkdir($dir, 0777, true); }
  return $dir;
}

function sanitize_name($name) {
  $name = preg_replace('/[^\w\-. ]+/u', '_', $name);
  return substr($name, 0, 200);
}

function save_files($conn, $projectId, $field = 'fichas') {
  if (!isset($_FILES[$field])) return 0;

  // ⛔ Bloqueamos solo extensiones peligrosas (scripts/ejecutables)
  $deny = [
    'php','phtml','phar','cgi','pl','exe','bat','cmd','com','sh','bash','zsh',
    'js','jse','jar','vb','vbs','ps1','psm1','msi','msp','scr','apk','dll','so','dylib','jsp','asp','aspx','hta'
  ];

  $dir = project_dir($projectId);
  $count = 0;

  // Normalizar estructura cuando viene un único archivo
  $files = $_FILES[$field];
  $names = is_array($files['name']) ? $files['name'] : [$files['name']];
  $tmps  = is_array($files['tmp_name']) ? $files['tmp_name'] : [$files['tmp_name']];
  $errs  = is_array($files['error']) ? $files['error'] : [$files['error']];
  $sizes = is_array($files['size']) ? $files['size'] : [$files['size']];
  $types = is_array($files['type']) ? $files['type'] : [$files['type']];

  // Para guardar MIME real
  $finfo = function_exists('finfo_open') ? finfo_open(FILEINFO_MIME_TYPE) : null;

  $stmt = $conn->prepare(
    "INSERT INTO proyecto_archivos (proyecto_id, nombre_original, archivo, mime, size) VALUES (?,?,?,?,?)"
  );

  for ($i = 0; $i < count($names); $i++) {
    if ($errs[$i] !== UPLOAD_ERR_OK) continue;

    $orig = sanitize_name($names[$i]);
    $ext  = strtolower(pathinfo($orig, PATHINFO_EXTENSION));

    // Bloqueo de extensiones peligrosas
    if ($ext !== '' && in_array($ext, $deny, true)) {
      // saltamos este archivo por seguridad
      continue;
    }

    // Nombre único (mantenemos extensión si existe)
    $unique = uniqid('adj_', true) . ($ext ? ".$ext" : '');
    $dest   = $dir . $unique;

    if (move_uploaded_file($tmps[$i], $dest)) {
      $mime = $finfo ? @finfo_file($finfo, $dest) : ($types[$i] ?? null);
      $size = (int)$sizes[$i];
      $pid  = (int)$projectId;
      $stmt->bind_param("isssi", $pid, $orig, $unique, $mime, $size);
      $stmt->execute();
      $count++;
    }
  }

  if ($finfo) finfo_close($finfo);
  $stmt->close();
  return $count;
}


function delete_project_files($conn, $projectId) {
  $pid = (int)$projectId;
  $res = $conn->query("SELECT archivo FROM proyecto_archivos WHERE proyecto_id = $pid");
  $dir = project_dir($pid);
  while ($row = $res->fetch_assoc()) {
    $path = $dir . $row['archivo'];
    if (is_file($path)) @unlink($path);
  }
  // Los registros se van por ON DELETE CASCADE si borramos el proyecto
  // Limpieza de carpeta (opcional)
  @rmdir($dir);
}

// Aseguramos tabla de adjuntos
ensure_attachments_table($conn);

$method = $_SERVER['REQUEST_METHOD'];

// --- Rutas especiales ---
if ($method === 'GET' && isset($_GET['archivos'])) {
  $pid = intval($_GET['archivos']);
  $stmt = $conn->prepare("SELECT id, proyecto_id, nombre_original, archivo, mime, size, creado_en
                          FROM proyecto_archivos WHERE proyecto_id = ? ORDER BY creado_en DESC");
  $stmt->bind_param("i", $pid);
  $stmt->execute();
  $res = $stmt->get_result();
  $out = [];
  while ($row = $res->fetch_assoc()) $out[] = $row;
  echo json_encode($out);
  exit;
}

if ($method === 'DELETE' && isset($_GET['file_id'])) {
  $fid = intval($_GET['file_id']);
  // obtener info
  $stmt = $conn->prepare("SELECT proyecto_id, archivo FROM proyecto_archivos WHERE id=?");
  $stmt->bind_param("i", $fid);
  $stmt->execute();
  $stmt->bind_result($pid, $archivo);
  if ($stmt->fetch()) {
    $stmt->close();
    // borrar archivo físico
    $path = project_dir($pid) . $archivo;
    if (is_file($path)) @unlink($path);
    // borrar registro
    $stmt2 = $conn->prepare("DELETE FROM proyecto_archivos WHERE id=?");
    $stmt2->bind_param("i", $fid);
    $ok = $stmt2->execute();
    $stmt2->close();
    echo json_encode(['success' => $ok]);
  } else {
    echo json_encode(['success' => false]);
  }
  exit;
}

// --- Flujo principal ---
switch ($method) {
  case 'GET': {
    $result = $conn->query("SELECT * FROM proyectos ORDER BY fecha DESC");
    $proyectos = [];
    while ($row = $result->fetch_assoc()) {
      $proyectos[] = $row;
    }
    echo json_encode($proyectos);
    break;
  }

  // Alta o edición (con adjuntos múltiples)
  case 'POST': {
    // ¿Edición?
    if (isset($_POST['id']) && $_POST['id'] !== '') {
      $id = intval($_POST['id']);

      // Construcción dinámica del UPDATE (solo campos presentes)
      $campos = [];
      $vals = [];
      $types = '';

      foreach (['titulo','responsable','descripcion','estado','fecha'] as $f) {
        if (isset($_POST[$f])) {
          $campos[] = "$f = ?";
          $vals[] = $_POST[$f];
          $types .= 's';
        }
      }

      if ($campos) {
        $sql = "UPDATE proyectos SET " . implode(', ', $campos) . " WHERE id = ?";
        $types .= 'i';
        $vals[] = $id;
        $stmt = $conn->prepare($sql);
        $stmt->bind_param($types, ...$vals);
        if (!$stmt->execute()) {
          http_response_code(500);
          echo json_encode(['error' => 'Error al actualizar el proyecto']);
          exit;
        }
        $stmt->close();
      }

      // Adjuntar archivos nuevos (sin borrar los anteriores)
      $added = 0;
      if (!empty($_FILES['fichas']))  $added += save_files($conn, $id, 'fichas');
      if (!empty($_FILES['edit-fichas'])) $added += save_files($conn, $id, 'edit-fichas');

      echo json_encode(['success' => true, 'adjuntos_agregados' => $added]);
      break;
    }

    // Alta
    foreach (['titulo','responsable','descripcion','estado','fecha'] as $f) {
      if (!isset($_POST[$f])) {
        http_response_code(400);
        echo json_encode(['error' => "Falta $f"]);
        exit;
      }
    }

    $stmt = $conn->prepare("INSERT INTO proyectos (titulo, responsable, descripcion, estado, fecha, ficha)
                            VALUES (?, ?, ?, ?, ?, NULL)");
    $stmt->bind_param("sssss", $_POST['titulo'], $_POST['responsable'], $_POST['descripcion'], $_POST['estado'], $_POST['fecha']);
    if (!$stmt->execute()) {
      http_response_code(500);
      echo json_encode(['error' => 'Error al crear el proyecto']);
      exit;
    }
    $newId = $stmt->insert_id;
    $stmt->close();

    $added = 0;
    if (!empty($_FILES['fichas'])) $added = save_files($conn, $newId, 'fichas');

    echo json_encode(['success' => true, 'id' => $newId, 'adjuntos_agregados' => $added]);
    break;
  }

  case 'PUT': {
    parse_str(file_get_contents("php://input"), $put_vars);
    $stmt = $conn->prepare("UPDATE proyectos SET titulo=?, responsable=?, descripcion=?, estado=?, fecha=? WHERE id=?");
    $stmt->bind_param("sssssi", $put_vars['titulo'], $put_vars['responsable'], $put_vars['descripcion'], $put_vars['estado'], $put_vars['fecha'], $put_vars['id']);
    $ok = $stmt->execute();
    echo json_encode(['success' => $ok]);
    break;
  }

  case 'DELETE': {
    if (isset($_GET['id'])) {
      $id = intval($_GET['id']);
      // borrar archivos físicos
      delete_project_files($conn, $id);
      // borrar proyecto
      $ok = $conn->query("DELETE FROM proyectos WHERE id = $id");
      echo json_encode(['success' => $ok ? true : false]);
    } else {
      http_response_code(400);
      echo json_encode(['error' => 'Falta el ID para eliminar']);
    }
    break;
  }
}

$conn->close();


