<?php
// secciones/agenda.php
require_once __DIR__ . '/../auth.php';
require_login();          // exige sesión
enforce_route_access();
?>
<!DOCTYPE html>
<html lang="es">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>SGC - Poder Judicial</title>
  <link rel="icon" type="image/x-icon" href="../favicon.ico">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">

  <style>
    html, body { height: 100%; }
    body {
      font-family: 'Inter', sans-serif;
      background-color: #fef1e9;
      margin: 0;
      display: flex;
      flex-direction: column;
    }

    .btn-volver {
      padding: 12px 16px;
      display: flex;
      justify-content: flex-start;
    }

    .titulo {
      text-align: center;
      color: #742a2a;
      margin: 10px 0 20px;
      font-weight: 800;
      letter-spacing: .3px;
    }

    .panel-opciones {
      background: #fff;
      border-radius: 16px;
      box-shadow: 0 6px 16px rgba(0,0,0,.08);
      padding: 24px;
    }

    .btn-sgc {
      width: 100%;
      padding: 18px;
      font-size: 1.05rem;
      border-radius: 12px;
      margin-bottom: 16px;
      box-shadow: 0 4px 8px rgba(0, 0, 0, 0.06);
    }
    .btn-proximamente {
      background-color: #e9ecef;
      color: #6c757d;
      cursor: not-allowed;
      border: 1px solid #dee2e6;
    }
    .btn-reuniones {
      background-color: #a0323e;
      color: #fff;
      border: none;
    }
    .btn-reuniones:hover { background-color: #8e2935; }

    /* Botones nuevos */
    .btn-doc { background-color: #495057; color: #fff; border: none; }
    .btn-doc:hover { background-color: #343a40; }

    .btn-proc { background-color: #0d6efd; color: #fff; border: none; }
    .btn-proc:hover { background-color: #0b5ed7; }

    .btn-reg { background-color: #198754; color: #fff; border: none; }
    .btn-reg:hover { background-color: #157347; }

    .btn-sgc2025 { background-color: #fd7e14; color: #fff; border: none; }
    .btn-sgc2025:hover { background-color: #e86a07; }

    @media (max-width: 576px) {
      .btn-volver {
        position: sticky;
        top: 0;
        z-index: 1020;
        background: #fef1e9;
        padding: 8px 12px;
      }
      .btn-volver .btn { width: 100%; }

      .titulo { font-size: 1.2rem; margin: 12px 0 16px; }

      .panel-opciones {
        padding: 16px;
        border-radius: 12px;
        box-shadow: none;
      }

      .btn-sgc {
        padding: 16px;
        font-size: 1rem;
        margin-bottom: 12px;
      }
    }
  </style>
</head>

<body>

  <div class="container my-2 my-md-4">
    <!-- Volver -->
    <div class="btn-volver">
      <a href="../index.php" class="btn btn-outline-dark">
        <i class="bi bi-arrow-left-circle"></i> Volver al Inicio
      </a>
    </div>

    <h2 class="titulo">Sistema de Gestión de Calidad (SGC)</h2>

    <div class="row justify-content-center">
      <div class="col-12 col-md-8 col-lg-6">
        <div class="panel-opciones">
          <!-- Reuniones -->
          <a href="reuniones-actividades.php" class="btn btn-reuniones btn-sgc">
            <i class="bi bi-calendar-event me-2"></i> Reuniones y Actividades
          </a>

          <!-- Documentación -->
          <a href="documentacion.php" class="btn btn-doc btn-sgc">
            <i class="bi bi-file-earmark-text me-2"></i> Documentación
          </a>

          <!-- Procedimientos -->
          <a href="procedimientos.php" class="btn btn-proc btn-sgc">
            <i class="bi bi-gear-wide-connected me-2"></i> Procedimientos
          </a>

          <!-- Registros -->
          <a href="registros.php" class="btn btn-reg btn-sgc">
            <i class="bi bi-journal-text me-2"></i> Registros
          </a>

          <!-- SGC 2025 -->
          <a href="sgc2025.php" class="btn btn-sgc2025 btn-sgc">
            <i class="bi bi-award me-2"></i> SGC 2025
          </a>
        </div>
      </div>
    </div>
  </div>

</body>
</html>
