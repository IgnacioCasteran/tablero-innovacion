<?php
session_start();
if (!isset($_SESSION['usuario'])) {
    header("Location: ../login/login.html");
    exit();
}

$carpeta = __DIR__ . '/../uploads/judiciales/';
$mensaje = "";

// SUBIR ARCHIVO
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['archivo']) && !isset($_POST['eliminar'])) {
    if ($_FILES['archivo']['error'] === UPLOAD_ERR_OK) {
        $nombreOriginal = $_FILES['archivo']['name'];
        $ext = strtolower(pathinfo($nombreOriginal, PATHINFO_EXTENSION));

        if ($ext === 'pdf') {
            if (!is_dir($carpeta)) {
                mkdir($carpeta, 0777, true);
            }

            // Normalizar nombre y agregar sufijo si ya existe (partiendo del SANITIZADO)
            $nombreSanitizado = preg_replace('/[^a-zA-Z0-9_\-\.]/', '_', basename($nombreOriginal));
            $rutaDestino = $carpeta . $nombreSanitizado;

            $contador = 1;
            $baseSanitizado = pathinfo($nombreSanitizado, PATHINFO_FILENAME);
            while (file_exists($rutaDestino)) {
                $nombreSanitizado = $baseSanitizado . "_$contador." . $ext;
                $rutaDestino = $carpeta . $nombreSanitizado;
                $contador++;
            }

            if (move_uploaded_file($_FILES['archivo']['tmp_name'], $rutaDestino)) {
                $_SESSION['mensaje'] = "Archivo subido correctamente.";
            } else {
                $_SESSION['mensaje'] = "Error al mover el archivo.";
            }
        } else {
            $_SESSION['mensaje'] = "Solo se permiten archivos PDF.";
        }

        header("Location: oficinas-judiciales.php");
        exit();
    }
}

// ELIMINAR ARCHIVO
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['eliminar'])) {
    $archivoAEliminar = basename($_POST['eliminar']);
    $rutaCompleta = $carpeta . $archivoAEliminar;

    if (file_exists($rutaCompleta) && is_file($rutaCompleta)) {
        if (unlink($rutaCompleta)) {
            $_SESSION['mensaje'] = "Archivo eliminado correctamente.";
        } else {
            $_SESSION['mensaje'] = "Error al eliminar el archivo.";
        }
    } else {
        $_SESSION['mensaje'] = "El archivo no existe.";
    }

    header("Location: oficinas-judiciales.php");
    exit();
}

// MENSAJE TEMPORAL
if (isset($_SESSION['mensaje'])) {
    $mensaje = $_SESSION['mensaje'];
    unset($_SESSION['mensaje']);
}

