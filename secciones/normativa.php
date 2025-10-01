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
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Normativa</title>
  <link rel="icon" type="image/x-icon" href="../favicon.ico">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" />
  <link rel="stylesheet" href="../css/styles.css" />
  <style>
    body {
      background-color: #fdf4e3;
    }

    .titulo-normativa {
      color: #b02a2a;
    }

    .btn-custom {
      padding: 1rem;
      font-size: 1.2rem;
    }
  </style>
</head>

<body>
  <div class="container py-5 text-center">
    <h2 class="titulo-normativa mb-4">Normativa</h2>

    <div class="row g-4 justify-content-center">
      <!-- Botón Oficina de Objetos Secuestrados -->
      <div class="col-md-4">
        <a href="objetos-secuestrados.php" class="btn btn-outline-dark w-100 btn-custom">
          🧳 Oficina de Objetos Secuestrados
        </a>
      </div>

      <!-- Botón Oficinas Judiciales -->
      <div class="col-md-4">
        <a href="oficinas-judiciales.php" class="btn btn-outline-dark w-100 btn-custom">
          🏛️ Oficinas Judiciales
        </a>
      </div>
    </div>

    <!-- Botón volver -->
    <div class="row justify-content-center mt-5">
      <div class="col-md-4">
        <a href="coordinacion.php" class="btn btn-outline-secondary w-100">
          ← Volver
        </a>
      </div>
    </div>
  </div>


  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>