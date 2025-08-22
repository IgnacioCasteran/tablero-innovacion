<?php
// secciones/registros.php
session_start();
if (!isset($_SESSION['usuario'])) {
    header("Location: ../login/login.html");
    exit();
}

/* ---------- Config ---------- */
$BASE_DIR = realpath(__DIR__ . '/../uploads/registros');
if ($BASE_DIR === false) {
    mkdir(__DIR__ . '/../uploads/registros', 0777, true);
    $BASE_DIR = realpath(__DIR__ . '/../uploads/registros');
}

/* ---------- Helpers ---------- */
function clean_name_with_dot($s)
{
    $s = str_replace('+', ' ', $s);
    $s = trim($s);
    $s = preg_replace('/[^\pL\pN _\.\-,]/u', '', $s); // letras (con acentos), números, espacio, _ . - ,
    return $s !== '' ? $s : 'nombre';
}
function clean_relpath($p)
{
    $p = str_replace('+', ' ', $p);
    $p = trim($p, "/");
    if ($p === '') return '';
    $p = preg_replace('#/{2,}#', '/', $p);
    $safe = [];
    foreach (explode('/', $p) as $part) {
        $part = clean_name_with_dot($part);
        if ($part !== '' && $part !== '.' && $part !== '..') $safe[] = $part;
    }
    return implode('/', $safe);
}
function inside_base($base, $path)
{
    $base = rtrim($base, DIRECTORY_SEPARATOR);
    return str_starts_with($path, $base . DIRECTORY_SEPARATOR) || $path === $base;
}
function rrmdir($dir)
{
    if (!is_dir($dir)) return false;
    $items = array_diff(scandir($dir), ['.', '..']);
    foreach ($items as $it) {
        $full = $dir . DIRECTORY_SEPARATOR . $it;
        if (is_dir($full)) rrmdir($full);
        else @unlink($full);
    }
    return @rmdir($dir);
}

/* ----- Normalización para búsqueda server-side (fallback) ----- */
function normalize_ci($s)
{
    $s = mb_strtolower($s ?? '', 'UTF-8');
    if (function_exists('iconv')) {
        $t = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $s);
        if ($t !== false) return $t;
    }
    $map = ['á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u', 'ü' => 'u', 'ñ' => 'n', 'Á' => 'a', 'É' => 'e', 'Í' => 'i', 'Ó' => 'o', 'Ú' => 'u', 'Ü' => 'u', 'Ñ' => 'n'];
    return strtr($s, $map);
}

function file_icon_class(string $filename): string
{
    $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    switch ($ext) {
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
        case 'txt':
        case 'rtf':
        case 'md':
            return 'bi-file-earmark-text text-secondary';
        case 'jpg':
        case 'jpeg':
        case 'png':
        case 'gif':
        case 'bmp':
        case 'webp':
        case 'svg':
            return 'bi-file-earmark-image text-info';
        case 'zip':
        case 'rar':
        case '7z':
        case 'tar':
        case 'gz':
            return 'bi-file-earmark-zip text-warning';
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
            return 'bi-file-earmark';
    }
}


/* ---------- Input ---------- */
$subpath = clean_relpath($_GET['path'] ?? '');
$q       = trim($_GET['q'] ?? '');
$currentDir = $BASE_DIR . ($subpath ? DIRECTORY_SEPARATOR . $subpath : '');
$currentDir = realpath($currentDir) ?: $currentDir;
if (!inside_base($BASE_DIR, $currentDir)) {
    $currentDir = $BASE_DIR;
    $subpath = '';
}