$archivos = is_dir($carpeta) ? array_values(array_diff(scandir($carpeta), ['.', '..'])) : [];
?>
<!DOCTYPE html>
<html lang="es">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Oficinas Judiciales</title>
  <link rel="icon" type="image/x-icon" href="../favicon.ico">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

  <style>
    body { background-color: #f8f9fa; }

    /* Cards mobile */
    @media (max-width: 575.98px) {
      .oj-card {
        border: 1px solid #e9ecef;
        border-left: 5px solid #0d6efd; /* azul para distinguir */
        border-radius: 12px;
        background: #fff;
        padding: 12px 12px 10px;
        margin-bottom: 12px;
        box-shadow: 0 2px 6px rgba(0,0,0,.05);
      }
      .oj-title { font-weight: 600; word-break: break-all; }
      .oj-row {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 6px 10px;
        font-size: .95rem;
        margin-top: 6px;
      }
      .oj-row .full { grid-column: 1 / -1; }
      .oj-meta { color: #6c757d; font-size: .9rem; }
      .oj-actions { display: flex; gap: 8px; margin-top: 8px; }
      .btn-block-xs { width: 100%; }
    }

    /* Inputs y botones más táctiles en móvil */
    @media (max-width: 576px) {
      .form-control, .form-select, .btn { font-size: 1rem; }
    }
  </style>
</head>

<body>
  <div class="container py-4 py-md-5">
    <h2 class="mb-4 text-center text-primary">Oficinas Judiciales</h2>

    <!-- FORMULARIO DE CARGA -->
    <div class="card mb-4 shadow-sm">
      <div class="card-body">
        <h5 class="card-title">Subir archivo PDF</h5>
        <?php if ($mensaje): ?>
          <div class="alert alert-info"><?= htmlspecialchars($mensaje) ?></div>
        <?php endif; ?>

        <form method="POST" enctype="multipart/form-data">
          <div class="row g-3 align-items-end">
            <div class="col-12 col-md-8">
              <label class="form-label">Seleccionar PDF</label>
              <input type="file" name="archivo" accept="application/pdf" class="form-control" required>
            </div>
            <div class="col-12 col-md-4">
              <button type="submit" class="btn btn-success w-100 btn-block-xs">Subir PDF</button>
            </div>
          </div>
        </form>
      </div>
    </div>

    <!-- LISTADO DE ARCHIVOS -->
    <div class="card shadow-sm">
      <div class="card-body">
        <h5 class="card-title">Archivos Subidos</h5>

        <input type="text" id="buscador" class="form-control mb-3" placeholder="Buscar por nombre de archivo...">

        <?php if (empty($archivos)): ?>
          <p class="text-muted mb-0">No hay archivos subidos.</p>
        <?php else: ?>

          <!-- Tabla (desktop / tablet) -->
          <div class="table-responsive d-none d-md-block">
            <table id="tabla-archivos" class="table table-bordered align-middle">
              <thead>
                <tr>
                  <th>Archivo</th>
                  <th>Fecha de subida</th>
                  <th>Acciones</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($archivos as $archivo): ?>
                  <?php
                    $rutaCompleta = $carpeta . $archivo;
                    $fecha = date("d/m/Y", filemtime($rutaCompleta));
                  ?>
                  <tr>
                    <td><?= htmlspecialchars($archivo) ?></td>
                    <td><?= htmlspecialchars($fecha) ?></td>
                    <td class="text-nowrap">
                      <a href="../uploads/judiciales/<?= rawurlencode($archivo) ?>" target="_blank" class="btn btn-sm btn-primary">Ver</a>
                      <form method="POST" class="d-inline" onsubmit="return confirm('¿Estás seguro de eliminar este archivo?');">
                        <input type="hidden" name="eliminar" value="<?= htmlspecialchars($archivo) ?>">
                        <button type="submit" class="btn btn-sm btn-danger">🗑️</button>
                      </form>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>

          <!-- Cards (mobile) -->
          <div id="cards-lista" class="d-md-none">
            <?php foreach ($archivos as $archivo): ?>
              <?php
                $rutaCompleta = $carpeta . $archivo;
                $fecha = date("d/m/Y", filemtime($rutaCompleta));
              ?>
              <div class="oj-card" data-nombre="<?= htmlspecialchars(mb_strtolower($archivo)) ?>">
                <div class="oj-title"><?= htmlspecialchars($archivo) ?></div>
                <div class="oj-row">
                  <div><strong>Fecha:</strong> <?= htmlspecialchars($fecha) ?></div>
                  <div class="full oj-actions">
                    <a href="../uploads/judiciales/<?= rawurlencode($archivo) ?>" target="_blank" class="btn btn-sm btn-primary flex-fill">Ver</a>
                    <form method="POST" class="flex-fill" onsubmit="return confirm('¿Estás seguro de eliminar este archivo?');">
                      <input type="hidden" name="eliminar" value="<?= htmlspecialchars($archivo) ?>">
                      <button type="submit" class="btn btn-sm btn-danger w-100">Eliminar</button>
                    </form>
                  </div>
                </div>
              </div>
            <?php endforeach; ?>
          </div>

        <?php endif; ?>
      </div>
    </div>

    <a href="../secciones/normativa.php" class="btn btn-outline-secondary mt-4">← Volver</a>
  </div>

  <script>
    // Buscador: filtra tabla y cards
    document.getElementById("buscador").addEventListener("keyup", function () {
      const filtro = this.value.toLowerCase();

      // Tabla
      const filas = document.querySelectorAll("#tabla-archivos tbody tr");
      filas.forEach(fila => {
        const nombre = (fila.querySelector("td")?.textContent || "").toLowerCase();
        fila.style.display = nombre.includes(filtro) ? "" : "none";
      });

      // Cards
      const cards = document.querySelectorAll("#cards-lista .oj-card");
      cards.forEach(card => {
        const nombre = card.getAttribute("data-nombre") || "";
        card.style.display = nombre.includes(filtro) ? "" : "none";
      });
    });
  </script>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

