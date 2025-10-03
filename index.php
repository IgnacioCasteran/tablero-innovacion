<?php
// index.php
require_once __DIR__ . '/auth.php';
require_login();                 // exige sesión
enforce_route_access();          // por si alguien pega URLs directas

// === Rol y usuario (normalizado para que funcione en prod) ===
$ROL_RAW  = current_role();                     // lo que venga del SSO/BD
$ROL      = mb_strtolower(trim((string)$ROL_RAW));
// quitar tildes por si viniera “Coordinadores” con acentos
$ROL      = strtr($ROL, ['á'=>'a','é'=>'e','í'=>'i','ó'=>'o','ú'=>'u','ä'=>'a','ë'=>'e','ï'=>'i','ö'=>'o','ü'=>'u']);

// true si es “coordinador”, “coordinadores” o empieza por “coordinador…”
$IS_COORD = ($ROL === 'coordinador'
          || $ROL === 'coordinadores'
          || strpos($ROL, 'coordinador') === 0);

// email a mostrar
$USER_EMAIL = $_SESSION['usuario'] ?? ($_SESSION['user']['email'] ?? '');

// 🔎 DEBUG (quitá esto luego)
error_log(
  'TABLERO role debug | user='.$USER_EMAIL.
  ' | raw='.var_export(current_role_raw(), true).
  ' | norm='.$ROL.
  ' | uri='.$_SERVER['REQUEST_URI']
);

