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
  <title>IIIª Circunscripción Judicial</title>
  <link rel="icon" type="image/x-icon" href="../favicon.ico">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" />
  <link rel="stylesheet" href="../css/styles.css" />
</head>

<body>

  <div class="container py-5 text-center">
    <h2 class="mb-4">IIIª Circunscripción Judicial</h2>

    <div class="row g-4 justify-content-center">
      <!-- General Acha -->
      <div class="col-md-5">
        <a href="oficina-penal3-acha.php" class="text-decoration-none text-dark">
          <div class="card shadow-sm border-0 p-4">
            <h5 class="fw-bold mb-0">Oficina Judicial Penal<br>General Acha</h5>
          </div>
        </a>
      </div>

      <!-- 25 de Mayo -->
      <div class="col-md-5">
        <a href="oficina-penal3-25mayo.php" class="text-decoration-none text-dark">
          <div class="card shadow-sm border-0 p-4">
            <h5 class="fw-bold mb-0">Oficina Judicial Penal<br>25 de Mayo</h5>
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