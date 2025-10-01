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
  <title>Coordinación de Oficinas Judiciales</title>
  <link rel="icon" type="image/x-icon" href="../favicon.ico">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" />
  <link rel="stylesheet" href="../css/styles.css" />
</head>

<body>

  <div class="container py-5 text-center">
    <h2 class="mb-4">Coordinación de Oficinas Judiciales</h2>

    <div class="row g-4 justify-content-center">
      <!-- I CJ -->
      <div class="col-md-3">
        <a href="circunscripcion1.php" class="text-decoration-none text-dark">
          <div class="card card-hover shadow-sm border-0 p-3">
            <img src="../img/cj1.png" class="img-fluid mb-2" alt="I CJ">
            <h5 class="fw-bold">Iª Circunscripción Judicial</h5>
          </div>
        </a>
      </div>

      <!-- II CJ -->
      <div class="col-md-3">
        <a href="circunscripcion2.php" class="text-decoration-none text-dark">
          <div class="card card-hover shadow-sm border-0 p-3">
            <img src="../img/cj2.png" class="img-fluid mb-2" alt="II CJ">
            <h5 class="fw-bold">IIª Circunscripción Judicial</h5>
          </div>
        </a>
      </div>

      <!-- III CJ -->
      <div class="col-md-3">
        <a href="circunscripcion3.php" class="text-decoration-none text-dark">
          <div class="card card-hover shadow-sm border-0 p-3">
            <img src="../img/cj3.png" class="img-fluid mb-2" alt="III CJ">
            <h5 class="fw-bold">IIIª Circunscripción Judicial</h5>
          </div>
        </a>
      </div>

      <!-- IV CJ -->
      <div class="col-md-3">
        <a href="circunscripcion4.php" class="text-decoration-none text-dark">
          <div class="card card-hover shadow-sm border-0 p-3">
            <img src="../img/cj4.png" class="img-fluid mb-2" alt="IV CJ">
            <h5 class="fw-bold">IVª Circunscripción Judicial</h5>
          </div>
        </a>
      </div>
    </div>

    <a href="../index.php" class="btn btn-outline-secondary mt-5">
      ← Volver al Inicio
    </a>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>