// helper para renderizar un “botón” deshabilitado
function disabled_link($label, $iconPath = null) {
    $icon = $iconPath ? '<img src="'.$iconPath.'" alt="" class="icon-img me-2">' : '';
    echo '<span class="btn-opcion disabled-link" title="Acceso limitado por rol">'
        . $icon . htmlspecialchars($label) .
        '</span>';
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
    <link rel="icon" href="img/favicon.ico" type="image/x-icon" />
    <link rel="stylesheet" href="css/styles.css" />
    <style>
        /* Widget de recordatorios (arriba-izquierda) */
        #recordatorios{position:fixed;top:16px;left:16px;max-width:360px;z-index:1050;border-left:6px solid #7c1c2c;}
        #recordatorios .card-header{background:#f9e7e3;color:#7c1c2c;font-weight:600;}
        #recordatorios .when-badge{font-size:.75rem;}

        /* Deshabilitar enlaces para rol coordinador (solo UI) */
        .disabled-link{
            display:inline-flex; align-items:center; justify-content:center;
            gap:.5rem; padding:18px; border-radius:16px;
            width:100%; min-height:64px;
            background:#e9ecef; color:#6c757d; cursor:not-allowed;
            pointer-events:none; opacity:.75; filter:grayscale(.2);
            text-decoration:none;
        }
    </style>
</head>
<body>

    <?php if (!$IS_COORD): ?>
    <!-- ===== Recordatorios (próximos 7 días) ===== -->
    <div id="recordatorios" class="card shadow d-none">
        <button type="button" class="btn-close position-absolute top-0 end-0 m-2" id="rec-close" aria-label="Cerrar"></button>
        <div class="card-header">
            <i class="bi bi-bell-fill me-1"></i> Recordatorios (próx. 7 días)
        </div>
        <div id="rec-list" class="list-group list-group-flush"></div>
        <div class="card-footer py-2 text-end">
            <a href="secciones/agenda.php" class="btn btn-sm btn-outline-primary">Abrir agenda</a>
        </div>
    </div>

    <!-- Botón minimizado -->
    <button id="rec-min" class="btn btn-sm btn-warning d-none" style="position:fixed;top:16px;left:16px;z-index:1051;border-radius:999px;box-shadow:0 2px 8px rgba(0,0,0,.15);">
        🔔 Recordatorios
    </button>
    <?php endif; ?>

    <!-- Cerrar sesión -->
    <div class="container mt-3 d-flex justify-content-end">
        <a href="login/logout.php" class="btn btn-danger btn-sm">
            <i class="bi bi-box-arrow-right"></i>
            Cerrar sesión (<?= htmlspecialchars($USER_EMAIL) ?>)
        </a>
    </div>

    <div class="container py-5 text-center">
        <div class="d-flex flex-column align-items-center mb-4">
            <a href="index.php" class="text-decoration-none text-dark text-center">
                <img src="img/poder-judicial.png" class="logo mb-2" alt="Poder Judicial de La Pampa">
                <h1 class="titulo mb-0">PODER JUDICIAL</h1>
                <h4 class="subtitulo text-muted">de La Pampa</h4>

                <div class="row g-4 justify-content-center">

                    <!-- Coordinación: SIEMPRE habilitada para todos los roles -->
                    <div class="col-md-5">
                        <a href="secciones/coordinacion.php" class="btn-opcion">
                            <img src="/img/icon-oficinas.png" alt="" class="icon-img me-2">
                            Coordinación de Oficinas Judiciales
                        </a>
                    </div>

                    <!-- Proyectos -->
                    <div class="col-md-5">
                        <?php if ($IS_COORD): ?>
                            <?php disabled_link('Proyectos', '/img/icon-proyectos.png'); ?>
                        <?php else: ?>
                            <a href="secciones/proyectos.php" class="btn-opcion">
                                <img src="/img/icon-proyectos.png" alt="" class="icon-img me-2">
                                Proyectos
                            </a>
                        <?php endif; ?>
                    </div>

                    <!-- Intervenciones -->
                    <div class="col-md-5">
                        <?php if ($IS_COORD): ?>
                            <?php disabled_link('Intervenciones Psicosociales', '/img/psychology.png'); ?>
                        <?php else: ?>
                            <a href="secciones/intervenciones.php" class="btn-opcion">
                                <img src="/img/psychology.png" alt="" class="icon-img me-2">
                                Intervenciones Psicosociales
                            </a>
                        <?php endif; ?>
                    </div>

                    <!-- Laboratorio -->
                    <div class="col-md-5">
                        <?php if ($IS_COORD): ?>
                            <?php disabled_link('Laboratorio de Innovación Judicial', '/img/icon-laboratorio.png'); ?>
                        <?php else: ?>
                            <a href="#" class="btn-opcion">
                                <img src="/img/icon-laboratorio.png" alt="" class="icon-img me-2">
                                Laboratorio de Innovación Judicial
                            </a>
                        <?php endif; ?>
                    </div>

                    <!-- SGC -->
                    <div class="col-md-5">
                        <?php if ($IS_COORD): ?>
                            <?php disabled_link('SGC', '/img/icon-sgc.png'); ?>
                        <?php else: ?>
                            <a href="secciones/sgc.php" class="btn-opcion">
                                <img src="/img/icon-sgc.png" alt="" class="icon-img me-2">
                                SGC
                            </a>
                        <?php endif; ?>
                    </div>

                    <!-- Agenda -->
                    <div class="col-12">
                        <?php if ($IS_COORD): ?>
                            <?php disabled_link('Agenda', '/img/icon-agenda.png'); ?>
                        <?php else: ?>
                            <a href="secciones/agenda.php" class="btn-opcion btn-agenda">
                                <img src="/img/icon-agenda.png" alt="" class="icon-img me-2">
                                Agenda
                            </a>
                        <?php endif; ?>
                    </div>

                </div>
            </a>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

    <?php if (!$IS_COORD): ?>
    <script>
    // === Recordatorios ===
    (function(){
        const box=document.getElementById('recordatorios');
        const list=document.getElementById('rec-list');
        const btnX=document.getElementById('rec-close');
        const mini=document.getElementById('rec-min');
        const KEY='recordatorios:min';

        function parseLocalDate(s){ if(!s) return null; const d=String(s).split(' ')[0]; const [Y,M,D]=d.split('-').map(n=>parseInt(n,10)); if(!Y||!M||!D) return null; return new Date(Y,M-1,D,0,0,0,0); }
        function startOfDay(dt){ return new Date(dt.getFullYear(),dt.getMonth(),dt.getDate()); }
        function ddmmyyyy(dt){ const d=String(dt.getDate()).padStart(2,'0'); const m=String(dt.getMonth()+1).padStart(2,'0'); const y=dt.getFullYear(); return `${d}/${m}/${y}`; }
        function humanWhen(days){ if(days===0) return 'hoy'; if(days===1) return 'mañana'; return `en ${days} días`; }
        function showFull(){ box.classList.remove('d-none'); mini.classList.add('d-none'); sessionStorage.removeItem(KEY); }
        function showMini(c){ mini.textContent=`🔔 Recordatorios (${c})`; mini.classList.remove('d-none'); box.classList.add('d-none'); sessionStorage.setItem(KEY,'1'); }
        function hideAll(){ box.classList.add('d-none'); mini.classList.add('d-none'); sessionStorage.removeItem(KEY); }

        btnX?.addEventListener('click',()=>{ const c=list?.children?.length||0; showMini(c); });
        mini?.addEventListener('click',showFull);

        fetch('api/api-agenda.php').then(r=>r.json()).then(items=>{
            const today=startOfDay(new Date());
            const soon=(items||[]).map(ev=>{const d=parseLocalDate(ev.start);return d?{...ev,_date:d}:null;})
                .filter(Boolean).map(ev=>({...ev,_diff:Math.round((ev._date-today)/86400000)}))
                .filter(ev=>ev._diff>=0 && ev._diff<=7).sort((a,b)=>a._date-b._date).slice(0,6);

            if(soon.length===0){ hideAll(); return; }

            list.innerHTML='';
            soon.forEach(ev=>{
                const a=document.createElement('a');
                a.href='secciones/agenda.php';
                a.className='list-group-item list-group-item-action d-flex justify-content-between align-items-start';
                a.innerHTML=`<div class="me-2">
                    <div class="fw-semibold">${ev.title || 'Sin título'}</div>
                    <small class="text-muted">${ddmmyyyy(ev._date)}</small>
                    ${ev.extendedProps?.descripcion ? `<div class="small text-secondary mt-1">${(ev.extendedProps.descripcion+'').replace(/\n/g,'<br>')}</div>` : ''}
                  </div>
                  <span class="badge bg-warning text-dark align-self-center" style="font-size:.75rem">${humanWhen(ev._diff)}</span>`;
                list.appendChild(a);
            });

            if(sessionStorage.getItem(KEY)==='1'){ showMini(soon.length); } else { showFull(); }
        }).catch(()=>{});
    })();
    </script>
    <?php endif; ?>
</body>
</html>
