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
  <title>Iª Circunscripción Judicial</title>
  <link rel="icon" type="image/x-icon" href="../favicon.ico">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" />
  <link rel="stylesheet" href="../css/styles.css" />
</head>

<body>

  <div class="container py-5 text-center">
    <h2 class="mb-4">Iª Circunscripción Judicial</h2>

    <div class="row g-4 justify-content-center">
      <!-- Oficina Judicial Penal -->
      <div class="col-md-4">
        <a href="oficina-penal1.php" class="text-decoration-none text-dark">
          <div class="card shadow-sm border-0 p-4">
            <h5 class="fw-bold mb-0">Oficina Judicial Penal</h5>
          </div>
        </a>
      </div>

      <!-- Oficina Gestión Común Civil -->
      <div class="col-md-4">
        <a href="oficina-civil1.php" class="text-decoration-none text-dark">
          <div class="card shadow-sm border-0 p-4">
            <h5 class="fw-bold mb-0">Oficina de Gestión Común Civil</h5>
          </div>
        </a>
      </div>

      <!-- Oficina Gestión Judicial de Familia -->
      <div class="col-md-4">
        <a href="oficina-familia1.php" class="text-decoration-none text-dark">
          <div class="card shadow-sm border-0 p-4">
            <h5 class="fw-bold mb-0">Oficina de Gestión Judicial de Familia</h5>
          </div>
        </a>
      </div>
    </div>

    <a href="coordinacion.php" class="btn btn-outline-secondary mt-5">
      ← Volver a Circunscripciones
    </a>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>
