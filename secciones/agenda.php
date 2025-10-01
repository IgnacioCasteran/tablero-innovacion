<?php
// secciones/agenda.php
require_once __DIR__ . '/../auth.php';
require_login();          // exige sesión
enforce_route_access();   // coord: whitelist; STJ: solo-lectura en servidor
render_readonly_ui();     // deshabilita inputs/botones básicos en UI

$ROL = current_role();
$IS_READONLY = ($ROL === 'stj');
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

    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">

    <style>
        html, body { height: 100%; }
        body{ font-family:'Inter',sans-serif; background:#fef1e9; margin:0; padding:0; display:flex; }
        .agenda-wrap{ display:flex; flex-direction:column; min-height:100svh; width:100%; }
        .btn-volver{ padding:12px 16px; display:flex; justify-content:center; }
        #calendar{ flex:1; width:100%; max-width:none; margin:0; padding:0; background:transparent; border-radius:0; box-shadow:none; }
        #calendar .fc{ height:100%; width:100%; max-width:none; background:transparent; box-shadow:none; }
        @media (min-width:768px){
            .btn-volver{ justify-content:flex-start; max-width:1100px; margin:0 auto; }
            #calendar{ max-width:1100px; margin:16px auto; }
            #calendar .fc{ background:#fff; border-radius:12px; box-shadow:0 4px 10px rgba(0,0,0,.1); padding:16px; }
        }
        /* Toolbar y colores */
        #calendar .fc-toolbar{ background:#742a2a; color:#fff; padding:8px 12px; border-radius:8px 8px 0 0; }
        #calendar .fc-toolbar-title{ color:#fff; font-weight:700; font-size:1.3rem; }
        @media (min-width:768px){ #calendar .fc-toolbar-title{ font-size:1.8rem; } }
        #calendar .fc-button{ background:#a0323e; border:none; }
        #calendar .fc-button:hover{ background:#8b2934; }
        #calendar .fc-col-header-cell{ background:#f7f1ee; font-weight:700; color:#5a3d3d; border:none; }
        #calendar .fc-daygrid-day{ background:#fff; }
        #calendar .fc-day-today{ background:#ffefd5 !important; border:2px solid #e77d11 !important; box-shadow: inset 0 0 8px #e77d11; }
        #calendar .fc-toolbar.fc-header-toolbar{ display:flex; flex-wrap:wrap; justify-content:center; }
        #calendar .fc-toolbar-title{ flex:1 1 100%; text-align:center; }
        #calendar .fc{
            --fc-event-bg-color:#7c1c2c;
            --fc-event-border-color:#7c1c2c;
            --fc-event-text-color:#ffffff;
        }
        #calendar .fc .fc-event, #calendar .fc .fc-daygrid-event{
            background:#7c1c2c !important; color:#fff !important; border:none !important;
            border-radius:6px; padding:2px 6px; font-weight:600;
        }
        #calendar .fc .fc-event:hover{ filter:brightness(.9); }
        #calendar .fc .fc-timegrid-event, #calendar .fc .fc-v-event{
            background:#7c1c2c !important; border-color:#7c1c2c !important; color:#fff !important;
            border:none !important; border-radius:6px; box-shadow:0 1px 2px rgba(0,0,0,.1);
        }
        #calendar .fc .fc-timegrid-event .fc-event-time,
        #calendar .fc .fc-timegrid-event .fc-event-title,
        #calendar .fc .fc-timegrid-event .fc-event-main,
        #calendar .fc .fc-v-event .fc-event-time,
        #calendar .fc .fc-v-event .fc-event-title,
        #calendar .fc .fc-v-event .fc-event-main{ color:#fff !important; }
        #calendar .fc .fc-list-event-title a{ color:#7c1c2c !important; font-weight:600; text-decoration:none; }
        #calendar .fc .fc-daygrid-event.fc-daygrid-dot-event{
            background:#7c1c2c !important; border-color:#7c1c2c !important; color:#fff !important;
            border-radius:6px; padding:2px 6px;
        }
        #calendar .fc .fc-daygrid-event.fc-daygrid-dot-event .fc-event-main,
        #calendar .fc .fc-daygrid-event.fc-daygrid-dot-event .fc-event-time,
        #calendar .fc .fc-daygrid-event.fc-daygrid-dot-event .fc-event-title,
        #calendar .fc .fc-daygrid-event.fc-daygrid-dot-event .fc-event-title a{
            color:#fff !important; -webkit-text-fill-color:#fff !important;
        }
        #calendar .fc .fc-daygrid-event-dot{ background:#fff !important; border-color:#fff !important; }

        /* (Opcional) en solo-lectura ocultar botones de agregar si tu agenda.js agrega alguno con clase .btn-agregar-evento */
        <?php if ($IS_READONLY): ?>
        .btn-agregar-evento { display:none !important; }
        <?php endif; ?>
    </style>

    <!-- Bandera de solo-lectura para que agenda.js pueda obedecer -->
    <script>
      window.AGENDA_ROLE = <?= json_encode($ROL) ?>;
      window.AGENDA_READONLY = <?= $IS_READONLY ? 'true' : 'false' ?>;
      // Guardia extra: si es STJ, BLOQUEA cualquier fetch no-GET (por si agenda.js intenta POST/DELETE)
      if (window.AGENDA_READONLY) {
        const __fetch = window.fetch;
        window.fetch = function(input, init){
          const method = ((init && init.method) ? String(init.method) : 'GET').toUpperCase();
          if (method !== 'GET') {
            console.warn('Solo lectura (STJ): bloqueado método', method, input);
            try {
              return Promise.resolve(new Response(JSON.stringify({error:'Solo lectura para (STJ)'}), {
                status: 403,
                headers: {'Content-Type':'application/json'}
              }));
            } catch(e) {
              return Promise.resolve(new Response('', { status:403 }));
            }
          }
          return __fetch(input, init);
        };
      }
    </script>
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

    <!-- Tu lógica de agenda -->
    <script src="../js/agenda.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
