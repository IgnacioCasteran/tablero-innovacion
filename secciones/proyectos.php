<?php
session_start();
if (!isset($_SESSION['usuario'])) {
  header("Location: ../login/login.html");
  exit();
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Gestión de Proyectos</title>
  <link rel="icon" type="image/x-icon" href="../favicon.ico">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="../css/styles.css">
  <style>
    /* Ajustes generales para móvil */
    @media (max-width: 576px) {
      h2 {
        font-size: 1.4rem;
      }

      .card h5 {
        font-size: 1.1rem;
      }

      .table th,
      .table td {
        font-size: 0.9rem;
        white-space: nowrap;
      }

      .btn {
        font-size: 1rem;
      }

      input,
      select,
      textarea {
        font-size: 1rem !important;
      }

      .table-responsive {
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
      }
    }

    /* ---- ESTILOS DE CARDS (NO anidados, bloque propio) ---- */
    @media (max-width: 575.98px) {
      .pj-card {
        border: 1px solid #e9ecef;
        border-left: 5px solid #198754;
        /* verde Bootstrap */
        border-radius: 12px;
        background: #fff;
        padding: 12px 12px 10px;
        margin-bottom: 12px;
        box-shadow: 0 2px 6px rgba(0, 0, 0, .05);
      }

      .pj-title {
        font-weight: 600;
        margin-bottom: 6px;
      }

      .pj-row {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 6px 10px;
        font-size: .95rem;
      }

      .pj-row .full {
        grid-column: 1 / -1;
      }

      .pj-meta {
        color: #6c757d;
        font-size: .9rem;
      }

      .pj-actions {
        display: flex;
        gap: 8px;
        margin-top: 8px;
      }

      .pj-badge {
        display: inline-block;
        padding: .25rem .5rem;
        border-radius: .5rem;
        font-size: .85rem;
        font-weight: 600;
        color: #fff;
      }

      .pj-badge.En\ curso {
        background: #ffc107;
        color: #000;
      }

      /* “En curso” */
      .pj-badge.Finalizado {
        background: #198754;
      }

      .pj-badge.Pendiente {
        background: #0d6efd;
      }
    }
  </style>


</head>

<body>
  <div class="container py-5">
    <div class="text-center mb-4">
      <img src="../img/icon-proyectos.png" style="max-width: 80px;">
      <h2 class="mt-2">Gestión de Proyectos</h2>
      <p class="text-muted">Alta, edición y eliminación de proyectos</p>
    </div>

    <!-- Formulario de Carga -->
    <div class="card p-4 mb-5">
      <h5>Nuevo Proyecto</h5>
      <form id="formProyecto" enctype="multipart/form-data" method="POST">
        <div class="row g-3">
          <div class="col-12 col-md-6">
            <input type="text" class="form-control" id="titulo" placeholder="Título del proyecto" required>
          </div>
          <div class="col-12 col-md-6">
            <input type="text" class="form-control" id="responsable" placeholder="Responsable" required>
          </div>
          <div class="col-12">
            <textarea class="form-control" id="descripcion" placeholder="Descripción" rows="3" required></textarea>
          </div>
          <div class="col-12 col-md-4">
            <select class="form-select" id="estado" required>
              <option value="">Estado</option>
              <option>En curso</option>
              <option>Finalizado</option>
              <option>Pendiente</option>
            </select>
          </div>
          <div class="col-12 col-md-4">
            <input type="date" class="form-control" id="fecha" required>
          </div>
          <div class="col-12 col-md-6">
            <input type="file" class="form-control" id="ficha" name="ficha" accept=".doc,.docx" />
          </div>
          <div class="col-12 col-md-4">
            <button type="submit" class="btn btn-success w-100">Guardar Proyecto</button>
          </div>
        </div>
      </form>
    </div>


    <!-- Lista de Proyectos -->
    <div>
      <h5 class="mb-3">Listado de Proyectos</h5>

      <!-- Tabla (desktop/tablet) -->
      <div class="table-responsive d-none d-md-block">
        <table class="table table-striped" id="tablaProyectos">
          <thead>
            <tr>
              <th>Título</th>
              <th>Responsable</th>
              <th>Estado</th>
              <th>Fecha</th>
              <th>Descripción</th>
              <th>Acciones</th>
            </tr>
          </thead>
          <tbody>
            <!-- Se cargarán los proyectos aquí -->
          </tbody>
        </table>
      </div>

      <!-- Tarjetas (mobile) -->
      <div id="cardsProyectos" class="d-block d-md-none">
        <!-- Se cargarán las cards aquí -->
      </div>


      <a href="../index.php" class="btn btn-outline-secondary mt-4">← Volver al Inicio</a>
    </div>


    <!-- Modal de Edición -->
    <div class="modal fade" id="modalEditar" tabindex="-1" aria-labelledby="modalEditarLabel" aria-hidden="true">
      <div class="modal-dialog">
        <div class="modal-content">
          <form id="formEditar">
            <input type="hidden" id="edit-id" />
            <div class="modal-header">
              <h5 class="modal-title" id="modalEditarLabel">Editar Proyecto</h5>
              <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
              <div class="mb-3">
                <input type="text" class="form-control" id="edit-titulo" placeholder="Título" required />
              </div>
              <div class="mb-3">
                <input type="text" class="form-control" id="edit-responsable" placeholder="Responsable" required />
              </div>
              <div class="mb-3">
                <textarea class="form-control" id="edit-descripcion" rows="3" placeholder="Descripción"
                  required></textarea>
              </div>
              <div class="mb-3">
                <select class="form-select" id="edit-estado" required>
                  <option value="">Estado</option>
                  <option>En curso</option>
                  <option>Finalizado</option>
                  <option>Pendiente</option>
                </select>
              </div>
              <div class="mb-3">
                <input type="date" class="form-control" id="edit-fecha" required />
              </div>
            </div>
            <div class="mb-3">
              <label class="form-label">Ficha actual:</label>
              <p id="ficha-actual" class="mb-2">No hay ficha cargada actualmente.</p>

              <label class="form-label">Cambiar Ficha</label>
              <input type="file" class="form-control" id="edit-ficha" name="edit-ficha" accept=".doc,.docx" />
            </div>


            <div class="modal-footer">
              <button type="submit" class="btn btn-success">Guardar Cambios</button>
              <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
            </div>
          </form>
        </div>
      </div>
    </div>

    <script src="../js/proyectos.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>