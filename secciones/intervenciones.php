<?php
// secciones/intervenciones.php
session_start();
if (!isset($_SESSION['usuario'])) {
    header("Location: ../login/login.html");
    exit();
}

/* ---------- Config ---------- */
$BASE_DIR = realpath(__DIR__ . '/../uploads/intervenciones');
if ($BASE_DIR === false) {
    mkdir(__DIR__ . '/../uploads/intervenciones', 0777, true);
    $BASE_DIR = realpath(__DIR__ . '/../uploads/intervenciones');
}
$CIRC_MAP = ['i' => 'I', 'ii' => 'II', 'iii' => 'III', 'iv' => 'IV'];

/* ---------- Helpers de seguridad ---------- */
// Acepta letras (con acentos), números, espacios, guiones, underscore y punto.
// Solo elimina caracteres peligrosos (/, \, etc.)
function clean_name_with_dot($s)
{
    $s = str_replace('+', ' ', $s);   // plus -> espacio
    $s = trim($s);
    // permitir letras (con acentos), números, espacios, _ . - y COMA
    $s = preg_replace('/[^\pL\pN _\.\-,]/u', '', $s);
    // no colapsamos espacios para respetar dobles espacios si existen
    return $s !== '' ? $s : 'nombre';
}
function clean_relpath($p)
{
    $p = str_replace('+', ' ', $p);   // plus -> espacio
    $p = trim($p, "/");
    if ($p === '') return '';
    $p = preg_replace('#/{2,}#', '/', $p);
    $safe = [];
    foreach (explode('/', $p) as $part) {
        $part = clean_name_with_dot($part);  // <-- ya no quita comas
        if ($part !== '' && $part !== '.' && $part !== '..') $safe[] = $part;
    }
    return implode('/', $safe);
}
// Verifica que $path está dentro de $base
function inside_base($base, $path)
{
    $base = rtrim($base, DIRECTORY_SEPARATOR);
    return str_starts_with($path, $base . DIRECTORY_SEPARATOR) || $path === $base;
}

function ensure_dir($path)
{
    if (!is_dir($path)) mkdir($path, 0777, true);
}

// Borra una carpeta con todo su contenido (archivos y subcarpetas)
function rrmdir($dir)
{
    if (!is_dir($dir)) return false;
    $items = array_diff(scandir($dir), ['.', '..']);
    foreach ($items as $it) {
        $full = $dir . DIRECTORY_SEPARATOR . $it;
        if (is_dir($full)) {
            rrmdir($full);
        } else {
            @unlink($full);
        }
    }
    return @rmdir($dir);
}

// Ícono por extensión (Bootstrap Icons + color)
function file_icon_class(string $filename): string
{
    $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    switch ($ext) {
        // Documentos
        case 'pdf':
            return 'bi-file-earmark-pdf text-danger';
        case 'doc':
        case 'docx':
            return 'bi-file-earmark-word text-primary';
        case 'odt':
            return 'bi-file-earmark-word text-primary';
        case 'xls':
        case 'xlsx':
        case 'csv':
        case 'ods':
            return 'bi-file-earmark-excel text-success';
        case 'ppt':
        case 'pptx':
        case 'odp':
            return 'bi-file-earmark-ppt text-warning';

            // Texto
        case 'txt':
        case 'rtf':
        case 'md':
            return 'bi-file-earmark-text text-secondary';

            // Imágenes
        case 'jpg':
        case 'jpeg':
        case 'png':
        case 'gif':
        case 'bmp':
        case 'webp':
        case 'svg':
            return 'bi-file-earmark-image text-info';

            // Comprimidos
        case 'zip':
        case 'rar':
        case '7z':
        case 'tar':
        case 'gz':
            return 'bi-file-earmark-zip text-warning';

            // Código / datos
        case 'html':
        case 'css':
        case 'js':
        case 'ts':
        case 'php':
        case 'json':
        case 'xml':
        case 'yml':
        case 'yaml':
            return 'bi-file-earmark-code text-secondary';

        default:
            return 'bi-file-earmark'; // genérico
    }
}


/* ---------- Input ---------- */
$circ = strtolower($_GET['circ'] ?? 'i');
if (!array_key_exists($circ, $CIRC_MAP)) $circ = 'i';

