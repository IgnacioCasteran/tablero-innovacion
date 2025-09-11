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
  <title>Oficina de Gestión Judicial de Familia - I CJ</title>
  <link rel="icon" type="image/x-icon" href="../favicon.ico">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" />
  <link rel="stylesheet" href="../css/styles.css" />
  <style>
    .cabecera-oficina {
      display: flex;
      flex-direction: column;
      align-items: center;
      margin-bottom: 2rem;
    }

    .cabecera-oficina img {
      max-width: 150px;
      margin-bottom: 1rem;
    }

    .titulo-verde {
      color: #17673c;
    }
  </style>
</head>

<body>
  <div class="container py-5 text-center">
    <div class="cabecera-oficina">
      <img src="../img/familia.png" alt="Oficina de Gestión Judicial de Familia" />
      <h2 class="titulo-verde">Oficina de Gestión Judicial de Familia</h2>
      <h5 class="text-muted">Iª Circunscripción Judicial</h5>
    </div>

    <div class="row g-4 justify-content-center">
      <div class="col-md-4">
        <a href="../informe-registrados.php" target="_blank" class="btn btn-primary btn-lg w-100">
          📅 Informe Periódico
        </a>
      </div>
      <div class="col-md-4">
        <a href="https://drive.google.com/drive/u/1/folders/1l0yBfhczz7UzqA5umb8Ak8mM-QxWE_lG" target="_blank" class="btn btn-outline-primary btn-lg w-100">
          📄 Protocolos
        </a>
      </div>

      <!-- Normativa -->
      <div class="col-md-4">
        <a href="normativa.php" class="btn btn-outline-success btn-lg w-100">
          📘 Normativa
        </a>
      </div>
    </div>

    <div class="row justify-content-center mt-5">
      <div class="col-md-4">
        <a href="circunscripcion1.php" class="btn btn-outline-secondary w-100">
          ← Volver a Oficinas
        </a>
      </div>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>