/* ---------- Acciones POST ---------- */
$flash = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'mkdir') {
        $name = clean_name_with_dot($_POST['folder_name'] ?? '');
        if ($name) {
            $target = $currentDir . DIRECTORY_SEPARATOR . $name;
            if (!file_exists($target)) $flash = mkdir($target, 0777, true) ? "Carpeta creada: $name" : "No se pudo crear la carpeta.";
            else $flash = "Ya existe una carpeta con ese nombre.";
        } else $flash = "Nombre de carpeta inválido.";
    }

    if ($action === 'upload' && isset($_FILES['archivo']) && $_FILES['archivo']['error'] === UPLOAD_ERR_OK) {
        $orig = $_FILES['archivo']['name'];
        $ext  = strtolower(pathinfo($orig, PATHINFO_EXTENSION));
        $permitidas = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'csv', 'txt', 'rtf', 'png', 'jpg', 'jpeg'];
        if (in_array($ext, $permitidas, true)) {
            $sanName = preg_replace('/[^A-Za-z0-9_\-\.]/', '_', basename($orig)); // nombre físico
            $dest = $currentDir . DIRECTORY_SEPARATOR . $sanName;
            $base = pathinfo($sanName, PATHINFO_FILENAME);
            $i = 1;
            while (file_exists($dest)) {
                $sanName = $base . "_$i.$ext";
                $dest = $currentDir . DIRECTORY_SEPARATOR . $sanName;
                $i++;
            }
            $flash = move_uploaded_file($_FILES['archivo']['tmp_name'], $dest) ? "Archivo subido: $sanName" : "No se pudo mover el archivo.";
        } else $flash = "Tipo de archivo no permitido.";
    }

    if ($action === 'delete') {
        $item = clean_name_with_dot($_POST['item'] ?? '');
        if ($item) {
            $target = $currentDir . DIRECTORY_SEPARATOR . $item;
            $parentReal = realpath(dirname($target)) ?: $currentDir;
            if (inside_base($BASE_DIR, $parentReal)) {
                if (is_file($target)) $flash = @unlink($target) ? "Archivo eliminado." : "No se pudo eliminar el archivo.";
                elseif (is_dir($target)) $flash = rrmdir($target) ? "Carpeta eliminada con todo su contenido." : "No se pudo eliminar la carpeta.";
                else $flash = "Elemento no encontrado.";
            } else $flash = "Ruta inválida.";
        }
    }

    $_SESSION['flash'] = $flash;
    $qs = ['path' => $subpath];
    if ($q !== '') $qs['q'] = $q;
    header("Location: registros.php?" . http_build_query($qs));
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
        $items[] = ['name' => $f, 'isDir' => $isDir, 'mtime' => @filemtime($full), 'size' => $isDir ? null : @filesize($full)];
    }
    // Filtro server-side (opcional; el live es cliente)
    if ($q !== '') {
        $qnorm = normalize_ci($q);
        $items = array_values(array_filter($items, fn($it) => strpos(normalize_ci($it['name']), $qnorm) !== false));
    }
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

