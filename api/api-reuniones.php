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

// ==== Paths de uploads ====
$uploadDir = realpath(__DIR__ . '/../uploads') ?: (__DIR__ . '/../uploads');
$uploadReu = $uploadDir . '/reuniones';
if (!is_dir($uploadReu)) {
    @mkdir($uploadReu, 0777, true);
}

// ==== Helpers ====
function to_mysql_date_or_null($s): ?string {
    if ($s === null) return null;
    $s = trim((string)$s);
    if ($s === '') return null;
    if (preg_match('/^(\d{2})\/(\d{2})\/(\d{4})$/', $s, $m)) {
        return "{$m[3]}-{$m[2]}-{$m[1]}";
    }
    return $s; // ya yyyy-mm-dd
}

function safe_filename($name) {
    $name = preg_replace('/[^\w\-.]+/u', '_', $name);
    return $name ?: ('archivo_' . uniqid());
}

/**
 * Normaliza archivos recibidos y devuelve un array plano con los que estén OK.
 * Soporta:
 *  - adjuntos[]  (plural recomendado)
 *  - archivos[]  (por compatibilidad)
 *  - archivo     (legacy)
 */
function collectIncomingFiles(): array {
    $all = [];

    $pluralKeys = ['adjuntos', 'archivos'];
    foreach ($pluralKeys as $key) {
        if (!empty($_FILES[$key]) && is_array($_FILES[$key]['name'])) {
            $N = count($_FILES[$key]['name']);
            for ($i = 0; $i < $N; $i++) {
                if (($_FILES[$key]['error'][$i] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK) {
                    $all[] = [
                        'name'     => $_FILES[$key]['name'][$i],
                        'type'     => $_FILES[$key]['type'][$i] ?? null,
                        'tmp_name' => $_FILES[$key]['tmp_name'][$i],
                        'error'    => $_FILES[$key]['error'][$i],
                        'size'     => $_FILES[$key]['size'][$i] ?? null,
                    ];
                }
            }
        }
    }

    // archivo (simple / legacy)
    if (!empty($_FILES['archivo']) && ($_FILES['archivo']['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK) {
        $all[] = $_FILES['archivo'];
    }

    return $all;
}

/** Mueve un archivo subido y devuelve metadatos */
function moveUpload(array $f, string $destDir): array {
    $orig = $f['name'] ?? 'archivo';
    $ext  = pathinfo($orig, PATHINFO_EXTENSION);
    $base = safe_filename(pathinfo($orig, PATHINFO_FILENAME));

    // nombre con sufijo _ra_<hash> para dedupe del front
    $hash = substr(sha1($orig . microtime(true) . random_int(0, 999999)), 0, 12);
    $final = $base . '_ra_' . $hash . ($ext ? ".{$ext}" : '');

    $dest = rtrim($destDir, '/\\') . '/' . $final;

    if (!move_uploaded_file($f['tmp_name'], $dest)) {
        throw new RuntimeException('Error al guardar el archivo');
    }

    return [
        'filename'      => $final,
        'original_name' => $orig,
        'mime'          => $f['type'] ?? null,
        'size'          => $f['size'] ?? null,
        'path'          => $dest,
    ];
}

/* =========================================================
 * GET accion=adjuntos&id=RA_ID  -> lista adjuntos del registro (modal)
 * ======================================================= */
if ($method === 'GET' && ($_GET['accion'] ?? '') === 'adjuntos') {
    $id = (int)($_GET['id'] ?? 0);
    if ($id <= 0) {
        http_response_code(400);
        echo json_encode(['error' => 'ID inválido']);
        exit;
    }

    // legacy (columna archivo)
    $legacy = null;
    if ($stmt = $cn->prepare("SELECT archivo FROM reuniones_actividades WHERE id=?")) {
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $stmt->bind_result($arch);
        if ($stmt->fetch() && $arch) {
            $legacy = ['filename' => $arch, 'original_name' => $arch];
        }
        $stmt->close();
    }

    // múltiples
    $items = [];
    if ($res = $cn->prepare("SELECT id, filename, original_name FROM reun_activ_adjuntos WHERE ra_id=? ORDER BY id ASC")) {
        $res->bind_param('i', $id);
        $res->execute();
        $res->bind_result($aid, $fn, $orig);
        while ($res->fetch()) {
            $items[] = ['id' => (int)$aid, 'filename' => $fn, 'original_name' => $orig];
        }
        $res->close();
    }

    echo json_encode(['legacy' => $legacy, 'items' => $items]);
    exit;
}

/* =========================================================
 * POST accion=pin : fijar / desfijar prioridad
 * ======================================================= */
if ($method === 'POST' && ($_POST['accion'] ?? '') === 'pin') {
    $id = (int)($_POST['id'] ?? 0);
    $fijado = (int)($_POST['fijado'] ?? 0);
    if ($id <= 0) {
        http_response_code(400);
        echo json_encode(['error' => 'ID inválido']);
        exit;
    }
    $stmt = $cn->prepare("UPDATE reuniones_actividades SET fijado=? WHERE id=?");
    $stmt->bind_param('ii', $fijado, $id);
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'mensaje' => $fijado ? 'Fijado' : 'Desfijado']);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'No se pudo actualizar prioridad']);
    }
    exit;
}

