<?php
header('Content-Type: application/json');

$host = 'localhost';
$db = 'informes_pj';
$user = 'root';
$pass = '';

$conn = new mysqli($host, $user, $pass, $db);
$conn->set_charset("utf8");

if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode(['error' => 'Error de conexión a la base de datos']);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'POST') {
    $id = $_POST['id'] ?? null;
    $tipo = $_POST['tipo'];
    $tarea = $_POST['tarea'];
    $estado = $_POST['estado'] ?? null;
    $notas = $_POST['notas'] ?? null;
    $fecha_inicio = $_POST['fecha_inicio'] ?? null;
    $fecha_fin = $_POST['fecha_fin'] ?? null;
    $asistentes = $_POST['asistentes'] ?? null;

    $archivo = null;

    // Subida de nuevo archivo (en edición o alta)
    if (isset($_FILES['archivo']) && $_FILES['archivo']['error'] === UPLOAD_ERR_OK) {
        $ext = pathinfo($_FILES['archivo']['name'], PATHINFO_EXTENSION);
        $archivo = uniqid('reunion_') . '.' . $ext;

        $carpetaDestino = __DIR__ . '/../uploads/reuniones/';
        if (!file_exists($carpetaDestino)) {
            mkdir($carpetaDestino, 0777, true);
        }

        $rutaFinal = $carpetaDestino . $archivo;
        move_uploaded_file($_FILES['archivo']['tmp_name'], $rutaFinal);
    }

    // EDICIÓN
    if ($id) {
        // Si hay archivo nuevo, eliminar el anterior
        if ($archivo) {
            $stmtAnt = $conn->prepare("SELECT archivo FROM reuniones_actividades WHERE id=?");
            $stmtAnt->bind_param("i", $id);
            $stmtAnt->execute();
            $stmtAnt->bind_result($archivoAnt);
            $stmtAnt->fetch();
            $stmtAnt->close();

            if ($archivoAnt && file_exists(__DIR__ . '/../uploads/reuniones/' . $archivoAnt)) {
                unlink(__DIR__ . '/../uploads/reuniones/' . $archivoAnt);
            }
        }

        $query = "UPDATE reuniones_actividades SET tipo=?, tarea=?, estado=?, notas=?, fecha_inicio=?, fecha_fin=?, asistentes=?";
        $params = [$tipo, $tarea, $estado, $notas, $fecha_inicio, $fecha_fin, $asistentes];
        $types = "sssssss";

        if ($archivo) {
            $query .= ", archivo=?";
            $params[] = $archivo;
            $types .= "s";
        }

        $query .= " WHERE id=?";
        $params[] = $id;
        $types .= "i";

        $stmt = $conn->prepare($query);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();

        echo json_encode(['mensaje' => 'Registro actualizado correctamente']);
        exit;
    }

    // ALTA
    $stmt = $conn->prepare("INSERT INTO reuniones_actividades (tipo, tarea, estado, notas, fecha_inicio, fecha_fin, asistentes, archivo) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssssss", $tipo, $tarea, $estado, $notas, $fecha_inicio, $fecha_fin, $asistentes, $archivo);

    if ($stmt->execute()) {
        echo json_encode(['mensaje' => 'Reunión/Actividad cargada correctamente']);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Error al guardar en la base de datos']);
    }

    $stmt->close();
    $conn->close();
    exit;
}

// ELIMINACIÓN
if ($method === 'DELETE') {
    parse_str(file_get_contents("php://input"), $data);
    $id = $data['id'] ?? null;

    if (!$id) {
        http_response_code(400);
        echo json_encode(['error' => 'Falta el ID']);
        exit;
    }

    // Eliminar archivo
    $stmt = $conn->prepare("SELECT archivo FROM reuniones_actividades WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->bind_result($archivo);
    $stmt->fetch();
    $stmt->close();

    if ($archivo && file_exists(__DIR__ . '/../uploads/reuniones/' . $archivo)) {
        unlink(__DIR__ . '/../uploads/reuniones/' . $archivo);
    }

    // Eliminar registro
    $stmt = $conn->prepare("DELETE FROM reuniones_actividades WHERE id = ?");
    $stmt->bind_param("i", $id);

    if ($stmt->execute()) {
        echo json_encode(['mensaje' => 'Registro eliminado correctamente']);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Error al eliminar']);
    }

    $stmt->close();
    $conn->close();
    exit;
}
?>
