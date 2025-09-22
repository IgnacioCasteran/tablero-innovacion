<?php
session_start();
if (!isset($_SESSION['usuario'])) {
    header("Location: /login/login.html");
    exit();
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Tablero Judicial</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet" />
    <link rel="icon" href="/img/favicon.ico" type="image/x-icon" />
    <link rel="stylesheet" href="/css/styles.css" />
</head>

<body>
    <!-- Botón cerrar sesión arriba a la derecha -->
    <div class="container mt-3 d-flex justify-content-end">
        <a href="/login/logout.php" class="btn btn-danger btn-sm">
            <i class="bi bi-box-arrow-right"></i>
            Cerrar sesión (<?php echo $_SESSION['usuario']; ?>)
        </a>
    </div>

    <div class="container py-5 text-center">
        <div class="d-flex flex-column align-items-center mb-4">
            <a href="/index.php" class="text-decoration-none text-dark text-center">
                <img src="/img/poder-judicial.png" class="logo mb-2" alt="Poder Judicial de La Pampa">
                <h1 class="titulo mb-0">PODER JUDICIAL </h1>
                <h4 class="subtitulo text-muted">de La Pampa</h4>

                <div class="row g-4 justify-content-center">
                    <div class="col-md-5">
                        <a href="/secciones/coordinacion.php" class="btn-opcion">
                            <img src="/img/icon-oficinas.png" alt="" class="icon-img me-2">
                            Coordinación de Oficinas Judiciales
                        </a>
                    </div>
                    <div class="col-md-5">
                        <a href="/secciones/proyectos.php" class="btn-opcion">
                            <img src="/img/icon-proyectos.png" alt="" class="icon-img me-2">
                            Proyectos
                        </a>
                    </div>

                    <div class="col-md-5">
                        <a href="/secciones/intervenciones.php" class="btn-opcion">
                            <img src="/img/psychology.png" alt="" class="icon-img me-2">
                            Intervenciones Psicosociales
                        </a>
                    </div>
                    <div class="col-md-5">
                        <a href="#" class="btn-opcion">
                            <img src="/img/icon-laboratorio.png" alt="" class="icon-img me-2">
                            Laboratorio de Innovación Judicial
                        </a>
                    </div>

                    <div class="col-md-5">
                        <a href="/secciones/sgc.php" class="btn-opcion">
                            <img src="/img/icon-sgc.png" alt="" class="icon-img me-2">
                            SGC
                        </a>
                    </div>

                    <div class="col-12">
                        <a href="/secciones/agenda.php" class="btn-opcion btn-agenda">
                            <img src="/img/icon-agenda.png" alt="" class="icon-img me-2">
                            Agenda
                        </a>
                    </div>
                </div>
            </a>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