$subpath = clean_relpath($_GET['path'] ?? '');
$circRoot = $BASE_DIR . DIRECTORY_SEPARATOR . $circ;
ensure_dir($circRoot);
$currentDir = $circRoot . ($subpath ? DIRECTORY_SEPARATOR . $subpath : '');
$currentDir = realpath($currentDir) ?: $currentDir;
if (!inside_base($circRoot, $currentDir)) {
    $currentDir = $circRoot;
    $subpath = '';
}

/* ---------- Acciones POST ---------- */
$flash = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // Crear carpeta
    if ($action === 'mkdir') {
        $name = clean_name_with_dot($_POST['folder_name'] ?? '');
        if ($name) {
            $target = $currentDir . DIRECTORY_SEPARATOR . $name;
            if (!file_exists($target)) {
                if (mkdir($target, 0777, true)) $flash = "Carpeta creada: $name";
                else $flash = "No se pudo crear la carpeta.";
            } else {
                $flash = "Ya existe una carpeta con ese nombre.";
            }
        } else {
            $flash = "Nombre de carpeta inválido.";
        }
    }

    // Subir archivo
    if ($action === 'upload' && isset($_FILES['archivo']) && $_FILES['archivo']['error'] === UPLOAD_ERR_OK) {
        $orig = $_FILES['archivo']['name'];
        $ext  = strtolower(pathinfo($orig, PATHINFO_EXTENSION));
        $permitidas = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'csv', 'txt', 'rtf', 'png', 'jpg', 'jpeg'];
        if (in_array($ext, $permitidas, true)) {
            $sanName = preg_replace('/[^A-Za-z0-9_\-\.]/', '_', basename($orig));
            $dest = $currentDir . DIRECTORY_SEPARATOR . $sanName;
            $base = pathinfo($sanName, PATHINFO_FILENAME);
            $i = 1;
            while (file_exists($dest)) {
                $sanName = $base . "_$i." . $ext;
                $dest = $currentDir . DIRECTORY_SEPARATOR . $sanName;
                $i++;
            }
            if (move_uploaded_file($_FILES['archivo']['tmp_name'], $dest)) {
                $flash = "Archivo subido: $sanName";
            } else {
                $flash = "No se pudo mover el archivo.";
            }
        } else {
            $flash = "Tipo de archivo no permitido.";
        }
    }

    if ($action === 'delete') {
        $item = clean_name_with_dot($_POST['item'] ?? '');
        if ($item) {
            $target = $currentDir . DIRECTORY_SEPARATOR . $item;

            // Verificá que sigue dentro de la circunscripción
            $parentReal = realpath(dirname($target)) ?: $currentDir;
            if (inside_base($circRoot, $parentReal)) {

                if (is_file($target)) {
                    $ok = @unlink($target);
                    $flash = $ok ? "Archivo eliminado." : "No se pudo eliminar el archivo.";
                } elseif (is_dir($target)) {
                    // ✅ ahora admite carpetas con contenido
                    $ok = rrmdir($target);
                    $flash = $ok ? "Carpeta eliminada con todo su contenido." : "No se pudo eliminar la carpeta.";
                } else {
                    $flash = "Elemento no encontrado.";
                }
            } else {
                $flash = "Ruta inválida.";
            }
        }
    }



    // Redirige (PRG)
    $qs = http_build_query(['circ' => $circ, 'path' => $subpath]);
    $_SESSION['flash'] = $flash;
    header("Location: intervenciones.php?$qs");
    exit();
}
if (isset($_SESSION['flash'])) {
    $flash = $_SESSION['flash'];
    unset($_SESSION['flash']);
}

/* ---------- Listado ---------- */
$items = [];
if (is_dir($currentDir)) {
    foreach (array_diff(scandir($currentDir), ['.', '..']) as $f) {
        $full = $currentDir . DIRECTORY_SEPARATOR . $f;
        $isDir = is_dir($full);
        $items[] = [
            'name' => $f,
            'isDir' => $isDir,
            'mtime' => @filemtime($full),
            'size' => $isDir ? null : @filesize($full)
        ];
    }
    // Carpetas primero, luego archivos; por nombre
    usort($items, function ($a, $b) {
        if ($a['isDir'] !== $b['isDir']) return $a['isDir'] ? -1 : 1;
        return strcasecmp($a['name'], $b['name']);
    });
}

