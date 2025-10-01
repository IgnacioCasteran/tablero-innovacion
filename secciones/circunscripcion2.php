<?php
// secciones/agenda.php
require_once __DIR__ . '/../auth.php';
require_login();          // exige sesión
enforce_route_access();   // aplica restricciones por rol (coord solo Informes, STJ solo lectura)
?>

<!DOCTYPE html>
<html lang="es">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>IIª Circunscripción Judicial</title>
  <link rel="icon" type="image/x-icon" href="../favicon.ico">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" />
  <link rel="stylesheet" href="../css/styles.css" />
</head>

<body>

  <div class="container py-5 text-center">
    <h2 class="mb-4">IIª Circunscripción Judicial</h2>

    <div class="row g-4 justify-content-center">
      <!-- Oficina Judicial Penal -->
      <div class="col-md-5">
        <a href="oficina-penal2.php" class="text-decoration-none text-dark">
          <div class="card shadow-sm border-0 p-4">
            <h5 class="fw-bold mb-0">Oficina Judicial Penal</h5>
          </div>
        </a>
      </div>

      <!-- Oficina Gestión Judicial de Familia -->
      <div class="col-md-5">
        <a href="oficina-familia2.php" class="text-decoration-none text-dark">
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