/* =========================================================
 * POST accion=finalizar : marcar finalizado / reabrir
 * ======================================================= */
if ($method === 'POST' && ($_POST['accion'] ?? '') === 'finalizar') {
    $id = (int)($_POST['id'] ?? 0);
    $finalizado = (int)($_POST['finalizado'] ?? 0);
    if ($id <= 0) {
        http_response_code(400);
        echo json_encode(['error' => 'ID inválido']);
        exit;
    }
    $stmt = $cn->prepare("UPDATE reuniones_actividades SET finalizado=? WHERE id=?");
    $stmt->bind_param('ii', $finalizado, $id);
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'mensaje' => $finalizado ? 'Marcado como finalizado' : 'Reabierto']);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'No se pudo actualizar finalizado']);
    }
    exit;
}

/* =========================================================
 * POST: alta o edición (con adjuntos múltiples)
 * ======================================================= */
if ($method === 'POST') {
    $id           = $_POST['id']           ?? null;
    $tipo         = $_POST['tipo']         ?? '';
    $tarea        = $_POST['tarea']        ?? '';
    $estado       = $_POST['estado']       ?? null;
    $organismo    = $_POST['organismo']    ?? null;
    $notas        = $_POST['notas']        ?? null;
    $fecha_inicio = to_mysql_date_or_null($_POST['fecha_inicio'] ?? null);
    $fecha_fin    = to_mysql_date_or_null($_POST['fecha_fin']    ?? null);
    $asistentes   = $_POST['asistentes']   ?? null;

    // banderas de borrado (desde el modal)
    $del_archivo_legacy = (int)($_POST['del_archivo_legacy'] ?? 0);
    $adj_del = $_POST['adj_del'] ?? [];
    if (!is_array($adj_del)) $adj_del = [$adj_del];
    $adj_del = array_values(array_filter(array_map('intval', $adj_del), fn($x) => $x > 0));

    if ($tipo === '' || $tarea === '') {
        http_response_code(400);
        echo json_encode(['error' => 'Faltan datos obligatorios (tipo, tarea)']);
        exit;
    }

    // Compat: en reunión el estado puede ser el organismo
    if ($tipo === 'reunion' && $organismo && ($estado === null || $estado === '')) {
        $estado = $organismo;
    }

    // Recolectar archivos entrantes (multi + legacy)
    $incoming = collectIncomingFiles();

    // ========= EDICIÓN =========
    if (!empty($id)) {
        $id = (int)$id;

        // 1) Actualizar campos base
        $sql = "UPDATE reuniones_actividades
                   SET tipo=?, tarea=?, estado=?, organismo=?, notas=?, fecha_inicio=?, fecha_fin=?, asistentes=?
                 WHERE id=?";
        $stmt = $cn->prepare($sql);
        $stmt->bind_param(
            'ssssssssi',
            $tipo,
            $tarea,
            $estado,
            $organismo,
            $notas,
            $fecha_inicio,
            $fecha_fin,
            $asistentes,
            $id
        );
        if (!$stmt->execute()) {
            http_response_code(500);
            echo json_encode(['error' => 'Error al actualizar']);
            exit;
        }

        // 2) Borrar adjuntos múltiples marcados
        if (!empty($adj_del)) {
            $sel = $cn->prepare("SELECT filename FROM reun_activ_adjuntos WHERE id=? AND ra_id=?");
            $del = $cn->prepare("DELETE FROM reun_activ_adjuntos WHERE id=? AND ra_id=?");
            foreach ($adj_del as $aid) {
                $sel->bind_param('ii', $aid, $id);
                $sel->execute();
                $sel->bind_result($fn);
                if ($sel->fetch() && $fn) {
                    $ruta = $uploadReu . '/' . $fn;
                    if (is_file($ruta)) @unlink($ruta);
                }
                $sel->free_result();
                $del->bind_param('ii', $aid, $id);
                $del->execute();
            }
            $sel->close();
            $del->close();
        }

        // 3) Borrar archivo legacy si corresponde
        if ($del_archivo_legacy === 1) {
            if ($q = $cn->prepare("SELECT archivo FROM reuniones_actividades WHERE id=?")) {
                $q->bind_param('i', $id);
                $q->execute();
                $q->bind_result($arch);
                if ($q->fetch() && $arch) {
                    $ruta = $uploadReu . '/' . $arch;
                    if (is_file($ruta)) @unlink($ruta);
                }
                $q->close();
            }
            if ($u = $cn->prepare("UPDATE reuniones_actividades SET archivo=NULL WHERE id=?")) {
                $u->bind_param('i', $id);
                $u->execute();
                $u->close();
            }
        }

        // 4) Subir y registrar NUEVOS adjuntos (si llegaron) — SOLO multi
        if (!empty($incoming)) {
            $insertAdj = $cn->prepare(
                "INSERT INTO reun_activ_adjuntos (ra_id, filename, original_name, mime, size)
                 VALUES (?,?,?,?,?)"
            );
            foreach ($incoming as $f) {
                try {
                    $meta = moveUpload($f, $uploadReu);
                    $s = (int)($meta['size'] ?? 0);
                    $m = (string)($meta['mime'] ?? '');
                    $o = (string)$meta['original_name'];
                    $fn = (string)$meta['filename'];
                    $insertAdj->bind_param('isssi', $id, $fn, $o, $m, $s);
                    $insertAdj->execute();
                } catch (Throwable $e) {
                    // ignorar fallos individuales
                }
            }
            $insertAdj->close();
        }

        // (IMPORTANTE) No tocar columna 'archivo' aquí.
        echo json_encode([
            'mensaje' => 'Registro actualizado',
            'adjuntos_agregados' => count($incoming),
            'adjuntos_eliminados' => count($adj_del),
            'legacy_eliminado' => (bool)$del_archivo_legacy
        ]);
        exit;
    }

    // ========= ALTA =========
    // Movemos todos primero a multi
    $moved = [];
    foreach ($incoming as $f) {
        try {
            $moved[] = moveUpload($f, $uploadReu);
        } catch (Throwable $e) {
            // ignorar fallos individuales
        }
    }

    // En alta NO completamos 'archivo' legacy (queda NULL)
    $archivoLegacyInicial = null;

    $sql = "INSERT INTO reuniones_actividades
              (tipo, tarea, estado, organismo, notas, fecha_inicio, fecha_fin, asistentes, archivo)
            VALUES (?,?,?,?,?,?,?,?,?)";
    $stmt = $cn->prepare($sql);
    $stmt->bind_param(
        'sssssssss',
        $tipo,
        $tarea,
        $estado,
        $organismo,
        $notas,
        $fecha_inicio,
        $fecha_fin,
        $asistentes,
        $archivoLegacyInicial
    );

    if (!$stmt->execute()) {
        http_response_code(500);
        echo json_encode(['error' => 'Error al guardar en la base de datos']);
        exit;
    }

    $newId = (int)$stmt->insert_id;

    // Registrar TODOS los adjuntos en la tabla nueva
    if (!empty($moved)) {
        $insertAdj = $cn->prepare(
            "INSERT INTO reun_activ_adjuntos (ra_id, filename, original_name, mime, size)
             VALUES (?,?,?,?,?)"
        );
        foreach ($moved as $meta) {
            $s = (int)($meta['size'] ?? 0);
            $m = (string)($meta['mime'] ?? '');
            $o = (string)$meta['original_name'];
            $fn = (string)$meta['filename'];
            $insertAdj->bind_param('isssi', $newId, $fn, $o, $m, $s);
            $insertAdj->execute();
        }
        $insertAdj->close();
    }

    echo json_encode([
        'mensaje'  => 'Reunión/Actividad cargada correctamente',
        'id'       => $newId,
        'adjuntos' => count($moved)
    ]);
    exit;
}