/* ---------- Breadcrumb ---------- */
$crumbs = [];
if ($subpath !== '') {
    $acc = [];
    foreach (explode('/', $subpath) as $seg) {
        $acc[] = $seg;
        $crumbs[] = ['name' => $seg, 'path' => implode('/', $acc)];
    }
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Intervenciones Psicosociales</title>
    <link rel="icon" type="image/x-icon" href="../favicon.ico">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body {
            background: #f7efe9;
            font-family: 'Inter', system-ui, Segoe UI, Arial, sans-serif;
        }

        .wrap {
            max-width: 1100px;
            margin: 24px auto;
        }

        .card {
            border: 0;
            border-radius: 14px;
            box-shadow: 0 6px 16px rgba(0, 0, 0, .08)
        }

        .folder {
            color: #0d6efd
        }

        .file {
            color: #6c757d
        }

        .grid {
            display: grid;
            grid-template-columns: 2fr 1fr 140px;
            gap: 10px;
            align-items: center
        }

        @media(max-width: 768px) {
            .grid {
                grid-template-columns: 1fr;
                gap: 6px
            }

            .actions {
                display: flex;
                gap: 8px
            }

            .btn,
            .form-control {
                font-size: 1rem
            }
        }

        .btn-outline-secondary {
            border-radius: 10px
        }

        .badge-path {
            background: #fff;
            border: 1px solid #eee
        }

        /* Mejor distribución y separación de botones de acción */
        .actions {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            flex-wrap: wrap;
        }

        .actions form {
            margin: 0;
        }

        /* saca espacios extra de forms inline */

        /* En móvil, los botones uno abajo del otro y bien táctiles */
        @media(max-width: 768px) {
            .actions {
                justify-content: stretch;
            }

            .actions .btn {
                width: 100%;
            }
        }

        .file-icon {
            font-size: 1.15rem;
        }
    </style>
</head>

<body>

    <div class="container wrap">
        <!-- Volver -->
        <div class="mb-3 d-flex justify-content-between align-items-center">
            <a href="../index.php" class="btn btn-outline-secondary"><i class="bi bi-arrow-left-circle"></i> Volver al Inicio</a>
            <div class="small text-muted">Sesión: <strong><?php echo htmlspecialchars($_SESSION['usuario']); ?></strong></div>
        </div>

        <h2 class="text-center mb-3" style="color:#7c1c2c;font-weight:800;">Intervenciones Psicosociales</h2>

        <!-- Tabs circunscripción -->
        <h5 class="text-center mb-2 text-muted">Elija su circunscripción:</h5>

        <ul class="nav nav-pills justify-content-center mb-3">
            <?php foreach ($CIRC_MAP as $k => $label): ?>
                <li class="nav-item">
                    <a class="nav-link <?php echo $circ === $k ? 'active' : ''; ?>"
                        href="intervenciones.php?<?php echo http_build_query(['circ' => $k]); ?>">
                        Circunscripción <?php echo $label; ?>
                    </a>
                </li>
            <?php endforeach; ?>
        </ul>


        <!-- Acciones: crear carpeta / subir archivo -->
        <div class="card p-3 mb-4">
            <div class="row g-3">
                <div class="col-12 col-md-6">
                    <form method="post" class="d-flex gap-2">
                        <input type="hidden" name="action" value="mkdir">
                        <input type="text" class="form-control" name="folder_name" placeholder="Nombre de carpeta… (p.ej. Informes_2025)" required>
                        <button class="btn btn-primary"><i class="bi bi-folder-plus"></i> Crear carpeta</button>
                    </form>
                </div>
                <div class="col-12 col-md-6">
                    <form method="post" enctype="multipart/form-data" class="d-flex gap-2">
                        <input type="hidden" name="action" value="upload">
                        <input type="file" class="form-control" name="archivo" required
                            accept=".pdf,.doc,.docx,.xls,.xlsx,.csv,.txt,.rtf,.png,.jpg,.jpeg,application/pdf,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document,application/vnd.ms-excel,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet,text/plain,image/png,image/jpeg">
                        <button class="btn btn-success"><i class="bi bi-upload"></i> Subir</button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Ubicación de carpetas -->
        <div class="alert alert-light border d-flex flex-wrap align-items-center gap-2 mt-0">
            <span><i class="bi bi-folder2-open me-1"></i><strong>Ubicación de carpetas:</strong></span>
            <span class="badge rounded-pill badge-path">/ <?= "{$circ}" ?></span>
            <?php foreach ($crumbs as $c): ?>
                <span class="badge rounded-pill badge-path">
                    <a class="text-decoration-none" href="intervenciones.php?<?php
                                                                                echo http_build_query(['circ' => $circ, 'path' => $c['path']]); ?>">/ <?php echo htmlspecialchars($c['name']); ?>
                    </a>
                </span>
            <?php endforeach; ?>
            <?php if ($subpath): ?>
                <a class="ms-auto btn btn-sm btn-outline-secondary"
                    href="intervenciones.php?<?php echo http_build_query(['circ' => $circ, 'path' => dirname($subpath) === '.' ? '' : dirname($subpath)]); ?>">
                    <i class="bi bi-arrow-90deg-up"></i> Subir nivel
                </a>
            <?php endif; ?>
        </div>


        <?php if ($flash): ?>
            <div class="alert alert-info"><?php echo htmlspecialchars($flash); ?></div>
        <?php endif; ?>


        <!-- Listado -->
        <div class="card p-3">
            <?php if (empty($items)): ?>
                <p class="text-muted mb-0">No hay elementos aquí. Creá una carpeta o subí un archivo.</p>
            <?php else: ?>
                <?php foreach ($items as $it): ?>
                    <div class="grid py-2 border-bottom">
                        <div class="d-flex align-items-center gap-2">
                            <?php if ($it['isDir']): ?>
                                <i class="bi bi-folder-fill folder"></i>
                                <a class="text-decoration-none" href="intervenciones.php?<?php
                                                                                            $new = $subpath ? $subpath . '/' . $it['name'] : $it['name'];
                                                                                            echo http_build_query(['circ' => $circ, 'path' => $new]); ?>">
                                    <strong><?php echo htmlspecialchars($it['name']); ?></strong>
                                </a>
                            <?php else: ?>
                                <i class="bi <?php echo file_icon_class($it['name']); ?> file-icon"></i>
                                <span><?php echo htmlspecialchars($it['name']); ?></span>
                            <?php endif; ?>
                        </div>

                        <div class="text-muted">
                            <?php
                            $when = $it['mtime'] ? date('d/m/Y H:i', $it['mtime']) : '';
                            $size = (!$it['isDir'] && $it['size'] !== null) ? number_format($it['size'] / 1024, 1) . ' KB' : '';
                            echo $when . ($when && $size ? ' · ' : '') . $size;
                            ?>
                        </div>

                        <div class="actions">
                            <?php if ($it['isDir']): ?>
                                <a class="btn btn-sm btn-outline-primary"
                                    href="intervenciones.php?<?php echo http_build_query(['circ' => $circ, 'path' => ($subpath ? $subpath . '/' : '') . $it['name']]); ?>">
                                    Abrir
                                </a>
                                <form method="post" class="d-inline"
                                    onsubmit="return confirm('¿Eliminar la carpeta y TODO su contenido? Esta acción no se puede deshacer.');">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="item" value="<?php echo htmlspecialchars($it['name']); ?>">
                                    <button class="btn btn-sm btn-outline-danger">Eliminar</button>
                                </form>
                            <?php else: ?>
                                <?php
                                $dlSub = $subpath ? $subpath . '/' : '';
                                $fullRelPath = $dlSub . $it['name'];
                                $segments = array_filter(explode('/', $fullRelPath), 'strlen');
                                $encodedPath = implode('/', array_map('rawurlencode', $segments));
                                $href = "../uploads/intervenciones/$circ/" . $encodedPath;
                                ?>
                                <a class="btn btn-sm btn-primary" href="<?php echo $href; ?>" target="_blank">Ver / Descargar</a>
                                <form method="post" class="d-inline" onsubmit="return confirm('¿Eliminar archivo?');">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="item" value="<?php echo htmlspecialchars($it['name']); ?>">
                                    <button class="btn btn-sm btn-outline-danger">Eliminar</button>
                                </form>
                            <?php endif; ?>
                        </div>

                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <div class="mt-4 text-center">
            <a href="../index.php" class="btn btn-outline-secondary"><i class="bi bi-arrow-left-circle"></i> Volver al Inicio</a>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>