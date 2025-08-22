<?php
// api-proyectos.php

header('Content-Type: application/json');
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE");
header("Access-Control-Allow-Headers: Content-Type");

$host = 'localhost';
$db = 'informes_pj';
$user = 'root';
$pass = '';

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
  http_response_code(500);
  echo json_encode(['error' => 'Error de conexión a la base de datos']);
  exit;
}

$method = $_SERVER['REQUEST_METHOD'];

// ✅ EDICIÓN vía POST (antes del INSERT)
if ($method === 'POST' && isset($_POST['id'])) {
  $id = intval($_POST['id']);
  $titulo = $_POST['titulo'];
  $responsable = $_POST['responsable'];
  $descripcion = $_POST['descripcion'];
  $estado = $_POST['estado'];
  $fecha = $_POST['fecha'];

  $query = "UPDATE proyectos SET titulo=?, responsable=?, descripcion=?, estado=?, fecha=?";
  $params = [$titulo, $responsable, $descripcion, $estado, $fecha];
  $types = "sssss";

  // Si hay nueva ficha, procesarla
  if (isset($_FILES['ficha']) && $_FILES['ficha']['error'] === UPLOAD_ERR_OK) {
    // 1. Eliminar la ficha anterior
    $stmt_anterior = $conn->prepare("SELECT ficha FROM proyectos WHERE id = ?");
    $stmt_anterior->bind_param("i", $id);
    $stmt_anterior->execute();
    $stmt_anterior->bind_result($fichaAnterior);
    $stmt_anterior->fetch();
    $stmt_anterior->close();

    if (!empty($fichaAnterior)) {
      $rutaAnterior = __DIR__ . '/../uploads/proyectos/' . $fichaAnterior;
      if (file_exists($rutaAnterior)) {
        unlink($rutaAnterior); // 🔥 Elimina el archivo anterior
      }
    }

    $ext = pathinfo($_FILES['ficha']['name'], PATHINFO_EXTENSION);
    $nuevoNombre = uniqid('ficha_') . '.' . $ext;
    $destino = __DIR__ . '/../uploads/proyectos/' . $nuevoNombre;
    move_uploaded_file($_FILES['ficha']['tmp_name'], $destino);

    $query .= ", ficha=?";
    $params[] = $nuevoNombre;
    $types .= "s";
  }

  $query .= " WHERE id=?";
  $params[] = $id;
  $types .= "i";

  $stmt = $conn->prepare($query);
  $stmt->bind_param($types, ...$params);

  if ($stmt->execute()) {
    echo json_encode(['mensaje' => 'Proyecto actualizado correctamente']);
  } else {
    http_response_code(500);
    echo json_encode(['error' => 'Error al actualizar el proyecto']);
  }

  $conn->close();
  exit;
}

// 🚀 Continuamos con el switch general
switch ($method) {
  case 'GET':
    $result = $conn->query("SELECT * FROM proyectos ORDER BY fecha DESC");
    $proyectos = [];
    while ($row = $result->fetch_assoc()) {
      $proyectos[] = $row;
    }
    echo json_encode($proyectos);
    break;

  case 'POST': // ALTA
    if (!isset($_POST['titulo'], $_POST['responsable'], $_POST['descripcion'], $_POST['estado'], $_POST['fecha'])) {
      http_response_code(400);
      echo json_encode(['error' => 'Faltan datos del formulario']);
      exit;
    }

    $titulo = $_POST['titulo'];
    $responsable = $_POST['responsable'];
    $descripcion = $_POST['descripcion'];
    $estado = $_POST['estado'];
    $fecha = $_POST['fecha'];
    $ficha = null;

    if (isset($_FILES['ficha']) && $_FILES['ficha']['error'] === UPLOAD_ERR_OK) {
      $nombreOriginal = $_FILES['ficha']['name'];
      $ext = pathinfo($nombreOriginal, PATHINFO_EXTENSION);
      $nuevoNombre = uniqid('ficha_') . '.' . $ext;

      $carpetaDestino = __DIR__ . '/../uploads/proyectos/';
      if (!file_exists($carpetaDestino)) {
        mkdir($carpetaDestino, 0777, true);
      }

      $rutaDestino = $carpetaDestino . $nuevoNombre;
      if (move_uploaded_file($_FILES['ficha']['tmp_name'], $rutaDestino)) {
        $ficha = $nuevoNombre;
      } else {
        http_response_code(500);
        echo json_encode(['error' => 'Error al guardar el archivo']);
        exit;
      }
    }

    $stmt = $conn->prepare("INSERT INTO proyectos (titulo, responsable, descripcion, estado, fecha, ficha) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssss", $titulo, $responsable, $descripcion, $estado, $fecha, $ficha);

    if ($stmt->execute()) {
      echo json_encode(['mensaje' => 'Proyecto creado correctamente']);
    } else {
      http_response_code(500);
      echo json_encode(['error' => 'Error al crear el proyecto']);
    }
    break;

  case 'PUT':
    parse_str(file_get_contents("php://input"), $put_vars);
    $stmt = $conn->prepare("UPDATE proyectos SET titulo=?, responsable=?, descripcion=?, estado=?, fecha=? WHERE id=?");
    $stmt->bind_param("sssssi", $put_vars['titulo'], $put_vars['responsable'], $put_vars['descripcion'], $put_vars['estado'], $put_vars['fecha'], $put_vars['id']);
    if ($stmt->execute()) {
      echo json_encode(['mensaje' => 'Proyecto actualizado correctamente']);
    } else {
      http_response_code(500);
      echo json_encode(['error' => 'Error al actualizar el proyecto']);
    }
    break;

  case 'DELETE':
    if (!isset($_GET['id'])) {
      http_response_code(400);
      echo json_encode(['error' => 'Falta el ID para eliminar']);
      break;
    }
    $id = intval($_GET['id']);
    // Obtener nombre del archivo antes de eliminar
    $stmt = $conn->prepare("SELECT ficha FROM proyectos WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->bind_result($ficha);
    $stmt->fetch();
    $stmt->close();

    // Eliminar archivo si existe
    if (!empty($ficha)) {
      $ruta = __DIR__ . '/../uploads/proyectos/' . $ficha;
      if (file_exists($ruta)) {
        unlink($ruta); // 🔥 Elimina el archivo del servidor
      }
    }

    // Eliminar el registro de la base de datos
    if ($conn->query("DELETE FROM proyectos WHERE id = $id")) {
      echo json_encode(['mensaje' => 'Proyecto eliminado correctamente']);
    } else {
      http_response_code(500);
      echo json_encode(['error' => 'Error al eliminar el proyecto']);
    }

    break;
}

$conn->close();
?>