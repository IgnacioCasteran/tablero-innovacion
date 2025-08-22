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
    <title>Agenda</title>
    <link rel="icon" type="image/x-icon" href="../favicon.ico">
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">

    <!-- FullCalendar -->
    <link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/locales-all.global.min.js"></script>

    <!-- SweetAlert -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <!-- Estilos propios -->
    <link rel="stylesheet" href="../css/agenda.css">

    <!-- Tipografía -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">

    <!-- Estilos adicionales responsivos -->
    <style>
        html,
        body {
            height: 100%;
        }

        body {
            font-family: 'Inter', sans-serif;
            background-color: #fef1e9;
            margin: 0;
            padding: 0;
            display: flex;
        }

        /* Wrapper para ocupar toda la altura de la pantalla */
        .agenda-wrap {
            display: flex;
            flex-direction: column;
            min-height: 100svh;
            /* seguro en móviles */
            width: 100%;
        }

        /* Botón volver */
        .btn-volver {
            padding: 12px 16px;
            display: flex;
            justify-content: center;
        }

        /* El contenedor del calendario se estira */
        #calendar {
            flex: 1;
            /* ocupa todo el alto restante */
            width: 100%;
            max-width: none;
            margin: 0;
            /* anula margen del css externo */
            padding: 0;
            /* anula padding del css externo */
            background: transparent;
            /* sin caja blanca en móvil */
            border-radius: 0;
            box-shadow: none;
        }

        /* Estilos internos de FullCalendar */
        #calendar .fc {
            height: 100%;
            /* clave: ocupar alto total */
            width: 100%;
            max-width: none;
            background: transparent;
            box-shadow: none;
        }

        /* Desktop/tablet: podés volver a la “caja” elegante */
        @media (min-width: 768px) {
            .btn-volver {
                justify-content: flex-start;
                max-width: 1100px;
                margin: 0 auto;
            }

            #calendar {
                max-width: 1100px;
                margin: 16px auto;
                padding: 0;
            }

            #calendar .fc {
                background: #fff;
                border-radius: 12px;
                box-shadow: 0 4px 10px rgba(0, 0, 0, .1);
                padding: 16px;
            }
        }

        /* Título más chico en móvil */
        #calendar .fc-toolbar-title {
            color: #742a2a;
            font-size: 1.3rem;
        }

        @media (min-width: 768px) {
            #calendar .fc-toolbar-title {
                font-size: 1.8rem;
            }
        }

        /* Fondo principal del calendario */
        #calendar .fc {
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
            padding: 10px;
        }

        /* Encabezado del mes y botones de navegación */
        #calendar .fc-toolbar {
            background-color: #742a2a;
            /* color institucional */
            color: #fff;
            padding: 8px 12px;
            border-radius: 8px 8px 0 0;
        }

        #calendar .fc-toolbar-title {
            color: #fff;
            font-weight: bold;
        }

        #calendar .fc-button {
            background-color: #a0323e;
            border: none;
        }

        #calendar .fc-button:hover {
            background-color: #8b2934;
        }

        /* Encabezados de los días (dom, lun, mar...) */
        #calendar .fc-col-header-cell {
            background-color: #f7f1ee;
            font-weight: bold;
            color: #5a3d3d;
            border: none;
        }

        /* Celdas normales */
        #calendar .fc-daygrid-day {
            background-color: #fff;
        }

        /* Día actual */
        #calendar .fc-day-today {
            background-color: #ffefd5 !important;
            /* amarillo pastel */
            border: 2px solid #e77d11 !important;
            box-shadow: inset 0 0 8px #e77d11;
        }

        /* Ajustes para móvil */
        @media (max-width: 576px) {
            #calendar .fc {
                padding: 5px;
                border-radius: 8px;
            }

            #calendar .fc-toolbar {
                border-radius: 0;
            }
        }

        /* Centrar el título del mes/año */
        #calendar .fc-toolbar.fc-header-toolbar {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            /* centra el contenido */
        }

        #calendar .fc-toolbar-title {
            flex: 1 1 100%;
            text-align: center;
        }
    </style>

</head>

<body>

    <main class="agenda-wrap">
        <div class="btn-volver">
            <a href="../index.php" class="btn btn-outline-dark w-auto">
                <i class="bi bi-arrow-left-circle"></i> Volver al Inicio
            </a>
        </div>

        <div id="calendar"></div>
    </main>
    <!-- Scripts -->
    <script src="../js/agenda.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>