/* ---------- UI helpers ---------- */
function h($s)
{
    return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
}
function human_size($bytes)
{
    if ($bytes === null) return '';
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    $i = 0;
    while ($bytes >= 1024 && $i < 4) {
        $bytes /= 1024;
        $i++;
    }
    return number_format($bytes, $i ? 1 : 0) . ' ' . $units[$i];
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Registros · SGC</title>
    <link rel="icon" type="image/x-icon" href="../favicon.ico">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body {
            background: #fef1e9;
            font-family: 'Inter', system-ui, Segoe UI, Arial, sans-serif;
        }

        .wrap {
            max-width: 1100px;
            margin: 24px auto;
        }

        .card {
            border: 0;
            border-radius: 14px;
            box-shadow: 0 6px 16px rgba(0, 0, 0, .08);
        }

        .grid {
            display: grid;
            grid-template-columns: 2fr 1fr 140px;
            gap: 10px;
            align-items: center;
        }

        .folder {
            color: #0d6efd;
        }

        .file {
            color: #6c757d;
        }

        @media (max-width:768px) {
            .grid {
                grid-template-columns: 1fr;
                gap: 6px;
            }

            .actions {
                display: flex;
                gap: 8px;
            }
        }

        .badge-path {
            background: #fff;
            border: 1px solid #eee;
        }

        .actions {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            flex-wrap: wrap;
        }

        .actions form {
            margin: 0;
        }

        .title {
            color: #742a2a;
            font-weight: 800;
        }

        .search .form-control {
            border-top-right-radius: 0;
            border-bottom-right-radius: 0;
        }

        .search .btn {
            border-top-left-radius: 0;
            border-bottom-left-radius: 0;
        }

        .file-icon {
            font-size: 1.15rem;
        }
    </style>
</head>

<body>
    <div class="container wrap">
        <div class="mb-3 d-flex justify-content-between align-items-center">
            <a href="sgc.php" class="btn btn-outline-dark"><i class="bi bi-arrow-left-circle"></i> Volver a SGC</a>
            <div class="small text-muted">Sesión: <strong><?php echo h($_SESSION['usuario']); ?></strong></div>
        </div>

        <h2 class="text-center mb-3 title">Registros</h2>

        <!-- Acciones -->
        <div class="card p-3 mb-4">
            <div class="row g-3 align-items-stretch">
                <div class="col-12 col-md-6">
                    <form method="post" class="d-flex gap-2">
                        <input type="hidden" name="action" value="mkdir">
                        <input type="text" class="form-control" name="folder_name" placeholder="Nombre de carpeta… (p.ej. Formularios / Actas / etc.)" required>
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

                <!-- Buscador (live + opcional submit) -->
                <div class="col-12">
                    <form method="get" class="d-flex search">
                        <input type="hidden" name="path" value="<?php echo h($subpath); ?>">
                        <input id="js-q" type="search" class="form-control" name="q" value="<?php echo h($q); ?>" placeholder="Buscar por nombre">
                        <button class="btn btn-outline-secondary" type="submit"><i class="bi bi-search"></i> Buscar</button>
                        <?php if ($q !== ''): ?>
                            <a class="btn btn-outline-dark ms-2" href="registros.php?<?php echo http_build_query(['path' => $subpath]); ?>">Limpiar</a>
                        <?php endif; ?>
                    </form>
                </div>
            </div>
        </div>

        <!-- Breadcrumb -->
        <div class="alert alert-light border d-flex flex-wrap align-items-center gap-2 mt-0">
            <span><i class="bi bi-folder2-open me-1"></i><strong>Ubicación:</strong></span>
            <span class="badge rounded-pill badge-path"><a class="text-decoration-none" href="registros.php">/ Registros</a></span>
            <?php foreach ($crumbs as $c): ?>
                <span class="badge rounded-pill badge-path">
                    <a class="text-decoration-none" href="registros.php?<?php $qs = ['path' => $c['path']];
                                                                        if ($q !== '') $qs['q'] = $q;
                                                                        echo http_build_query($qs); ?>">/ <?php echo h($c['name']); ?></a>
                </span>
            <?php endforeach; ?>
            <?php if ($subpath): ?>
                <a class="ms-auto btn btn-sm btn-outline-secondary"
                    href="registros.php?<?php $qs = ['path' => dirname($subpath) === '.' ? '' : dirname($subpath)];
                                        if ($q !== '') $qs['q'] = $q;
                                        echo http_build_query($qs); ?>">
                    <i class="bi bi-arrow-90deg-up"></i> Subir nivel
                </a>
            <?php endif; ?>
        </div>

        <?php if ($flash): ?>
            <div class="alert alert-info"><?php echo h($flash); ?></div>
        <?php endif; ?>

        <!-- Listado -->
        <div class="card p-3" id="itemsCard">
            <?php if (empty($items)): ?>
                <p class="text-muted mb-0" id="noResultsStatic">No hay elementos aquí. Creá una carpeta o subí un archivo.</p>
            <?php else: ?>
                <div id="itemsList">
                    <?php foreach ($items as $it): ?>
                        <div class="grid py-2 border-bottom js-row">
                            <div class="d-flex align-items-center gap-2">
                                <?php if ($it['isDir']): ?>
                                    <i class="bi bi-folder-fill folder"></i>
                                    <a class="text-decoration-none" href="registros.php?<?php
                                                                                        $new = $subpath ? $subpath . '/' . $it['name'] : $it['name'];
                                                                                        $qs = ['path' => $new];
                                                                                        if ($q !== '') $qs['q'] = $q;
                                                                                        echo http_build_query($qs); ?>">
                                        <strong><span class="js-name" data-orig="<?php echo h($it['name']); ?>"><?php echo h($it['name']); ?></span></strong>
                                    </a>
                                <?php else: ?>
                                    <i class="bi <?php echo file_icon_class($it['name']); ?> file-icon"></i>
                                    <span class="js-name" data-orig="<?php echo h($it['name']); ?>"><?php echo h($it['name']); ?></span>
                                <?php endif; ?>
                            </div>

                            <div class="text-muted">
                                <?php
                                $when = $it['mtime'] ? date('d/m/Y H:i', $it['mtime']) : '';
                                $size = (!$it['isDir'] && $it['size'] !== null) ? human_size($it['size']) : '';
                                echo $when . ($when && $size ? ' · ' : '') . $size;
                                ?>
                            </div>

                            <div class="actions">
                                <?php if ($it['isDir']): ?>
                                    <a class="btn btn-sm btn-outline-primary"
                                        href="registros.php?<?php $qs = ['path' => ($subpath ? $subpath . '/' : '') . $it['name']];
                                                            if ($q !== '') $qs['q'] = $q;
                                                            echo http_build_query($qs); ?>">
                                        Abrir
                                    </a>
                                    <form method="post" class="d-inline" onsubmit="return confirm('¿Eliminar la carpeta y TODO su contenido? Esta acción no se puede deshacer.');">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="item" value="<?php echo h($it['name']); ?>">
                                        <button class="btn btn-sm btn-outline-danger">Eliminar</button>
                                    </form>
                                <?php else: ?>
                                    <?php
                                    $dlSub = $subpath ? $subpath . '/' : '';
                                    $fullRelPath = $dlSub . $it['name'];
                                    $segments = array_filter(explode('/', $fullRelPath), 'strlen');
                                    $encodedPath = implode('/', array_map('rawurlencode', $segments));
                                    $href = "../uploads/registros/" . $encodedPath;
                                    ?>
                                    <a class="btn btn-sm btn-primary" href="<?php echo $href; ?>" target="_blank">Ver / Descargar</a>
                                    <form method="post" class="d-inline" onsubmit="return confirm('¿Eliminar archivo?');">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="item" value="<?php echo h($it['name']); ?>">
                                        <button class="btn btn-sm btn-outline-danger">Eliminar</button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                <p class="text-muted mb-0" id="noResults" style="display:none;">No hay coincidencias para la búsqueda.</p>
            <?php endif; ?>
        </div>

        <div class="mt-4 text-center">
            <a href="sgc.php" class="btn btn-outline-dark"><i class="bi bi-arrow-left-circle"></i> Volver a SGC</a>
        </div>
    </div>

    <script>
        // Búsqueda en tiempo real (case/acentos-insensible)
        (function() {
            const norm = s => (s || '').toLowerCase()
                .normalize('NFD').replace(/[\u0300-\u036f]/g, '');
            const qInput = document.getElementById('js-q');
            const rows = Array.from(document.querySelectorAll('.js-row'));
            const noResults = document.getElementById('noResults');
            const noResultsStatic = document.getElementById('noResultsStatic');

            rows.forEach(r => {
                const nameEl = r.querySelector('.js-name');
                const orig = nameEl ? nameEl.getAttribute('data-orig') : '';
                r.dataset.normname = norm(orig);
            });

            function applyFilter() {
                const q = norm(qInput.value);
                let visible = 0;
                rows.forEach(r => {
                    if (!q || r.dataset.normname.includes(q)) {
                        r.style.display = '';
                        visible++;
                    } else {
                        r.style.display = 'none';
                    }
                });
                if (noResults) noResults.style.display = (rows.length && visible === 0) ? '' : 'none';
                if (noResultsStatic) noResultsStatic.style.display = rows.length ? 'none' : '';
            }

            qInput.addEventListener('input', applyFilter);
            applyFilter();
        })();
    </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>