/* =========================================================
 * DELETE: elimina registro y TODOS los archivos asociados
 * ======================================================= */
if ($method === 'DELETE') {
    parse_str(file_get_contents('php://input'), $body);
    $id = $body['id'] ?? ($_GET['id'] ?? null);

    if (!$id) {
        http_response_code(400);
        echo json_encode(['error' => 'Falta el ID']);
        exit;
    }
    $id = (int)$id;

    // 1) Obtener nombres de archivos antes de borrar
    $archivoLegacy = null;
    if ($s = $cn->prepare("SELECT archivo FROM reuniones_actividades WHERE id = ?")) {
        $s->bind_param('i', $id);
        $s->execute();
        $s->bind_result($archivoLegacy);
        $s->fetch();
        $s->close();
    }

    $adjuntos = [];
    if ($res = $cn->prepare("SELECT filename FROM reun_activ_adjuntos WHERE ra_id=?")) {
        $res->bind_param('i', $id);
        $res->execute();
        $res->bind_result($fn);
        while ($res->fetch()) $adjuntos[] = $fn;
        $res->close();
    }

    // 2) Borrar filas de adjuntos múltiples (por si no hay ON DELETE CASCADE)
    if ($d = $cn->prepare("DELETE FROM reun_activ_adjuntos WHERE ra_id=?")) {
        $d->bind_param('i', $id);
        $d->execute();
        $d->close();
    }

    // 3) Borrar registro principal
    $stmtDel = $cn->prepare("DELETE FROM reuniones_actividades WHERE id = ?");
    $stmtDel->bind_param('i', $id);
    if ($stmtDel->execute()) {
        // 4) Borrar archivo legacy si existía
        if (!empty($archivoLegacy)) {
            $ruta = $uploadReu . '/' . $archivoLegacy;
            if (is_file($ruta)) @unlink($ruta);
        }
        // 5) Borrar archivos múltiples del FS
        foreach ($adjuntos as $fn) {
            $ruta = $uploadReu . '/' . $fn;
            if (is_file($ruta)) @unlink($ruta);
        }
        echo json_encode(['mensaje' => 'Registro eliminado correctamente']);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Error al eliminar']);
    }
    exit;
}

// Otro método
http_response_code(405);
echo json_encode(['error' => 'Método no permitido']);
